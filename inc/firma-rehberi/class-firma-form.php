<?php
/**
 * Firma Rehberi — Ön Yüz Başvuru Formu (AJAX + Dosya Yükleme)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Firma_Form {

    public function __construct() {
        add_action( 'wp_ajax_firma_submit',        [ $this, 'handle_submit' ] );
        add_action( 'wp_ajax_nopriv_firma_submit', [ $this, 'handle_submit' ] );
        add_action( 'wp_ajax_firma_view',          [ $this, 'handle_view' ] );
        add_action( 'wp_ajax_nopriv_firma_view',   [ $this, 'handle_view' ] );
        add_action( 'wp_ajax_firma_rate',          [ $this, 'handle_rate' ] );
        add_action( 'wp_ajax_nopriv_firma_rate',   [ $this, 'handle_rate' ] );
    }

    /* ------------------------------------------------------------------ */
    /* AJAX: Sayfa Görüntülenme Sayacı                                      */
    /* ------------------------------------------------------------------ */

    public function handle_view() {
        $post_id = absint( $_POST['post_id'] ?? 0 );
        if ( ! $post_id || get_post_type( $post_id ) !== 'firma' ) {
            wp_send_json_error();
        }

        // Aynı ziyaretçi 1 saat içinde tekrar sayılmaz
        $ip_key   = 'firma_view_' . $post_id . '_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
        if ( get_transient( $ip_key ) ) {
            wp_send_json_success( [ 'count' => (int) get_post_meta( $post_id, '_firma_view_count', true ) ] );
        }

        $count = (int) get_post_meta( $post_id, '_firma_view_count', true ) + 1;
        update_post_meta( $post_id, '_firma_view_count', $count );
        set_transient( $ip_key, 1, HOUR_IN_SECONDS );

        wp_send_json_success( [ 'count' => $count ] );
    }

    /* ------------------------------------------------------------------ */
    /* AJAX: Başvuru İşle                                                   */
    /* ------------------------------------------------------------------ */

    public function handle_submit() {
        // Nonce kontrolü
        if ( ! check_ajax_referer( 'firma_submit_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Güvenlik doğrulaması başarısız.' );
        }

        // Giriş zorunluluğu
        $login_required = Firma_Admin::get( 'login_required', false );
        if ( $login_required && ! is_user_logged_in() ) {
            wp_send_json_error( 'Bu işlem için giriş yapmanız gerekiyor.' );
        }

        // Zorunlu alan kontrolleri
        $firma_adi = sanitize_text_field( $_POST['firma_adi'] ?? '' );
        if ( empty( $firma_adi ) ) {
            wp_send_json_error( 'Firma adı zorunludur.' );
        }

        $submitter_name  = sanitize_text_field( $_POST['submitter_name']  ?? '' );
        $submitter_email = sanitize_email(      $_POST['submitter_email'] ?? '' );

        if ( empty( $submitter_email ) || ! is_email( $submitter_email ) ) {
            wp_send_json_error( 'Geçerli bir e-posta adresi giriniz.' );
        }

        // Aynı e-posta ile son 24 saatte kaç başvuru var? (spam koruması)
        if ( ! current_user_can( 'manage_options' ) ) {
            $key     = 'firma_submit_' . md5( $submitter_email );
            $count   = (int) get_transient( $key );
            if ( $count >= 3 ) {
                wp_send_json_error( 'Çok fazla başvuru yaptınız. Lütfen daha sonra tekrar deneyin.' );
            }
            set_transient( $key, $count + 1, DAY_IN_SECONDS );
        }

        // Post oluştur (pending — moderasyon)
        $post_id = wp_insert_post( [
            'post_title'   => $firma_adi,
            'post_content' => wp_kses_post( $_POST['aciklama'] ?? '' ),
            'post_type'    => 'firma',
            'post_status'  => 'pending',
            'post_author'  => is_user_logged_in() ? get_current_user_id() : 1,
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( 'Başvuru kaydedilemedi. Lütfen tekrar deneyin.' );
        }

        // Meta kaydet
        $meta_map = [
            '_firma_yetkili'          => sanitize_text_field( $_POST['yetkili']     ?? '' ),
            '_firma_telefon'          => sanitize_text_field( $_POST['telefon']     ?? '' ),
            '_firma_sabit_tel1'       => sanitize_text_field( $_POST['sabit_tel1']  ?? '' ),
            '_firma_sabit_tel2'       => sanitize_text_field( $_POST['sabit_tel2']  ?? '' ),
            '_firma_eposta'           => sanitize_email(      $_POST['eposta']      ?? '' ),
            '_firma_video'            => esc_url_raw(         $_POST['video']       ?? '' ),
            '_firma_website'          => esc_url_raw(         $_POST['website']     ?? '' ),
            '_firma_adres'            => sanitize_textarea_field( $_POST['adres']   ?? '' ),
            '_firma_submitter_name'   => $submitter_name,
            '_firma_submitter_email'  => $submitter_email,
            '_firma_prev_status'      => 'pending',
        ];
        foreach ( $meta_map as $k => $v ) {
            if ( $v !== '' ) update_post_meta( $post_id, $k, $v );
        }

        // Adres → otomatik koordinat
        $adres = sanitize_textarea_field( $_POST['adres'] ?? '' );
        if ( $adres ) {
            $coords = $this->geocode_address( $adres );
            if ( $coords ) {
                update_post_meta( $post_id, '_firma_lat', $coords['lat'] );
                update_post_meta( $post_id, '_firma_lng', $coords['lng'] );
                update_post_meta( $post_id, '_firma_adres_geocoded', $adres );
            }
        }

        // Çalışma saatleri
        $saatler_unknown = ! empty( $_POST['saatler_unknown'] ) ? '1' : '0';
        update_post_meta( $post_id, '_firma_saatler_unknown', $saatler_unknown );
        if ( ! $saatler_unknown && ! empty( $_POST['saatler'] ) && is_array( $_POST['saatler'] ) ) {
            $valid_days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];
            $saatler    = [];
            foreach ( $valid_days as $day ) {
                $d = $_POST['saatler'][ $day ] ?? [];
                $saatler[ $day ] = [
                    'open'   => sanitize_text_field( $d['open']   ?? '09:00' ),
                    'close'  => sanitize_text_field( $d['close']  ?? '18:00' ),
                    'closed' => ! empty( $d['closed'] ),
                ];
            }
            update_post_meta( $post_id, '_firma_saatler', wp_json_encode( $saatler ) );
        }

        // Taxonomy atama
        if ( ! empty( $_POST['kategori'] ) ) {
            $kat_id = absint( $_POST['kategori'] );
            if ( $kat_id ) wp_set_object_terms( $post_id, $kat_id, 'firma-kategori' );
        }
        if ( ! empty( $_POST['sehir'] ) ) {
            $sehir_id = absint( $_POST['sehir'] );
            if ( $sehir_id ) wp_set_object_terms( $post_id, $sehir_id, 'firma-sehir' );
        }

        // Logo yükleme
        if ( ! empty( $_FILES['firma_logo']['name'] ) ) {
            $logo_id = $this->handle_logo_upload( $post_id );
            if ( $logo_id && ! is_wp_error( $logo_id ) ) {
                set_post_thumbnail( $post_id, $logo_id );
            }
        }

        // Galeri yükleme
        if ( ! empty( $_FILES['firma_galeri']['name'][0] ) ) {
            $galeri_ids = $this->handle_galeri_upload( $post_id );
            if ( ! empty( $galeri_ids ) ) {
                update_post_meta( $post_id, '_firma_galeri', wp_json_encode( $galeri_ids ) );
            }
        }

        // Admin bildirimi
        Firma_Notification::send_new_submission( $post_id );

        wp_send_json_success( [
            'message' => 'Başvurunuz alındı! İncelendikten sonra yayınlanacak.',
        ] );
    }

    /* ------------------------------------------------------------------ */
    /* Galeri Yükle                                                         */
    /* ------------------------------------------------------------------ */

    private function handle_galeri_upload( $post_id ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $allowed  = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
        $max_size = 2 * 1024 * 1024;
        $ids      = [];
        $files    = $_FILES['firma_galeri'];
        $count    = min( count( $files['name'] ), 5 );

        for ( $i = 0; $i < $count; $i++ ) {
            if ( empty( $files['name'][ $i ] ) || $files['error'][ $i ] !== UPLOAD_ERR_OK ) continue;
            if ( ! in_array( $files['type'][ $i ], $allowed ) ) continue;
            if ( $files['size'][ $i ] > $max_size ) continue;

            $file = [
                'name'     => $files['name'][ $i ],
                'type'     => $files['type'][ $i ],
                'tmp_name' => $files['tmp_name'][ $i ],
                'error'    => $files['error'][ $i ],
                'size'     => $files['size'][ $i ],
            ];

            $uploaded = wp_handle_upload( $file, [ 'test_form' => false ] );
            if ( isset( $uploaded['error'] ) ) continue;

            $attach_id = wp_insert_attachment( [
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name( pathinfo( $uploaded['file'], PATHINFO_FILENAME ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ], $uploaded['file'], $post_id );

            if ( ! is_wp_error( $attach_id ) ) {
                wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $uploaded['file'] ) );
                $ids[] = $attach_id;
            }
        }

        return $ids;
    }

    /* ------------------------------------------------------------------ */
    /* Logo Yükle                                                           */
    /* ------------------------------------------------------------------ */

    private function handle_logo_upload( $post_id ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $file     = $_FILES['firma_logo'];
        $allowed  = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ( ! in_array( $file['type'], $allowed ) ) return new WP_Error( 'type', 'Sadece JPG, PNG, WebP veya GIF yükleyebilirsiniz.' );
        if ( $file['size'] > $max_size )             return new WP_Error( 'size', 'Görsel 2MB\'ı aşamaz.' );

        $overrides = [ 'test_form' => false ];
        $uploaded  = wp_handle_upload( $file, $overrides );

        if ( isset( $uploaded['error'] ) ) return new WP_Error( 'upload', $uploaded['error'] );

        $attachment = [
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name( pathinfo( $uploaded['file'], PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], $post_id );
        $metadata  = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
        wp_update_attachment_metadata( $attach_id, $metadata );

        return $attach_id;
    }

    /* ------------------------------------------------------------------ */
    /* AJAX: Yıldız Değerlendirmesi                                         */
    /* ------------------------------------------------------------------ */

    public function handle_rate() {
        check_ajax_referer( 'firma_submit_nonce', 'nonce' );

        $post_id = absint( $_POST['post_id'] ?? 0 );
        $stars   = absint( $_POST['stars']   ?? 0 );

        if ( ! $post_id || $stars < 1 || $stars > 5 ) {
            wp_send_json_error( 'Geçersiz değerlendirme.' );
        }

        if ( get_post_type( $post_id ) !== 'firma' ) {
            wp_send_json_error( 'Geçersiz içerik.' );
        }

        // Voter tanımlayıcısı — giriş yapmış kullanıcı için ID, misafir için cookie token
        if ( is_user_logged_in() ) {
            $voter_id = 'u_' . get_current_user_id();
        } else {
            $cookie_name = 'firma_voter_' . $post_id;
            if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
                $voter_id = sanitize_text_field( $_COOKIE[ $cookie_name ] );
            } else {
                $voter_id = 'g_' . wp_generate_password( 16, false );
            }
        }

        // Oy vermiş mi kontrol et
        $voters = get_post_meta( $post_id, '_firma_rating_voters', true );
        $voters = $voters ? json_decode( $voters, true ) : [];

        if ( in_array( $voter_id, $voters, true ) ) {
            $avg   = $this->get_rating_avg( $post_id );
            $count = (int) get_post_meta( $post_id, '_firma_rating_count', true );
            wp_send_json_error( [
                'msg'   => 'Bu firmayı daha önce değerlendirdiniz.',
                'avg'   => $avg,
                'count' => $count,
                'voted' => true,
            ] );
        }

        // Puanı kaydet
        $total = (int) get_post_meta( $post_id, '_firma_rating_total', true ) + $stars;
        $count = (int) get_post_meta( $post_id, '_firma_rating_count', true ) + 1;
        update_post_meta( $post_id, '_firma_rating_total', $total );
        update_post_meta( $post_id, '_firma_rating_count', $count );

        // Voter listesine ekle
        $voters[] = $voter_id;
        update_post_meta( $post_id, '_firma_rating_voters', wp_json_encode( $voters ) );

        $avg = round( $total / $count, 1 );

        // Misafir için cookie ayarla (1 yıl)
        if ( ! is_user_logged_in() ) {
            setcookie( 'firma_voter_' . $post_id, $voter_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        }

        wp_send_json_success( [
            'msg'   => $stars . '. yıldızı verdiniz.',
            'avg'   => $avg,
            'count' => $count,
            'stars' => $stars,
        ] );
    }

    private function get_rating_avg( $post_id ) {
        $total = (int) get_post_meta( $post_id, '_firma_rating_total', true );
        $count = (int) get_post_meta( $post_id, '_firma_rating_count', true );
        return $count > 0 ? round( $total / $count, 1 ) : 0;
    }

    private function geocode_address( $address ) {
        if ( ! function_exists( 'curl_init' ) ) return null;

        $url  = 'https://nominatim.openstreetmap.org/search?' . http_build_query( [
            'q' => $address, 'format' => 'json', 'limit' => '1',
        ] );
        $body = $this->do_curl( $url );
        $data = $body ? json_decode( $body, true ) : null;

        if ( ! empty( $data[0]['lat'] ) ) {
            return [
                'lat' => number_format( (float) $data[0]['lat'], 6, '.', '' ),
                'lng' => number_format( (float) $data[0]['lon'], 6, '.', '' ),
            ];
        }

        $photon = 'https://photon.komoot.io/api/?' . http_build_query( [ 'q' => $address, 'limit' => 1, 'lang' => 'tr' ] );
        $body2  = $this->do_curl( $photon );
        $geo    = $body2 ? json_decode( $body2, true ) : null;

        if ( ! empty( $geo['features'][0]['geometry']['coordinates'] ) ) {
            $c = $geo['features'][0]['geometry']['coordinates'];
            return [
                'lat' => number_format( (float) $c[1], 6, '.', '' ),
                'lng' => number_format( (float) $c[0], 6, '.', '' ),
            ];
        }

        return null;
    }

    private function do_curl( $url ) {
        $ch = curl_init( $url );
        curl_setopt_array( $ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'MevzuFirmaRehberi/1.0 (contact@example.com)',
            CURLOPT_HTTPHEADER     => [ 'Accept-Language: tr,en' ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ] );
        $result = curl_exec( $ch );
        curl_close( $ch );
        return $result ?: null;
    }
}
