<?php
namespace bulk_image_resizer;

/**
 * Hız & Güvenlik — Admin Sınıfı
 *
 * Görsel Optimizasyonu + XML-RPC Güvenliği + Admin URL Gizleme
 * özelliklerini tek bir admin sayfasında yönetir.
 */
class Bulk_image_resizer_admin {

    public function __construct() {
        add_action( 'admin_menu',              [$this, 'add_admin_menu'], 20 );
        add_action( 'admin_head',              [$this, 'hide_external_plugin_menus'], 99 );
        add_action( 'admin_enqueue_scripts',   [$this, 'enqueue_scripts'] );

        // AJAX: Güvenlik Önlemleri kaydetme
        add_action( 'wp_ajax_hiz_guvenlik_save_security',    [$this, 'ajax_save_security'] );
        // AJAX: Admin URL Gizleme kaydetme
        add_action( 'wp_ajax_hiz_guvenlik_save_hide_login',  [$this, 'ajax_save_hide_login'] );
        // AJAX: Kaynak yükleme tercihlerini kaydetme
        add_action( 'wp_ajax_hiz_guvenlik_save_kaynak',      [$this, 'ajax_save_kaynak'] );
    }

    /* ------------------------------------------------------------------ */
    /*  Admin Menü                                                          */
    /* ------------------------------------------------------------------ */

    public function add_admin_menu() {
        add_submenu_page(
            'mevzu-ayarlar',
            __( 'Hız & Güvenlik', 'bulk-image-resizer' ),
            'Hız & Güvenlik',
            'manage_options',
            'hiz-guvenlik',
            [$this, 'get_template']
        );
    }

    /**
     * Bağımsız eklentilerin oluşturduğu üst-menü girişlerini gizle.
     * (Skelet Framework'ün 'Security Settings' menüsü)
     */
    public function hide_external_plugin_menus() {
        remove_menu_page( 'Security Settings' );
    }

