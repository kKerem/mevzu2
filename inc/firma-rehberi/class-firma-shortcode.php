<?php
/**
 * Firma Rehberi — Shortcode'lar
 *
 * [firma_rehberi]   — Firma listesi / kart görünümü
 * [firma_basvuru]   — Başvuru formu
 * [firma_one_cikan] — Öne çıkan firmalar
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Firma_Shortcode {

    public function __construct() {
        add_shortcode( 'firma_rehberi',   [ $this, 'sc_liste'      ] );
        add_shortcode( 'firma_basvuru',   [ $this, 'sc_basvuru'    ] );
        add_shortcode( 'firma_one_cikan', [ $this, 'sc_one_cikan'  ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue'       ] );
        add_action( 'wp_ajax_firma_load_more',        [ $this, 'ajax_load_more' ] );
        add_action( 'wp_ajax_nopriv_firma_load_more', [ $this, 'ajax_load_more' ] );
    }

    /* ------------------------------------------------------------------ */
    /* Assets                                                               */
    /* ------------------------------------------------------------------ */

    public function enqueue() {
        if ( ! is_singular() && ! is_post_type_archive( 'firma' ) && ! is_tax( [ 'firma-kategori', 'firma-sehir' ] ) ) {
            global $post;
            if ( ! $post || ( ! has_shortcode( $post->post_content, 'firma_rehberi' )
                && ! has_shortcode( $post->post_content, 'firma_basvuru' )
                && ! has_shortcode( $post->post_content, 'firma_one_cikan' ) ) ) {
                if ( ! is_singular( 'firma' ) && ! is_post_type_archive( 'firma' )
                    && ! is_tax( 'firma-kategori' ) && ! is_tax( 'firma-sehir' ) ) {
                    return;
                }
            }
        }

        wp_enqueue_style(  'firma-frontend-css', FIRMA_REHBERI_URL . 'assets/css/frontend.css', [], FIRMA_REHBERI_VER );
        wp_enqueue_script( 'firma-frontend-js',  FIRMA_REHBERI_URL . 'assets/js/frontend.js',
            [ 'jquery' ], _S_VERSION, true );

        wp_localize_script( 'firma-frontend-js', 'firmaData', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'firma_submit_nonce' ),
            'submitNonce'=> wp_create_nonce( 'firma_submit_nonce' ),
            'loginUrl'   => wp_login_url( get_permalink() ),
            'isLoggedIn' => is_user_logged_in() ? 1 : 0,
            'strings'    => [
                'submitting' => 'Gönderiliyor...',
                'success'    => 'Başvurunuz alındı! İncelendikten sonra yayınlanacak.',
                'error'      => 'Bir hata oluştu. Lütfen tekrar deneyin.',
                'file_size'  => 'Görsel 2MB\'ı aşamaz.',
                'file_type'  => 'Sadece JPG, PNG, WebP veya GIF yükleyebilirsiniz.',
            ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /* [firma_rehberi]                                                      */
    /* ------------------------------------------------------------------ */

    public function sc_liste( $atts ) {
        $atts = shortcode_atts( [
            'kategori'     => '',
            'sehir'        => '',
            'limit'        => Firma_Admin::get( 'per_page', 12 ),
            'featured_only'=> 0,
        ], $atts );

        // URL param'larını shortcode atts yoksa kullan
        if ( ! $atts['kategori'] ) $atts['kategori'] = sanitize_text_field( $_GET['firma_kat']   ?? '' );
        if ( ! $atts['sehir'] )    $atts['sehir']    = sanitize_text_field( $_GET['firma_sehir'] ?? '' );

        $args = [
            'post_type'      => 'firma',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['limit'] ),
            'orderby'        => [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ],
            'meta_key'       => '_firma_featured',
            'tax_query'      => [],
        ];

        if ( $atts['kategori'] ) {
            $args['tax_query'][] = [ 'taxonomy' => 'firma-kategori', 'field' => 'slug', 'terms' => $atts['kategori'], 'include_children' => true ];
        }
        if ( $atts['sehir'] ) {
            $args['tax_query'][] = [ 'taxonomy' => 'firma-sehir', 'field' => 'slug', 'terms' => $atts['sehir'] ];
        }
        if ( $atts['featured_only'] ) {
            $today = date('Y-m-d');
            $args['meta_query'] = [
                'relation' => 'AND',
                [ 'key' => '_firma_featured', 'value' => '1' ],
                [
                    'relation' => 'OR',
                    [ 'key' => '_firma_featured_start', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_firma_featured_start', 'value' => '', 'compare' => '=' ],
                    [ 'key' => '_firma_featured_start', 'value' => $today, 'compare' => '<=' ],
                ],
                [
                    'relation' => 'OR',
                    [ 'key' => '_firma_featured_end', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_firma_featured_end', 'value' => '', 'compare' => '=' ],
                    [ 'key' => '_firma_featured_end', 'value' => $today, 'compare' => '>=' ],
                ],
            ];
        }

        if ( count( $args['tax_query'] ) > 1 ) $args['tax_query']['relation'] = 'AND';

        $query = new WP_Query( $args );

        ob_start();
        include FIRMA_REHBERI_PATH . 'templates/parts/firma-filter.php';
        if ( $query->have_posts() ) {
            echo '<div class="firma-listesi firma-gorunum-kart">';
            while ( $query->have_posts() ) {
                $query->the_post();
                include FIRMA_REHBERI_PATH . 'templates/parts/firma-card.php';
            }
            wp_reset_postdata();
            echo '</div>';
        } else {
            echo '<p class="firma-yok">Bu kriterlere uygun firma bulunamadı.</p>';
        }
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ */
    /* [firma_basvuru]                                                      */
    /* ------------------------------------------------------------------ */

    public function sc_basvuru( $atts ) {
        $login_required = Firma_Admin::get( 'login_required', false );
        if ( $login_required && ! is_user_logged_in() ) {
            return sprintf(
                '<div class="firma-basvuru-login-uyari"><p>Firma eklemek için <a href="%s">giriş yapmanız</a> gerekiyor.</p></div>',
                wp_login_url( get_permalink() )
            );
        }
        ob_start();
        include FIRMA_REHBERI_PATH . 'templates/form-basvuru.php';
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ */
    /* [firma_one_cikan]                                                    */
    /* ------------------------------------------------------------------ */

    public function sc_one_cikan( $atts ) {
        $atts  = shortcode_atts( [ 'limit' => Firma_Admin::get( 'featured_count', 6 ) ], $atts );
        $today = date('Y-m-d');
        $query = new WP_Query( [
            'post_type'      => 'firma',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['limit'] ),
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_firma_featured', 'value' => '1' ],
                [
                    'relation' => 'OR',
                    [ 'key' => '_firma_featured_start', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_firma_featured_start', 'value' => '', 'compare' => '=' ],
                    [ 'key' => '_firma_featured_start', 'value' => $today, 'compare' => '<=' ],
                ],
                [
                    'relation' => 'OR',
                    [ 'key' => '_firma_featured_end', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_firma_featured_end', 'value' => '', 'compare' => '=' ],
                    [ 'key' => '_firma_featured_end', 'value' => $today, 'compare' => '>=' ],
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        if ( ! $query->have_posts() ) return '';

        ob_start();
        echo '<div class="firma-one-cikan-widget">';
        echo '<h3 class="firma-one-cikan-baslik">Öne Çıkan Firmalar</h3>';
        echo '<div class="firma-listesi firma-gorunum-kart">';
        while ( $query->have_posts() ) {
            $query->the_post();
            include FIRMA_REHBERI_PATH . 'templates/parts/firma-card.php';
        }
        wp_reset_postdata();
        echo '</div></div>';
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ */
    /* AJAX: Daha Fazla Yükle                                               */
    /* ------------------------------------------------------------------ */

    public function ajax_load_more() {
        $page     = absint( $_POST['page']     ?? 2 );
        $kategori = sanitize_text_field( $_POST['kategori'] ?? '' );
        $sehir    = sanitize_text_field( $_POST['sehir']    ?? '' );
        $per_page = Firma_Admin::get( 'per_page', 12 );

        $args = [
            'post_type'      => 'firma',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'tax_query'      => [],
        ];
        if ( $kategori ) $args['tax_query'][] = [ 'taxonomy' => 'firma-kategori', 'field' => 'slug', 'terms' => $kategori ];
        if ( $sehir )    $args['tax_query'][] = [ 'taxonomy' => 'firma-sehir',    'field' => 'slug', 'terms' => $sehir    ];

        $query = new WP_Query( $args );
        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include FIRMA_REHBERI_PATH . 'templates/parts/firma-card.php';
            }
            wp_reset_postdata();
        }
        $html = ob_get_clean();
        wp_send_json_success( [ 'html' => $html, 'has_more' => $page < $query->max_num_pages ] );
    }
}
