<?php
/**
 * Cloudflare R2 video yükleme — AWS S3 Signature V4 (saf PHP, harici kütüphane gerektirmez)
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Mevzu_Video_R2 {

    /* ── Ayar okumaları ── */
    private static function cfg( $key ) {
        return (string) get_option( 'options_' . $key, '' );
    }

    public static function is_active() {
        return self::cfg( 'video_depolama' ) === 'r2';
    }

    private static function get_config() {
        return [
            'account_id'   => self::cfg( 'r2_account_id' ),
            'access_key'   => self::cfg( 'r2_access_key' ),
            'secret_key'   => self::cfg( 'r2_secret_key' ),
            'bucket'       => self::cfg( 'r2_bucket' ),
            'public_url'   => rtrim( self::cfg( 'r2_public_url' ), '/' ),
            's3_endpoint'  => rtrim( self::cfg( 'r2_s3_endpoint' ), '/' ),
        ];
    }

    /* ================================================================
     *  Ana upload metodu
     *  @param string $file_path  Yerel dosya yolu (wp_get_attachment_file_path ile alınır)
     *  @param string $object_key R2'deki nesne adı (örn. "2025/05/video.mp4")
     *  @param string $mime       MIME tipi (örn. "video/mp4")
     *  @return string|WP_Error   Başarıda public URL, hata durumunda WP_Error
     * ================================================================ */
    public static function upload( $file_path, $object_key, $mime ) {
        $cfg = self::get_config();

        foreach ( [ 'account_id', 'access_key', 'secret_key', 'bucket', 'public_url' ] as $k ) {
            if ( empty( $cfg[ $k ] ) ) {
                return new WP_Error( 'r2_config', "R2 ayarı eksik: {$k}" );
            }
        }

        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return new WP_Error( 'r2_file', 'Dosya bulunamadı: ' . $file_path );
        }

        $body = file_get_contents( $file_path );

        // S3 endpoint: ayarda varsa doğrudan kullan, yoksa standart URL'yi türet
        $s3_base = $cfg['s3_endpoint']
            ? $cfg['s3_endpoint']
            : "https://{$cfg['account_id']}.r2.cloudflarestorage.com";
        $s3_base = rtrim( $s3_base, '/' );

        // Host = endpoint'in host kısmı (imza için)
        $host     = (string) wp_parse_url( $s3_base, PHP_URL_HOST );
        $endpoint = $s3_base . '/' . $cfg['bucket'] . '/' . $object_key;

        $datetime  = gmdate( 'Ymd\THis\Z' );
        $date      = gmdate( 'Ymd' );
        $region    = 'auto';
        $service   = 's3';

        $payload_hash = hash( 'sha256', $body );

        // ── Canonical request ──
        $canonical_headers = "content-type:{$mime}\nhost:{$host}\nx-amz-content-sha256:{$payload_hash}\nx-amz-date:{$datetime}\n";
        $signed_headers    = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonical_request = implode( "\n", [
            'PUT',
            '/' . $cfg['bucket'] . '/' . $object_key,
            '',
            $canonical_headers,
            $signed_headers,
            $payload_hash,
        ] );

        // ── String to sign ──
        $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
        $string_to_sign   = implode( "\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credential_scope,
            hash( 'sha256', $canonical_request ),
        ] );

        // ── Signing key ──
        $signing_key = self::hmac( self::hmac( self::hmac( self::hmac(
            'AWS4' . $cfg['secret_key'],
            $date ), $region ), $service ), 'aws4_request' );

        $signature = bin2hex( self::hmac( $signing_key, $string_to_sign ) );

        $auth_header = "AWS4-HMAC-SHA256 Credential={$cfg['access_key']}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

        // ── cURL isteği ──
        $ch = curl_init( $endpoint );
        curl_setopt_array( $ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$auth_header}",
                "Content-Type: {$mime}",
                "x-amz-content-sha256: {$payload_hash}",
                "x-amz-date: {$datetime}",
                "Content-Length: " . strlen( $body ),
            ],
        ] );

        $response    = curl_exec( $ch );
        $http_code   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $curl_error  = curl_error( $ch );
        curl_close( $ch );

        if ( $curl_error ) {
            return new WP_Error( 'r2_curl', 'cURL hatası: ' . $curl_error );
        }

        if ( $http_code < 200 || $http_code >= 300 ) {
            return new WP_Error( 'r2_http', "R2 HTTP {$http_code}: " . wp_strip_all_tags( $response ) );
        }

        return $cfg['public_url'] . '/' . $object_key;
    }

    /* HMAC-SHA256 yardımcısı (raw binary) */
    private static function hmac( $key, $data ) {
        return hash_hmac( 'sha256', $data, $key, true );
    }

    /* ================================================================
     *  Attachment'tan nesne anahtarı üret  (örn. "videos/2025/05/video.mp4")
     * ================================================================ */
    public static function make_object_key( $file_path ) {
        $filename = wp_unique_filename(
            dirname( $file_path ),
            wp_basename( $file_path )
        );
        $subdir = gmdate( 'Y/m' );
        return "videos/{$subdir}/" . sanitize_file_name( $filename );
    }
}

/* ================================================================
 *  Global yardımcı fonksiyon (metabox'tan çağrılır)
 * ================================================================ */
function mevzu_upload_video_to_r2( $attachment_id ) {
    if ( ! Mevzu_Video_R2::is_active() ) return false;

    $file_path = get_attached_file( $attachment_id );
    if ( ! $file_path ) return false;

    $mime       = get_post_mime_type( $attachment_id );
    $object_key = Mevzu_Video_R2::make_object_key( $file_path );

    return Mevzu_Video_R2::upload( $file_path, $object_key, $mime );
}