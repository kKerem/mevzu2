<?php
/**
 * Firma Rehberi — Admin Ayarlar Sayfası
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Firma_Admin {

    const OPT = 'firma_rehberi_settings';

    public static function get( $key, $default = null ) {
        $opts = get_option( self::OPT, [] );
        return $opts[ $key ] ?? $default;
    }

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action( 'wp_ajax_firma_save_settings',   [ $this, 'ajax_save' ] );
        add_action( 'wp_ajax_firma_create_page',     [ $this, 'ajax_create_page' ] );
        add_action( 'wp_ajax_firma_geocode',         [ $this, 'ajax_geocode' ] );
        add_action( 'admin_notices',         [ $this, 'pending_notice' ] );
    }

    public function pending_notice() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $pending = (int) wp_count_posts( 'firma' )->pending;
        if ( $pending < 1 ) return;
        $url = admin_url( 'edit.php?post_type=firma&post_status=pending' );
        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            sprintf(
                _n( 'Onay bekleyen %d firma başvurusu var.', 'Onay bekleyen %d firma başvurusu var.', $pending, 'mevzu2' ),
                $pending
            ),
            esc_url( $url ),
            'Başvuruları incele'
        );
    }

    public function add_menu() {
        // Firma Rehberi CPT menüsüne ayarlar sayfası
        add_submenu_page(
            'edit.php?post_type=firma',
            'Firma Rehberi Ayarları',
            'Ayarlar',
            'manage_options',
            'firma-rehberi',
            [ $this, 'render_page' ]
        );

        // Firma CPT menüsüne "Başvurular" alt menüsü ekle
        $pending = (int) wp_count_posts( 'firma' )->pending;
        $label   = 'Başvurular';
        if ( $pending > 0 ) {
            $label .= ' <span class="awaiting-mod" style="background:#d63638;color:#fff;border-radius:10px;padding:1px 6px;font-size:11px;margin-left:4px;">'
                    . $pending . '</span>';
        }
        add_submenu_page(
            'edit.php?post_type=firma',
            'Başvurular',
            $label,
            'edit_posts',
            'edit.php?post_type=firma&post_status=pending',
            ''
        );
    }

    public function enqueue( $hook ) {
        if ( strpos( $hook, 'firma-rehberi' ) === false ) return;
        wp_enqueue_style(  'firma-admin-css', FIRMA_REHBERI_URL . 'assets/css/admin.css', [], FIRMA_REHBERI_VER );
        wp_enqueue_script( 'firma-admin-settings-js', FIRMA_REHBERI_URL . 'assets/js/admin.js', [ 'jquery' ], FIRMA_REHBERI_VER, true );
        wp_localize_script( 'firma-admin-settings-js', 'firmaAdminData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'firma_admin_nonce' ),
            'saved'   => 'Ayarlar kaydedildi.',
            'error'   => 'Bir hata oluştu.',
        ] );
    }

    public function render_page() {
        $opts = get_option( self::OPT, [] );
        $defaults = self::defaults();
        $o = array_merge( $defaults, $opts );

        // Tüm WordPress sayfaları (basvuru formu için)
        $pages = get_pages( [ 'post_status' => 'publish' ] );
        ?>
        <div class="wrap mevzu-settings-wrap">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h1 class="m-0">
                    Firma Rehberi Ayarları
                </h1>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=firma' ) ); ?>" class="button button-primary">
                        Yeni Firma Ekle
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=firma' ) ); ?>" class="button button-secondary">
                        Tüm Firmalar
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=firma&post_status=pending' ) ); ?>" class="button button-secondary">
                        Onay Bekleyenler
                        <?php
                        $pending = wp_count_posts('firma')->pending;
                        if ( $pending > 0 ) {
                            echo ' <span class="awaiting-mod count-' . $pending . '"><span class="pending-count">' . $pending . '</span></span>';
                        }
                        ?>
                    </a>
                </div>
            </div>

            <div class="mevzu-settings-container">

                <!-- Sekme Nav -->
                <div class="mevzu-settings-tabs" id="fr_tabs">
                    <div class="hg-nav-group">
                        <a href="#" class="tab-link active" data-tab="fr-genel">
                            Genel Ayarlar
                        </a>
                        <a href="#" class="tab-link" data-tab="fr-bildirimler">
                            Bildirimler
                        </a>
                        <a href="#" class="tab-link" data-tab="fr-kategoriler">
                            Kategoriler
                        </a>
                    </div>
                </div>

                <!-- İçerik -->
                <div class="mevzu-settings-content">
                    <form id="fr-settings-form">
                        <?php wp_nonce_field( 'firma_admin_nonce', 'firma_nonce' ); ?>

                        <!-- Genel Ayarlar -->
                        <div id="fr-genel" class="hg-tab-content hg-tab-active">
                            <h2 class="hg-tab-heading">Genel Ayarlar</h2>

                            <div class="bir-config-box js-config-box my-3 small">
                                <div class="bir-config-row mevzu-field mb-0">
                                    <div class="row g-3 align-items-center w-100">
                                        <div class="col-12 col-lg-auto">
                                            <div class="switch-content">
                                                <label class="switch ms-0">
                                                    <input type="checkbox" name="login_required" value="1" <?php checked( $o['login_required'] ); ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-auto">
                                            <label class="mb-0"><strong>Firma Eklemek İçin Giriş Zorunlu</strong></label>
                                        </div>
                                    </div>
                                    <p class="description m-0 mt-2">Aktifse üye olmayan ziyaretçiler başvuru formunu göremez.</p>
                                </div>
                            </div>

                            <hr>

                            <div class="bir-config-box js-config-box my-3 small">
                                <label class="fw-semibold d-block mb-2">Sayfa Başına Firma Sayısı</label>
                                <input type="number" name="per_page" value="<?php echo intval( $o['per_page'] ); ?>"
                                    min="4" max="100" class="small-text">
                                <p class="description mt-1">Listeleme sayfalarında bir sayfada kaç firma gösterileceği.</p>
                            </div>

                            <hr>

                            <div class="bir-config-box js-config-box my-3 small">
                                <label class="fw-semibold d-block mb-2">Firma Başvuru Sayfası</label>
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                    <select name="basvuru_sayfasi" id="fr-basvuru-sayfasi">
                                        <option value="">— Seçiniz —</option>
                                        <?php foreach ( $pages as $page ) : ?>
                                            <option value="<?php echo $page->ID; ?>" <?php selected( $o['basvuru_sayfasi'], $page->ID ); ?>>
                                                <?php echo esc_html( $page->post_title ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="button button-secondary" id="fr-create-page-btn">
                                        Otomatik Oluştur
                                    </button>
                                    <span id="fr-create-page-status" class="small"></span>
                                </div>
                                <p class="description mt-1">
                                    Başvuru formunu [firma_basvuru] shortcode'uyla bu sayfaya ekleyin.
                                    <br>Sayfa yoksa "Otomatik Oluştur"a tıklayın.
                                </p>
                            </div>

                            <hr>

                            <div class="bir-config-box js-config-box my-3 small">
                                <label class="fw-semibold d-block mb-2">Öne Çıkan Firma Sayısı</label>
                                <input type="number" name="featured_count" value="<?php echo intval( $o['featured_count'] ); ?>"
                                    min="1" max="50" class="small-text">
                                <p class="description mt-1">[firma_one_cikan] shortcode'unda kaç firma gösterileceği.</p>
                            </div>
                        </div>

                        <!-- Bildirimler -->
                        <div id="fr-bildirimler" class="hg-tab-content">
                            <h2 class="hg-tab-heading">Bildirim Ayarları</h2>

                            <div class="bir-config-box js-config-box my-3 small">
                                <div class="bir-config-row mevzu-field mb-0">
                                    <div class="row g-3 align-items-center w-100">
                                        <div class="col-12 col-lg-auto">
                                            <div class="switch-content">
                                                <label class="switch ms-0">
                                                    <input type="checkbox" name="notify_admin" value="1" <?php checked( $o['notify_admin'] ); ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-auto">
                                            <label class="mb-0"><strong>Yeni Başvuruda Admin'e E-posta</strong></label>
                                        </div>
                                    </div>
                                    <p class="description m-0 mt-2">Yeni firma başvurusu geldiğinde belirtilen adrese bildirim gönderilir.</p>
                                </div>
                            </div>

                            <div class="bir-config-box js-config-box my-3 small">
                                <label class="fw-semibold d-block mb-2">Admin Bildirim E-posta Adresi</label>
                                <input type="email" name="admin_email"
                                    value="<?php echo esc_attr( $o['admin_email'] ?: get_option('admin_email') ); ?>"
                                    class="regular-text" placeholder="<?php echo esc_attr( get_option('admin_email') ); ?>">
                            </div>

                            <hr>

                            <div class="bir-config-box js-config-box my-3 small">
                                <div class="bir-config-row mevzu-field mb-0">
                                    <div class="row g-3 align-items-center w-100">
                                        <div class="col-12 col-lg-auto">
                                            <div class="switch-content">
                                                <label class="switch ms-0">
                                                    <input type="checkbox" name="notify_submitter" value="1" <?php checked( $o['notify_submitter'] ); ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-auto">
                                            <label class="mb-0"><strong>Başvurusu Onaylananına E-posta</strong></label>
                                        </div>
                                    </div>
                                    <p class="description m-0 mt-2">Firma başvurusu onaylandığında başvuru sahibine bildirim gönderilir.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kategoriler -->
                        <div id="fr-kategoriler" class="hg-tab-content">
                            <h2 class="hg-tab-heading">Kategoriler</h2>
                            <p class="description">
                                Firma kategorilerini ve şehirleri doğrudan WordPress taxonomy sayfalarından yönetebilirsiniz.
                            </p>
                            <div class="d-flex gap-3 mt-3 flex-wrap">
                                <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=firma-kategori&post_type=firma' ); ?>" class="button button-secondary">Kategorileri Düzenle</a>
                                <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=firma-sehir&post_type=firma' ); ?>" class="button button-secondary">Şehirleri Düzenle</a>
                                <a href="<?php echo admin_url( 'edit.php?post_type=firma&post_status=pending' ); ?>" class="button button-secondary">Onay Bekleyenler</a>
                            </div>
                        </div>

                        <!-- Kaydet Butonu -->
                        <div class="mevzu-settings-actions mt-4">
                            <button type="submit" class="button button-primary" id="fr-save-btn">
                                Değişiklikleri Kaydet
                            </button>
                            <span id="fr-save-status" class="ms-2 small"></span>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($) {

            // Otomatik Sayfa Oluştur
            $('#fr-create-page-btn').on('click', function() {
                var $btn    = $(this);
                var $status = $('#fr-create-page-status');
                $btn.prop('disabled', true).text('Oluşturuluyor...');
                $status.text('').css('color','#888');

                $.post(ajaxurl, {
                    action: 'firma_create_page',
                    nonce:  firmaAdminData.nonce,
                }, function(res) {
                    $btn.prop('disabled', false).text('Otomatik Oluştur');
                    if (res.success) {
                        var d = res.data;
                        $status.text(d.message).css('color','#00a32a');
                        // Dropdown'a ekle ve seç
                        var $sel = $('#fr-basvuru-sayfasi');
                        if ($sel.find('option[value="' + d.page_id + '"]').length === 0) {
                            $sel.append('<option value="' + d.page_id + '">' + d.page_title + '</option>');
                        }
                        $sel.val(d.page_id);
                    } else {
                        $status.text(res.data || 'Hata.').css('color','#d63638');
                    }
                });
            });

            // Sekme navigasyonu
            $('#fr_tabs .tab-link').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                $('#fr_tabs .tab-link').removeClass('active');
                $(this).addClass('active');
                $('.hg-tab-content').removeClass('hg-tab-active');
                $('#' + tab).addClass('hg-tab-active');
            });

            // Kaydet
            $('#fr-settings-form').on('submit', function(e) {
                e.preventDefault();
                var $btn    = $('#fr-save-btn');
                var $status = $('#fr-save-status');
                var data    = {};

                $(this).serializeArray().forEach(function(f) { data[f.name] = f.value; });
                // Checkbox'lar için '0' gönder
                ['login_required','notify_admin','notify_submitter'].forEach(function(k) {
                    if (!data[k]) data[k] = '0';
                });
                data.action = 'firma_save_settings';
                data.nonce  = firmaAdminData.nonce;

                $btn.prop('disabled', true);
                $status.text('Kaydediliyor...').css('color', '#888');

                $.post(firmaAdminData.ajaxUrl, data, function(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        $status.text(firmaAdminData.saved).css('color', '#00a32a');
                    } else {
                        $status.text(firmaAdminData.error).css('color', '#d63638');
                    }
                    setTimeout(function() { $status.text(''); }, 3000);
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $status.text(firmaAdminData.error).css('color', '#d63638');
                });
            });
        });
        </script>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* AJAX Kaydet                                                           */
    /* ------------------------------------------------------------------ */

    public function ajax_save() {
        check_ajax_referer( 'firma_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Yetki hatası.' );

        $opts = [
            'login_required'   => ! empty( $_POST['login_required'] ) && $_POST['login_required'] !== '0',
            'per_page'         => absint( $_POST['per_page'] ?? 12 ),
            'basvuru_sayfasi'  => absint( $_POST['basvuru_sayfasi'] ?? 0 ),
            'featured_count'   => absint( $_POST['featured_count'] ?? 6 ),
            'notify_admin'     => ! empty( $_POST['notify_admin'] ) && $_POST['notify_admin'] !== '0',
            'admin_email'      => sanitize_email( $_POST['admin_email'] ?? get_option( 'admin_email' ) ),
            'notify_submitter' => ! empty( $_POST['notify_submitter'] ) && $_POST['notify_submitter'] !== '0',
        ];

        update_option( self::OPT, $opts );
        wp_send_json_success( 'Kayıt başarılı.' );
    }

    public function ajax_create_page() {
        check_ajax_referer( 'firma_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Yetki hatası.' );

        // [firma_basvuru] içeren sayfa zaten var mı?
        global $wpdb;
        $found = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type='page' AND post_status='publish'
             AND post_content LIKE '%[firma_basvuru]%'
             LIMIT 1"
        );

        if ( $found ) {
            $settings = get_option( self::OPT, [] );
            $settings['basvuru_sayfasi'] = (int) $found;
            update_option( self::OPT, $settings );
            wp_send_json_success( [
                'page_id'    => (int) $found,
                'page_title' => get_the_title( $found ),
                'message'    => 'Mevcut sayfa bulundu ve seçildi.',
            ] );
        }

        $page_id = wp_insert_post( [
            'post_title'   => 'Firma Başvurusu',
            'post_content' => '[firma_basvuru]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $page_id ) ) {
            wp_send_json_error( 'Sayfa oluşturulamadı: ' . $page_id->get_error_message() );
        }

        $settings = get_option( self::OPT, [] );
        $settings['basvuru_sayfasi'] = $page_id;
        update_option( self::OPT, $settings );

        wp_send_json_success( [
            'page_id'    => $page_id,
            'page_title' => get_the_title( $page_id ),
            'message'    => '"Firma Başvurusu" sayfası oluşturuldu ve seçildi.',
        ] );
    }

    /* ------------------------------------------------------------------ */
    /* AJAX: Geocoding (Nominatim — sunucu taraflı)                        */
    /* ------------------------------------------------------------------ */

    public function ajax_geocode() {
        check_ajax_referer( 'firma_admin_nonce', 'nonce' );

        $query = sanitize_text_field( $_POST['q'] ?? '' );
        if ( ! $query ) wp_send_json_error( 'Sorgu boş.' );

        $url = add_query_arg( [
            'q'      => $query,
            'format' => 'json',
            'limit'  => '5',
        ], 'https://nominatim.openstreetmap.org/search' );

        $body = $this->geocode_curl( $url );
        $data = $body ? json_decode( $body, true ) : null;

        if ( empty( $data ) ) {
            $photon_url = 'https://photon.komoot.io/api/?' . http_build_query( [ 'q' => $query, 'limit' => 1, 'lang' => 'tr' ] );
            $body2      = $this->geocode_curl( $photon_url );
            $geo        = $body2 ? json_decode( $body2, true ) : null;
            if ( ! empty( $geo['features'] ) ) {
                $coords = $geo['features'][0]['geometry']['coordinates'];
                wp_send_json_success( [
                    'lat'   => number_format( (float) $coords[1], 6, '.', '' ),
                    'lng'   => number_format( (float) $coords[0], 6, '.', '' ),
                    'label' => $geo['features'][0]['properties']['name'] ?? $query,
                ] );
            }
            wp_send_json_error( 'Sonuç bulunamadı.' );
        }

        wp_send_json_success( [
            'lat'   => number_format( (float) $data[0]['lat'], 6, '.', '' ),
            'lng'   => number_format( (float) $data[0]['lon'], 6, '.', '' ),
            'label' => $data[0]['display_name'],
        ] );
    }

    private function geocode_curl( $url ) {
        if ( ! function_exists( 'curl_init' ) ) return null;
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

    public static function defaults() {
        return [
            'login_required'   => false,
            'per_page'         => 12,
            'basvuru_sayfasi'  => 0,
            'featured_count'   => 6,
            'notify_admin'     => true,
            'admin_email'      => get_option( 'admin_email', '' ),
            'notify_submitter' => true,
        ];
    }
}