    /* ------------------------------------------------------------------ */
    /*  Script / Style Yükleme                                             */
    /* ------------------------------------------------------------------ */

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'hiz-guvenlik' ) === false ) {
            return;
        }
        wp_enqueue_style( 'bir-css', BULK_IMAGE_RESIZER_URL . 'admin/css/bir.css', [], filemtime( BULK_IMAGE_RESIZER_DIR . 'admin/css/bir.css' ) );
        wp_enqueue_script( 'bulk-image-resizer-chart', BULK_IMAGE_RESIZER_URL . 'admin/js/chart.js', [], null, true );
        wp_enqueue_script( 'bir-js', BULK_IMAGE_RESIZER_URL . 'admin/js/bir.js', ['jquery'], filemtime( BULK_IMAGE_RESIZER_DIR . 'admin/js/bir.js' ), true );

        wp_localize_script( 'bir-js', 'hizGuvenlikData', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'hiz_guvenlik_nonce' ),
            'siteUrl'  => trailingslashit( home_url() ),
            'strings'  => [
                'saved'    => 'Ayarlar kaydedildi.',
                'error'    => 'Bir hata oluştu, lütfen tekrar deneyin.',
                'confirm_disable_hide' => 'Admin URL gizlemeyi devre dışı bırakmak wp-login.php\'ye erişimi geri açar. Devam etmek istiyor musunuz?',
            ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Sayfa Template'i                                                    */
    /* ------------------------------------------------------------------ */

    public function get_template() {
        global $bir_options;
        $options        = $bir_options;
        $check_fn_editor = Bir_functions::check_image_editor();
        require_once __DIR__ . '/template/class-config-functions.php';
        require       __DIR__ . '/template/bir-container.php';
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: XML-RPC + Extra Güvenlik + WP Hızlandırma Kaydetme          */
    /* ------------------------------------------------------------------ */

    public function ajax_save_security() {
        check_ajax_referer( 'hiz_guvenlik_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki hatası.' );
        }

        $old     = get_option( 'dsxmlrpc-settings', [] );
        $options = is_array( $old ) ? $old : [];

        /* ---- XML-RPC Ayarları ---- */
        $options['dsxmlrpc-switcher']  = ! empty( $_POST['dsxmlrpc-switcher'] );
        $options['xmlrpc-slug']        = sanitize_text_field( $_POST['xmlrpc-slug'] ?? '' );
        $options['jetpack-switcher']   = ! empty( $_POST['jetpack-switcher'] );
        $options['White-list-IPs']     = sanitize_textarea_field( $_POST['White-list-IPs'] ?? '' );
        $options['Black-list-IPs']     = sanitize_textarea_field( $_POST['Black-list-IPs'] ?? '' );

        $methods_raw                   = isset( $_POST['disabled-methods'] ) ? (array) $_POST['disabled-methods'] : [];
        $options['disabled-methods']   = array_map( 'sanitize_text_field', $methods_raw );

        /* ---- Extra Güvenlik ---- */
        $options['security-headers']   = ! empty( $_POST['security-headers'] );
        $options['login-rate-limit']   = ! empty( $_POST['login-rate-limit'] );
        $options['json-rest-api']      = ! empty( $_POST['json-rest-api'] );
        $options['htaccess protection']= ! empty( $_POST['htaccess-protection'] );
        $options['remove-wp-ver']      = ! empty( $_POST['remove-wp-ver'] );
        $options['disable-code-editor']= ! empty( $_POST['disable-code-editor'] );
        $options['disable-wlw']        = ! empty( $_POST['disable-wlw'] );

        /* ---- WordPress Hızlandırma ---- */
        $options['slow-heartbeat']     = ! empty( $_POST['slow-heartbeat'] );
        $options['hotlink-fix']        = ! empty( $_POST['hotlink-fix'] );
        $options['remove-emojis']      = ! empty( $_POST['remove-emojis'] );
        $options['remove-rss']         = ! empty( $_POST['remove-rss'] );
        $options['disable-oembed']     = ! empty( $_POST['disable-oembed'] );

        update_option( 'dsxmlrpc-settings', $options );

        // .htaccess güncelle
        $this->update_htaccess( $options );

        wp_send_json_success( 'Güvenlik ayarları kaydedildi.' );
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: Admin URL Gizleme Kaydetme                                   */
    /* ------------------------------------------------------------------ */

    public function ajax_save_hide_login() {
        check_ajax_referer( 'hiz_guvenlik_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki hatası.' );
        }

        $enabled = ! empty( $_POST['mevzu_whl_enabled'] );
        update_option( 'mevzu_whl_enabled', $enabled ? 1 : 0 );

        if ( $enabled ) {
            $whl_page     = sanitize_title_with_dashes( $_POST['whl_page']           ?? 'giris' );
            $whl_redirect = sanitize_title_with_dashes( $_POST['whl_redirect_admin'] ?? '404'   );

            if ( $whl_page !== 'wp-login' && strpos( $whl_page, 'wp-login' ) === false ) {
                update_option( 'whl_page', $whl_page );
            }
            update_option( 'whl_redirect_admin', $whl_redirect );

            // wps-hide-login aktif değilse etkinleştir
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if ( ! is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
                activate_plugin( 'wps-hide-login/wps-hide-login.php' );
            }
        } else {
            // wps-hide-login'i devre dışı bırak
            if ( ! function_exists( 'deactivate_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
                deactivate_plugins( 'wps-hide-login/wps-hide-login.php' );
            }
        }

        flush_rewrite_rules();
        wp_send_json_success( 'Admin URL gizleme ayarları kaydedildi.' );
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: Kaynak Yükleme Tercihlerini Kaydetme                         */
    /* ------------------------------------------------------------------ */

    public function ajax_save_kaynak() {
        check_ajax_referer( 'hiz_guvenlik_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Yetki hatası.' );
        }

        $options = get_option( 'dsxmlrpc-settings', array() );
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $options['swiper_source']  = isset( $_POST['swiper_source'] ) && in_array( $_POST['swiper_source'], array( 'local', 'cdn' ), true ) ? sanitize_key( $_POST['swiper_source'] ) : 'local';
        $options['select2_source'] = isset( $_POST['select2_source'] ) && in_array( $_POST['select2_source'], array( 'local', 'cdn' ), true ) ? sanitize_key( $_POST['select2_source'] ) : 'local';
        $options['jquery_source']  = isset( $_POST['jquery_source'] ) && in_array( $_POST['jquery_source'], array( 'wordpress', 'theme' ), true ) ? sanitize_key( $_POST['jquery_source'] ) : 'wordpress';

        update_option( 'dsxmlrpc-settings', $options );
        wp_send_json_success( 'Kaynak yükleme ayarları kaydedildi.' );
    }

    /* ------------------------------------------------------------------ */
    /*  .htaccess Güncelleme                                               */
    /* ------------------------------------------------------------------ */

    public static function apply_security_options( $options ) {
        $instance = new self();
        $instance->update_htaccess( $options );

        // wps-hide-login durumunu güncelle
        $enabled = ! empty( get_option( 'mevzu_whl_enabled', 0 ) );
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( $enabled ) {
            if ( ! is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
                activate_plugin( 'wps-hide-login/wps-hide-login.php' );
            }
        } else {
            if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
                deactivate_plugins( 'wps-hide-login/wps-hide-login.php' );
            }
        }
        flush_rewrite_rules();
    }

    private function update_htaccess( $options ) {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $home_path    = function_exists( 'get_home_path' ) ? get_home_path() : ABSPATH;
        $htaccess     = $home_path . '.htaccess';
        $htaccess_code = '';

        // Hotlink koruması
        if ( ! empty( $options['hotlink-fix'] ) ) {
            $home_url = get_home_url();
            $htaccess_code .= "RewriteEngine on\n";
            $htaccess_code .= "RewriteCond %{HTTP_REFERER} !^$\n";
            $htaccess_code .= "RewriteCond %{HTTP_REFERER} !^{$home_url} [NC]\n";
            $htaccess_code .= "RewriteCond %{HTTP_REFERER} !^http(s)?://(www\\.)?google.com [NC]\n";
            $htaccess_code .= "RewriteRule \\.(jpg|jpeg|png|gif)$ - [NC,F,L]\n\n";
        }

        // Jetpack IP listesi
        $jp_allowed_ips = '';
        if ( ! empty( $options['jetpack-switcher'] ) ) {
            $jp_allowed_ips  = "Allow from 122.248.245.244/32\n";
            $jp_allowed_ips .= "Allow from 54.217.201.243/32\n";
            $jp_allowed_ips .= "Allow from 54.232.116.4/32\n";
            $jp_allowed_ips .= "Allow from 192.0.80.0/20\n";
            $jp_allowed_ips .= "Allow from 192.0.96.0/20\n";
            $jp_allowed_ips .= "Allow from 192.0.112.0/20\n";
            $jp_allowed_ips .= "Allow from 195.234.108.0/22\n";
            $jp_allowed_ips .= "Allow from 192.0.64.0/18\n";
        }

        if ( empty( $options['dsxmlrpc-switcher'] ) ) {
            // XML-RPC'yi kapat (whitelist varsa izin ver)
            $allowed_ips = '';
            if ( ! empty( $options['White-list-IPs'] ) ) {
                foreach ( explode( ',', $options['White-list-IPs'] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
                        $allowed_ips .= "Allow from {$ip}\n";
                    }
                }
            }
            $allowed_ips .= $jp_allowed_ips;
            $htaccess_code .= "<Files xmlrpc.php>\norder deny,allow\ndeny from all\n{$allowed_ips}</Files>\n";
        } else {
            // XML-RPC açık (blacklist varsa engelle)
            $denied_ips = '';
            if ( ! empty( $options['Black-list-IPs'] ) ) {
                foreach ( explode( ',', $options['Black-list-IPs'] ) as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
                        $denied_ips .= "Deny from {$ip}\n";
                    }
                }
            }
            $htaccess_code .= "<Files xmlrpc.php>\norder allow,deny\nallow from all\n{$denied_ips}</Files>\n";
        }

        // htaccess yazılabilirsa güncelle
        if ( file_exists( $htaccess ) && ! is_writable( $htaccess ) ) {
            @chmod( $htaccess, 0644 );
        }
        if ( is_writable( $htaccess ) ) {
            insert_with_markers( $htaccess, 'DS-XML-RPC-API', $htaccess_code );
            // htaccess koruma aktifse salt-okunur yap
            if ( ! empty( $options['htaccess protection'] ) ) {
                @chmod( $htaccess, 0444 );
            }
        }
    }
}
