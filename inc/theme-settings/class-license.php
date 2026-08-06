<?php
/**
 * Mevzu² Lisans Sistemi
 * 
 * - lisans.kkerem.com API ile lisans doğrulama
 * - Benzersiz Site ID oluşturma ve footer'a ekleme
 * - Tema downgrade desteği
 */

if (!defined('ABSPATH')) exit;

class Mevzu_License {

    /** @var string Lisans API endpoint */
    const API_URL = 'https://lisans.kkerem.com/api/v1/verify/';
    
    /** @var string Güncelleme API endpoint */
    const UPDATE_API_URL = 'https://lisans.kkerem.com/api/v1/update/';

    /** @var string Transient key */
    const TRANSIENT_KEY = 'mevzu_license_status';

    /** @var int Kontrol aralığı (saniye) — 12 saat */
    const CHECK_INTERVAL = 43200;

    /** @var int Sunucu erişilemezken aktif lisans için grace period (saniye) */
    const GRACE_PERIOD = 172800;

    /** @var int Sürüm yönetiminde en fazla kaç önceki sürüme dönülebilir */
    const MAX_ROLLBACK_VERSIONS = 3;

    public function __construct() {
        // Lisans kontrolü (her sayfa yüklemesinde transient kontrolü)
        add_action('admin_init', array($this, 'schedule_license_check'));
        
        // Banned durumunda temayı kilitle
        add_action('admin_init', array($this, 'enforce_license'));
        add_action('template_redirect', array($this, 'enforce_license_frontend'));
        
        // Footer'a gizli site ID ekle
        add_action('wp_footer', array($this, 'render_site_id_in_footer'), 999);
        
        // AJAX — El ile lisans doğrulama
        add_action('wp_ajax_mevzu_verify_license', array($this, 'ajax_verify_license'));
        
        // AJAX — Lisans anahtarı kaydet
        add_action('wp_ajax_mevzu_save_license', array($this, 'ajax_save_license'));
        
        // AJAX — Sürüm listesi al
        add_action('wp_ajax_mevzu_list_versions', array($this, 'ajax_list_versions'));
        
        // AJAX — Downgrade/Upgrade uygula
        add_action('wp_ajax_mevzu_apply_version', array($this, 'ajax_apply_version'));
    }

    // ============================================================
    //  SITE ID
    // ============================================================

    /**
     * Benzersiz site parmak izi oluştur
     * DB adı + AUTH_KEY + site URL'sinin MD5 hash'i
     */
    public static function get_site_id() {
        $site_id = get_option('mevzu_site_id');
        
        if (empty($site_id)) {
            // İlk kez oluştur
            $raw = DB_NAME . (defined('AUTH_KEY') ? AUTH_KEY : '') . get_site_url();
            $site_id = md5($raw);
            update_option('mevzu_site_id', $site_id, false);
        }
        
        return $site_id;
    }

    /**
     * Footer'a gizli site ID ekle (HTML yorum)
     */
    public function render_site_id_in_footer() {
        $site_id = self::get_site_id();
        echo "\n<!-- mevzu-site-id: " . esc_html($site_id) . " -->\n";
    }

    // ============================================================
    //  LİSANS KONTROLÜ
    // ============================================================

    /**
     * Lisans anahtarını al
     */
    public static function get_license_key() {
        return get_option('mevzu_license_key', '');
    }

    /**
     * Lisans durumunu al (cache'li)
     */
    public static function get_license_status() {
        return get_option('mevzu_license_cached_status', array(
            'status'     => 'unchecked',
            'message'    => 'Henüz kontrol edilmedi.',
            'ban_reason' => '',
            'checked_at' => 0,
            'expires_at' => 0,
            'grace_until' => 0,
        ));
    }

