<?php
/**
 * Hız & Güvenlik — WordPress Hook Uygulayıcı
 *
 * dsxmlrpc-settings option'ından okunan değerleri WordPress hook'larına bağlar.
 * Admin sayfası ayarları değiştirdiğinde bu sınıf sitenin gerçek davranışını günceller.
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

class Hiz_Guvenlik_Init {

    private $opts;

    public function __construct() {
        $raw = get_option( 'dsxmlrpc-settings', [] );
        $defaults = [
            'dsxmlrpc-switcher'   => false,
            'xmlrpc-slug'         => '',
            'jetpack-switcher'    => false,
            'disabled-methods'    => [ 'pingback.ping', 'x-pingback', 'mt.getTrackbackPings', 'pingback.extensions.getPingbacks' ],
            'White-list-IPs'      => '',
            'Black-list-IPs'      => '',
            'json-rest-api'       => false,
            'htaccess protection' => false,
            'remove-wp-ver'       => true,
            'disable-code-editor' => false,
            'disable-wlw'         => true,
            'slow-heartbeat'      => true,
            'hotlink-fix'         => false,
            'remove-emojis'       => true,
            'remove-rss'          => false,
            'disable-oembed'      => false,
            'security-headers'    => true,
            'login-rate-limit'    => true,
        ];
        $this->opts = is_array( $raw ) ? array_merge( $defaults, $raw ) : $defaults;
        $this->apply();
    }

    private function get( $key ) {
        return ! empty( $this->opts[ $key ] );
    }

    private function apply() {

        /* ----------------------------------------------------------
         *  XML-RPC
         * ---------------------------------------------------------- */
        if ( ! $this->get( 'dsxmlrpc-switcher' ) ) {
            // Tamamen kapat
            add_filter( 'xmlrpc_enabled', '__return_false' );
        } else {
            // Belirli methodları devre dışı bırak
            $disabled_methods = is_array( $this->opts['disabled-methods'] ) ? $this->opts['disabled-methods'] : [];
            if ( ! empty( $disabled_methods ) ) {
                add_filter( 'xmlrpc_methods', function( $methods ) use ( $disabled_methods ) {
                    foreach ( $disabled_methods as $m ) {
                        unset( $methods[ $m ] );
                    }
                    return $methods;
                } );
            }
        }

        /* ----------------------------------------------------------
         *  JSON REST API — giriş yapmamış kullanıcılar için kapat
         * ---------------------------------------------------------- */
        if ( $this->get( 'json-rest-api' ) ) {
            add_filter( 'rest_authentication_errors', function( $result ) {
                if ( ! empty( $result ) ) return $result;
                if ( ! is_user_logged_in() ) {
                    return new \WP_Error(
                        'rest_not_logged_in',
                        'REST API yalnızca oturum açmış kullanıcılar için kullanılabilir.',
                        [ 'status' => 401 ]
                    );
                }
                return $result;
            } );
        }

        /* ----------------------------------------------------------
         *  WordPress sürümünü gizle
         * ---------------------------------------------------------- */
        if ( $this->get( 'remove-wp-ver' ) ) {
            remove_action( 'wp_head', 'wp_generator' );
            add_filter( 'the_generator', '__return_empty_string' );
            add_filter( 'style_loader_src',  [ $this, 'strip_ver_query' ], 9999 );
            add_filter( 'script_loader_src', [ $this, 'strip_ver_query' ], 9999 );
        }

        /* ----------------------------------------------------------
         *  Dahili Kod Editörü — tema/eklenti editörünü kapat
         * ---------------------------------------------------------- */
        if ( $this->get( 'disable-code-editor' ) ) {
            add_action( 'admin_init', function() {
                if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) {
                    remove_submenu_page( 'themes.php',  'theme-editor.php' );
                    remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
                }
            } );
        }

        /* ----------------------------------------------------------
         *  WLW Manifest kaldır
         * ---------------------------------------------------------- */
        if ( $this->get( 'disable-wlw' ) ) {
            remove_action( 'wp_head', 'wlwmanifest_link' );
        }

        /* ----------------------------------------------------------
         *  Heartbeat yavaşlat (admin'de 60s, ön yüz'de devre dışı)
         * ---------------------------------------------------------- */
        if ( $this->get( 'slow-heartbeat' ) ) {
            add_filter( 'heartbeat_settings', function( $settings ) {
                $settings['interval'] = 60;
                return $settings;
            } );
            // Ön yüzde heartbeat'i tamamen kaldır
            add_action( 'init', function() {
                if ( ! is_admin() ) {
                    wp_deregister_script( 'heartbeat' );
                }
            }, 1 );
        }

        /* ----------------------------------------------------------
         *  Emoji kaldır
         * ---------------------------------------------------------- */
        if ( $this->get( 'remove-emojis' ) ) {
            remove_action( 'wp_head',              'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles',      'print_emoji_styles' );
            remove_action( 'admin_print_scripts',  'print_emoji_detection_script' );
            remove_action( 'admin_print_styles',   'print_emoji_styles' );
            remove_filter( 'the_content',          'convert_smilies', 20 );
            remove_filter( 'the_excerpt',          'convert_smilies' );
            add_filter( 'tiny_mce_plugins', function( $plugins ) {
                return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
            } );
            add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
                if ( 'dns-prefetch' === $relation_type ) {
                    $urls = array_filter( $urls, function( $url ) {
                        return strpos( $url, 'cdn.jsdelivr.net' ) === false
                            && strpos( $url, 'twemoji' ) === false
                            && strpos( $url, 's.w.org/images/core/emoji' ) === false;
                    } );
                }
                return $urls;
            }, 10, 2 );
        }

        /* ----------------------------------------------------------
         *  RSS Feed devre dışı bırak
         * ---------------------------------------------------------- */
        if ( $this->get( 'remove-rss' ) ) {
            remove_action( 'wp_head', 'feed_links_extra', 3 );
            remove_action( 'wp_head', 'feed_links', 2 );
            add_action( 'wp', function() {
                if ( is_feed() ) {
                    wp_redirect( home_url() );
                    exit;
                }
            } );
        }

        /* ----------------------------------------------------------
         *  oEmbed devre dışı bırak
         * ---------------------------------------------------------- */
        if ( $this->get( 'disable-oembed' ) ) {
            remove_action( 'wp_head',          'wp_oembed_add_discovery_links' );
            remove_action( 'wp_head',          'rest_output_link_wp_head' );
            remove_action( 'template_redirect','rest_output_link_header', 11 );
            add_filter( 'embed_oembed_discover',  '__return_false' );
            add_filter( 'rewrite_rules_array', function( $rules ) {
                foreach ( $rules as $key => $val ) {
                    if ( false !== strpos( $key, 'embed' ) ) {
                        unset( $rules[ $key ] );
                    }
                }
                return $rules;
            } );
            add_action( 'wp_enqueue_scripts', function() {
                wp_deregister_script( 'wp-embed' );
            } );
        }

        /* ----------------------------------------------------------
         *  Güvenlik HTTP Başlıkları (yeni özellik)
         * ---------------------------------------------------------- */
        if ( $this->get( 'security-headers' ) ) {
            add_action( 'send_headers', [ $this, 'send_security_headers' ] );
        }

        /* ----------------------------------------------------------
         *  Giriş Denemesi Sınırlama (yeni özellik)
         * ---------------------------------------------------------- */
        if ( $this->get( 'login-rate-limit' ) ) {
            add_action( 'wp_login_failed',        [ $this, 'on_login_failed' ] );
            add_filter( 'authenticate',           [ $this, 'check_login_block' ], 30, 3 );
        }
    }

    /* ----------------------------------------------------------
     *  Yardımcı metodlar
     * ---------------------------------------------------------- */

    /** Style/script URL'lerinden ?ver=... parametresini kaldırır */
    public function strip_ver_query( $src ) {
        return $src && strpos( $src, 'ver=' ) !== false
            ? remove_query_arg( 'ver', $src )
            : $src;
    }

    /** Tarayıcı güvenlik başlıkları gönderir */
    public function send_security_headers() {
        if ( headers_sent() ) return;
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
    }

    /** Başarısız giriş denemesini logla */
    public function on_login_failed( $username ) {
        $ip      = $this->get_client_ip();
        $key     = 'hg_login_fails_' . md5( $ip );
        $count   = (int) get_transient( $key );
        set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
    }

    /** Çok fazla başarısız deneme varsa girişi engelle */
    public function check_login_block( $user, $username, $password ) {
        if ( empty( $username ) ) return $user;
        $ip    = $this->get_client_ip();
        $key   = 'hg_login_fails_' . md5( $ip );
        $count = (int) get_transient( $key );
        if ( $count >= 5 ) {
            return new \WP_Error(
                'too_many_retries',
                sprintf(
                    '<strong>Çok fazla başarısız giriş denemesi.</strong> Lütfen %d dakika bekleyin.',
                    15
                )
            );
        }
        return $user;
    }

    /** Gerçek istemci IP adresini döndürür */
    private function get_client_ip() {
        $headers = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
        foreach ( $headers as $h ) {
            if ( ! empty( $_SERVER[ $h ] ) ) {
                $ip = trim( explode( ',', $_SERVER[ $h ] )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