    /**
     * Domain normalize (www/protocol/port temizler)
     */
    private static function normalize_domain(string $domain): string {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#/.*$#', '', $domain);
        $domain = preg_replace('#:\d+$#', '', $domain);
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }
        return $domain;
    }

    /**
     * Ortak imza anahtarı
     * Not: Üretimde wp-config.php içine MEVZU_LICENSE_SHARED_SECRET ekleyin.
     */
    public static function get_shared_secret(): string {
        if (defined('MEVZU_LICENSE_SHARED_SECRET') && MEVZU_LICENSE_SHARED_SECRET !== '') {
            return (string) MEVZU_LICENSE_SHARED_SECRET;
        }
        return (string) mevzu_key('license_shared_secret');
    }

    /**
     * Mevzu² AI / lisans API istek imzası
     */
    public static function sign_hmac_payload(array $payload): string {
        $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $json ?: '', self::get_shared_secret());
    }

    /**
     * Mevzu² AI kullanımı için lisans uygun mu?
     */
    public static function can_use_ai_services(): bool {
        if (self::get_license_key() === '') {
            return false;
        }
        $status = self::get_license_status();
        $state  = (string) ($status['status'] ?? 'inactive');
        if ($state === 'banned') {
            return false;
        }
        if ($state === 'active') {
            return true;
        }
        if ($state === 'inactive' && (int) ($status['grace_until'] ?? 0) > time()) {
            return true;
        }
        return false;
    }

    /**
     * API istekleri için normalize domain.
     */
    public static function normalize_domain_for_api(): string {
        return self::normalize_domain((string) parse_url(get_site_url(), PHP_URL_HOST));
    }

    /**
     * İmzalı payload doğrula
     */
    private static function verify_signature(array $payload, string $signature): bool {
        $expected = self::sign_hmac_payload($payload);
        return hash_equals($expected, $signature);
    }

    /**
     * Periyodik lisans kontrolü (12 saatte bir)
     */
    public function schedule_license_check() {
        $license_key = self::get_license_key();
        if (empty($license_key)) return;

        $status = self::get_license_status();
        $last_check = isset($status['checked_at']) ? intval($status['checked_at']) : 0;

        if ((time() - $last_check) >= self::CHECK_INTERVAL) {
            $this->do_license_check($license_key);
        }
    }

    /**
     * Sunucuya lisans doğrulama isteği gönder
     */
    private function do_license_check($license_key) {
        $domain   = self::normalize_domain((string) parse_url(get_site_url(), PHP_URL_HOST));
        $site_id  = self::get_site_id();
        $version  = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';
        $request_nonce = wp_generate_password(20, false, false);
        $request_time = time();

        $response = wp_remote_post(self::API_URL, array(
            'timeout' => 15,
            'body'    => array(
                'license_key' => $license_key,
                'domain'      => $domain,
                'site_id'     => $site_id,
                'version'     => $version,
                'request_nonce' => $request_nonce,
                'request_time' => $request_time,
            ),
        ));

        if (is_wp_error($response)) {
            $cached = self::get_license_status();
            $cached['checked_at'] = time();
            if ($cached['status'] === 'active') {
                $current_grace = (int) ($cached['grace_until'] ?? 0);
                if ($current_grace > 0 && $current_grace < time()) {
                    $cached['status'] = 'inactive';
                    $cached['message'] = 'Lisans sunucusuna ulaşılamadı ve bekleme süresi doldu.';
                    update_option('mevzu_license_cached_status', $cached, false);
                    return;
                }
                if ($current_grace <= 0) {
                    $cached['grace_until'] = time() + self::GRACE_PERIOD;
                }
            }
            $cached['message'] = 'Lisans sunucusuna ulaşılamadı, mevcut durum korunuyor.';
            update_option('mevzu_license_cached_status', $cached, false);
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body)) {
            $cached = self::get_license_status();
            $cached['checked_at'] = time();
            if ($cached['status'] === 'active' && (int) ($cached['grace_until'] ?? 0) < time()) {
                $cached['status'] = 'inactive';
                $cached['message'] = 'Lisans yanıtı geçersiz ve bekleme süresi doldu.';
            }
            update_option('mevzu_license_cached_status', $cached, false);
            return;
        }

        $payload = [];
        if (isset($body['payload'], $body['signature']) && is_array($body['payload'])) {
            $signature = sanitize_text_field((string) $body['signature']);
            if (!self::verify_signature($body['payload'], $signature)) {
                $cached = self::get_license_status();
                $cached['checked_at'] = time();
                if ($cached['status'] === 'active' && (int) ($cached['grace_until'] ?? 0) < time()) {
                    $cached['status'] = 'inactive';
                    $cached['message'] = 'Lisans yanıt imzası doğrulanamadı ve bekleme süresi doldu.';
                } else {
                    $cached['message'] = 'Lisans yanıt imzası doğrulanamadı.';
                }
                update_option('mevzu_license_cached_status', $cached, false);
                return;
            }
            $payload = $body['payload'];
        } else {
            // Geriye dönük uyumluluk (imzasız eski API yanıtı)
            $payload = $body;
        }

        $expires_at = isset($payload['expires_at']) ? (int) $payload['expires_at'] : (time() + self::CHECK_INTERVAL);
        $resp_nonce = isset($payload['nonce']) ? (string) $payload['nonce'] : '';
        if ($resp_nonce !== '' && !hash_equals($request_nonce, $resp_nonce)) {
            $cached = self::get_license_status();
            $cached['checked_at'] = time();
            if ($cached['status'] === 'active' && (int) ($cached['grace_until'] ?? 0) < time()) {
                $cached['status'] = 'inactive';
                $cached['message'] = 'Lisans yanıt nonce doğrulaması başarısız ve bekleme süresi doldu.';
            } else {
                $cached['message'] = 'Lisans yanıt nonce doğrulaması başarısız.';
            }
            update_option('mevzu_license_cached_status', $cached, false);
            return;
        }

        if ($expires_at < time() - 60) {
            $cached = self::get_license_status();
            $cached['checked_at'] = time();
            if ($cached['status'] === 'active' && (int) ($cached['grace_until'] ?? 0) < time()) {
                $cached['status'] = 'inactive';
                $cached['message'] = 'Lisans yanıtının süresi dolmuş ve bekleme süresi doldu.';
            } else {
                $cached['message'] = 'Lisans yanıtının süresi dolmuş.';
            }
            update_option('mevzu_license_cached_status', $cached, false);
            return;
        }

        $new_status = array(
            'status'     => sanitize_text_field((string) ($payload['status'] ?? 'inactive')),
            'message'    => isset($payload['message']) ? sanitize_text_field((string) $payload['message']) : '',
            'ban_reason' => isset($payload['ban_reason']) ? sanitize_text_field((string) $payload['ban_reason']) : '',
            'checked_at' => time(),
            'expires_at' => $expires_at,
            'grace_until' => 0,
        );

        if ($new_status['status'] === 'active') {
            $new_status['grace_until'] = time() + self::GRACE_PERIOD;
        }

        update_option('mevzu_license_cached_status', $new_status, false);
    }

    /**
     * Banned durumdaysa admin panelini kilitle
     */
    public function enforce_license() {
        $status = self::get_license_status();
        $license_key = self::get_license_key();

        // AJAX isteklerini engelleme (ayar sayfasında lisans düzeltebilmek için)
        if (defined('DOING_AJAX') && DOING_AJAX) return;

        // 1. Yasaklı Durumu (Tüm Admin Paneline Erişimi Kilitler - sadece ayarlar hariç)
        if ($status['status'] === 'banned') {
            if (isset($_GET['page']) && $_GET['page'] === 'mevzu-ayarlar') return;

            $title = 'Mevzu² — Lisans İptal Edildi';
            $reason = !empty($status['ban_reason']) 
                ? $status['ban_reason'] 
                : 'Lisans politikasını ihlal ettiğiniz için erişiminiz kesilmiştir.';
            
            $settings_url = admin_url('admin.php?page=mevzu-ayarlar');

            wp_die(
                '<div style="text-align:center;padding:40px;font-family:system-ui">' .
                '<h1 style="color:#d63638">Tema Erişimi Engellendi</h1>' .
                '<p style="font-size:16px;max-width:600px;margin:20px auto">' . esc_html($reason) . '</p>' .
                '<p style="margin-top:30px;"><a href="' . esc_url($settings_url) . '" style="display:inline-block;padding:10px 20px;background:#2271b1;color:#fff;text-decoration:none;border-radius:3px;">Lisans Ayarlarına Git</a></p>' .
                '<p style="color:#666;margin-top:20px;">Destek için: <a href="mailto:kerem.er35@gmail.com">kerem.er35@gmail.com</a></p>' .
                '</div>',
                $title,
                array('response' => 403)
            );
        }

        // 2. Eğer hiç lisans anahtarı girilmemişse, yönetici paneli içinde "mevzu-" ile başlayan
        // menülere girmek istediğinde Kurulum Sihirbazı'na yönlendir
        if (empty($license_key)) {
            if (isset($_GET['page']) && strpos($_GET['page'], 'mevzu-') === 0 && $_GET['page'] !== 'mevzu-setup-wizard') {
                wp_safe_redirect(admin_url('admin.php?page=mevzu-setup-wizard'));
                exit;
            }
        }
    }

    /**
     * Frontend'de de banned kontrolü
     */
    public function enforce_license_frontend() {
        $status = self::get_license_status();
        $license_key = self::get_license_key();
        
        $is_blocked = false;
        $reason = '';
        $title = 'Erişim Kısıtlandı';

        if ($status['status'] === 'banned') {
            $is_blocked = true;
            $reason = !empty($status['ban_reason']) 
                ? $status['ban_reason'] 
                : 'Bu web sitesinin tema lisansı iptal edilmiştir.';
        } elseif ($status['status'] === 'inactive' || empty($license_key)) {
            $is_blocked = true;
            $reason = 'Bu web sitesi için geçerli bir tema lisansı bulunmamaktadır.';
        } elseif ($status['status'] !== 'active' && $status['status'] !== 'unchecked') {
            $is_blocked = true;
            $reason = 'Tema lisansı doğrulanamadı.';
        }

        if ($is_blocked) {
            wp_die(
                '<div style="text-align:center;padding:60px;font-family:system-ui">' .
                '<h1 style="color:#d63638">' . esc_html($title) . '</h1>' .
                '<p style="font-size:16px;max-width:600px;margin:20px auto">' . esc_html($reason) . '</p>' .
                '<p style="color:#666">Site yöneticisiyle iletişime geçin.</p>' .
                '</div>',
                $title,
                array('response' => 403)
            );
        }
    }

    // ============================================================
    //  AJAX
    // ============================================================

    /**
     * AJAX — El ile lisans doğrula
     */
    public function ajax_verify_license() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');

        $license_key = self::get_license_key();
        if (empty($license_key)) {
            wp_send_json_error('Lisans anahtarı girilmemiş.');
        }

        $this->do_license_check($license_key);
        $status = self::get_license_status();

        wp_send_json_success($status);
    }

    /**
     * AJAX — Lisans anahtarını kaydet
     */
    public function ajax_save_license() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');

        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        $existing_key = self::get_license_key();
        
        if (empty($license_key)) {
            if (empty($existing_key)) {
                // Önceden bir lisans yoksa ve boş olarak butona basıldıysa hata ver.
                wp_send_json_error('Lütfen geçerli bir lisans anahtarı giriniz.');
            } else {
                // Önceden lisans varsa ve içini boşaltıp kaydet dediyse, lisansı sil.
                delete_option('mevzu_license_key');
                delete_option('mevzu_license_cached_status');
                wp_send_json_success(array(
                    'message' => 'Lisans anahtarı başarıyla kaldırıldı.',
                    'status'  => array('status' => 'unchecked')
                ));
            }
        }

        update_option('mevzu_license_key', $license_key, false);
        
        // Hemen doğrulama yap
        $this->do_license_check($license_key);
        $status = self::get_license_status();
        
        if ($status['status'] === 'active') {
            $message = 'Lisans başarıyla doğrulandı.';
        } elseif ($status['status'] === 'banned') {
            $message = 'Lisansınız yasaklanmıştır' . (!empty($status['ban_reason']) ? ': ' . $status['ban_reason'] : '.');
        } elseif ($status['status'] === 'inactive') {
            $message = 'Girdiğiniz lisans anahtarı geçersiz veya inaktif.';
        } else {
            $message = 'Lisans doğrulanamadı.';
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'status'  => $status,
        ));
    }

    // ============================================================
    //  SÜRÜM YÖNETİMİ (DOWNGRADE/UPGRADE)
    // ============================================================

    /**
     * Lisans sunucusundan sürüm listesini alır.
     *
     * @return array|WP_Error
     */
    private static function fetch_versions_from_api() {
        $license_key = self::get_license_key();
        if ( empty( $license_key ) ) {
            return new WP_Error( 'mevzu_no_license', 'Lisans anahtarı gerekli.' );
        }

        $current_version = defined( 'MEVZU_THEME_VERSION' ) ? MEVZU_THEME_VERSION : '1.0.0';
        $domain          = self::normalize_domain( (string) parse_url( get_site_url(), PHP_URL_HOST ) );
        $site_id         = self::get_site_id();

        $response = wp_remote_post(
            self::UPDATE_API_URL,
            array(
                'timeout' => 15,
                'body'    => array(
                    'license_key'     => $license_key,
                    'current_version' => $current_version,
                    'action'          => 'list_versions',
                    'domain'          => $domain,
                    'site_id'         => $site_id,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'mevzu_remote', 'Sunucuya bağlanılamadı.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['success'] ) || ! $body['success'] ) {
            return new WP_Error(
                'mevzu_versions_api',
                isset( $body['message'] ) ? $body['message'] : 'Bilinmeyen hata.'
            );
        }

        return $body;
    }

    /**
     * Sürüm listesini arayüz için filtreler: mevcut, daha yeni sürümler ve en fazla N önceki.
     *
     * @param array  $versions        API versions dizisi.
     * @param string $current_version Kurulu tema sürümü.
     * @return array
     */
    public static function filter_versions_for_management( array $versions, string $current_version ): array {
        if ( empty( $versions ) ) {
            return array();
        }

        $normalized = array();
        foreach ( $versions as $v ) {
            if ( ! is_array( $v ) || empty( $v['version'] ) ) {
                continue;
            }
            $normalized[] = array(
                'version'    => (string) $v['version'],
                'is_current' => false,
                'is_latest'  => false,
            );
        }

        if ( empty( $normalized ) ) {
            return array();
        }

        usort(
            $normalized,
            function ( $a, $b ) {
                return version_compare( $b['version'], $a['version'], '>' ) ? 1 : ( version_compare( $b['version'], $a['version'], '<' ) ? -1 : 0 );
            }
        );

        $latest_ver = $normalized[0]['version'];
        foreach ( $normalized as &$row ) {
            $row['is_latest']  = version_compare( $row['version'], $latest_ver, '=' );
            $row['is_current'] = version_compare( $row['version'], $current_version, '=' );
        }
        unset( $row );

        $current_index = null;
        foreach ( $normalized as $i => $row ) {
            if ( $row['is_current'] ) {
                $current_index = $i;
                break;
            }
        }

        if ( $current_index === null ) {
            return array_slice( $normalized, 0, 1 + self::MAX_ROLLBACK_VERSIONS );
        }

        $allowed = array();
        for ( $i = 0; $i < $current_index; $i++ ) {
            $allowed[] = $normalized[ $i ];
        }
        $allowed[] = $normalized[ $current_index ];
        for ( $offset = 1; $offset <= self::MAX_ROLLBACK_VERSIONS; $offset++ ) {
            $idx = $current_index + $offset;
            if ( isset( $normalized[ $idx ] ) ) {
                $allowed[] = $normalized[ $idx ];
            }
        }

        return $allowed;
    }

    /**
     * İstenen sürüme geçiş izinli mi? (Güncelleme serbest; geri dönüş en fazla N adım.)
     */
    public static function is_version_target_allowed( string $requested_version, string $current_version, array $versions_from_api ): bool {
        if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $requested_version ) ) {
            return false;
        }
        if ( version_compare( $requested_version, $current_version, '=' ) ) {
            return false;
        }
        if ( version_compare( $requested_version, $current_version, '>' ) ) {
            return true;
        }

        $allowed = self::filter_versions_for_management( $versions_from_api, $current_version );
        foreach ( $allowed as $row ) {
            if ( $row['is_current'] ) {
                continue;
            }
            if ( version_compare( $row['version'], $requested_version, '=' ) ) {
                return version_compare( $row['version'], $current_version, '<' );
            }
        }

        return false;
    }

    /**
     * AJAX — Mevcut sürümleri listele
     */
    public function ajax_list_versions() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');

        $current_version = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';
        $body            = self::fetch_versions_from_api();

        if ( is_wp_error( $body ) ) {
            wp_send_json_error( $body->get_error_message() );
        }

        $body['versions']              = self::filter_versions_for_management( $body['versions'] ?? array(), $current_version );
        $body['max_rollback_versions'] = self::MAX_ROLLBACK_VERSIONS;

        wp_send_json_success($body);
    }

    /**
     * Belirtilen tema sürümünü indirip kurar (AJAX ve otomatik güncelleme ortak).
     *
     * @param bool $silent true ise çıktı üretmeyen skin (cron/arka plan); false ise AJAX arayüzü için.
     * @return array|WP_Error Başarıda message, new_version, is_downgrade
     */
    public static function apply_theme_version($requested_version, $silent = false) {
        $requested_version = sanitize_text_field((string) $requested_version);
        if ($requested_version === '') {
            return new WP_Error('mevzu_no_version', 'Sürüm belirtilmedi.');
        }

        $license_key = self::get_license_key();
        if (empty($license_key)) {
            return new WP_Error('mevzu_no_license', 'Lisans anahtarı gerekli.');
        }

        $current_version = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';

        $versions_body = self::fetch_versions_from_api();
        if ( is_wp_error( $versions_body ) ) {
            return $versions_body;
        }
        if ( ! self::is_version_target_allowed( $requested_version, $current_version, $versions_body['versions'] ?? array() ) ) {
            return new WP_Error(
                'mevzu_rollback_limit',
                sprintf(
                    /* translators: %d: max rollback count */
                    __( 'En fazla %d önceki sürüme dönebilirsiniz.', 'mevzu2' ),
                    self::MAX_ROLLBACK_VERSIONS
                )
            );
        }

        $domain = self::normalize_domain((string) parse_url(get_site_url(), PHP_URL_HOST));
        $site_id = self::get_site_id();

        $response = wp_remote_post(self::UPDATE_API_URL, array(
            'timeout' => 15,
            'body'    => array(
                'license_key'       => $license_key,
                'current_version'   => $current_version,
                'requested_version' => $requested_version,
                'domain'            => $domain,
                'site_id'           => $site_id,
            ),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('mevzu_remote', 'Sunucuya bağlanılamadı.');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['success']) || !$body['success'] || !isset($body['package_url'])) {
            return new WP_Error(
                'mevzu_package',
                isset($body['message']) ? $body['message'] : 'İndirme bilgisi alınamadı.'
            );
        }

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';

        if ($silent) {
            require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
            $skin = new Automatic_Upgrader_Skin();
        } else {
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
            $skin = new WP_Ajax_Upgrader_Skin();
        }
        $upgrader = new Theme_Upgrader($skin);

        $theme_slug = get_template();
        $package_url = esc_url_raw($body['package_url']);

        // ============================================================
        //  WordPress'in "tema zaten güncel" kontrolünü atlatmak için
        //  update_themes transient'ına geçici olarak sahte bir güncelleme
        //  kaydı enjekte ediyoruz. Bu sayede hem upgrade hem downgrade çalışır.
        // ============================================================
        $original_transient = get_site_transient('update_themes');

        $fake_transient = $original_transient ?: new stdClass();
        if (!isset($fake_transient->response)) {
            $fake_transient->response = array();
        }

        $fake_transient->response[ $theme_slug ] = array(
            'theme'       => $theme_slug,
            'new_version' => $requested_version,
            'package'     => $package_url,
            'url'         => '',
        );

        set_site_transient('update_themes', $fake_transient);

        $result = $upgrader->upgrade($theme_slug);

        if ($original_transient) {
            set_site_transient('update_themes', $original_transient);
        } else {
            delete_site_transient('update_themes');
        }

        if (is_wp_error($result)) {
            return new WP_Error('mevzu_upgrade', 'Güncelleme hatası: ' . $result->get_error_message());
        }

        if ($result === false) {
            $errors = $skin->get_errors();
            if (is_wp_error($errors) && $errors->has_errors()) {
                return new WP_Error('mevzu_upgrade', 'Güncelleme hatası: ' . $errors->get_error_message());
            }
            return new WP_Error('mevzu_upgrade', 'Güncelleme başarısız oldu.');
        }

        delete_transient('kkerem_theme_gist_content');

        $is_downgrade = version_compare($requested_version, $current_version, '<');

        return array(
            'message'      => $is_downgrade
                ? 'Tema v' . $requested_version . ' sürümüne başarıyla düşürüldü.'
                : 'Tema v' . $requested_version . ' sürümüne başarıyla güncellendi.',
            'new_version'  => $requested_version,
            'is_downgrade' => $is_downgrade,
        );
    }

    /**
     * AJAX — Belirli bir sürüme geç (downgrade/upgrade)
     */
    public function ajax_apply_version() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }

        $requested_version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
        $result = self::apply_theme_version($requested_version, false);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    // ============================================================
    //  HAKKINDA TAB İÇERİĞİ (RENDER)
    // ============================================================

    /**
     * Lisans bölümünü render et (Hakkında tabı içine)
     */
    public static function render_license_section() {
        $license_key = self::get_license_key();
        $status = self::get_license_status();
        $site_id = self::get_site_id();
        $current_version = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';
        
        // Durum renkleri
        $status_colors = array(
            'active'    => '#00a32a',
            'inactive'  => '#dba617',
            'banned'    => '#d63638',
            'unchecked' => '#646970',
            'error'     => '#d63638',
        );
        $color = isset($status_colors[$status['status']]) ? $status_colors[$status['status']] : '#646970';
        
        $status_labels = array(
            'active'    => 'Aktif',
            'inactive'  => 'İnaktif',
            'banned'    => 'Yasaklı',
            'unchecked' => 'Kontrol Edilmedi',
            'error'     => 'Hata',
        );
        $label = isset($status_labels[$status['status']]) ? $status_labels[$status['status']] : $status['status'];
        
        $is_locked = false;
        if ($status['status'] === 'banned') {
            $is_locked = true;
        } elseif ($status['status'] === 'inactive' || empty($license_key)) {
            $is_locked = true;
        } elseif ($status['status'] !== 'active' && $status['status'] !== 'unchecked') {
            $is_locked = true;
        }
        
        $is_wizard = (isset($_GET['page']) && $_GET['page'] === 'mevzu-setup-wizard');
        ?>
        <?php if (!$is_locked && !$is_wizard): ?>
        <!-- SÜRÜM YÖNETİMİ -->
        <hr class="my-4">
        <h3>Sürüm Yönetimi</h3>
        <p class="description">Mevcut sürümünüz sorun çıkardıysa önceki sürüme geri dönebilirsiniz. Daha yeni bir sürüm yayınlandıysa güncelleyebilirsiniz.</p>
        
        <div class="my-3">
            <button type="button" class="button button-secondary d-flex align-items-center" id="mevzu-list-versions">
            <i class="ri-folder-history-line dashicons me-1 fs-6 d-flex align-items-center"></i>
                Sürümleri Listele
            </button>
            <div id="mevzu-versions-list" class="mt-3"></div>
        </div>
        <?php endif; ?>

        <!-- LİSANS BİLGİLERİ -->
        <div class="mevzu-license-section">
            <div class="mevzu-field mb-3">
                <label for="mevzu_license_key"><strong>Lisans Anahtarı</strong></label>
                <div class="d-flex gap-2 align-items-stretch mt-1 col-12 col-md-3">
                    <input type="text" id="mevzu_license_key" 
                           value="<?php echo esc_attr($license_key); ?>" 
                           class="regular-text form-control form-control-sm flex-grow-1" 
                           placeholder="XXXX-XXXX-XXXX">
                    <button type="button" class="button button-primary text-nowrap" id="mevzu-save-license">
                        Kaydet & Doğrula
                    </button>
                </div>
                <?php 
                    $status_class = '';
                    if ($status['status'] === 'active') {
                        $status_class = 'text-success';
                    } elseif ($status['status'] === 'banned' || $status['status'] === 'inactive' || empty($license_key)) {
                        $status_class = 'text-danger';
                    }
                ?>
                <div id="license-verify-status" class="d-flex align-items-center small fw-semibold mt-2 <?php echo $status_class; ?>">
                    <?php 
                    if ($status['status'] === 'active') {
                        echo '✓ Lisansınız aktif ve geçerli.';
                    } elseif ($status['status'] === 'banned') {
                        echo '✗ Lisansınız yasaklanmıştır' . (!empty($status['ban_reason']) ? ': ' . esc_html($status['ban_reason']) : '.');
                    } elseif ($status['status'] === 'inactive' && !empty($license_key)) {
                        echo '✗ Lisans anahtarı geçersiz veya inaktif.';
                    } elseif (empty($license_key)) {
                        // Hiçbir şey yazdırma.
                    } else {
                        // echo esc_html($label);
                    }
                    ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Lisans Kaydet & Doğrula
            $('#mevzu-save-license').on('click', function() {
                var $btn = $(this);
                var key = $('#mevzu_license_key').val();
                var $verifyStatus = $('#license-verify-status');
                
                $btn.prop('disabled', true).text('Doğrulanıyor...');
                $verifyStatus.html('').removeClass('text-success text-danger');
                
                $.post(mevzuSettings.ajaxUrl, {
                    action: 'mevzu_save_license',
                    nonce: mevzuSettings.nonce,
                    license_key: key
                }, function(res) {
                    $btn.prop('disabled', false).text('Kaydet & Doğrula');
                    if (res.success) {
                        var st = res.data.status || {};
                        var isSuccess = (st.status === 'active');
                        var statusClass = isSuccess ? 'text-success' : 'text-danger';
                        var icon = isSuccess ? '✓ ' : '✗ ';
                        
                        $verifyStatus.html(icon + res.data.message).addClass(statusClass);
                        
                        // İşlem tamamlandıktan 1 saniye sonra sayfayı yenile veya sihirbazsa 2. adıma geç
                        setTimeout(function() {
                            if (isSuccess && window.location.search.indexOf('page=mevzu-setup-wizard') !== -1) {
                                window.location.href = '?page=mevzu-setup-wizard&step=2';
                            } else {
                                window.location.reload();
                            }
                        }, 1000);
                    } else {
                        $verifyStatus.text('✗ ' + res.data).addClass('text-danger');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('Kaydet & Doğrula');
                    $verifyStatus.text('Bağlantı hatası').addClass('text-danger');
                });
            });

            // Sürümleri Listele
            $('#mevzu-list-versions').on('click', function() {
                var $btn = $(this);
                var $list = $('#mevzu-versions-list');
                
                $btn.prop('disabled', true).find('.dashicons').addClass('ri-spin');
                $list.html('<p class="text-muted">Y\u00fckleniyor...</p>');
                
                $.post(mevzuSettings.ajaxUrl, {
                    action: 'mevzu_list_versions',
                    nonce: mevzuSettings.nonce
                }, function(res) {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('ri-spin');
                    
                    if (!res.success) {
                        $list.html('<p class="text-danger">Hata: ' + res.data + '</p>');
                        return;
                    }
                    
                    var versions = res.data.versions || [];
                    var maxRollback = res.data.max_rollback_versions || <?php echo (int) self::MAX_ROLLBACK_VERSIONS; ?>;
                    if (!versions.length) {
                        $list.html('<p>Sunucuda s\u00fcr\u00fcm bulunamadı.</p>');
                        return;
                    }
                    
                    var html = '<div class="d-grid gap-2 col-12 col-md-3">';
                    $.each(versions, function(i, v) {
                        var isCurrent = v.is_current;
                        var isLatest = v.is_latest;
                        var badge = '';
                        
                        if (isCurrent) badge += ' <span class="badge badge-primary text-primary ms-1">Mevcut</span>';
                        if (isLatest) badge += ' <span class="badge badge-success ms-1"><i class="ri-fire-line me-2"></i>En Yeni Sürüm</span>';
                        
                        html += '<div class="d-flex align-items-center justify-content-between p-3 border rounded ' + (isCurrent ? 'bg-light border-primary' : '') + '">';
                        html += '<div>Mevzu² <strong>v' + v.version + '</strong>' + badge + '</div>';
                        
                        if (!isCurrent) {
                            var btnText = isLatest ? 'Güncelle' : 'Bu sürüme dön';
                            var btnClass = isLatest ? 'button-primary' : 'button-secondary';
                            html += '<button type="button" class="button ' + btnClass + ' mevzu-apply-version" data-version="' + v.version + '">' + btnText + '</button>';
                        } else {
                            html += '<span class="text-primary fw-semibold small">Kullanılıyor</span>';
                        }
                        
                        html += '</div>';
                    });
                    html += '</div>';
                    $list.html(html);
                }).fail(function() {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('ri-spin');
                    $list.html('<p class="text-danger">Bağlantı hatası</p>');
                });
            });

            // Sürüm Uygula (Downgrade/Upgrade)
            $(document).on('click', '.mevzu-apply-version', function() {
                var $btn = $(this);
                var version = $btn.data('version');
                var currentVersion = '<?php echo esc_js($current_version); ?>';
                var isDowngrade = version < currentVersion;
                
                var msg = isDowngrade 
                    ? 'v' + version + ' sürümüne GERİ DÖNMEK istediğinize emin misiniz?\n\nBu işlem temanızı eski sürüme döndürecektir.' 
                    : 'v' + version + ' sürümüne GÜNCELLEMEK istediğinize emin misiniz?';
                
                if (!confirm(msg)) return;
                
                $btn.prop('disabled', true).text('İşleniyor...');
                
                $.post(mevzuSettings.ajaxUrl, {
                    action: 'mevzu_apply_version',
                    nonce: mevzuSettings.nonce,
                    version: version
                }, function(res) {
                    if (res.success) {
                        alert(res.data.message + '\n\nSayfa yeniden yüklenecek.');
                        window.location.reload();
                    } else {
                        $btn.prop('disabled', false).text(isDowngrade ? 'Geç' : 'Güncelle');
                        alert('Hata: ' + res.data);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text(isDowngrade ? 'Geç' : 'Güncelle');
                    alert('Bağlantı hatası');
                });
            });
        });
        </script>
        <?php
    }
}

new Mevzu_License();
