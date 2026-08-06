<?php
// Minumum PHP Requirements: PHP 8.2
if (!defined('_S_VERSION')) {
    define('_S_VERSION', '1.3.7');
}


include('inc/keys.php');
include('inc/ajax.php');
include('inc/wp-bootstrap5.0-pagination.php');
include('inc/bootstrap-5-wordpress-navbar-walker.php');

/* Mevzu 2 */
// add_image_size('gorsel-305-171', 305, 171, true); // RESMI ILANLAR AYARLANMADI

// add_image_size('gorsel-thumbnail', 298, 167, true); //thumbnail
// add_image_size('gorsel-medium', 399, 224, true); //thumbnail
// add_image_size('gorsel-large', 776, 436, true); //thumbnail
// add_image_size('gorsel-thumbnail-yazarkosesi', 371, 224, true); // yazar kosesi thumbnail
// add_image_size('gorsel-388-218', 388, 218, true); // sag index bas
// add_image_size('gorsel-107-60', 107, 60, true); // sag index bas alt
// add_image_size('gorsel-120-67', 120, 67, true); // Videolar sag kucuk, manset-sablon
// add_image_size('ust-manset-gorsel', 1176, 330, true); // Üst Manşet



add_image_size('gorsel-thumbnail-col-3', 276, 155, true); //thumbnail
add_image_size('gorsel-thumbnail-col-4', 376, 212, true); //thumbnail
add_image_size('gorsel-thumbnail-col-8', 800, 450, true); //thumbnail
add_image_size('gorsel-thumbnail-widget', 133, 75, true); //widget
// add_image_size('gorsel-139-78', 139, 78, true); // Videolar sag kucuk, manset-sablon
/* Mevzu 2 */

// Eski bilesenler ve yazi editoru
add_filter('gutenberg_use_widgets_block_editor', '__return_false');
add_filter('use_widgets_block_editor', '__return_false');
add_filter('use_block_editor_for_post', '__return_false');
// eski bilesenler ve yazi editoru

/* if is single page */
/**
 * Kayıtlı haber şablonu değerini normalize eder (3 → sade).
 */
function mevzu_normalize_haber_sablon_option( $sablon ) {
    $sablon = (string) $sablon;
    return $sablon === '3' ? 'sade' : $sablon;
}

/**
 * Tekil haber/köşe yazısı için aktif detay şablonu: 1, 2, sade veya koseyazisi.
 *
 * @param int|null $post_id
 * @return string
 */
function mevzu_get_single_haber_sablon( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
    if ( $post_id ) {
        $kose_cat = (int) get_option( 'options_kose_yazilari_kategorisi', 0 );
        if ( ( $kose_cat && has_category( $kose_cat, $post_id ) ) || has_category( 'kose-yazilari', $post_id ) ) {
            return 'koseyazisi';
        }
    }
    $sablon = mevzu_normalize_haber_sablon_option( (string) get_option( 'options_sablon', '2' ) );
    return in_array( $sablon, array( '1', '2', 'sade' ), true ) ? $sablon : '2';
}

/**
 * Eski kayıtlı şablon değerini (3) sade olarak günceller.
 */
function mevzu_migrate_single_sade_sablon_option() {
    if ( get_option( 'options_sablon' ) === '3' ) {
        update_option( 'options_sablon', 'sade' );
    }
}
add_action( 'init', 'mevzu_migrate_single_sade_sablon_option' );

/**
 * Şablon 1 ve 2 sidebar kullanır.
 */
function mevzu_single_has_sidebar( $sablon = null ) {
    if ( null === $sablon ) {
        $sablon = mevzu_get_single_haber_sablon();
    }
    return in_array( $sablon, array( '1', '2' ), true );
}

/**
 * Aktif haber detay şablonunu yükler.
 */
function mevzu_load_single_haber_template( $sablon = null ) {
    $sablon = null !== $sablon ? $sablon : mevzu_get_single_haber_sablon( get_the_ID() );
    switch ( $sablon ) {
        case 'koseyazisi':
            get_template_part( 'sablon/sablon-koseyazisi' );
            break;
        case '1':
            get_template_part( 'sablon/sablon-single-1' );
            break;
        case 'sade':
            get_template_part( 'sablon/sablon-single-sade' );
            break;
        default:
            get_template_part( 'sablon/sablon-single-2' );
    }
}

/**
 * Son dakika şeridinin gösterilip gösterilmeyeceği (Sade haber detayında kapalı).
 */
function mevzu_should_show_son_dakika() {
    if ( is_singular( 'post' ) && mevzu_get_single_haber_sablon() === 'sade' ) {
        return false;
    }
    return get_option( 'options_son_dakika_goster', '1' ) === '1';
}

function add_bg_white_class_to_body( $classes ) {
    if ( is_singular( 'post' ) ) {
        $classes[] = 'bg-white';
        $sablon    = mevzu_get_single_haber_sablon();
        $classes[] = 'haber-sablon-' . $sablon;
        $classes[] = mevzu_single_has_sidebar( $sablon ) ? 'single-sidebar' : 'single-no-sidebar';
    }
    return $classes;
}
add_filter( 'body_class', 'add_bg_white_class_to_body' );
/* if is single page */

/* Archive baslik listeleme */
function my_theme_archive_title($title)
{
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    } elseif (is_tax()) {
        $title = single_term_title('', false);
    } elseif (is_home()) {
        $title = 'Blog'; // Örnek olarak "Blog" yazabilirsiniz veya istediğiniz başka bir metin ekleyebilirsiniz
    }

    return $title;
}

add_filter('get_the_archive_title', 'my_theme_archive_title');
/* Archive baslik listeleme */

/* post views */
function get_post_view()
{
    $count = get_post_meta(get_the_ID(), 'views_count', true);
    if (empty($count))
        $count = 0;
    return $count;
}
function set_post_view()
{
    // Bot ve crawler kontrolü - gereksiz view artışını önle
    if (is_user_logged_in() && current_user_can('edit_posts')) {
        return; // Admin kullanıcıları için view artırma
    }

    // User-Agent kontrolü (basit bot kontrolü)
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $bots = ['bot', 'crawler', 'spider', 'scraper'];
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return; // Bot ise view artırma
        }
    }

    $key = 'views_count';
    $post_id = get_the_ID();
    $count = (int) get_post_meta($post_id, $key, true);
    $count++;
    update_post_meta($post_id, $key, $count);
}
function posts_column_views($columns)
{
    $columns['post_views'] = '<span class="dashicons dashicons-welcome-view-site"></span>';
    return $columns;
}
function posts_custom_column_views($column)
{
    if ($column === 'post_views') {
        echo get_post_view();
    }
}
add_filter('manage_posts_columns', 'posts_column_views');
add_action('manage_posts_custom_column', 'posts_custom_column_views');
/* post views */

/* Breadcrumbs */
function custom_breadcrumbs()
{

    // Settings
    $separator = '';
    $breadcrums_id = 'breadcrumbs';
    $breadcrums_class = 'breadcrumb m-0 d-flex align-items-center';
    $home_title = 'Haberler';

    // If you have any custom post types with custom taxonomies, put the taxonomy name below (e.g. product_cat)
    $custom_taxonomy = 'reklam';

    // Get the query & post information
    global $post, $wp_query;

    // Do not display on the homepage
    if (!is_front_page()) {

        // Build the breadcrums
        echo '<nav class="breadcrumb m-0 d-flex align-items-center" aria-label="breadcrumb"><ol id="' . $breadcrums_id . '" class="' . $breadcrums_class . '">';

        // Home page
        echo '<li class="breadcrumb-item"><a href="' . get_home_url() . '" title="' . $home_title . '">' . $home_title . '</a>' . $separator . '</li>';

        if (is_archive() && !is_tax() && !is_category() && !is_tag()) {

            echo '<li class="breadcrumb-item active">' . post_type_archive_title('', false) . '</li>';

        } else if (is_archive() && is_tax() && !is_category() && !is_tag()) {

            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if ($post_type != 'post') {

                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);

                echo '<li class="breadcrumb-item item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';

            }

            $custom_tax_name = get_queried_object()->name;
            echo '<li class="item-current item-archive active">' . $custom_tax_name . '</li>';

        } else if (is_single()) {

            // If post is a custom post type
            $post_type = get_post_type();

            // If it is a custom post type display name and link
            if ($post_type != 'post') {

                $post_type_object = get_post_type_object($post_type);
                $post_type_archive = get_post_type_archive_link($post_type);

                if ($post_type == 'reklam') {
                    echo '<li class="breadcrumb-item item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . ' px-3 py-2 text-white bg-success rounded-3 fw-semibold" href="' . $post_type_archive . '">' . $post_type_object->labels->name . '</a></li>';
                } else {
                    echo '<li class="breadcrumb-item item-custom-post-type-' . $post_type . '"><a class="bread-cat bread-custom-post-type-' . $post_type . '" href="' . $post_type_archive . '" title="' . $post_type_object->labels->name . '">' . $post_type_object->labels->name . '</a></li>';
                }

            }

            // Get post category info
            $category = get_the_category();

            if (!empty($category)) {

                // Get last category post is in
                $category_values = array_values($category);
                $last_category = end($category_values);


                // Get parent any categories and create array
                $get_cat_parents = rtrim(get_category_parents($last_category->term_id, true, ','), ',');
                $cat_parents = explode(',', $get_cat_parents);

                // Loop through parent categories and store in variable $cat_display
                $cat_display = '';
                foreach ($cat_parents as $parents) {
                    if (apply_filters('mevzu_breadcrumb_show_parents', $post_type != 'post', $post_type)) {
                        $cat_display .= '<li class="breadcrumb-item">' . $parents . '</li>';
                    }
                }

            }

            // If it's a custom post type within a custom taxonomy
            $taxonomy_exists = taxonomy_exists($custom_taxonomy);
            if (empty($last_category) && !empty($custom_taxonomy) && $taxonomy_exists) {
                echo 1;
                $taxonomy_terms = get_the_terms($post->ID, $custom_taxonomy);
                $cat_id = $taxonomy_terms[0]->term_id;
                $cat_nicename = $taxonomy_terms[0]->slug;
                $cat_link = get_term_link($taxonomy_terms[0]->term_id, $custom_taxonomy);
                $cat_name = $taxonomy_terms[0]->name;

            }

            // Check if the post is in a category
            if (!empty($last_category)) {
                $cat_color = get_term_meta($last_category->term_id, 'cat_renk', true) ?: 'primary';
                
                echo '<li class="breadcrumb-item active item-current mt-1">';
                echo '<div class="d-inline-flex bg-' . esc_attr($cat_color) . ' overflow-hidden align-items-center kategori-dugme-takip" style="transform: translateY(-2px);">';
                echo '<a class="px-2 py-1 fw-semibold text-decoration-none small text-white" href="' . esc_url(get_category_link($last_category->term_id)) . '">' . $last_category->name . '</a>';
                
                if ( function_exists('mevzu_render_category_follow_button') ) {
                    echo '<div class="pe-1 d-flex align-items-center">';
                    mevzu_render_category_follow_button($last_category->term_id, false, true);
                    echo '</div>';
                }
                echo '</div>';
                echo '</li>';

                // Else if post is in a custom taxonomy
            } else if (!empty($cat_id)) {
                echo '<li class="breadcrumb-item breadcrumb-item-' . $cat_id . ' breadcrumb-item-' . $cat_nicename . '"><a class="bread-cat bread-cat-' . $cat_id . ' bread-cat-' . $cat_nicename . '" href="' . $cat_link . '" title="' . $cat_name . '">' . $cat_name . '</a></li>';
                echo '<li class="breadcrumb-item active item-current item-' . $post->ID . '">' . get_the_title() . '</li>';

            } else {
                if ($post_type != 'reklam') {
                    echo '<li class="breadcrumb-item active item-current item-' . $post->ID . '">' . get_the_title() . '</li>';
                }
            }

        } else if (is_category()) {

            // Category page
            echo '<li class="breadcrumb-item active item-current breadcrumb-item">' . single_cat_title('', false) . '</li>';

        } else if (is_page()) {

            // Standard page
            if ($post->post_parent) {

                // If child page, get parents 
                $anc = get_post_ancestors($post->ID);

                // Get parents in the right order
                $anc = array_reverse($anc);

                // Parent page loop
                if (!isset($parents))
                    $parents = null;
                foreach ($anc as $ancestor) {
                    $parents .= '<li class="item-parent item-parent-' . $ancestor . '"><a class="bread-parent bread-parent-' . $ancestor . '" href="' . get_permalink($ancestor) . '" title="' . get_the_title($ancestor) . '">' . get_the_title($ancestor) . '</a></li>';
                }

                // Display parent pages
                echo $parents;

                // Current page
                echo '<li class="breadcrumb-item active item-current item-' . $post->ID . '">' . get_the_title() . '</li>';

            } else {

                // Just display current page if not parents
                echo '<li class="breadcrumb-item active item-current item-' . $post->ID . '">' . get_the_title() . '</li>';

            }

        } else if (is_tag()) {

            // Tag page

            // Get tag information
            $term_id = get_query_var('tag_id');
            $taxonomy = 'post_tag';
            $args = 'include=' . $term_id;
            $terms = get_terms($taxonomy, $args);
            $get_term_id = $terms[0]->term_id;
            $get_term_slug = $terms[0]->slug;
            $get_term_name = $terms[0]->name;

            // Display the tag name
            echo '<li class="breadcrumb-item active item-current item-tag-' . $get_term_id . ' item-tag-' . $get_term_slug . '">' . $get_term_name . '</li>';

        } elseif (is_day()) {

            // Day archive

            // Year link
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';

            // Month link
            echo '<li class="item-month item-month-' . get_the_time('m') . '"><a class="bread-month bread-month-' . get_the_time('m') . '" href="' . get_month_link(get_the_time('Y'), get_the_time('m')) . '" title="' . get_the_time('M') . '">' . get_the_time('M') . ' Archives</a></li>';

            // Day display
            echo '<li class="breadcrumb-item active item-current item-' . get_the_time('j') . '">' . get_the_time('jS') . ' ' . get_the_time('M') . ' Archives</li>';

        } else if (is_month()) {

            // Month Archive

            // Year link
            echo '<li class="item-year item-year-' . get_the_time('Y') . '"><a class="bread-year bread-year-' . get_the_time('Y') . '" href="' . get_year_link(get_the_time('Y')) . '" title="' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</a></li>';

            // Month display
            echo '<li class="active item-month item-month-' . get_the_time('m') . '">' . get_the_time('M') . ' Archives</li>';

        } else if (is_year()) {

            // Display year archive
            echo '<li class="breadcrumb-item active item-current item-current-' . get_the_time('Y') . '">' . get_the_time('Y') . ' Archives</li>';

        } else if (is_author()) {

            // Auhor archive

            // Get the author information
            global $author;
            $userdata = get_userdata($author);

            // Display author name
            echo '<li class="breadcrumb-item active item-current item-current-' . $userdata->user_nicename . '">' . 'Author: ' . $userdata->display_name . '</li>';

        } else if (get_query_var('paged')) {

            // Paginated archives
            echo '<li class="breadcrumb-item active item-current item-current-' . get_query_var('paged') . '">' . __('Page') . ' ' . get_query_var('paged') . '</li>';

        } else if (is_search()) {

            // Search results page
            echo '<li class="breadcrumb-item active item-current item-current-' . get_search_query() . '">Arama: ' . get_search_query() . '</li>';

        } elseif (is_404()) {

            // 404 page
            echo '<li class="active">' . 'Error 404' . '</li>';
        }

        echo '</nav></ol>';

    }

}
/* Breadcrumbs */

function mevzu_setup()
{
    load_theme_textdomain('mevzu2', get_template_directory() . '/languages');
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus(
        array(
            'main-menu' => esc_html__('Ana Menü', 'mevzu'),
            'ust-menu' => esc_html__('Üst Menü', 'mevzu'),
            'mobil-menu' => esc_html__('Mobil Menü', 'mevzu'),
            'footer-menu-1' => esc_html__('Footer Menü 1', 'mevzu'),
            'footer-menu-2' => esc_html__('Footer Menü 2', 'mevzu'),
            'footer-menu-3' => esc_html__('Footer Menü 3', 'mevzu'),
            'footer-menu-4' => esc_html__('Footer Menü 4', 'mevzu'),
        )
    );

    // Otomatik Footer Menülerini Oluştur ve Ayarla
    if (is_admin() && !get_option('mevzu_footer_menus_created')) {
        $default_titles = array('Kurumsal', 'Hızlı Erişim', 'Kategoriler', 'Yasal');
        for ($i = 1; $i <= 4; $i++) {
            $menu_name = 'Footer Menü ' . $i;
            $menu_exists = wp_get_nav_menu_object($menu_name);

            if (!$menu_exists) {
                $menu_id = wp_create_nav_menu($menu_name);
                if (!is_wp_error($menu_id)) {
                    // Ayarlar paneline varsayılanları ata
                    update_option('options_footer_menu_' . $i, $menu_id);
                    update_option('options_footer_menu_' . $i . '_title', $default_titles[$i-1]);
                    
                    // Lokasyona ata
                    $locations = get_theme_mod('nav_menu_locations');
                    $locations['footer-menu-' . $i] = $menu_id;
                    set_theme_mod('nav_menu_locations', $locations);
                }
            }
        }
        update_option('mevzu_footer_menus_created', 1);
    }
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );
    add_theme_support(
        'custom-background',
        apply_filters(
            'mevzu_custom_background_args',
            array(
                'default-color' => 'ffffff',
                'default-image' => '',
            )
        )
    );
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support(
        'custom-logo',
        array(
            'height' => 250,
            'width' => 250,
            'flex-width' => true,
            'flex-height' => true,
        )
    );
}
add_action('after_setup_theme', 'mevzu_setup');

function mevzu_content_width()
{
    $GLOBALS['content_width'] = apply_filters('mevzu_content_width', 776);
}
add_action('after_setup_theme', 'mevzu_content_width', 0);

function mevzu_widgets_init()
{
    register_sidebar(
        array(
            'name' => esc_html__('Kenar Çubuğu: Anasayfa', 'mevzu'),
            'id' => 'sidebar-anasayfa',
            'description' => esc_html__('Buraya bileşen ekleyebilirsiniz.', 'mevzu'),
            'before_widget' => '<section id="%1$s" class="widget mt-3 mt-lg-4 %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
        )
    );
    register_sidebar(
        array(
            'name' => esc_html__('Kenar Çubuğu: Haber Sayfası', 'mevzu'),
            'id' => 'sidebar-single',
            'description' => esc_html__('Buraya bileşen ekleyebilirsiniz.', 'mevzu'),
            'before_widget' => '<section id="%1$s" class="widget mt-3 mt-lg-4 %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
        )
    );
    register_sidebar(
        array(
            'name' => esc_html__('Kenar Çubuğu: Arşiv', 'mevzu'),
            'id' => 'sidebar-archive',
            'description' => esc_html__('Buraya bileşen ekleyebilirsiniz.', 'mevzu'),
            'before_widget' => '<section id="%1$s" class="widget mt-3 mt-lg-4 %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
        )
    );
    register_sidebar(
        array(
            'name' => esc_html__('Kenar Çubuğu: Köşe Yazıları', 'mevzu'),
            'id' => 'sidebar-koseyazilari',
            'description' => esc_html__('Buraya bileşen ekleyebilirsiniz.', 'mevzu'),
            'before_widget' => '<section id="%1$s" class="widget bg-white rounded-3 shadow-sm mt-3 mt-lg-4 %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
        )
    );
}
add_action('widgets_init', 'mevzu_widgets_init');

/**
 * WordPress'in varsayılan gereksiz widget'larını devre dışı bırak.
 * Öncelik 20 — WP kendi widget'larını 10'da kaydeder, biz 20'de kaldırıyoruz.
 */
add_action('widgets_init', function () {
    $kaldir = [
        'WP_Widget_Search',          // Ara
        'WP_Widget_Archives',        // Arşivler
        'WP_Widget_Block',           // Blok
        'WP_Widget_Tag_Cloud',       // Etiket Bulutu
        'WP_Widget_Media_Gallery',   // Galeri
        'WP_Nav_Menu_Widget',        // Gezinme Menüsü
        'WP_Widget_Media_Image',     // Görsel
        'WP_Widget_Categories',      // Kategoriler
        'WP_Widget_Text',            // Metin
        'WP_Widget_RSS',             // RSS
        'WP_Widget_Pages',           // Sayfalar
        'WP_Widget_Media_Audio',     // Ses dosyası
        'WP_Widget_Recent_Posts',    // Son Yazılar
        'WP_Widget_Recent_Comments', // Son Yorumlar
        'WP_Widget_Calendar',        // Takvim
        'WP_Widget_Media_Video',     // Video
        'WP_Widget_Meta',            // Üst veri
    ];

    foreach ($kaldir as $widget_class) {
        unregister_widget($widget_class);
    }
}, 20);

function mevzu_scripts()
{
    wp_enqueue_style(
        'mevzu-style',
        get_stylesheet_directory_uri() . '/style.min.css',
        array(),
        _S_VERSION
    );

    // wp_enqueue_script( 'mevzu-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'mevzu_scripts');


/**
 * Implement the Custom Header feature.
 */
// require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
// require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
// require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if (defined('JETPACK__VERSION')) {
    require get_template_directory() . '/inc/jetpack.php';
}

add_filter('admin_footer_text', 'ozel_admin_footer_bilgi');
function ozel_admin_footer_bilgi()
{
    $tema = wp_get_theme();
    $isim = $tema->get('Name');
    $gelistirici = '<a href="' . $tema->get('AuthorURI') . '" target="_blank" alt="' . $tema->get('Author') . '">' . str_replace(" ", "<b>", $tema->get('Author')) . '</b></a>';
    $versiyon = $tema->get('Version');

    return sprintf(
        '<div class="admin-footer-mevzu-info small">%s [v%s] • Design & Software by %s • <span style="color:#2271b1">Lisanslı Kullanım</span></div>',
        esc_html($isim),
        esc_html($versiyon),
        $gelistirici
    );
}
// Sağ alttaki "Get Version" kısmını kaldır
add_filter('update_footer', '__return_empty_string', 11);

// Mevzu² Tema Ayar Paneli (ACF yerine native çözüm)
require_once get_template_directory() . '/inc/theme-settings/init.php';

// ========== ESKİ ACF ENTEGRASYONU (Devre Dışı) ==========
// ACF artık tema içinde değil, tüm alanlar native olarak yönetiliyor.
// define('MY_ACF_PATH', get_stylesheet_directory() . '/inc/acf/');
// define('MY_ACF_URL', get_stylesheet_directory_uri() . '/inc/acf/');
// include_once(MY_ACF_PATH . 'acf.php');
// add_filter('acf/settings/url', 'my_acf_settings_url');
// function my_acf_settings_url($url) { return MY_ACF_URL; }
// add_filter('acf/settings/show_updates', '__return_false', 100);
// ========== ESKİ ACF ENTEGRASYONU SONU ==========

function limit_classic_editor_to_pages($use_block_editor, $post_type)
{
    // Eğer post type 'page' ise Classic Editor'ü aktif et
    if ($post_type === 'page') {
        return false; // Classic Editor
    }
    return $use_block_editor; // Diğer içerik türleri için varsayılan editörü kullan
}
add_filter('use_block_editor_for_post_type', 'limit_classic_editor_to_pages', 10, 2);
function remove_yoast_meta_box_from_pages()
{
    // 'wpseo_meta' Yoast SEO meta box'un ID'sidir.
    remove_meta_box('wpseo_meta', 'page', 'normal');
}
add_action('add_meta_boxes', 'remove_yoast_meta_box_from_pages', 99);










// Bileşen Tanımlama
function fonksiyon_haftalik_gundem()
{
    register_widget('bilesen_haftalik_gundem');
}
add_action('widgets_init', 'fonksiyon_haftalik_gundem');

// Bileşen Sınıfı
class bilesen_haftalik_gundem extends WP_Widget
{

    public function __construct()
    {
        $widget_options = array(
            'classname' => 'fonksiyon_haftalik_gundem',
            'description' => 'Özelleştirilebilir haber listeleme',
        );
        parent::__construct('fonksiyon_haftalik_gundem', 'Mevzu² — Haber Sıralama', $widget_options);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';

        $order_by = !empty($instance['order_by']) ? $instance['order_by'] : 'date';
        $post_count = !empty($instance['post_count']) ? absint($instance['post_count']) : 5;
        $template = !empty($instance['template']) ? $instance['template'] : 'populer-basliklar';
        $selected_category = !empty($instance['selected_category']) ? absint($instance['selected_category']) : 0;

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $args_q = array(
            'post_type' => 'post',
            'posts_per_page' => $post_count,
            'orderby' => $order_by,
            'post__not_in' => array(get_the_ID()),
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            ),
            'date_query' => array(
                array(
                    'after' => '1 week ago',
                    'inclusive' => true,
                ),
            ),
        );
        if ($order_by == 'meta_value_num') {
            $args_q['meta_key'] = 'views_count';
            $args_q['order'] = 'DESC';
        }
        // Kategori filtresi
        if (!empty($selected_category)) {
            $args_q['cat'] = $selected_category;
        }
        // Ön sayfa kategorisini hariç tutma
        // if (is_front_page()) {
        //     $args_q['category__not_in'] = array(6556);
        // }
        $custom_query = new WP_Query($args_q);

        if ($custom_query->have_posts()) {
            $count = 0;
            while ($custom_query->have_posts()) {
                $custom_query->the_post();
                $count++;

                if ($template == 'populer-basliklar-1') { ?>
                    <a href="<?php the_permalink() ?>" class="ripple position-relative overflow-hidden d-block border-bottom text-link<?php if ($count == 1)
                          echo ' active'; ?>" data-bs-ripple-color="light">

                        <?php if ($count == 1) : ?>
                            <div class="px-2">
                            <?php the_post_thumbnail('gorsel-thumbnail-col-4', ['title' => get_the_title(), 'loading' => 'lazy', 'class' => 'rounded-0']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="p-3">
                            <?php
                            $first_category = get_filtered_first_category();
                            if ($first_category) {
                                $cat_color = get_term_meta($first_category->term_id, 'cat_renk', true);
                                echo '<div class="text-muted small-2 fw-normal">' . 
                                    esc_html($first_category->name) . 
                                '</div>';
                            }
                            ?>
                            <h3 class="title"><?php the_title(); ?></h3>
                        </div>
                    </a>
                <?php }

                if ($template == 'populer-basliklar-2') {
                    get_template_part('sablon/sablon-3');
                }
                if ($template == 'populer-basliklar-3') {
                    if ($count == 1) {
                        get_template_part('sablon/card-bolunmus-ilk');
                    } else {
                        get_template_part('sablon/card-bolunmus');
                    }

                    if ($count == $custom_query->post_count) {
                        echo '<div class="text-center py-2 border-top">
                                <a href=" ' . esc_url(get_category_link($selected_category)) . '" class="btn btn-dark btn-sm d-inline-block py-2 px-4 rounded-4 view-all-link fw-semibold bg-body-secondary text-body small-2 border-0">Daha Fazla</a>
                        </div>';
                    }
                }
            }
            wp_reset_postdata();
        } else {
            echo '<span class="d-block p-3 fw-normal small text-muted text-center">Yazı bulunamadı</span>';
        }
        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Haber Sıralaması', 'mevzu2');
        $order_by = !empty($instance['order_by']) ? $instance['order_by'] : 'date';
        $post_count = !empty($instance['post_count']) ? absint($instance['post_count']) : 5;
        $template = !empty($instance['template']) ? $instance['template'] : 'populer-basliklar';
        $selected_category = !empty($instance['selected_category']) ? absint($instance['selected_category']) : 0;

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('order_by'); ?>">Sıralama:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('order_by'); ?>"
                name="<?php echo $this->get_field_name('order_by'); ?>">
                <option value="date" <?php selected($order_by, 'date'); ?>>Tarihe göre</option>
                <option value="meta_value_num" <?php selected($order_by, 'meta_value_num'); ?>>Haftalık tıklanmaya göre</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_count'); ?>">Gösterilecek haber sayısı:</label>
            <input class="widefat" type="number" min="1" max="20" id="<?php echo $this->get_field_id('post_count'); ?>"
                name="<?php echo $this->get_field_name('post_count'); ?>" value="<?php echo esc_attr($post_count); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('template'); ?>">Şablon:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('template'); ?>"
                name="<?php echo $this->get_field_name('template'); ?>">
                <option value="populer-basliklar-1" <?php selected($template, 'populer-basliklar-1'); ?>>Şablon - 1</option>
                <option value="populer-basliklar-2" <?php selected($template, 'populer-basliklar-2'); ?>>Şablon - 2</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('selected_category'); ?>">Kategori Seç:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('selected_category'); ?>"
                name="<?php echo $this->get_field_name('selected_category'); ?>">
                <option value="0">Tüm Kategoriler</option>
                <?php
                $categories = get_categories(array('hide_empty' => false));
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($selected_category, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['order_by'] = !empty($new_instance['order_by']) ? sanitize_text_field($new_instance['order_by']) : 'date';
        $instance['post_count'] = !empty($new_instance['post_count']) ? absint($new_instance['post_count']) : 5;
        $instance['template'] = !empty($new_instance['template']) ? sanitize_text_field($new_instance['template']) : 'populer-basliklar';
        $instance['selected_category'] = !empty($new_instance['selected_category']) ? absint($new_instance['selected_category']) : 0;
        return $instance;
    }
}

// Bileşen Tanımlama
class bilesen_videohaberler extends WP_Widget
{

    public function __construct()
    {
        $widget_options = array(
            'classname' => 'video-haberler',
            'description' => 'Özelleştirilebilir videolu haberleri listeleme',
        );
        parent::__construct('custom_bilesen_videohaberler', 'Mevzu² — Video Haberler', $widget_options);
    }

    public function widget($args, $instance)
    {

        echo $args['before_widget'];

        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : __('Video Haberler', 'mevzu2');
        $order_by = !empty($instance['order_by']) ? $instance['order_by'] : 'date';
        $post_count = !empty($instance['post_count']) ? absint($instance['post_count']) : 5;
        $category = !empty($instance['category']) ? absint($instance['category']) : 0;


        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // WP_Query için argümanlar
        $args_q = array(
            'post_type' => 'post',
            'posts_per_page' => $post_count,
            'orderby' => $order_by,
            'cat' => $category,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id', // Öne çıkan görseli kontrol eder
                    'compare' => 'EXISTS',
                ),
            ),
        );

        $custom_query = new WP_Query($args_q);

        if ($custom_query->have_posts()) {
            echo '<div class="px-1 pb-1">';
            while ($custom_query->have_posts()) {
                $custom_query->the_post(); ?>
                <a href="<?php the_permalink() ?>" class="text-link ripple d-block p-2" data-bs-ripple-color="light">
                    <div class="row align-items-center">
                        <div class="col-4 col-md-2 col-lg position-relative">
                            <?php the_post_thumbnail('gorsel-thumbnail-widget', ['title' => get_the_title(), 'loading' => 'lazy']); ?>
                            <svg class="position-absolute top-50 start-50 translate-middle bg-primary text-white rounded-circle p-1 opacity-75"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 15 15">
                                <path fill="currentColor"
                                    d="M4.79 2.093A.5.5 0 0 0 4 2.5v10a.5.5 0 0 0 .79.407l7-5a.5.5 0 0 0 0-.814l-7-5Z" />
                            </svg>
                        </div>
                        <div class="col-8 col-md-10 col-lg-7 ps-0">
                            <h3 class="satir-2"><?php the_title(); ?></h3>
                        </div>
                    </div>
                </a>
                <?php
            }
            wp_reset_postdata();
            echo '</div>';
        } else {
            echo 'Bulunamadı';
        }


        echo $args['after_widget'];

    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Video Haberler', 'mevzu2');
        $order_by = !empty($instance['order_by']) ? $instance['order_by'] : 'date';
        $post_count = !empty($instance['post_count']) ? absint($instance['post_count']) : 5;
        $category = !empty($instance['category']) ? absint($instance['category']) : '';

        // Kategorileri getirme
        $categories = get_categories(array(
            'hide_empty' => false,
        ));
        ?>

        <!-- Başlık Alanı -->
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>">
        </p>

        <!-- Sıralama Seçenekleri -->
        <p>
            <label for="<?php echo $this->get_field_id('order_by'); ?>">Sıralama:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('order_by'); ?>"
                name="<?php echo $this->get_field_name('order_by'); ?>">
                <option value="date" <?php selected($order_by, 'date'); ?>>Tarihe göre</option>
                <option value="meta_value_num" <?php selected($order_by, 'meta_value_num'); ?>>Tıklanmaya göre</option>
            </select>
        </p>

        <!-- Kategori Seçimi -->
        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>">Kategori Seç:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('category'); ?>"
                name="<?php echo $this->get_field_name('category'); ?>">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($category, $cat->term_id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <!-- Çıktı Adedi -->
        <p>
            <label for="<?php echo $this->get_field_id('post_count'); ?>">Görüntülenecek haber sayısı: </label>
            <input class="widefat" type="number" id="<?php echo $this->get_field_id('post_count'); ?>"
                name="<?php echo $this->get_field_name('post_count'); ?>" value="<?php echo esc_attr($post_count); ?>" min="1"
                max="20">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['order_by'] = !empty($new_instance['order_by']) ? sanitize_text_field($new_instance['order_by']) : 'date';
        $instance['post_count'] = !empty($new_instance['post_count']) ? absint($new_instance['post_count']) : 5;
        $instance['category'] = !empty($new_instance['category']) ? absint($new_instance['category']) : '';
        return $instance;
    }
}

// Widget'i kaydet
function custom_bilesen_videohaberler()
{
    register_widget('bilesen_videohaberler');
}
add_action('widgets_init', 'custom_bilesen_videohaberler');









// Bileşen Tanımlama
function custom_bilesen_reklam()
{
    register_widget('bilesen_reklam');
}
add_action('widgets_init', 'custom_bilesen_reklam');

// Bileşen Sınıfı
class bilesen_reklam extends WP_Widget
{

    public function __construct()
    {
        $widget_options = array(
            'classname' => 'custom_bilesen_reklam',
            'description' => 'Özelleştirilebilir reklam alanı',
        );
        parent::__construct('custom_bilesen_reklam', 'Mevzu² — Reklam', $widget_options);

        // Performans için özel boyut kaydı (genişlik maks 366px)
        add_image_size('bilesen_reklam_366', 366, 9999, false);

        // Gereken scriptleri widgets sayfasına yükle
        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook === 'widgets.php') {
                wp_enqueue_media();
            }
        });
    }

    public function widget($args, $instance)
    {
        $active      = isset($instance['active']) ? (bool) $instance['active'] : true;
        $type        = !empty($instance['type']) && in_array($instance['type'], ['image', 'html'], true) ? $instance['type'] : 'image';
        $image_url = !empty($instance['image_url']) ? $instance['image_url'] : '';
        $link_url  = !empty($instance['link_url']) ? $instance['link_url'] : '';
        $link_title = !empty($instance['link_title']) ? $instance['link_title'] : 'Reklam';
        $html_code   = !empty($instance['html_code']) ? $instance['html_code'] : '';
        $placeholder = !empty($instance['placeholder']);
        $start_date  = !empty($instance['start_date']) ? $instance['start_date'] : '';
        $end_date    = !empty($instance['end_date']) ? $instance['end_date'] : '';

        $today = current_time('Y-m-d');
        if (!empty($start_date) && $today < $start_date) {
            if ($placeholder) {
                echo '<div class="widget mt-3 mt-lg-4 reklam h-240 rounded-3 shadow-sm small d-flex flex-column align-items-center justify-content-center text-center bg-light"><span class="d-block fw-semibold text-body mt-2">Reklam Alanı</span><span class="small text-muted">Bu reklam henüz yayında değil</span></div>';
            }
            return;
        }
        if (!empty($end_date) && $today > $end_date) {
            if ($placeholder) {
                echo '<div class="widget mt-3 mt-lg-4 reklam h-240 rounded-3 shadow-sm small d-flex flex-column align-items-center justify-content-center text-center bg-light"><span class="d-block fw-semibold text-body mt-2">Reklam Alanı</span><span class="small text-muted">Bu reklamın yayın süresi sona erdi</span></div>';
            }
            return;
        }

        if (!$active) {
            return;
        }

        // Fallback: Görsel ayarları tanımlanmamışsa önceki ACF yöntemini kontrol et
        if (empty($image_url)) {
            $widget_id = 'widget_' . $this->id; // ACF'de widget ID'si bu formatta olur
            $image_data = get_option('options_' . $widget_id . '_widget_reklam_gorsel');

            if ($image_data) {
                if (is_array($image_data)) {
                    $image_url = $image_data['url'] ?? '';
                } elseif (is_numeric($image_data)) {
                    $image_url = wp_get_attachment_url($image_data);
                } else {
                    $image_url = $image_data;
                }
            }
        }

        if ($type === 'html' && !empty($html_code)) {
            echo '<div class="widget mt-3 mt-lg-4 reklam rounded-3 shadow-sm small">' . $html_code . '</div>';
            return;
        }

        if ($image_url) {
            $img_src = '';
            if (is_numeric($image_url)) {
                $img_array = wp_get_attachment_image_src($image_url, 'bilesen_reklam_366');
                $img_src = $img_array ? $img_array[0] : wp_get_attachment_url($image_url);
            } else {
                $img_src = $image_url;
            }

            if ($link_url) {
                echo '<a href="' . esc_url($link_url) . '" target="_blank" rel="nofollow" title="' . esc_attr($link_title) . '" aria-label="' . esc_attr($link_title) . '">';
            }
            if ($img_src) {
                echo '<img loading="lazy" width="366" class="widget mt-3 mt-lg-4 d-block mx-auto" style="max-width: 100%; height: auto; border-radius: 4px;" src="' . esc_url($img_src) . '" alt="' . esc_attr($link_title) . '">';
            }
            if ($link_url) {
                echo '</a>';
            }
        } else {
            if (!$placeholder) {
                return;
            }
            echo '<div class="widget mt-3 mt-lg-4 reklam h-240 rounded-3 shadow-sm small d-flex flex-column align-items-center justify-content-center text-center bg-light">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24">
            <path class="text-primary" fill="currentColor" d="M11.71 17.99A5.993 5.993 0 0 1 6 12c0-3.31 2.69-6 6-6c3.22 0 5.84 2.53 5.99 5.71l-2.1-.63a3.999 3.999 0 1 0-4.81 4.81zM22 12c0 .3-.01.6-.04.9l-1.97-.59c.01-.1.01-.21.01-.31c0-4.42-3.58-8-8-8s-8 3.58-8 8s3.58 8 8 8c.1 0 .21 0 .31-.01l.59 1.97c-.3.03-.6.04-.9.04c-5.52 0-10-4.48-10-10S6.48 2 12 2s10 4.48 10 10m-3.77 4.26L22 15l-10-3l3 10l1.26-3.77l4.27 4.27l1.98-1.98z"></path>
        </svg>
                        <span class="d-block fw-semibold text-body mt-2">Reklam Alanı</span><span class="small text-muted">Bileşen ayarlarından görsel ekleyin</span>
                    </div>';
        }
    }


    public function form($instance)
    {
        $active      = isset($instance['active']) ? (bool) $instance['active'] : true;
        $type        = !empty($instance['type']) && in_array($instance['type'], ['image', 'html'], true) ? $instance['type'] : 'image';
        $image_url = !empty($instance['image_url']) ? $instance['image_url'] : '';
        $link_url  = !empty($instance['link_url']) ? $instance['link_url'] : '';
        $link_title = !empty($instance['link_title']) ? $instance['link_title'] : '';
        $html_code   = !empty($instance['html_code']) ? $instance['html_code'] : '';
        $placeholder = !empty($instance['placeholder']);
        $start_date  = !empty($instance['start_date']) ? $instance['start_date'] : '';
        $end_date    = !empty($instance['end_date']) ? $instance['end_date'] : '';
        
        $preview_url = '';
        if ($image_url) {
            $preview_url = is_numeric($image_url) ? wp_get_attachment_url($image_url) : $image_url;
        }
        ?>
        <div class="mevzu-widget-image-uploader">
            <p>
                <label>
                    <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('active')); ?>" name="<?php echo esc_attr($this->get_field_name('active')); ?>" value="1" <?php checked($active, true); ?>>
                    Reklam Aktif
                </label>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('type')); ?>">Reklam Tipi</label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('type')); ?>" name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                    <option value="image" <?php selected($type, 'image'); ?>>Resim + Link</option>
                    <option value="html" <?php selected($type, 'html'); ?>>HTML Kodu</option>
                </select>
            </p>
            <p>
                <label>Reklam Görseli:</label><br>
                <?php if ($preview_url): ?>
                    <img src="<?php echo esc_url($preview_url); ?>" style="max-width:100%; height:auto; margin-top:5px; margin-bottom:10px; border-radius:4px; display:block;" class="mevzu-widget-img-preview" />
                <?php else: ?>
                    <img src="" style="max-width:100%; height:auto; margin-top:5px; margin-bottom:10px; border-radius:4px; display:none;" class="mevzu-widget-img-preview" />
                <?php endif; ?>
                
                <input type="hidden" class="mevzu-widget-img-input" id="<?php echo esc_attr($this->get_field_id('image_url')); ?>" name="<?php echo esc_attr($this->get_field_name('image_url')); ?>" value="<?php echo esc_attr($image_url); ?>">
                
                <button type="button" class="button button-secondary mevzu-widget-upload-btn">Görsel Seç / Yükle</button>
                <button type="button" class="button mevzu-widget-remove-btn" style="<?php echo $image_url ? '' : 'display:none;'; ?>">Kaldır</button>
                <small class="text-muted d-block mt-2">En fazla 366px genişliğinde önerilir.</small>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('link_url')); ?>">Tıklama Linki</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('link_url')); ?>" name="<?php echo esc_attr($this->get_field_name('link_url')); ?>" type="url" value="<?php echo esc_attr($link_url); ?>" placeholder="https://...">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('link_title')); ?>">Başlık / Alt Metin</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('link_title')); ?>" name="<?php echo esc_attr($this->get_field_name('link_title')); ?>" type="text" value="<?php echo esc_attr($link_title); ?>" placeholder="Reklam">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('html_code')); ?>">HTML / Reklam Kodu</label>
                <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id('html_code')); ?>" name="<?php echo esc_attr($this->get_field_name('html_code')); ?>" rows="5" placeholder="Google AdSense veya iframe kodu"><?php echo esc_textarea($html_code); ?></textarea>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('start_date')); ?>">Yayın Başlangıcı</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('start_date')); ?>" name="<?php echo esc_attr($this->get_field_name('start_date')); ?>" type="date" value="<?php echo esc_attr($start_date); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('end_date')); ?>">Yayın Bitişi</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('end_date')); ?>" name="<?php echo esc_attr($this->get_field_name('end_date')); ?>" type="date" value="<?php echo esc_attr($end_date); ?>">
                <small class="text-muted d-block mt-2">Boş bırakılırsa tarih sınırı uygulanmaz.</small>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>" name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" value="1" <?php checked($placeholder, true); ?>>
                    Reklam boşsa placeholder göster
                </label>
            </p>

            <script>
            jQuery(document).ready(function($){
                // Widget DOM işlemleri
                $('body').off('click', '.mevzu-widget-upload-btn').on('click', '.mevzu-widget-upload-btn', function(e){
                    e.preventDefault();
                    var button = $(this);
                    var container = button.closest('.mevzu-widget-image-uploader');
                    var custom_uploader = wp.media({
                        title: 'Reklam Görseli Seç',
                        button: { text: 'Görseli Kullan' },
                        multiple: false
                    }).on('select', function() {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        container.find('.mevzu-widget-img-preview').attr('src', attachment.url).show();
                        // Veritabanına fiziksel id kaydediyoruz, böylece frontendde "bilesen_reklam_366" boyutunu çağırabiliriz
                        container.find('.mevzu-widget-img-input').val(attachment.id).trigger('change');
                        container.find('.mevzu-widget-remove-btn').show();
                    }).open();
                });

                $('body').off('click', '.mevzu-widget-remove-btn').on('click', '.mevzu-widget-remove-btn', function(e){
                    e.preventDefault();
                    var container = $(this).closest('.mevzu-widget-image-uploader');
                    container.find('.mevzu-widget-img-preview').attr('src', '').hide();
                    container.find('.mevzu-widget-img-input').val('').trigger('change');
                    $(this).hide();
                });
            });
            </script>
        </div>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['active'] = !empty($new_instance['active']) ? 1 : 0;
        $instance['type'] = (!empty($new_instance['type']) && in_array($new_instance['type'], ['image', 'html'], true)) ? $new_instance['type'] : 'image';
        $instance['image_url'] = (!empty($new_instance['image_url'])) ? sanitize_text_field($new_instance['image_url']) : '';
        $instance['link_url']  = (!empty($new_instance['link_url'])) ? esc_url_raw($new_instance['link_url']) : '';
        $instance['link_title'] = (!empty($new_instance['link_title'])) ? sanitize_text_field($new_instance['link_title']) : '';
        $instance['html_code'] = current_user_can('unfiltered_html')
            ? wp_unslash($new_instance['html_code'] ?? '')
            : wp_kses_post(wp_unslash($new_instance['html_code'] ?? ''));
        $instance['start_date'] = (!empty($new_instance['start_date'])) ? sanitize_text_field($new_instance['start_date']) : '';
        $instance['end_date'] = (!empty($new_instance['end_date'])) ? sanitize_text_field($new_instance['end_date']) : '';
        $instance['placeholder'] = !empty($new_instance['placeholder']) ? 1 : 0;
        return $instance;
    }
}

/* Options Pages — Artık inc/theme-settings/ tarafından yönetiliyor */
// ACF Options sayfaları devre dışı bırakıldı.
// Tüm ayar sayfaları: class-settings-page.php, class-ads-page.php, class-popup-page.php
/**
 * Yalnızca Administrator rolü (admin bar Mevzu² menüleri).
 */
function mevzu_current_user_is_administrator() {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    $user = wp_get_current_user();
    return in_array( 'administrator', (array) $user->roles, true );
}

function mevzu_admin_bar_menu( $wp_admin_bar ) {
    if ( ! mevzu_current_user_is_administrator() ) {
        return;
    }

    $badge = '<span class="bg-primary text-white py-1 px-2 small rounded">m².<span class="fw-semibold">Studio</span></span>';

    $preview_param = '';
    if ( ! is_admin() ) {
        $current_url   = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $preview_param = '&preview_url=' . urlencode( $current_url );
    }

    $wp_admin_bar->add_node(
        array(
            'id'     => 'mevzu-ayarlar',
            'title'  => 'Tema Ayarları',
            'href'   => admin_url( 'admin.php?page=mevzu-ayarlar' ),
            'parent' => 'top-secondary',
            'meta'   => array(
                'class' => 'mevzu-admin-bar',
            ),
        )
    );

    $wp_admin_bar->add_node(
        array(
            'id'     => 'mevzu-studyo',
            'title'  => $badge,
            'href'   => admin_url( 'admin.php?page=mevzu-studyo' . $preview_param ),
            'parent' => 'top-secondary',
            'meta'   => array(
                'class' => 'mevzu-admin-bar',
            ),
        )
    );

    // Ekran Ayarları — yalnızca wp-admin içinde
    if ( is_admin() ) {
        $wp_admin_bar->add_node(
            array(
                'id'     => 'mevzu-screen-options',
                'title'  => '<span class="ab-icon dashicons dashicons-admin-generic me-2" style="margin-top:2px"></span>Ayarlar',
                'href'   => '#',
                'parent' => 'top-secondary',
                'meta'   => array(
                    'title' => 'Ekran Ayarları',
                    'class' => 'mevzu-screen-options-toggle',
                ),
            )
        );
    }
}
add_action('admin_bar_menu', 'mevzu_admin_bar_menu', 100);

/**
 * Site Ana Rengi alanındaki örnek palet (ayarlar, sihirbaz, ön yüz renk paneli).
 *
 * @return string[] HEX listesi
 */
function mevzu_get_site_primary_color_presets() {
    return array( '#e90808', '#2196f3', '#4caf50', '#9c27b0', '#ff9800', '#1a237e', '#222222' );
}

/**
 * Ön yüzde admin bar renk paneli için geçerli başlangıç değerleri (Customizer ile aynı kaynak).
 *
 * @return array{bg:string,primary:string}
 */
function mevzu_get_front_theme_color_defaults() {
    $bg = get_theme_mod('mevzu_background_color', '#f1f5f9');
    $bg = is_string($bg) ? sanitize_hex_color($bg) : '';
    if (!$bg) {
        $bg = '#f1f5f9';
    }

    $primary_tm = get_theme_mod('mevzu_primary_color');
    $primary    = is_string($primary_tm) ? sanitize_hex_color($primary_tm) : '';
    if (!$primary) {
        $fallback = get_option('options_site_rengi', '#e90808');
        $primary  = is_string($fallback) ? sanitize_hex_color($fallback) : '';
    }
    if (!$primary) {
        $primary = '#e90808';
    }

    return array(
        'bg'      => $bg,
        'primary' => $primary,
    );
}

/**
 * Ön yüz: wp-admin-bar-mevzu-screen-options (dashicons-admin-generic) ile renk paneli.
 */
function mevzu_enqueue_front_color_panel() {
    if (is_admin() || !is_user_logged_in() || !current_user_can('edit_theme_options')) {
        return;
    }

    $path = get_template_directory() . '/js/front-color-panel.js';
    if (!is_readable($path)) {
        return;
    }

    wp_enqueue_script(
        'mevzu-front-color-panel',
        get_template_directory_uri() . '/js/front-color-panel.js',
        array('jquery'),
        filemtime($path),
        true
    );

    $defaults = mevzu_get_front_theme_color_defaults();

    wp_localize_script(
        'mevzu-front-color-panel',
        'mevzuFrontColors',
        array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mevzu_front_theme_colors'),
            'defaults'        => $defaults,
            'primaryPresets'  => array_values( mevzu_get_site_primary_color_presets() ),
            'i18n'            => array(
                'panelTitle'   => __('Renkler', 'mevzu'),
                'bgLabel'      => __('Arka Plan Rengi', 'mevzu'),
                'primaryLabel' => __('Ana Renk', 'mevzu'),
                'save'         => __('Kaydet', 'mevzu'),
                'cancel'       => __('İptal', 'mevzu'),
                'saved'        => __('Kaydedildi. Sayfa yenileniyor…', 'mevzu'),
                'error'        => __('Kaydedilemedi.', 'mevzu'),
                'toolbarTitle' => __('Site renkleri', 'mevzu'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', 'mevzu_enqueue_front_color_panel', 20);

/**
 * AJAX: Ön yüz renk panelinden theme_mod kaydı (Customizer ile aynı ayarlar).
 */
function mevzu_ajax_save_front_theme_colors() {
    if (!check_ajax_referer('mevzu_front_theme_colors', 'nonce', false)) {
        wp_send_json_error(array('message' => 'nonce'), 403);
    }
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => 'cap'), 403);
    }

    $bg      = isset($_POST['mevzu_background_color']) ? sanitize_hex_color(wp_unslash($_POST['mevzu_background_color'])) : '';
    $primary = isset($_POST['mevzu_primary_color']) ? sanitize_hex_color(wp_unslash($_POST['mevzu_primary_color'])) : '';

    if (!$bg || !$primary) {
        wp_send_json_error(array('message' => 'invalid_color'), 400);
    }

    set_theme_mod('mevzu_background_color', $bg);
    set_theme_mod('mevzu_primary_color', $primary);
    update_option('options_site_rengi', $primary);

    wp_send_json_success(
        array(
            'mevzu_background_color' => $bg,
            'mevzu_primary_color'     => $primary,
        )
    );
}
add_action('wp_ajax_mevzu_save_front_theme_colors', 'mevzu_ajax_save_front_theme_colors');


// Ekran Ayarları düğmesi için JS (yalnızca yönetim arayüzü)
add_action('admin_footer', function () {
    if (!is_admin()) {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        $('#wp-admin-bar-mevzu-screen-options > a').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $origBtn = $('#show-settings-link');
            if ($origBtn.length) {
                $origBtn.trigger('click');
            } else {
                $('#screen-options-wrap').slideToggle(200);
            }
        });
    });
    </script>
    <style>
        #screen-options-link-wrap { display: none !important; }
    </style>
    <?php
});

/* Options Pages */

/* Admin theme - Özel admin CSS'i her zaman yükle (renk şemasından bağımsız) */
function mevzu_admin_custom_css()
{
    $theme_dir = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'remixicon',
        $theme_dir . '/css/fonts/remixicon.css',
        array(),
        _S_VERSION
    );

    wp_enqueue_style(
        'mevzu2-style',
        $theme_dir . '/css/mevzu2.min.css',
        array(),
        _S_VERSION
    );

    wp_enqueue_style(
        'mevzu-admin-css',
        $theme_dir . '/css/admin_mevzu2.min.css',
        array(),
        filemtime(get_stylesheet_directory() . '/css/admin_mevzu2.min.css')
    );

    // Editor stillerini (TinyMCE) destekle
    add_editor_style('css/admin_mevzu2.css');
}
add_action('admin_enqueue_scripts', 'mevzu_admin_custom_css', 99);

/* Kullanıcının seçtiği WP renk şemasını Mevzu2 değişkenlerine uygula */
function mevzu_dynamic_admin_colors()
{
    global $_wp_admin_css_colors;
    $user_color = get_user_option('admin_color');
    if (empty($user_color)) $user_color = 'fresh'; // WordPress varsayılanı

    if (!isset($_wp_admin_css_colors[$user_color])) return;

    $colors = $_wp_admin_css_colors[$user_color]->colors;
    // WP renk şeması dizisi: [0]=menü bg, [1]=submenu bg, [2]=highlight, [3]=accent/notification
    $secondary = $colors[0]; // sidebar/menü arka planı
    $primary   = $colors[2]; // vurgu rengi (butonlar, aktif linkler)

    // HEX → RGB çevirme fonksiyonu
    $hex_to_rgb = function($hex) {
        $hex = ltrim($hex, '#');
        return implode(', ', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]);
    };

    $primary_rgb   = $hex_to_rgb($primary);
    $secondary_rgb = $hex_to_rgb($secondary);

    echo '<style id="mevzu-dynamic-colors">
        :root {
            --mevzu-primary: ' . esc_attr($primary) . ' !important;
            --mevzu-secondary: ' . esc_attr($secondary) . ' !important;
            --mevzu-primary-rgb: ' . esc_attr($primary_rgb) . ' !important;
            --mevzu-link-color-rgb: ' . esc_attr($primary_rgb) . ' !important;
            --mevzu-secondary-rgb: ' . esc_attr($secondary_rgb) . ' !important;
            --mevzu-link-color: ' . esc_attr($primary) . ' !important;
        }
    </style>';
}
add_action('admin_head', 'mevzu_dynamic_admin_colors', 999);

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen->id !== 'dashboard')
        return;
    ?>
    <script>
        jQuery(function ($) {
            // Sol ana sütun (container-1)
            var col1 = $('#postbox-container-1 .meta-box-sortables');
            col1.append($('#github_releases_widget'));

            var col2 = $('#postbox-container-2 .meta-box-sortables');
            col2.append($('#dashboard_right_now'));
            col2.append($('#dashboard_primary'));

            var col3 = $('#postbox-container-3 .meta-box-sortables');
            col3.append($('#dashboard_quick_press'));

            var col4 = $('#postbox-container-4 .meta-box-sortables');
            col4.append($('#dashboard_activity'));
        });
    </script>
    <?php
});
/* Admin theme */

/* Admin page logo */
function wpb_custom_logo()
{
    echo '
<style type="text/css">
#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
background-image: url(' . get_bloginfo('stylesheet_directory') . '/img/favicon.png) !important;
background-size:cover;
background-position: 0 0;
color:rgba(0, 0, 0, 0);
}
#wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
background-position: 0 0;
}
</style>
';
}
add_action('wp_before_admin_bar_render', 'wpb_custom_logo');
/* Admin page logo */

/* Remove jQuery Migrate */
function remove_jquery_migrate($scripts)
{
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
add_action('wp_default_scripts', 'remove_jquery_migrate');
/* Remove jQuery Migrate */

/* Custom Posts Name */
function custom_change_post_labels($args, $post_type)
{
    if ($post_type === 'post') { // Check if it's the default "post" post type
        $new_name = 'Haberler'; // Replace 'Your Custom Name' with the name you want to use
        $new_icon = 'dashicons-admin-page'; // Replace 'dashicons-star-filled' with the Dashicon class or URL of the custom icon you want to use

        $args['labels']['name'] = $new_name;
        $args['labels']['singular_name'] = $new_name;
        $args['menu_icon'] = $new_icon;
    }

    return $args;
}
add_filter('register_post_type_args', 'custom_change_post_labels', 10, 2);
/* Custom Posts Name */

/* Default thumbnail */

/* Default thumbnail */

/* Comment Form */

/* Comment Form */

/* Custom WP Login */
function ppwp_custom_login_logo()
{ ?>
    <style type="text/css">
        #login {
            padding: 7rem 1rem 0 1rem !important;
        }

        #login h1 a,
        .login h1 a {
            background-image: url(<?php echo get_template_directory_uri() . '/img/logo.svg' ?>);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 60px;
            width: 268px;
            background-size: contain;
            background-repeat: no-repeat;
        }

        #nav {
            text-align: center;
        }

        #backtoblog {
            display: none;
        }

        .kerem {
            text-align: center;
            margin-top: 1.5rem !important;
        }

        .kerem a {
            opacity: 1;
            display: inline-block;
            text-decoration: none;
            color: #000;
        }

        .kerem a:focus {
            box-shadow: none !important;
        }

        .kerem a:hover {
            opacity: .8;
            color: currentColor;
        }

        .kerem img {
            width: 42px;
        }

        .privacy-policy-page-link {
            display: none;
        }


        body.login {
            background: #f1f5f9 !important;
        }

        #loginform {
            border: none;
            box-shadow: 0 0px 5px 0 rgba(50, 53, 61, 0.1) !important;
            border-radius: .25rem;
        }

        .wp-core-ui .button-primary {
            transition: .1s;
            font-weight: 500 !important;
            border-radius: .5rem !important;
            padding: 0 2rem !important;
        }

        input[type=checkbox]:focus,
        input[type=color]:focus,
        input[type=date]:focus,
        input[type=datetime-local]:focus,
        input[type=datetime]:focus,
        input[type=email]:focus,
        input[type=month]:focus,
        input[type=number]:focus,
        input[type=password]:focus,
        input[type=radio]:focus,
        input[type=search]:focus,
        input[type=tel]:focus,
        input[type=text]:focus,
        input[type=time]:focus,
        input[type=url]:focus,
        input[type=week]:focus,
        select:focus,
        textarea:focus {
            border-color: #2271b1 !important;
            box-shadow: 0 0 0 1px #2271b1 !important;
            outline: 2px solid transparent;
        }

        .wp-hide-pw {
            color: #000000 !important;
        }

        .wp-core-ui .button,
        .wp-core-ui .button-secondary {
            color: #505050;
            border-color: #505050;
        }

        .login #backtoblog a:hover,
        .login #nav a:hover,
        .login h1 a:hover {
            color: #961313;
            text-decoration: underline;
        }

        .login #backtoblog a:focus,
        .login #nav a:focus,
        .login h1 a:focus {
            color: #590404
        }

        .login #backtoblog a,
        .login #nav a {
            color: #5e5050;
        }

        .login #backtoblog a:hover,
        .login #nav a:hover,
        .login h1 a:hover {
            color: #961313 !important;
        }

        .login .button.wp-hide-pw:focus {
            background: 0 0;
            border-color: #c43535;
            box-shadow: 0 0 0 1px #c43535;
            outline: 2px solid transparent;
        }

        .login .message,
        .login .notice,
        .login .success {
            box-shadow: 0 0px 5px 0 rgba(50, 53, 61, 0.1) !important;
            border-radius: .25rem;
        }

        a {
            color: #c90914;
        }
    </style>
<?php }
add_action('login_enqueue_scripts', 'ppwp_custom_login_logo');
function ppwp_custom_login_url()
{
    return home_url();
}
add_filter('login_headerurl', 'ppwp_custom_login_url');
function ppwp_login_logo_url_redirect()
{
    return get_home_url();
}
add_filter('login_headertitle', 'ppwp_login_logo_url_redirect');
add_filter('login_display_language_dropdown', '__return_false');
function custom_login_footer_text()
{ ?>
    <div class="kerem">
        <a href="http://kkerem.com" target="_blank">
            <img src="<?php bloginfo('template_url') ?>/img/kkerem.png" title="Kerem ER">
        </a>
    </div>
<?php }
add_action('login_footer', 'custom_login_footer_text');
function custom_admin_title($admin_title, $title)
{
    if (is_admin()) {
        $admin_title = get_bloginfo('name') . ' &lsaquo; ' . $title;
    }
    return $admin_title;
}
add_filter('admin_title', 'custom_admin_title', 10, 2);

function custom_login_title($login_title)
{
    // Replace "WordPress" with an empty string to remove it from the title
    $login_title = get_bloginfo('name');

    return $login_title;
}
add_filter('login_title', 'custom_login_title');
/* Custom WP Login */


function fb_opengraph()
{

    if (is_single()) {
        if (has_post_thumbnail(get_the_ID())) {
            $img_src = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'large')[0];
        } ?>
        <meta name="title" content="<?php echo the_title(); ?>" />
        <meta name="description" content="<?php echo strip_tags(mb_strimwidth(get_the_content(), 0, 300, '...')); ?>" />
        <meta name="datePublished" content="<?php echo get_post_time('c', false, get_the_ID()); ?>" />
        <meta name="dateModified" content="<?php echo get_the_modified_time('c', get_the_ID()); ?>" />
        <meta name="url" content="<?php echo the_permalink(); ?>" />
        <?php
        $degisken = apply_filters('mevzu_meta_degisken', 'news');
        // Kategoriye göre kontrol et
        if (!empty(get_the_category())) {
            foreach (get_the_category() as $category) {
                // print_r($category);
                if ($category->term_id == (get_option('options_kose_yazilari_kategorisi') ?: 1)) {
                    $degisken = 'columnist';
                    break;
                } elseif ($category->term_id == 24) {
                    $degisken = 'video';
                    break;
                }
            }
        }

        // Meta etiketini oluştur
        ?>
        <meta name="articleSection" content="<?php echo esc_attr($degisken); ?>">

        <?php
        if (get_post_meta(get_the_ID(), 'ajans', true)) {
            $yazar = get_post_meta(get_the_ID(), 'ajans', true);
        } else {
            $author_id = get_post_field('post_author', get_the_ID());
            $yazar = get_the_author_meta('display_name', $author_id);
        }
        ?>

        <meta name="articleAuthor" content="<?php echo esc_attr($yazar); ?>">


        <meta property="og:title" content="<?php echo the_title(); ?>" />
        <meta property="og:description" content="<?php echo strip_tags(mb_strimwidth(get_the_content(), 0, 300, '...')); ?>" />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="<?php echo the_permalink(); ?>" />
        <meta property="og:site_name" content="<?php echo get_bloginfo(); ?>" />
        <meta property="og:image" content="<?php if (!isset($img_src)) {
            $img_src = '';
        } else {
            echo $img_src;
        } ?>" />
    <?php }
}
add_action('wp_head', 'fb_opengraph', 5);


// Function to handle the thumbnail request
function get_the_post_thumbnail_src($img)
{
    return (preg_match('~\bsrc="([^"]++)"~', $img, $matches)) ? $matches[1] : '';
}
function wpvkp_social_buttons($content)
{
    global $post;
    $content = "";

    // $post'un obje olup olmadığını kontrol et
    if (!is_object($post)) {
        error_log('$post değişkeni doğru bir obje değil: ' . print_r($post, true));
        return $content;
    }

    // Sadece tekil yazılarda veya ana sayfada sosyal butonları göster
    if (is_singular() || is_home()) {
        // Permalink ve başlık kontrolü
        $sb_url = get_permalink($post);
        $sb_title = get_the_title($post);

        // Değerler geçerli mi kontrol et
        if (!is_string($sb_url)) {
            error_log('get_permalink() beklenmeyen bir değer döndürdü. $sb_url: ' . print_r($sb_url, true));
            return $content;
        }

        if (!is_string($sb_title)) {
            error_log('get_the_title() beklenmeyen bir değer döndürdü. $sb_title: ' . print_r($sb_title, true));
            return $content;
        }

        // URL ve başlık üzerinde işlemler
        $sb_url = urlencode($sb_url);
        $sb_title = str_replace(' ', '%20', $sb_title);

        // Sosyal medya URL'leri
        $twitterURL = 'https://twitter.com/intent/tweet?text=' . $sb_title . '&amp;url=' . $sb_url . '&amp;via=wpvkp';
        $facebookURL = 'https://www.facebook.com/sharer/sharer.php?u=' . $sb_url;
        $whatsappURL = 'whatsapp://send?text=' . $sb_title . ' ' . $sb_url;
        $telegramURL = 'https://t.me/share/url?url=' . $sb_url . '&text=' . $sb_title;

        // HTML İçeriği
        // $content .= '<span class="small pe-2 d-none d-lg-inline-block">Paylaş: </span>';
        $content .= '<div class="row align-items-center justify-content-between">';
        $content .= '<div class="col"><a class="facebook text-white d-block text-center rounded-3 p-2" href="' . $facebookURL . '" target="_blank" rel="nofollow">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" viewBox="0 0 512 512"><path fill="currentColor" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48c27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256"></path></svg>
        </a></div>';
        $content .= '<div class="col"><a class="twitter text-white d-block text-center rounded-3 p-2" href="' . $twitterURL . '" target="_blank" rel="nofollow">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" viewBox="0 0 16 16"><path fill="currentColor" d="M9.294 6.928L14.357 1h-1.2L8.762 6.147L5.25 1H1.2l5.31 7.784L1.2 15h1.2l4.642-5.436L10.751 15h4.05zM7.651 8.852l-.538-.775L2.832 1.91h1.843l3.454 4.977l.538.775l4.491 6.47h-1.843z"></path></svg>
        </a></div>';
        $content .= '<div class="col"><a class="whatsapp text-white d-block text-center rounded-3 p-2" href="' . $whatsappURL . '" target="_blank" rel="nofollow">
        <svg xmlns="http://www.w3.org/2000/svg" width="37.5" height="30" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222c0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222c0-59.3-25.2-115-67.1-157m-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4l-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2c0-101.7 82.8-184.5 184.6-184.5c49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6m101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18c-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4c-32.6-16.3-54-29.1-75.5-66c-5.7-9.8 5.7-9.1 16.3-30.3c1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5c-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8c35.2 15.2 49 16.5 66.6 13.9c10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6"></path></svg>
        </a></div>';
        $content .= '<div class="col"><a class="telegram text-white d-block text-center rounded-3 p-2" href="' . $telegramURL . '" target="_blank" rel="nofollow">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M2.148 11.81q7.87-3.429 10.497-4.522c4.999-2.079 6.037-2.44 6.714-2.452c.15-.003.482.034.698.21c.182.147.232.347.256.487s.054.459.03.708c-.27 2.847-1.443 9.754-2.04 12.942c-.252 1.348-.748 1.8-1.23 1.845c-1.045.096-1.838-.69-2.85-1.354c-1.585-1.039-2.48-1.686-4.018-2.699c-1.777-1.171-.625-1.815.388-2.867c.265-.275 4.87-4.464 4.96-4.844c.01-.048.021-.225-.084-.318c-.105-.094-.26-.062-.373-.036q-.239.054-7.592 5.018q-1.079.74-1.952.721c-.643-.014-1.88-.363-2.798-.662c-1.128-.367-2.024-.56-1.946-1.183q.061-.486 1.34-.994"/></svg>
        </a></div>';
        $content .= '
  </div>
  <h6 class="fw-semibold my-3 small">veya linki kopyala</h6>
  <div class="p-1 p-lg-3 border rounded">
      <div class="row align-items-center">
          <div class="col pe-0">
              <input type="text" name="" class="form-control border-0 bg-body-tertiary rounded-end-0 text-muted small" id="pageLink" value="" readonly="">
          </div>
          <div class="col-auto ps-0">
              <button onclick="copyPageUrl()" class="btn btn-primary text-white py-2 px-4" type="button" id="copyButton">
<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M13.569 4.25h.225c1.118 0 1.83 0 2.436.162a4.75 4.75 0 0 1 3.358 3.359c.162.605.162 1.317.162 2.435v1.624c0 .535 0 .98-.03 1.345c-.03.38-.098.736-.27 1.073a2.75 2.75 0 0 1-1.201 1.202c-.338.172-.694.24-1.074.27c-.364.03-.81.03-1.344.03H14.17c-.534 0-.98 0-1.345-.03c-.38-.03-.736-.098-1.073-.27a2.75 2.75 0 0 1-1.202-1.201c-.172-.338-.24-.694-.27-1.074c-.03-.365-.03-.81-.03-1.345V7.57c0-.524 0-.929.094-1.28a2.75 2.75 0 0 1 1.944-1.945c.352-.095.757-.094 1.281-.094m.098 1.5c-.668 0-.855.006-.99.043a1.25 1.25 0 0 0-.884.883c-.036.135-.043.322-.043.99V11.8c0 .572 0 .957.025 1.252c.023.288.065.425.111.516c.12.235.311.426.547.546c.09.046.227.088.515.111c.295.024.68.025 1.252.025h1.6c.572 0 .957 0 1.253-.025c.287-.023.424-.065.515-.111a1.25 1.25 0 0 0 .546-.546c.046-.091.088-.228.111-.515c.024-.296.025-.68.025-1.253v-1.467c0-.446 0-.798-.006-1.083H16.5a1.75 1.75 0 0 1-1.75-1.75V5.756a62 62 0 0 0-1.083-.006M16.25 6v1.5c0 .138.112.25.25.25H18A3.25 3.25 0 0 0 16.25 6"/><path fill="currentColor" d="M8.496 8.25H8.5a.75.75 0 1 1 0 1.5c-.602 0-1.214-.005-1.812.084c-.587.087-.869.572-.913 1.114c-.024.295-.025.68-.025 1.252v3.6c0 .572 0 .957.025 1.252c.054.664.481 1.117 1.172 1.173c.296.024.68.025 1.253.025h1.6c.525 0 1.275.114 1.768-.136c.424-.216.668-.647.682-1.115a.76.76 0 0 1 .75-.749c.415 0 .754.356.75.767c-.024 1.02-.583 1.965-1.501 2.433c-.729.371-1.627.3-2.419.3H8.17c-.792 0-1.69.071-2.418-.3a2.75 2.75 0 0 1-1.202-1.2c-.172-.338-.24-.694-.27-1.074c-.03-.365-.03-.81-.03-1.345v-3.66c0-.535 0-.98.03-1.345c.08-.973.582-1.822 1.472-2.275c.44-.225.99-.244 1.475-.272c.507-.028 1.019-.028 1.27-.028" opacity="0.5"/></svg>
              </button>
          </div>
      </div>';
        $content .= '</div>';

        $content .= '<script>
												document.addEventListener("DOMContentLoaded", function() {
													var pageLink = window.location.href;
													document.getElementById("pageLink").value = pageLink;

													document.getElementById("copyButton").addEventListener("click", function() {
														var copyText = document.getElementById("pageLink");
														copyText.select();
														copyText.setSelectionRange(0, 99999); // Mobil cihazlar için
														navigator.clipboard.writeText(copyText.value).then(function() {
															alert("Link kopyalandı: " + copyText.value);
														}).catch(function(error) {
															alert("Kopyalama başarısız: " + error);
														});
													});
												});
											</script>';
    }

    return $content;
}

add_shortcode('social', 'wpvkp_social_buttons');


add_filter('comment_form_fields', 'move_comment_field');
function move_comment_field($fields)
{
    $comment_field = $fields['comment'];
    unset($fields['comment']);
    $fields['comment'] = $comment_field;
    return $fields;
}

function saat()
{
    return human_time_diff(get_comment_time('U'), current_time('timestamp')) . ' önce';
}


// 5 dakikalık bir interval ekle
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300, // 300 saniye = 5 dakika
        'display' => __('Every 5 Minutes'),
    );
    return $schedules;
});








function default_featured_image($content)
{
    global $post;
    if (empty($content) && has_post_thumbnail($post->ID)) {
        $img_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
        $img_src = $img_src[0];
        $content = '<img src="' . $img_src . '" alt="' . get_the_title() . '" />';
    } elseif (empty($content)) {
        // Varsayılan görsel URL'si
        $default_image_url = 'https://bolgeninsesigazetesi.com/wp-content/uploads/2023/09/resimsiz-jpg.webp';
        $content = '<img src="' . $default_image_url . '" alt="' . get_the_title() . '" />';
    }
    return $content;
}
add_filter('the_content', 'default_featured_image');

function display_havadurumu_temperature($sablon = 'sablon-2')
{
    $city_raw = mevzu_get_current_city();
    $city = turkce_karakter($city_raw);

    $w_data = get_weather_data($city);
    $icon = $w_data['weather'][0]['icon'] ?? '01d';
    $desc = ucfirst($w_data['list'][0]['weather'][0]['description'] ?? '-');
    $temp = round($w_data['list'][0]['temp']['max'] ?? 0);

    $icon_url = get_template_directory_uri() . '/img/assets/havalar/' . $icon . '.svg';

    switch($sablon) {
        case 'sablon1':
            $sablon_deger = ' bg-light border rounded-3 ps-1 ms-3';
            break;
        case 'sablon2':
            $sablon_deger = '';
            break;
        case 'sablon3':
            $sablon_deger = '';
            break;
        default:
            $sablon_deger = '';
            break;
    }
    ?>
    <div class="header-havadurumu d-flex align-items-center<?=$sablon_deger?>">
        <div class="row align-items-center justify-content-center small mx-0" title="<?php echo esc_attr($desc); ?>">
            <div class="col-auto px-1 text-end">
                <a href="<?php bloginfo('url') ?>/hava-durumu" class="d-block" alt="Hava Durumu">
                    <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($desc); ?>" width="28" height="28">
                </a>
            </div>
            <div class="col-auto ps-2 pe-0 fw-semibold">
                <?php echo $temp; ?>°
            </div>
            <div class="col-auto h-100 pe-1">
                <select id="havaDurumuSehirSelect" class="form-select" style="height: auto;">
                    <?php foreach(sehirler() as $s): 
                        $s_name = turkce_karakter($s['name']);
                        $is_selected = ($s_name == $city) ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr($s_name); ?>" <?php echo $is_selected; ?>><?php echo esc_html(mb_convert_case($s['name_tr'], MB_CASE_TITLE, "UTF-8")); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function initHavaSelect2() {
            var $select = jQuery('#havaDurumuSehirSelect');
            if ($select.length && typeof jQuery.fn.select2 !== 'undefined') {
                $select.select2({
                    theme: 'bootstrap-5',
                    minimumResultsForSearch: 3
                }).on('change', function() {
                    var secilen = jQuery(this).val();
                    var d = new Date();
                    d.setTime(d.getTime() + (365*24*60*60*1000));
                    var expires = "expires="+ d.toUTCString();
                    document.cookie = "mevzu_hava_sehir=" + secilen + ";" + expires + ";path=/";
                    window.location.reload();
                });
            } else if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 === 'undefined') {
                jQuery.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function(){
                    initHavaSelect2();
                });
            }
        }
        if (typeof jQuery !== 'undefined') {
           initHavaSelect2();
        }
    });
    </script>
    <?php
}


add_action('acf/init', 'my_acfe_modules');
function my_acfe_modules()
{

    // Disable Multilingual
    acf_update_setting('acfe/modules/multilang', false);

}












/**
 * Dashboard Haber Yazma İstatistikleri
 */

function mevzu_add_dashboard_stats_widget() {
    $allowed_roles = array('administrator', 'editor', 'author');
    if (array_intersect($allowed_roles, (array)wp_get_current_user()->roles)) {
        wp_add_dashboard_widget(
            'mevzu_post_statistics',
            'Haber ve Yazı İstatistikleri',
            'mevzu_render_dashboard_stats'
        );

    }
}
add_action('wp_dashboard_setup', 'mevzu_add_dashboard_stats_widget');



function mevzu_render_dashboard_stats() {
    $stats = get_transient('mevzu_dashboard_stats_daily');
    // delete_transient('mevzu_dashboard_stats_daily');
    
    if (false === $stats) {
        $stats = array(
            'Editör'   => get_mevzu_user_stats_by_role('editor'),
            'Yazar'    => get_mevzu_user_stats_by_role('author'),
        );
        set_transient('mevzu_dashboard_stats_daily', $stats, mevzu_get_seconds_until_02am());
    }

    echo '<div class="mevzu-stats">';
    foreach ($stats as $role_label => $users) {
        if (empty($users)) continue;
        $class = ($role_label === 'Editör') ? ' border-bottom mb-3' : '';
        $class2 = ($role_label === 'Editör') ? ' mb-2' : ' mb-0';
        echo '<div class="table-responsive' . $class . '">';
        echo '<table class="table table-sm align-middle small text-center shadow-none' . $class2 . '">';
        echo '<thead><tr class="text-muted fw-normal"><th style="width:42%;" class="ps-0 pt-0 text-start">' . esc_html($role_label) . '</th><th class="pt-0">Bu Hafta</th><th class="pt-0">Geçen Hafta</th><th class="pt-0 pe-0">Bu Ay</th></tr></thead>';
        echo '<tbody>';
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td class="ps-0 text-start"><span class="d-block" title="' . esc_attr($user['display_name']) . '">' . esc_html($user['display_name']) . '</span></td>';
            echo '<td>' . intval($user['this_week']) . '</td>';
            echo '<td>' . intval($user['last_week']) . '</td>';
            echo '<td>' . intval($user['this_month']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
    echo '<div class="text-end mt-0">';
    echo "<small class=\"text-muted fz-10\">* Veriler her gece 02:00'da güncellenmektedir.</small>";
    echo '</div>';
    echo '</div>';
}

function get_mevzu_user_stats_by_role($role) {
    $users = get_users(array('role' => $role, 'number' => 20)); // Her rolden ilk 20 aktif kullanıcı
    $results = array();

    // Zaman sınırları
    $this_week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
    
    $last_week_start = date('Y-m-d 00:00:00', strtotime('monday last week'));
    $last_week_end   = date('Y-m-d 23:59:59', strtotime('sunday last week'));
    
    $this_month_start = date('Y-m-01 00:00:00');

    foreach ($users as $user) {
        $this_week  = get_mevzu_post_count_timerange($user->ID, $this_week_start);
        $last_week  = get_mevzu_post_count_timerange($user->ID, $last_week_start, $last_week_end);
        $this_month = get_mevzu_post_count_timerange($user->ID, $this_month_start);

        $results[] = array(
            'display_name' => $user->display_name,
            'this_week'    => $this_week,
            'last_week'    => $last_week,
            'this_month'   => $this_month,
        );
    }
    
    // Haberi çok olanı başa al
    usort($results, function($a, $b) {
        return ($b['this_week'] + $b['last_week'] + $b['this_month']) <=> ($a['this_week'] + $a['last_week'] + $a['this_month']);
    });

    return $results;
}

function get_mevzu_post_count_timerange($user_id, $after, $before = '') {
    $args = array(
        'author'      => $user_id,
        'post_type'   => array('post', 'yazilar'),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields'      => 'ids',
        'no_found_rows' => false,
        'date_query'  => array(
            array(
                'after'     => $after,
                'before'    => $before,
                'inclusive' => true,
            ),
        ),
    );
    $q = new WP_Query($args);
    return $q->found_posts;
}

/**
 * Hoş Geldiniz Paneli Görünürlüğü
 */
function mevzu_force_show_welcome_panel() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) return;
    // WordPress welcome panel user_meta 1 = göster, 0 = gizle
    if ( (int) get_user_meta( $user_id, 'show_welcome_panel', true ) !== 1 ) {
        update_user_meta( $user_id, 'show_welcome_panel', 1 );
    }
}
add_action('admin_init', 'mevzu_force_show_welcome_panel');

function mevzu_render_welcome_panel_for_all() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'dashboard') return;
    
    echo '<div id="welcome-panel" class="welcome-panel" style="display:block !important; margin-bottom: 20px;">';
    if (function_exists('custom_welcome_panel')) {
        custom_welcome_panel();
    }
    echo '</div>';
    echo '<style>.metabox-holder { padding-top: 0; }</style>';
}
// wpacg_reset_all_dashboard_meta(); // Bir kere elle çalıştır, sonra yoruma al.

/**
 * Popüler Haberler ve Kategoriler Dashboard Widget'ları
 */
function mevzu_add_popular_dashboard_widgets() {
    wp_add_dashboard_widget('mevzu_popular_posts', 'Haber Tıklanma İstatistikleri', 'mevzu_render_popular_posts_widget');
    wp_add_dashboard_widget('mevzu_popular_categories', 'Kategori Tıklanma İstatistikleri', 'mevzu_render_popular_categories_widget');
}
add_action('wp_dashboard_setup', 'mevzu_add_popular_dashboard_widgets');

function mevzu_dashboard_widgets_js() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'dashboard' ) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.querySelector(btn.getAttribute('data-bs-target'));
                if (!target) return;
                // Aynı tab-content içindeki tüm panelleri kapat
                target.closest('.tab-content').querySelectorAll('.tab-pane').forEach(function (p) {
                    p.classList.remove('show', 'active');
                });
                // Aynı nav içindeki tüm butonları pasif yap
                btn.closest('.nav').querySelectorAll('.nav-link').forEach(function (b) {
                    b.classList.remove('active');
                });
                target.classList.add('show', 'active');
                btn.classList.add('active');
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'mevzu_dashboard_widgets_js');

/**
 * 02:00 AM'e kadar kalan süreyi hesaplayan helper
 */
function mevzu_get_seconds_until_02am() {
    $now = time();
    $today_02am = strtotime('02:00:00');
    
    // Eğer saat 2:00'yi geçtiyse, hedef yarın 2:00
    if ($now >= $today_02am) {
        $target = strtotime('tomorrow 02:00:00');
    } else {
        $target = $today_02am;
    }
    
    return $target - $now;
}

/**
 * Popüler Haberler Verisi (Helper)
 */
function mevzu_get_popular_posts_data($days = 7) {
    $transient_key = 'mevzu_pop_posts_' . $days . 'd';
    $data = get_transient($transient_key);

    if (false === $data) {
        $args = array(
            'post_type'      => array('post', 'yazilar'),
            'posts_per_page' => 10,
            'meta_key'       => 'views_count',
            'meta_value'     => '0',
            'meta_compare'   => '>',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'date_query'     => array(
                array('after' => $days . ' days ago')
            ),
        );
        $query = new WP_Query($args);
        $data  = $query->posts ?: [];
        set_transient($transient_key, $data, mevzu_get_seconds_until_02am());
    }
    return $data;
}

/**
 * Popüler Kategoriler Verisi (Helper)
 */
function mevzu_get_popular_categories_data($days = 30) {
    $transient_key = 'mevzu_pop_cats_' . $days . 'd';
    $data = get_transient($transient_key);
    // delete_transient($transient_key);
    
    if (false === $data) {
        $excluded_cat_id = (int) get_option('manset_secili_kategori');
        $args = array(
            'post_type'      => array('post', 'yazilar'),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => 'views_count',
            'meta_value'     => '0',
            'meta_compare'   => '>',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'date_query'     => array(
                array('after' => $days . ' days ago')
            ),
        );
        $posts = get_posts($args);
        $cat_views = array();

        foreach ($posts as $post_id) {
            $views = (int) get_post_meta($post_id, 'views_count', true) ?: 0;
            $cats = get_the_category($post_id);
            if ($cats) {
                foreach ($cats as $cat) {
                    if (($excluded_cat_id > 0 && (int)$cat->term_id === $excluded_cat_id) || 
                        mb_strtolower($cat->name, 'UTF-8') === 'manşet') {
                        continue;
                    }
                    if (!isset($cat_views[$cat->term_id])) {
                        $cat_views[$cat->term_id] = array('id' => $cat->term_id, 'name' => $cat->name, 'views' => 0);
                    }
                    $cat_views[$cat->term_id]['views'] += $views;
                }
            }
        }
        uasort($cat_views, function($a, $b) { return $b['views'] <=> $a['views']; });
        $data = array_slice($cat_views, 0, 10, true);
        set_transient($transient_key, $data, mevzu_get_seconds_until_02am());
    }
    return $data;
}

function mevzu_render_popular_posts_widget() {
    $periods = array(
        '7'  => 'Haftalık',
        '14' => '15 Günlük',
        '30' => 'Aylık'
    );
    ?>
    <ul class="nav nav-pills nav-fill fz-13 gap-3 mt-0" id="pop-posts-tabs" role="tablist">
        <?php foreach ($periods as $days => $label): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 mt-0 w-100 small text-uppercase <?php echo $days == '7' ? 'active' : ''; ?>" id="posts-tab-<?php echo $days; ?>" data-bs-toggle="pill" data-bs-target="#posts-content-<?php echo $days; ?>" type="button" role="tab"><?php echo $label; ?></button>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="tab-content" id="pop-posts-content">
        <?php foreach ($periods as $days => $label): 
            $popular_posts = mevzu_get_popular_posts_data($days);
            ?>
            <div class="tab-pane fade <?php echo $days == '7' ? 'show active' : ''; ?>" id="posts-content-<?php echo $days; ?>" role="tabpanel">
                <?php if (!empty($popular_posts)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle fz-13 mb-0 shadow-none">
                            <thead>
                                <tr class="text-muted fw-normal small">
                                    <th class="text-start py-0 fw-normal" style="width:82%;">Haber</th>
                                    <th class="text-end py-0 fw-normal">Tıklama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_posts as $post): 
                                    $views = get_post_meta($post->ID, 'views_count', true) ?: 0;
                                    $trimmed_title = mb_strimwidth($post->post_title, 0, 45, "...");
                                ?>
                                    <tr>
                                        <td><a class="text-decoration-none text-link-hover fw-normal" href="<?php echo get_permalink($post->ID); ?>" target="_blank" title="<?php echo esc_attr($post->post_title); ?>"><?php echo esc_html($trimmed_title); ?></a></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($views); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted p-2 fz-12">Bu dönem için henüz yeterli veri yok.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-end mt-0">
        <small class="text-muted fz-10">* Veriler her gece 02:00'da güncellenmektedir.</small>
    </div>
    <?php
}

function mevzu_render_popular_categories_widget() {
    $periods = array(
        '7'  => 'Haftalık',
        '14' => '15 Günlük',
        '30' => 'Aylık'
    );
    ?>
    <ul class="nav nav-pills nav-fill fz-13 gap-3 mt-0" id="pop-cats-tabs" role="tablist">
        <?php foreach ($periods as $days => $label): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 mt-0 w-100 small text-uppercase <?php echo $days == '7' ? 'active' : ''; ?>" id="cats-tab-<?php echo $days; ?>" data-bs-toggle="pill" data-bs-target="#cats-content-<?php echo $days; ?>" type="button" role="tab"><?php echo $label; ?></button>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="tab-content" id="pop-cats-content">
        <?php foreach ($periods as $days => $label): 
            $popular_cats = mevzu_get_popular_categories_data($days);
            ?>
            <div class="tab-pane fade <?php echo $days == '7' ? 'show active' : ''; ?>" id="cats-content-<?php echo $days; ?>" role="tabpanel">
                <?php if (!empty($popular_cats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle fz-13 mb-0 shadow-none">
                            <thead>
                                <tr class="text-muted fw-normal small">
                                    <th class="text-start py-0 fw-normal" style="width:70%;">Kategori</th>
                                    <th class="text-end py-0 fw-normal">Toplam Tıklama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_cats as $cat): 
                                    $cat_url = get_category_link($cat['id']);
                                ?>
                                    <tr>
                                        <td><a class="text-decoration-none text-link-hover fw-normal" href="<?php echo esc_url($cat_url); ?>" target="_blank"><?php echo esc_html($cat['name']); ?></a></td>
                                        <td class="text-end fw-semibold"><?php echo number_format($cat['views']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted p-2 fz-12">Bu dönem için henüz yeterli veri yok.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-end mt-0">
        <small class="text-muted fz-10">* Veriler her gece 02:00'da güncellenmektedir.</small>
    </div>
    <?php
}

function remove_wp_events_and_news_widget()
{
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('themeisle', 'dashboard', 'side');
    remove_meta_box('aioseo-overview', 'dashboard', 'side');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
}
add_action('wp_dashboard_setup', 'remove_wp_events_and_news_widget');

/**
 * Yazı düzenleme ekranındaki gereksiz bileşenleri kaldırır
 */
function mevzu_remove_default_post_metaboxes()
{
    // Post Type Support'ları kaldır (Gutenberg panellerini de etkiler)
    // remove_post_type_support('post', 'comments');
    // remove_post_type_support('post', 'trackbacks');
    // remove_post_type_support('post', 'excerpt');
    // remove_post_type_support('post', 'author');
    
    remove_post_type_support('page', 'comments');
    // remove_post_type_support('page', 'author');

    // Metaboxları kaldır (Klasik Editör ve diğer alanlar için)
    $boxes = array('slugdiv', 'commentstatusdiv', 'commentsdiv', 'postcustom', 'trackbacksdiv', 'postexcerpt');
    
    foreach ($boxes as $box) {
        remove_meta_box($box, 'post', 'normal');
        remove_meta_box($box, 'page', 'normal');
        remove_meta_box($box, 'post', 'side');
        remove_meta_box($box, 'page', 'side');
    }
}
add_action('admin_init', 'mevzu_remove_default_post_metaboxes', 999);
add_action('add_meta_boxes', 'mevzu_remove_default_post_metaboxes', 999);

function custom_welcome_panel()
{
    ?>
    <div class="merhaba welcome-panel-content">
        <div class="welcome-panel-header position-relative rounded-5 overflow-hidden">
            <svg style="position:absolute;bottom: -37px;left: 0;" id="visual" viewBox="0 0 1824 60" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"><path d="M0 56L43.5 50.8C87 45.7 174 35.3 260.8 28.7C347.7 22 434.3 19 521.2 24.3C608 29.7 695 43.3 781.8 40.7C868.7 38 955.3 19 1042.2 19.3C1129 19.7 1216 39.3 1302.8 42C1389.7 44.7 1476.3 30.3 1563.2 29C1650 27.7 1737 39.3 1780.5 45.2L1824 51L1824 61L1780.5 61C1737 61 1650 61 1563.2 61C1476.3 61 1389.7 61 1302.8 61C1216 61 1129 61 1042.2 61C955.3 61 868.7 61 781.8 61C695 61 608 61 521.2 61C434.3 61 347.7 61 260.8 61C174 61 87 61 43.5 61L0 61Z" fill="#f2f5f9" stroke-linecap="round" stroke-linejoin="miter"></path></svg>
            <div class="px-4 pt-4 pb-5">
                <div class="row">
                    <div class="col col-md-3 small">
                        <div class="d-flex gap-2 align-items-center">
                            <a href="http://kkerem.com" target="_blank">
                                <img src="<?php bloginfo('template_url') ?>/img/kkerem.png" title="Kerem ER" width="30">
                            </a>
                            <div class="text">
                                <div class="m-0 mb-1 fw-normal">Geliştirici: <span class="fw-semibold">Kerem ER</span></div>
                                <div class="m-0 mb-1 fw-normal small">Sürüm: <span class="fw-semibold">v<?php echo _S_VERSION; ?></span></div>
                            </div>
                        </div>
                    </div>
                    <?php if(get_opt_img('options_logo')) : ?>
                    <div class="col-md mx-auto d-none d-md-flex text-center align-items-center justify-content-center">
                        <a href="<?php bloginfo('url'); ?>" target="_blank">
                            <img src="<?php echo get_opt_img('options_logo') ?>" alt="Mevzu2" height="40px">
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-auto col-md-3 ms-auto">
                        <div class="d-inline-block p-2 rounded-3 bg-light float-md-end fs-6">
                            <span class="fw-semibold"><b class="text-primary">:</b>mevzu<b class="text-primary fw-bold">²</b></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function replace_welcome_panel()
{
    remove_action('welcome_panel', 'wp_welcome_panel'); // Varsayılan paneli kaldır
    add_action('welcome_panel', 'custom_welcome_panel'); // Özelleştirilmiş paneli ekle
}
add_action('admin_init', 'replace_welcome_panel');










// Changelog sayfası


// Markdown'ı HTML'e dönüştüren fonksiyon
function parse_markdown($markdown)
{
    // Parsedown kütüphanesini yükle
    require_once 'inc/Parsedown.php'; // Parsedown.php dosyasını projenize ekleyin
    $parsedown = new Parsedown();
    return $parsedown->text($markdown);
}














// function custom_upload_filename($file) {
//     $info = pathinfo($file['name']);
//     $ext = !empty($info['extension']) ? '.' . $info['extension'] : '';

//     $unique_id = uniqid();

//     $file['name'] = $unique_id . $ext;

//     return $file;
// }
// add_filter('wp_handle_upload_prefilter', 'custom_upload_filename');




function formatTurkishPhoneNumber($phoneNumber)
{
    // Sadece rakamları al
    $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

    // Türkiye telefon numarası için 11 haneli olmalı
    if (strlen($phoneNumber) === 11 && substr($phoneNumber, 0, 1) === '0') {
        $phoneNumber = substr($phoneNumber, 1); // 0'ı çıkar
    }

    // Formatı uygula: (ddd) ddd-dddd
    if (strlen($phoneNumber) === 10) {
        $formattedNumber = sprintf(
            '(%s) %s-%s',
            substr($phoneNumber, 0, 3),
            substr($phoneNumber, 3, 3),
            substr($phoneNumber, 6, 4)
        );
        return $formattedNumber;
    }

    return 'Geçersiz numara'; // Numara geçerli değilse
}


// Editörlere yazar değiştirme yetkisi verir
function allow_editor_edit_author($roles)
{
    // Editör yetkisi olan kullanıcıların rolleri
    $roles = array('editor');

    // Rol kontrolü
    foreach ($roles as $role_name) {
        $role = get_role($role_name);

        if (!empty($role)) {
            // 'edit_others_posts' yetkisi zaten var, bu yüzden 'edit_post' yetkisini de veriyoruz
            $role->add_cap('edit_others_posts');
            $role->add_cap('edit_post');
        }
    }
}
add_action('admin_init', 'allow_editor_edit_author');

// Yazar meta kutusunu editörlere açar
function allow_editors_edit_authors()
{
    if (current_user_can('editor') && !current_user_can('edit_others_posts')) {
        $editor = get_role('editor');
        $editor->add_cap('edit_others_posts');
    }
}
add_action('admin_init', 'allow_editors_edit_authors');

// Yazı düzenleme ekranında yazar seçeneğini gösterir
function show_author_meta_box()
{
    if (current_user_can('editor')) {
        add_meta_box('authordiv', __('Author'), 'post_author_meta_box', null, 'normal', 'core');
    }
}
add_action('add_meta_boxes', 'show_author_meta_box');


// do_action('fetch_pharmacy_data_event');


function add_custom_category_to_glance()
{
    $category_id = get_option('options_koseyazilari_kategorisi');
    if ($category_id) {
        $num_posts_in_category = get_term_by('id', $category_id, 'category')->count;
        $text = _n('%s Köşe Yazısı', '%s Köşe Yazısı', $num_posts_in_category, 'mevzu');
        if (current_user_can('edit_posts')) { // Kullanıcının düzenleme izni varsa
            $num = sprintf('<a href="edit.php?cat=%1$s">%2$s %3$s</a>', $category_id, $num_posts_in_category, 'Köşe Yazısı');
        } else {
            $num = sprintf('<span>%1$s %2$s</span>', $num_posts_in_category, 'Köşe Yazısı');
        }
        echo '<li class="post-count yazilar-count category-' . $category_id . '-count">' . $num . '</li>';
    }
}
add_filter('dashboard_glance_items', 'add_custom_category_to_glance');




/* Kose yazilari menu */
function add_kose_yazilari_menu()
{
    $cat_id = get_option('options_kose_yazilari_kategorisi') ?: 1;
    $cat = get_category($cat_id);
    $cat_slug = (!is_wp_error($cat) && !empty($cat)) ? $cat->slug : 'uncategorized';
    $menu_slug = 'edit.php?category_name=' . $cat_slug;

    add_menu_page(
        'Köşe Yazıları',              // Sayfa Başlığı
        'Köşe Yazıları',              // Menüde Görünen İsim
        'edit_posts',                 // Yetki
        $menu_slug,                   // URL
        '',                           // Fonksiyon (boş çünkü yönlendirme yapıyoruz)
        'dashicons-edit',             // İkon
        5                             // Menüde Görünme Sırası
    );
    add_submenu_page(
        $menu_slug,                   // Üst menü slug
        'Tüm Yazılar',                // Sayfa Başlığı
        'Tüm Yazılar',                // Alt Menüde Görünen İsim
        'edit_posts',                 // Yetki
        $menu_slug                    // URL
    );
    add_submenu_page(
        $menu_slug,                   // Üst menü slug
        'Yeni Yazı Ekle',             // Sayfa Başlığı
        'Yeni Yazı Ekle',             // Alt Menüde Görünen İsim
        'edit_posts',                 // Yetki
        'post-new.php?category_id=' . $cat_id // Yeni Yazı Ekleme Sayfası URL'si
    );
}
add_action('admin_menu', 'add_kose_yazilari_menu');
function set_kose_yazilari_active_menu($parent_file)
{
    $screen = get_current_screen();
    $target_id = (get_option('options_kose_yazilari_kategorisi') ?: 1);
    $cat = get_category($target_id);
    $target_slug = (!is_wp_error($cat) && !empty($cat)) ? $cat->slug : 'kose-yazilari';

    $is_category_link = (
        (isset($_GET['category_name']) && $_GET['category_name'] == $target_slug) || 
        (isset($_GET['category_id']) && $_GET['category_id'] == $target_id)
    );

    if ($is_category_link || ($screen->base == 'post' && isset($_GET['post_type']) && $_GET['post_type'] == 'post')) {
        $parent_file = 'edit.php?category_name=' . $target_slug;
    }
    return $parent_file;
}
add_filter('parent_file', 'set_kose_yazilari_active_menu');
function auto_select_gutenberg_category() 
{
    $screen = get_current_screen();
    if (!$screen || ($screen->base != 'post' && $screen->base != 'post-new')) return;

    $cat_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    if (!$cat_id && isset($_GET['category_name'])) {
        $cat = get_category_by_slug($_GET['category_name']);
        if ($cat) $cat_id = $cat->term_id;
    }
    if (!$cat_id) return;

    ?>
    <script type="text/javascript">
        (function() {
            var targetId = <?php echo intval($cat_id); ?>;
            var attempts = 0;
            console.log('Mevzu Debug: Başlatıldı. Hedef ID:', targetId);

            var interval = setInterval(function() {
                attempts++;
                
                // 1. Gutenberg Kontrolü
                if (window.wp && wp.data && wp.data.select && wp.data.dispatch) {
                    var editorSelect = wp.data.select('core/editor');
                    var editorDispatch = wp.data.dispatch('core/editor');
                    
                    if (editorSelect && editorSelect.getCurrentPostType() && editorDispatch) {
                        var currentCats = editorSelect.getEditedPostAttribute('categories');
                        
                        // Eğer kategori listesi bir dizi olarak hazırsa
                        if (Array.isArray(currentCats)) {
                            if (!currentCats.includes(targetId)) {
                                var newCats = [...currentCats, targetId];
                                editorDispatch.editPost({ categories: newCats });
                                console.log('Mevzu Debug: Gutenberg kategorisi seçildi.');
                            } else {
                                console.log('Mevzu Debug: Kategori zaten seçili.');
                            }
                            clearInterval(interval);
                            return;
                        }
                    }
                }

                // 2. Klasik Editör Kontrolü
                // Hem tireli hem tiresiz hem de popüler kategoriler tabındaki checkboxları tara
                var selectors = [
                    '#in-category-' + targetId,
                    '#in-category-' + targetId + '-2',
                    '#in-popular-category-' + targetId,
                    '#categorychecklist input[value="' + targetId + '"]',
                    '#category-checklist input[value="' + targetId + '"]'
                ];
                
                var cb = null;
                for (var s of selectors) {
                    cb = document.querySelector(s);
                    if (cb) break;
                }

                if (cb) {
                    if (!cb.checked) {
                        cb.click(); // Sadece checked yapmak yerine tıklama simüle et
                        cb.checked = true; // Garantiye al
                        console.log('Mevzu Debug: Klasik kategori işaretlendi.');
                    } else {
                        console.log('Mevzu Debug: Kategori zaten işaretli.');
                    }
                    clearInterval(interval);
                    return;
                }

                if (attempts > 60) { // 30 saniye deneme
                    console.error('Mevzu Debug: Kategori seçilemedi, zaman aşımı.');
                    clearInterval(interval);
                }
            }, 500);
        })();
    </script>
    <?php
}
add_action('admin_footer', 'auto_select_gutenberg_category', 999);
/* Kose yazilari menu */


function kose_yazilari_rewrite_rule()
{
    add_rewrite_rule(
        '^yazilar/([^/]*)/?',
        'index.php?post_type=post&name=$matches[1]',
        'top'
    );
}
add_action('init', 'kose_yazilari_rewrite_rule');

function add_kose_yazilari_permalink($permalink, $post)
{
    if ($post->post_type == 'post') {
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            // Eğer kategori ID'si 115 ise, permalink'i 'yazilar' ile değiştir
            if ($category->term_id == (get_option('options_kose_yazilari_kategorisi') ?: 1)) {
                return home_url('/yazilar/' . $post->post_name . '/');
            }
        }
    }
    return $permalink;
}
add_filter('post_link', 'add_kose_yazilari_permalink', 10, 2);




function custom_theme_setup()
{
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'custom_theme_setup');

function custom_post_block_template()
{
    $cat_id = get_option('options_kose_yazilari_kategorisi') ?: 1;
    $cat = get_category($cat_id);
    $cat_slug = (!is_wp_error($cat) && !empty($cat)) ? $cat->slug : 'kose-yazilari';

    if (is_admin() && (!isset($_GET['category_name']) || $_GET['category_name'] != $cat_slug)) {
        $post_type_object = get_post_type_object('post');
        if ($post_type_object) {
            $post_type_object->template = array(
                array(
                    'core/heading',
                    array(
                        'level' => 2,
                        'placeholder' => __('Alt başlık', 'custom'),
                        'className' => 'ust-baslik'
                    )
                ),
                array(
                    'core/post-featured-image',
                    array(
                        'className' => 'cikarilmis-gorsel'
                    )
                ),
                array(
                    'core/paragraph',
                    array(
                        'placeholder' => __('Haber içeriği buraya', 'custom'),
                    )
                ),
            );
        } else {
            error_log('Post type "post" bulunamadı.');
        }
    }
}


add_action('init', 'custom_post_block_template');

// function disable_png_uploads( $mimes ) {
//     // .png dosya uzantısını mime türlerinden çıkarıyoruz
//     unset( $mimes['png'] );
//     return $mimes;
// }
// add_filter( 'upload_mimes', 'disable_png_uploads' );




/* INDEX AJAX TABLOLARI */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('ajax-tabs', get_template_directory_uri() . '/js/ajax-tabs.js', ['jquery'], null, true);
    wp_enqueue_script('mevzu-player', get_template_directory_uri() . '/js/mevzu-player.js', [], null, true);
    wp_localize_script('ajax-tabs', 'ajax_tabs', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});

add_action('wp_ajax_load_tab_posts', 'load_tab_posts');
add_action('wp_ajax_nopriv_load_tab_posts', 'load_tab_posts');

function load_tab_posts()
{
    if (empty($_POST['category_id']) || $_POST['category_id'] == "")
        $category_id = 5686;
    else
        $category_id = intval($_POST['category_id']); // AJAX'tan gelen kategori ID'si
    $haber_sayisi = isset($_POST['haber_sayisi']) ? intval($_POST['haber_sayisi']) : 12;
    $haber_sayisi = ( $haber_sayisi > 0 && $haber_sayisi <= 50 ) ? $haber_sayisi : 12;

    $query_args = [
        'post_type'      => 'post',
        'posts_per_page' => $haber_sayisi,
        'post_status'    => array('publish'),
        'cat'            => $category_id,
        'meta_key'       => '_thumbnail_id',
    ];

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        echo '<div class="row g-3">';
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <div class="col-12 col-md-3 mb-1">
                <div class="bg-white shadow-sm rounded-3 h-100">
                    <a href="<?php the_permalink(); ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
                        <?php if (get_post_thumbnail_id()): ?>
                            <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading' => 'lazy']); ?>
                        <?php else: ?>
                            <?php echo m_default(NULL); ?>
                        <?php endif; ?>
                        <h3 class="m-2 satir-2"><?php the_title(); ?></h3>
                    </a>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        ?>
        <?php /*<div class="text-center">
            <a id="view-all-link" href="<?php echo get_permalink($category_id); ?>"
                class="btn btn-outline-dark d-inline-block px-md-4 py-2">Tüm <?php echo get_cat_name($category_id); ?> haberlerini göster</a>
        </div>*/ ?>
        <?php
    }
    wp_reset_postdata();
    wp_die(); // AJAX çağrısını sonlandır
}

/* INDEX AJAX TABLOLARI */


/* BILESEN: SOSYAL MEDYA */
class Mevzu_Bize_Katilin_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'mevzu_bize_katilin',
            __('Mevzu² — Bizi Takip Edin', 'mevzu2'),
            array('description' => __('Sosyal medya bağlantılarınızı gösterir.', 'mevzu2'))
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = !empty($instance['title']) ? $instance['title'] : __('Bizi Takip Edin', 'mevzu2');

        $social_links = array(
            'Facebook' => array(
                'link' => get_option('options_facebook'),
                'class' => 'bg-facebook',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="20" viewBox="0 0 512 512"><path fill="currentColor" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48c27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256"/></svg>',
            ),
            'Twitter' => array(
                'link' => get_option('options_twitter'),
                'class' => 'bg-twitter',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="20" viewBox="0 0 16 16"><path fill="currentColor" d="M9.294 6.928L14.357 1h-1.2L8.762 6.147L5.25 1H1.2l5.31 7.784L1.2 15h1.2l4.642-5.436L10.751 15h4.05zM7.651 8.852l-.538-.775L2.832 1.91h1.843l3.454 4.977l.538.775l4.491 6.47h-1.843z"/></svg>',
            ),
            'Youtube' => array(
                'link' => get_option('options_youtube'),
                'class' => 'bg-youtube',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32.5" height="20" viewBox="0 0 576 512"><path fill="currentColor" d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597c-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821c11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305m-317.51 213.508V175.185l142.739 81.205z"/></svg>',
            ),
            'Instagram' => array(
                'link' => get_option('options_instagram'),
                'class' => 'bg-instagram',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="27.5" height="20" viewBox="0 0 448 512"><path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9S287.7 141 224.1 141m0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7s74.7 33.5 74.7 74.7s-33.6 74.7-74.7 74.7m146.4-194.3c0 14.9-12 26.8-26.8 26.8c-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8s26.8 12 26.8 26.8m76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9c-26.2-26.2-58-34.4-93.9-36.2c-37-2.1-147.9-2.1-184.9 0c-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9c1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0c35.9-1.7 67.7-9.9 93.9-36.2c26.2-26.2 34.4-58 36.2-93.9c2.1-37 2.1-147.8 0-184.8M398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6c-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6c-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6c29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6c11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1"/></svg>',
            ),
        );

        echo '<div class="sag-widget widget-sosyalmedya">';
        echo $args['before_title'] . $title . $args['after_title'];
        echo '<div class="widget-body px-3 pb-3">';
        echo '<div class="row align-items-center justify-content-between g-3">';
        foreach ($social_links as $key => $social) {
            if (!empty($social['link'])) {
                echo '<div class="col-12 col-md-6">';
                echo '<a rel="nofollow noopener" target="_blank" class="ripple m-0 py-2 px-3 rounded-3 text-white ' . esc_attr($social['class']) . '" href="' . esc_url($social['link']) . '" aria-label="' . esc_attr($key) . '">';
                echo $social['icon'];
                echo esc_html($key);
                echo '</a>';
                echo '</div>';
            }
        }
        echo '</div></div></div>';
        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = isset($instance['title']) ? $instance['title'] : __('Bizi Takip Edin', 'mevzu2');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mevzu2'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                value="<?php echo esc_attr($title); ?>">
        </p>
        <p>Sosyal medya bağlantılarını <a href="./admin.php?page=mevzu-ayarlar#sosyal" target="_blank" class="text-link-hover">Mevzu² Ayarları → Sosyal Medya</a> bölümünden düzenleyebilirsiniz.</p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

function register_mevzu_bize_katilin_widget()
{
    register_widget('Mevzu_Bize_Katilin_Widget');
}
add_action('widgets_init', 'register_mevzu_bize_katilin_widget');

/* BILESEN: SOSYAL MEDYA */


function time_ago($date)
{
    // Tarihi DateTime nesnesine dönüştür
    $date_to_compare = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    if (!$date_to_compare) {
        return "Geçersiz tarih formatı!";
    }

    // Şu anki tarih
    $current_date = new DateTime();

    // Tarihler arasındaki fark
    $interval = $current_date->diff($date_to_compare);

    // Farkı yıl, ay, gün, saat ve dakika bazında kontrol et
    if ($interval->y > 0) {
        return $interval->y . ' yıl önce';
    } elseif ($interval->m > 0) {
        return $interval->m . ' ay önce';
    } elseif ($interval->d > 0) {
        return $interval->d . ' gün önce';
    } elseif ($interval->h > 0) {
        return $interval->h . ' saat önce';
    } elseif ($interval->i > 0) {
        return $interval->i . 'dk önce';
    } else {
        return 'Bugün';
    }
}



/**
 * Belirli bir kategorideki geçerli yazıları alır.
 * 
 * @param int $category_id Kategori ID'si
 * @param int $post_count Alınmak istenen maksimum yazı sayısı
 * @return array Geçerli yazı ID'lerini döndüren bir dizi
 */
function get_valid_posts($category_id, $post_count = 6)
{
    if (!$category_id) {
        return [];
    }

    // Optimized transient anahtarı
    $transient_key = 'valid_posts_query_' . $category_id . '_' . $post_count;
    $posts = get_transient($transient_key);

    if ($posts === false) {
        $posts = [];
        $args_q = array(
            'post_type' => 'post',
            'posts_per_page' => $post_count * 2, // Daha fazla post al, filtreleyerek azalt
            'orderby' => 'date',
            'order' => 'DESC',
            'category__in' => array($category_id),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            ),
            'fields' => 'ids', // Sadece ID'leri al - performans artışı
            'no_found_rows' => true,  // Toplam sayım yapma - performans artışı
            'update_post_meta_cache' => false, // Meta cache'i güncelleme - performans artışı
            'update_post_term_cache' => false, // Term cache'i güncelleme - performans artışı
        );

        // İlk sorgu
        $q = new WP_Query($args_q);

        if ($q->posts) {
            foreach ($q->posts as $post_id) {
                $image_size_kb = get_thumbnail_size_in_kb2($post_id);

                // Görsel boyutu 19-20KB arasında ise geç
                if ($image_size_kb !== false && $image_size_kb >= 19 && $image_size_kb <= 20) {
                    continue;
                }

                // Geçerli postları diziye ekle
                $posts[] = $post_id;

                // İstenen sayıya ulaştık mı kontrol et
                if (count($posts) >= $post_count) {
                    break;
                }
            }
        }

        // Transient kaydet - cache süresini 15 dakikaya çıkar
        set_transient($transient_key, $posts, 15 * MINUTE_IN_SECONDS);
    }

    return $posts;
}


function gorselKontrol($post_id)
{
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        $image_path = get_attached_file($thumbnail_id);

        if ($image_path) {
            $image_size = filesize($image_path);

            if ($image_size !== false) {
                $image_size_kb = $image_size / 1024;

                if ($image_size_kb >= 19 && $image_size_kb <= 20) {
                    return true;
                }
            }
        }
    }
    return false;
}



function get_thumbnail_size_in_kb2($post_id)
{
    // Öne çıkarılmış görsel ID'sini al
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if (!$thumbnail_id) {
        return false; // Görsel yoksa
    }

    // Görselin dosya yolunu al
    $image_path = get_attached_file($thumbnail_id);
    if (!$image_path || !file_exists($image_path)) {
        return false; // Dosya bulunamadıysa
    }

    // Görselin boyutunu byte cinsinden al
    $image_size = filesize($image_path);
    if ($image_size === false) {
        return false; // Boyut alınamadıysa
    }

    // Byte'ı KB'ye çevir ve sonucu döndür
    return round($image_size / 1024, 2); // İki ondalık basamak ile
}



/**
 * Yeni post oluşturulduğunda `gorselDefault` alanını varsayılan olarak 1 yap.
 */
function set_default_gorsel_default_meta($post_id, $post, $update)
{
    // Sadece yeni postlar için çalıştır
    if ($update === false) {
        if (!metadata_exists('post', $post_id, 'gorselDefault')) {
            update_post_meta($post_id, 'gorselDefault', 0);
        }
    }
}
add_action('save_post', 'set_default_gorsel_default_meta', 10, 3);
/**
 * Öne çıkarılmış görsel güncellendiğinde veya eklendiğinde `gorselDefault` alanını 0 yap
 */
function update_gorsel_default_on_thumbnail_change($post_id)
{
    // Döngü engelleme (gereksiz güncellemeleri önler)
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;

    // Yazının tipini kontrol et (post olmalı)
    if (get_post_type($post_id) !== 'post') {
        return $post_id;
    }

    // Öne çıkarılmış görseli kontrol et
    $thumbnail_id = get_post_thumbnail_id($post_id);

    // Eğer öne çıkarılmış görsel varsa, gorselDefault değerini 0 yap
    if ($thumbnail_id) {
        update_post_meta($post_id, 'gorselDefault', 0);
    }

    return $post_id;
}

add_action('save_post', 'update_gorsel_default_on_thumbnail_change');
// Yeni meta eklenirse de çalıştır








function format_team_name_for_url($team_name)
{
    $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'];
    $english = ['c', 'g', 'i', 'o', 's', 'u', 'C', 'G', 'I', 'O', 'S', 'U'];
    $team_name = str_replace($turkish, $english, $team_name);

    $team_name = strtolower($team_name);

    $team_name = str_replace(' ', '-', $team_name);

    $team_name = preg_replace('/[^a-z0-9-]/', '', $team_name);

    $team_name = preg_replace('/-+/', '-', $team_name);

    $team_name = trim($team_name, '-');

    return $team_name;
}


class widget_spor extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'sport_widget',
            __('Mevzu² — Lig Tablosu', 'mevzu2'),
            array('description' => __('Süper Lig tablosunu listeler', 'mevzu2'))
        );
    }

    public function fetch_api_data()
    {
        $transient_key = 'league_data_super_lig';
        $league_data = get_transient($transient_key);

        if ($league_data === false) {
            $response = wp_remote_get('https://tff.kkerem.com/?apikey=8fc681771ac26d8f86b29ca26717dcf6&tip=puan', array(
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                return null;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                return null;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data['durum']) && $data['durum'] === 'basarili' && !empty($data['puan_cetveli'])) {
                $league_data = $data['puan_cetveli'];
                // Haftalık 500 sorgu limiti — 1 saatte bir güncelle (~168/hafta)
                set_transient($transient_key, $league_data, HOUR_IN_SECONDS);
            }
        }

        return $league_data;
    }

    public function widget($args, $instance)
    {

        echo $args['before_widget'];

        $league_data = $this->fetch_api_data();

        // Widget başlığı
        if (!empty($instance['title'])) {
            echo '<h2 class="mb-0">' . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        if ($league_data) {
            echo '<div class="px-2 py-1 text-body">';
                echo "<div class='row text-center small fw-normal align-items-center gx-3'>
                        <div class='col-8 text-start'>Takımlar</div>
                        <div class='col' title='Oynanan'>O</div>
                        <div class='col' title='Averaj'>A</div>
                        <div class='col fw-semibold' title='Puan'>P</div>
                    </div>";
            echo '</div>';

            // Sponsor ön ek temizleme
            echo '<div class="px-2 takimlar small">';
            foreach ($league_data as $team) {
                // if($team['sira'] == count($league_data)-2) echo '<div class="border-top my-2"></div>'; 
                $takim_tam  = $team['takim'];
                $takim_kisa = $takim_tam;
                $sira       = ($team['sira'] >= 16) ? '<span class="position-relative text-danger">'.$team['sira'].'</span>' : $team['sira'];
                switch ($sira) {
                    case 1:
                        $sira = '<span class="position-relative text-blue">'.$team['sira'].'</span>';
                        break;
                    case 3:
                        $sira = '<span class="position-relative text-orange">'.$team['sira'].'</span>';
                        break;
                    case 4:
                        $sira = '<span class="position-relative text-green">'.$team['sira'].'</span>';
                        break;
                    case $sira >= 16:
                        $sira = '<span class="position-relative text-red">'.$team['sira'].'</span>';
                        break;
                    
                    default:
                        $sira = $team['sira'];
                        break;
                }
                $oynan      = $team['oynan'];
                $averaj     = $team['averaj'];
                $puan       = $team['puan'];
                $takim_url  = format_team_name_for_url($takim_tam);
                $search_url = get_bloginfo('url') . '/?s=' . urlencode($takim_kisa);
                $sampiyon = ((int)date('m') >= 5 && (int)date('m') < 8 && $team['sira'] == 1) ? " <span class='badge text-bg-danger position-relative ms-1' style='font-size:10px !important'><i class='ri-trophy-fill'></i><span class='small ms-1'>Şampiyon</span></span>" : '';
                echo "<div class='row text-center small py-1 align-items-center justify-content-center gx-3'>
                        <div class='col-1'>{$sira}</div>
                        <div class='col-1'><div class='takim-gorseller takim-gorsel-{$takim_url} rounded-circle' alt='" . esc_attr($takim_tam) . "'></div></div>
                        <div class='col-6 text-start fw-semibold'><a class='text-link-underline' href='" . esc_url($search_url) . "'>" . esc_html($takim_kisa) . $sampiyon .  "</a></div>
                        <div class='col fw-normal'>{$oynan}</div>
                        <div class='col fw-normal'>{$averaj}</div>
                        <div class='col fw-semibold'>{$puan}</div>
                      </div>";
            }
            echo '</div>';
        } else {
            echo '<p class="p-3 m-0 small fw-normal text-muted">Veri şu anda mevcut değil.</p>';
        }

        echo $args['after_widget'];
    }

    // Widget ayar formu
    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Lig Tablosu', 'mevzu2');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Başlık:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    // Widget ayarlarını kaydetme
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Widget'i kaydetme
function register_sport_widget()
{
    register_widget('widget_spor');
}
add_action('widgets_init', 'register_sport_widget');

// Saat 23:00'te Süper Lig verisini güncellemek için cron job ekle
function update_league_data_event()
{
    if (!wp_next_scheduled('update_league_data_cron')) {
        wp_schedule_event(strtotime('23:00:00'), 'daily', 'update_league_data_cron');
    }
}
add_action('wp', 'update_league_data_event');

// Günlük Süper Lig verilerini güncelleyen işlev
function update_league_data_cron_job()
{
    $widget = new widget_spor();
    $widget->fetch_api_data(); // Sadece Süper Lig verisini güncelle
}
add_action('update_league_data_cron', 'update_league_data_cron_job');




function custom_comment_order($args)
{
    if (isset($_GET['comment_order']) && in_array($_GET['comment_order'], ['asc', 'desc'])) {
        $args['order'] = sanitize_text_field($_GET['comment_order']);
    }
    return $args;
}
add_filter('comments_template_query_args', 'custom_comment_order');






class LastMinuteWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'last_minute_widget',
            __('Mevzu² — Son Dakika', 'mevzu2'),
            ['description' => __('Son dakika haberlerini gösterir.', 'mevzu2')]
        );
    }

    // Widget ayar formu
    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Son Dakika', 'mevzu2');
        $post_count = !empty($instance['post_count']) ? $instance['post_count'] : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Başlık:', 'mevzu2'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_count'); ?>">
                <?php _e('Gösterilecek Post Sayısı:', 'mevzu2'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('post_count'); ?>"
                name="<?php echo $this->get_field_name('post_count'); ?>" type="number"
                value="<?php echo esc_attr($post_count); ?>" />
        </p>
        <?php
    }

    // Widget ayarlarını kaydet
    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['post_count'] = (!empty($new_instance['post_count'])) ? absint($new_instance['post_count']) : 5;
        return $instance;
    }

    // Widget içeriği
    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title']);
        $post_count = !empty($instance['post_count']) ? $instance['post_count'] : 5;

        echo '<div class="pb-1">';
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => $post_count,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if ($query->have_posts()) {
            echo '<ul class="widget-sondakika">';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li><span class="post-date d-block text-body">' . get_the_date("H:i") . '</span><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('Son dakika haberi bulunamadı.', 'mevzu2') . '</p>';
        }

        echo '</div>';


        echo $args['after_widget'];
    }
}


// Widget'i kayıt et
function register_last_minute_widget()
{
    register_widget('LastMinuteWidget');
}
add_action('widgets_init', 'register_last_minute_widget');

/* WP Bar Avatar */
add_filter('get_avatar', 'replace_admin_bar_avatar', 10, 6);

function replace_admin_bar_avatar($avatar, $id_or_email, $size, $default, $alt, $args)
{
    // Sadece admin barında çalışması için kontrol
    if (is_admin_bar_showing() && is_user_logged_in()) {
        // Kullanıcı ID'sini al
        $user_id = 0;
        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        }
        // elseif (is_email($id_or_email)) {
        //     $user = get_user_by('email', $id_or_email);
        //     $user_id = $user ? $user->ID : 0;
        // }
        else {
            // $user = get_user_by('login', $id_or_email);
            // $user = get_user_by('ID', intval($id_or_email));
            // $user_id = $user ? $user->ID : 0;
        }

        // Kullanıcı ID'sine göre avatarı al
        $avatar_url = mevzu_get_user_avatar_url($user_id);

        if ($avatar_url) {
            $avatar = sprintf(
                '<img alt="%s" src="%s" class="%s" height="%d" width="%d" />',
                esc_attr($alt),
                esc_url($avatar_url),
                esc_attr($args['class']),
                $size,
                $size
            );
        }
    }

    return $avatar;
}

/* WP Bar Avatar */


/* Türkçe Günler */
function turkishDay($day)
{
    $turkish_days = array(
        'Monday' => 'Pazartesi',
        'Tuesday' => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe',
        'Friday' => 'Cuma',
        'Saturday' => 'Cumartesi',
        'Sunday' => 'Pazar'
    );
    return $turkish_days[$day];
}
/* Türkçe Günler */

// get_the_category sonuçlarını filtrelemek için bir fonksiyon oluşturun
function get_filtered_first_category()
{
    $categories = get_the_category();

    if (!empty($categories)) {
        foreach ($categories as $category) {
            // 'manset' ve 'alt-manset' slug'larını atla
            if (!in_array($category->slug, array('manset', 'alt-manset'), true)) {
                return $category; // İlk uygun kategoriyi döndür
            }
        }
    }

    return null; // Uygun bir kategori bulunmazsa null döndür
}

// Okuma suresi
function okumaSuresi($post_id = null)
{
    $post_id = $post_id ? $post_id : get_the_ID();
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    // Ortalama okuma hızını ayarla (dakikada 200 kelime)
    $reading_speed = 400;
    $minutes = ceil($word_count / $reading_speed);

    return $minutes . 'dk';
}
// Okuma suresi

function remove_default_background_settings($wp_customize)
{
    $wp_customize->remove_setting('background_color');
    $wp_customize->remove_control('background_color');
    $wp_customize->remove_setting('header_textcolor');
    $wp_customize->remove_control('header_textcolor');
}
add_action('customize_register', 'remove_default_background_settings', 20);

function hex_to_rgb($hex)
{
    $hex = str_replace('#', '', $hex);

    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }

    return [$r, $g, $b];
}

function rgb_to_hex($rgb)
{
    return sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
}

function darken_color($rgb, $percentage)
{
    // Renkleri karartmak için her bir kanalın değerini azaltır
    return array_map(function ($channel) use ($percentage) {
        return max(0, round($channel * (1 - $percentage / 100)));
    }, $rgb);
}

function mevzu_customize_register($wp_customize)
{
    // Arka Plan Rengi Ayarı
    $wp_customize->add_setting('mevzu_background_color', array(
        'default' => '#f1f5f9', // Varsayılan arka plan rengi
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));

    // Arka Plan Rengi Kontrolü
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mevzu_background_color', array(
        'label' => __('Arka Plan Rengi', 'mevzu'),
        'section' => 'colors',
        'settings' => 'mevzu_background_color',
    )));
}
add_action('customize_register', 'mevzu_customize_register');
function customizer_mevzu_dynamic_background()
{
    $background_color = get_theme_mod('mevzu_background_color', '#f1f5f9'); // Varsayılan renk

    echo '<style id="mevzu-dynamic-background">
        body {
            background-color: ' . esc_attr($background_color) . ' !important;
        }
    </style>';
}
add_action('wp_head', 'customizer_mevzu_dynamic_background', 999);

function customizer_mevzu_dynamic_styles()
{
    global $post;
    $page_id = 0;

    if (isset($post->ID)) {
        $page_id = $post->ID;
    } else {
        $page_id = get_queried_object_id();
    }
    $sayfa_renk = get_post_meta($page_id, 'sayfa_renk', true);
    if ($sayfa_renk) {
        $mevzu_primary_color = $sayfa_renk; // Varsayılan renk
    } else {
        $mevzu_primary_color = get_theme_mod('mevzu_primary_color', '#007bff'); // Varsayılan renk
    }

    $rgb = hex_to_rgb($mevzu_primary_color);
    $darkened_rgb = darken_color($rgb, 10); // %10 karartılmış renk
    $darkened_hex = rgb_to_hex($darkened_rgb); // Karartılmış HEX kodu

    $mdb_primary_rgb = implode(', ', $rgb);
    $darkened_rgb_string = implode(', ', $darkened_rgb);

    echo '<style id="mevzu-dynamic-styles">
        :root {
            --mevzu-primary: ' . esc_attr($mevzu_primary_color) . ' !important;
            --mevzu-primary-rgb: ' . esc_attr($mdb_primary_rgb) . ' !important;
            --mevzu-link-color-rgb: ' . esc_attr($mdb_primary_rgb) . ' !important;
        }
        body.dark {
            --mevzu-primary: ' . esc_attr($darkened_hex) . ' !important;
            --mevzu-primary-rgb: ' . esc_attr($darkened_rgb_string) . ';
            --mevzu-link-color-rgb: ' . esc_attr($darkened_rgb_string) . ' !important;
        }
    </style>';
}
add_action('wp_head', 'customizer_mevzu_dynamic_styles', 999);



function register_customizer_settings($wp_customize)
{
    $wp_customize->add_setting('mevzu_primary_color', array(
        'default' => '#e90708',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'mevzu_primary_color_control', array(
        'label' => 'Ana Renk',
        'section' => 'colors',
        'settings' => 'mevzu_primary_color',
    )));
}
add_action('customize_register', 'register_customizer_settings');

function enqueue_customizer_live_preview()
{
    wp_enqueue_script(
        'customizer-live-preview',
        get_template_directory_uri() . '/js/customizer.js', // Doğru yolu kontrol edin
        array('jquery', 'customize-preview'),
        null,
        true
    );
}
add_action('customize_preview_init', 'enqueue_customizer_live_preview');

// Customize Menu
function remove_unwanted_customizer_sections($wp_customize)
{
    // "Header Image" bölümünü kaldır
    $wp_customize->remove_section('header_image');

    // "Background Image" bölümünü kaldır
    $wp_customize->remove_section('background_image');
}
add_action('customize_register', 'remove_unwanted_customizer_sections', 20);
// Customize Menu





// Tema aktiflestirildikten sonra menü oluşturma
// NOT: Menü kurulumu sadece tema aktivasyonunda çalışan mevzu_setup_menus_and_widgets() üzerinden yapılır.
// Bu fonksiyon her sayfa yüklemesinde çalışmasın diye kaldırıldı.


// ACF Auto-import
// function import_acf_json_on_theme_activation() {
//     // JSON dosyanızın yolu
//     $json_file = get_template_directory() . '/inc/acf.json';

//     // Dosyanın var olup olmadığını kontrol et
//     if (!file_exists($json_file)) {
//         return;
//     }

//     // JSON içeriğini al
//     $json_data = file_get_contents($json_file);
//     $fields = json_decode($json_data, true);

//     if (!$fields) {
//         return;
//     }

//     foreach ($fields as $field_group) {
//         if (!isset($field_group['key'])) {
//             continue;
//         }

//         // Aynı key'e sahip bir grup olup olmadığını kontrol et
//         if (!get_posts([
//             'post_type'   => 'acf-field-group',
//             'meta_key'    => '_acf_field_group_key',
//             'meta_value'  => $field_group['key'],
//             'numberposts' => 1
//         ])) {
//             // Eğer yoksa içe aktar
//             acf_import_field_group($field_group);
//         }
//     }
// }
// add_action('after_switch_theme', 'import_acf_json_on_theme_activation');
// ACF Auto-import


// Taksonomi

// Default Yönetim Renk Düzeni
function set_default_admin_color_scheme($user_id)
{
    $default_scheme = 'mevzu';
    $existing_scheme = get_user_meta($user_id, 'admin_color', true);

    if (!$existing_scheme) {
        update_user_meta($user_id, 'admin_color', $default_scheme);
    }
}
add_action('user_register', 'set_default_admin_color_scheme');
// Default Yönetim Renk Düzeni

// Tema yuklendikten sonra otomatik sayfalarin olusuturulmasi
function create_theme_specific_pages()
{
    // 1. KORUMA: Bu işlem daha önce yapıldıysa kodu anında durdur ve sunucuyu yorma
    if (get_option('mevzu2_sayfalar_olusturuldu')) {
        return;
    }

    $pages = array(
        'İletişim' => array(
            'slug' => 'iletisim'
        ),
        'Künye' => array(
            'slug' => 'kunye'
        ),
        'Yazarlar' => array(
            'slug' => 'yazarlar'
        ),
        'Hava Durumu' => array(
            'slug' => 'hava-durumu'
        ),
        'Son Dakika' => array(
            'slug' => 'sondakika'
        ),
        'Namaz Vakitleri' => array(
            'slug' => 'namaz-vakitleri'
        ),
        'Yol Durumu' => array(
            'slug' => 'yol-durumu'
        ),
        'Döviz & Borsa' => array(
            'slug' => 'finans' // Slug her zaman küçük harf olmalıdır, düzeltildi
        )
    );

    foreach ($pages as $title => $details) {
        $page = get_page_by_path($details['slug'], OBJECT, 'page');

        if (!$page) {
            $page_data = array(
                'post_title' => $title,
                'post_name' => $details['slug'],
                // 2. KORUMA: Content tanımlanmamışsa boş geçerek PHP Warning hatasını engelliyoruz
                'post_content' => $details['content'] ?? '', 
                'post_status' => 'publish',
                'post_author' => 1, // Yazar ID'si
                'post_type' => 'page'
            );
            wp_insert_post($page_data);
        }
    }

    // 3. KORUMA: Döngü bittikten sonra veritabanına "işlem tamamlandı" notunu düşüyoruz
    update_option('mevzu2_sayfalar_olusturuldu', true);
}
add_action('after_setup_theme', 'create_theme_specific_pages');
// Tema yuklendikten sonra otomatik sayfalarin olusuturulmasi

// Defaults
function m_default($x, $size = 'auto')
{
    if ($x == 'logo') {
    } elseif ($x == 'avatar') {
        return '<svg class="rounded-circle m-0" version="1.0" xmlns="http://www.w3.org/2000/svg"
            width="40" height="40" viewBox="0 0 300.000000 300.000000"
            preserveAspectRatio="xMidYMid meet">

            <g transform="translate(0.000000,300.000000) scale(0.100000,-0.100000)"
            fill="#000000" stroke="none">
            <path class="primary" d="M2467 2039 c-56 -13 -114 -45 -163 -91 l-45 -41 57 -58 56 -57 46 35
            c58 44 82 53 138 53 86 0 121 -75 70 -151 -14 -20 -99 -92 -190 -160 l-166
            -124 0 -72 0 -73 290 0 290 0 0 80 0 79 -146 3 -146 3 79 55 c158 110 213 196
            200 311 -19 159 -184 252 -370 208z"/>
            <path class="dark" d="M760 1315 l0 -635 135 0 135 0 2 392 3 392 188 -344 c104 -190 192
            -349 197 -353 4 -5 92 146 195 335 103 189 191 347 196 352 5 6 9 -154 9 -382
            l0 -392 135 0 135 0 0 635 0 635 -138 0 -138 0 -193 -355 c-106 -195 -195
            -355 -196 -355 -2 0 -90 160 -196 355 l-194 354 -137 1 -138 0 0 -635z"/>
            <path class="primary" d="M301 1628 c-137 -71 -84 -268 71 -268 71 0 138 71 138 145 0 109
            -111 174 -209 123z"/>
            <path class="primary" d="M296 927 c-53 -30 -79 -79 -72 -135 18 -141 193 -180 266 -60 42 69
            14 163 -60 200 -47 24 -87 22 -134 -5z"/>
            </g>
            </svg>';
    } elseif ($x == NULL) {
        return '<svg xmlns="http://www.w3.org/2000/svg" class="w-100 h-auto" version="1.0" width="1824.000000pt" height="1080.000000pt" viewBox="0 0 1824.000000 1080.000000" preserveAspectRatio="xMidYMid meet"> <g transform="translate(0.000000,1080.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none"> <path xmlns="http://www.w3.org/2000/svg" class="primary" d="M11320 8243 c-53 -9 -157 -46 -205 -73 -56 -32 -142 -103 -182 -150 l-26 -32 100 -100 101 -101 62 52 c92 77 151 103 240 108 65 4 81 1 119 -19 115 -62 105 -197 -25 -318 -32 -30 -177 -143 -321 -250 l-262 -195 -1 -132 0 -133 520 0 520 0 0 145 0 145 -261 2 -261 3 150 108 c238 169 322 269 353 415 15 74 6 182 -21 255 -46 121 -177 225 -331 262 -59 13 -208 18 -269 8z"/> <path d="M8200 6935 l0 -1145 245 0 245 0 2 705 3 705 350 -639 c287 -525 352 -637 362 -625 6 9 164 295 350 636 l338 621 3 -702 2 -701 250 0 250 0 -2 1143 -3 1142 -245 2 -245 3 -349 -640 c-192 -352 -352 -642 -355 -644 -3 -3 -165 284 -358 637 l-352 642 -246 3 -245 2 0 -1145z"/> <path xmlns="http://www.w3.org/2000/svg" class="primary" d="M7390 7509 c-171 -77 -212 -288 -82 -418 109 -109 254 -109 362 -1 62 61 80 105 80 192 0 178 -196 302 -360 227z"/> <path xmlns="http://www.w3.org/2000/svg" class="primary" d="M7435 6261 c-93 -24 -165 -90 -190 -175 -19 -63 -19 -89 1 -157 20 -67 97 -148 160 -167 179 -53 343 64 344 246 0 34 -7 79 -15 100 -20 47 -78 108 -129 134 -43 22 -125 31 -171 19z"/> <path d="M3324 4370 c-63 -25 -71 -114 -15 -157 24 -19 75 -15 104 7 49 39 42 116 -14 145 -31 17 -44 18 -75 5z"/> <path d="M3600 4373 c-52 -20 -76 -70 -56 -118 37 -88 166 -66 166 29 0 58 -62 108 -110 89z"/> <path d="M2510 4129 c-106 -14 -182 -50 -262 -127 -104 -99 -143 -196 -136 -341 3 -71 10 -99 36 -154 73 -157 247 -267 422 -267 105 0 249 53 318 118 l32 30 0 176 0 176 -195 0 -196 0 3 -72 3 -73 98 -3 97 -3 0 -65 0 -65 -47 -22 c-67 -30 -172 -30 -229 0 -57 30 -120 104 -140 165 -42 127 19 273 138 332 95 47 226 29 300 -41 l27 -26 48 41 c26 22 57 49 67 61 19 21 19 22 -3 45 -13 14 -53 40 -90 59 -95 50 -194 69 -291 56z"/> <path d="M3431 4129 c-154 -21 -292 -122 -357 -264 -113 -248 35 -538 311 -610 332 -86 645 234 545 557 -24 79 -66 144 -133 204 -59 53 -102 76 -177 97 -73 20 -124 25 -189 16z m196 -208 c98 -67 146 -177 123 -284 -22 -108 -101 -193 -201 -217 -161 -39 -317 91 -319 266 0 104 57 201 149 249 39 21 57 25 122 23 68 -3 82 -7 126 -37z"/> <path d="M15720 4129 c-201 -28 -359 -178 -393 -373 -37 -217 115 -443 338 -501 145 -37 303 1 415 102 157 141 198 335 111 518 -84 176 -277 280 -471 254z m165 -193 c140 -65 196 -232 124 -375 -100 -200 -366 -197 -465 5 -37 75 -37 169 -1 241 28 54 92 117 141 139 50 22 143 17 201 -10z"/> <path d="M5102 4115 c-89 -28 -157 -96 -181 -181 -38 -137 30 -246 185 -297 44 -15 111 -34 151 -42 93 -21 137 -50 141 -92 7 -73 -51 -109 -164 -100 -78 6 -137 28 -209 77 -26 18 -47 30 -49 28 -1 -1 -23 -28 -49 -59 -26 -31 -47 -61 -47 -67 0 -18 110 -84 184 -110 92 -33 261 -37 342 -9 104 36 172 121 181 227 7 69 -9 120 -50 166 -42 49 -105 78 -241 113 -64 17 -130 37 -146 46 -61 31 -64 96 -8 138 44 33 141 27 226 -13 37 -17 74 -38 83 -46 16 -14 21 -10 60 43 23 32 46 63 51 69 12 17 -91 80 -171 104 -75 24 -222 26 -289 5z"/> <path d="M4080 3691 l0 -431 95 0 95 0 0 135 0 135 68 0 68 0 94 -135 95 -135 108 0 108 0 -48 68 c-26 37 -71 100 -100 141 -29 41 -53 77 -53 80 0 4 16 15 36 26 54 29 119 107 135 162 43 148 -20 286 -160 352 -55 25 -61 26 -298 29 l-243 4 0 -431z m424 248 c56 -16 96 -58 96 -104 0 -96 -50 -125 -214 -125 l-116 0 0 120 0 120 98 0 c53 0 115 -5 136 -11z"/> <path d="M5722 3688 l3 -433 330 0 330 0 0 85 0 85 -237 3 -238 2 0 90 0 90 215 0 215 0 0 85 0 85 -215 0 -215 0 0 85 0 85 240 0 240 0 0 85 0 85 -335 0 -335 0 2 -432z"/> <path d="M6520 3685 l0 -435 305 0 305 0 0 90 0 90 -210 0 -210 0 -2 343 -3 342 -92 3 -93 3 0 -436z"/> <path d="M7600 3690 l0 -430 90 0 90 0 0 175 0 175 195 0 195 0 0 -175 0 -175 95 0 95 0 0 430 0 430 -95 0 -95 0 0 -170 0 -170 -195 0 -195 0 0 170 0 170 -90 0 -90 0 0 -430z"/> <path d="M8638 3707 c-92 -226 -170 -420 -174 -429 -6 -16 3 -18 98 -18 l105 0 24 63 25 62 193 0 193 0 25 -62 25 -63 105 0 c96 0 105 2 99 18 -4 9 -82 203 -174 429 l-167 413 -105 0 -105 0 -167 -413z m336 -9 c31 -75 56 -139 56 -142 0 -3 -54 -6 -121 -6 -91 0 -120 3 -117 13 12 45 114 285 120 279 3 -4 31 -69 62 -144z"/> <path d="M9450 4035 l0 -85 215 0 c118 0 215 -2 215 -5 0 -3 -99 -126 -220 -275 l-220 -269 0 -76 0 -75 355 0 355 0 0 90 0 90 -220 0 c-121 0 -220 2 -220 6 0 3 99 127 220 275 l220 270 0 70 0 69 -350 0 -350 0 0 -85z"/> <path d="M10290 3690 l0 -430 95 0 95 0 0 430 0 430 -95 0 -95 0 0 -430z"/> <path d="M10650 3690 l0 -430 95 0 95 0 0 135 0 135 68 0 67 -1 93 -134 94 -135 104 0 c57 0 104 2 104 5 0 3 -45 69 -100 147 l-100 141 49 28 c166 95 191 326 49 453 -82 75 -135 86 -399 86 l-219 0 0 -430z m462 232 c62 -38 74 -125 25 -169 -41 -38 -64 -43 -181 -43 l-116 0 0 121 0 121 118 -4 c100 -3 124 -7 154 -26z"/> <path d="M11492 3688 l3 -433 305 0 305 0 0 85 0 85 -212 3 -213 2 0 345 0 345 -95 0 -95 0 2 -432z"/> <path d="M12497 4108 c-31 -79 -340 -835 -344 -841 -2 -4 44 -6 103 -5 l108 3 25 63 24 62 192 0 193 0 22 -61 c13 -33 28 -63 35 -65 6 -3 55 -3 108 -2 l96 3 -174 425 -173 425 -105 3 c-78 2 -106 -1 -110 -10z m151 -355 c22 -54 50 -122 61 -150 l21 -53 -124 0 -124 0 60 150 c33 83 61 150 63 150 1 0 21 -44 43 -97z"/> <path d="M13170 3690 l0 -430 95 0 95 0 2 267 3 268 198 -268 199 -267 94 0 94 0 0 430 0 431 -92 -3 -93 -3 -5 -268 -5 -267 -200 270 -200 269 -92 1 -93 0 0 -430z"/> <path d="M14130 3690 l0 -430 95 0 95 0 0 430 0 430 -95 0 -95 0 0 -430z"/> <path d="M14410 4116 c0 -3 74 -126 165 -274 l165 -270 0 -156 0 -156 95 0 95 0 0 153 0 152 165 270 c91 149 165 274 165 278 0 4 -46 6 -102 5 l-102 -3 -106 -177 c-58 -97 -108 -179 -111 -182 -3 -3 -52 77 -110 179 l-104 185 -107 0 c-60 0 -108 -2 -108 -4z"/> <path d="M16370 3690 l0 -430 95 0 95 0 0 135 0 135 68 0 67 -1 93 -134 94 -135 104 0 c57 0 104 2 104 5 0 3 -45 69 -100 147 l-100 141 49 28 c166 95 191 326 49 453 -82 75 -135 86 -399 86 l-219 0 0 -430z m462 232 c62 -38 74 -125 25 -169 -41 -38 -64 -43 -181 -43 l-116 0 0 121 0 121 118 -4 c100 -3 124 -7 154 -26z"/> </g> </svg>';
    }
}
// Defaults


// Varsayılan post sayısı
function mevzu_custom_query_settings($query)
{
    if (!is_admin() && $query->is_main_query()) {
        // Anasayfada post sayısını sınırla
        if ($query->is_home()) {
            $query->set('posts_per_page', 10); // Varsayılan olan ne ise onu koruyun
            $query->set('no_found_rows', true); // Pagination için toplam sayım yapma
        }

        // Archive sayfalarda da sınırla
        if ($query->is_archive()) {
            $query->set('no_found_rows', true);
        }

        // Search sayfalarda da sınırla
        if ($query->is_search()) {
            $query->set('posts_per_page', 10);
            $query->set('no_found_rows', true);
        }
    }
}
add_action('pre_get_posts', 'mevzu_custom_query_settings');

// Varsayılan post sayısı


// Gereksizleri kaldır
// function remove_wp_jquery() {
//     if (!is_admin()) { // Sadece frontend için kaldır
//         wp_deregister_script('jquery');
//     }
// }
// add_action('wp_enqueue_scripts', 'remove_wp_jquery', 11);

function remove_block_library_css()
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles'); // WordPress 6.1+ için eklendi
    wp_dequeue_style('global-styles'); // Global styles kaldır
    wp_dequeue_style('wp-block-library-inline'); // Inline block styles kaldır
}
add_action('wp_enqueue_scripts', 'remove_block_library_css', 100);

// Emoji kaldırma artık Hız & Güvenlik → "Yerleşik Emojileri Kaldır" ayarından yönetilir.
// class-hiz-guvenlik-init.php
function remove_navigation_js()
{
    wp_dequeue_script('navigation'); // navigation.js dosyasını kaldırır
    wp_deregister_script('navigation'); // Kayıtlı script listesinden çıkarır
}
add_action('wp_enqueue_scripts', 'remove_navigation_js', 100);



function remove_wordpress_performance_blockers()
{
    // RSD link kaldır (her zaman gereksiz)
    remove_action('wp_head', 'rsd_link');

    // Shortlink kaldır (her zaman gereksiz)
    remove_action('wp_head', 'wp_shortlink_wp_head');

    // DNS prefetch kaldır (her zaman gereksiz)
    remove_action('wp_head', 'wp_resource_hints', 2);

    // Canonical link duplicate kaldır (Yoast varsa)
    remove_action('wp_head', 'rel_canonical');

    // Aşağıdakiler artık Hız & Güvenlik ayarlarından yönetilir:
    // - WLW Manifest (disable-wlw)
    // - WP Generator meta (remove-wp-ver)
    // - RSS feed links (remove-rss)
    // - REST/oEmbed head links (disable-oembed)
}
add_action('init', 'remove_wordpress_performance_blockers');

// jQuery Migrate kaldır (zaten var ama güçlendirelim)
function remove_jquery_migrate_and_optimize($scripts)
{
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
add_action('wp_default_scripts', 'remove_jquery_migrate_and_optimize');

/**
 * Hız & Güvenlik ayarlarından kaynak yükleme tercihini döndür.
 *
 * @param string $key     Ayar anahtarı (swiper_source, select2_source, jquery_source).
 * @param string $default Varsayılan değer.
 * @return string
 */
function mevzu_get_kaynak_source($key, $default = 'local') {
    $opts = get_option('dsxmlrpc-settings', array());
    if (!is_array($opts) || !isset($opts[$key])) {
        return $default;
    }
    return sanitize_key($opts[$key]);
}

/**
 * Kaynak tercihine göre asset URL'si döndür.
 *
 * @param string $key         Ayar anahtarı.
 * @param string $cdn_url     CDN URL.
 * @param string $local_path  Tema içindeki local yol (get_template_directory_uri sonrası).
 * @param string $default     Varsayılan kaynak ('local' veya 'cdn').
 * @return string
 */
function mevzu_asset_url($key, $cdn_url, $local_path, $default = 'local') {
    $source = mevzu_get_kaynak_source($key, $default);
    if ($source === 'cdn') {
        return $cdn_url;
    }
    return get_template_directory_uri() . $local_path;
}

// Gutenberg CSS'i sadece gerekli sayfalarda yükle
function conditionally_load_gutenberg_css()
{
    if (!has_blocks() && !is_admin()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
    }
}
add_action('wp_enqueue_scripts', 'conditionally_load_gutenberg_css', 100);

// Heartbeat API artık Hız & Güvenlik → "Heartbeat Yavaşlatma" ayarından yönetilir.



// Namaz Vakitleri
function normalizeString(string $string): string
{
    $search = ['ç', 'ğ', 'ı', 'i', 'ö', 'ş', 'ü'];
    $replace = ['c', 'g', 'i', 'i', 'o', 's', 'u'];
    return strtoupper(str_replace($search, $replace, trim($string)));
}

class HttpClient
{
    public function get(string $url, int $maxRetries = 3): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: " . $error);
        }

        curl_close($ch);
        return $response ?: '';
    }
}

/**
 * Yeni namaz vakitleri verisi — diyanet.kkerem.com JSON API
 * Haftalık 500 sorgu limiti var, transient ile cache kullanılır.
 * Cache süresi: 24 saat (limiti korumak için)
 */
function get_prayer_times_data_by_city_name(string $sehir): array
{
    $apiKey    = mevzu_key('diyanet_api_key');
    $gun       = 30;
    $cache_key = 'namaz_kkerem_' . md5(strtolower($sehir));

    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $url = add_query_arg(array(
        'apikey' => $apiKey,
        'sehir'  => urlencode(strtolower($sehir)),
        'gun'    => $gun,
    ), 'https://diyanet.kkerem.com/');

    $response = wp_remote_get($url, array('timeout' => 10));

    if (is_wp_error($response)) {
        error_log('[Namaz API] wp_remote_get hatası: ' . $response->get_error_message());
        return array('error' => $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data) || ($data['durum'] ?? '') !== 'basarili' || empty($data['vakitler'])) {
        error_log('[Namaz API] Geçersiz yanıt: ' . $body);
        return array('error' => 'API geçersiz veri döndürdü');
    }

    $vakitler = $data['vakitler'];

    // 24 saat cache — API limitini korur (500/hafta = ~71/gün)
    set_transient($cache_key, $vakitler, 24 * HOUR_IN_SECONDS);

    return $vakitler;
}

function get_prayer_times_data(int $cityId): array
{
    // Eski Diyanet scraper'dan yeni JSON API'ya geçiş:
    // cityId yerine sehir adını kullanıyoruz
    $sehirler = diyanet_sehirler();
    $sehir_adi = isset($sehirler[$cityId]) ? $sehirler[$cityId] : '';
    if (!$sehir_adi) {
        return array('error' => 'Geçersiz şehir ID');
    }
    // Türkçe karakterleri sadeleştir (API küçük harf+ASCII bekliyor)
    $sehir_ascii = strtolower(str_replace(
        array('ç','ğ','ı','i','ö','ş','ü'),
        array('c','g','i','i','o','s','u'),
        mb_strtolower($sehir_adi, 'UTF-8')
    ));
    return get_prayer_times_data_by_city_name($sehir_ascii);
}

function get_prayer_times_table(array $prayerTimes): string
{
    $todayDate = date('d.m.Y');
    $count = 0;

    $table = '<div class="table-responsive"><table class="table table-stripped table-bordered text-center m-0">';
    $table .= '<thead><tr><th class="text-start">Miladi Tarih</th><th class="text-start">Hicri Tarih</th><th>İmsak</th><th>Güneş</th><th>Öğle</th><th>İkindi</th><th>Akşam</th><th>Yatsı</th></tr></thead>';
    $table .= '<tbody>';

    foreach ($prayerTimes as $time) {
        $count++;
        $isToday = ($time['miladi_tarih'] === $todayDate);
        $rowClass = $count == 1 ? ' bg-warning bg-opacity-25' : '';

        $table .= '<tr class="small">';
        $table .= '<td class="text-start' . esc_attr($rowClass) . '">' . esc_html($time['miladi_tarih']) . '</td>';
        $table .= '<td class="text-start' . esc_attr($rowClass) . '">' . esc_html($time['hicri_tarih']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['imsak']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['gunes']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['ogle']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['ikindi']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['aksam']) . '</td>';
        $table .= '<td class="' . esc_attr($rowClass) . '">' . esc_html($time['yatsi']) . '</td>';
        $table .= '</tr>';
    }

    $table .= '</tbody></table></div>';
    return $table;
}

/**
 * Anasayfa Ramazan "İmsaka kalan süre" geri sayımı için bir sonraki imsak vaktini ISO 8601 olarak döndürür.
 * Varsayılan şehir (ACF varsayilan_sehir) kullanılır.
 *
 * @return string|null Bir sonraki imsak tarih-saati (format: 'c') veya hata/veri yoksa null
 */
function get_next_imsak_for_anasayfa()
{
    $city = normalizeString(strtoupper(mevzu_get_current_city()));
    $sehirler = diyanet_sehirler();
    if (!is_array($sehirler)) {
        return null;
    }
    $cityId = array_search($city, $sehirler, true);
    if ($cityId === false) {
        return null;
    }
    $prayerTimes = get_prayer_times_data($cityId);
    if (empty($prayerTimes) || isset($prayerTimes['error']) || !is_array($prayerTimes)) {
        return null;
    }

    $aylar = array(
        'Ocak' => 1,
        'Şubat' => 2,
        'Mart' => 3,
        'Nisan' => 4,
        'Mayıs' => 5,
        'Haziran' => 6,
        'Temmuz' => 7,
        'Ağustos' => 8,
        'Eylül' => 9,
        'Ekim' => 10,
        'Kasım' => 11,
        'Aralık' => 12
    );

    $today = new DateTime('@' . current_time('timestamp'));
    $today->setTime(0, 0, 0);
    $todayY = (int) $today->format('Y');
    $todayM = (int) $today->format('n');
    $todayD = (int) $today->format('j');

    $tomorrow = (clone $today)->modify('+1 day');
    $tomY = (int) $tomorrow->format('Y');
    $tomM = (int) $tomorrow->format('n');
    $tomD = (int) $tomorrow->format('j');

    $todayRow = null;
    $tomorrowRow = null;

    foreach ($prayerTimes as $row) {
        if (!isset($row['miladi_tarih'], $row['imsak'])) {
            continue;
        }
        if (preg_match('/^\s*(\d{1,2})\s+(\S+)\s+(\d{4})/u', $row['miladi_tarih'], $m)) {
            $gun = (int) $m[1];
            $ayAdi = $m[2];
            $yil = (int) $m[3];
            if (!isset($aylar[$ayAdi])) {
                continue;
            }
            $ay = $aylar[$ayAdi];
            if ($yil === $todayY && $ay === $todayM && $gun === $todayD) {
                $todayRow = $row;
            }
            if ($yil === $tomY && $ay === $tomM && $gun === $tomD) {
                $tomorrowRow = $row;
            }
            if ($todayRow && $tomorrowRow) {
                break;
            }
        }
    }

    if (!$todayRow) {
        $todayRow = isset($prayerTimes[0]) ? $prayerTimes[0] : null;
    }
    if (!$todayRow) {
        return null;
    }

    $imsakToday = DateTime::createFromFormat('H:i', trim($todayRow['imsak']));
    if (!$imsakToday) {
        return null;
    }
    $imsakToday->setDate($todayY, $todayM, $todayD);

    $now = new DateTime('@' . current_time('timestamp'));
    if ($now < $imsakToday) {
        return $imsakToday->format('c');
    }

    if ($tomorrowRow) {
        $imsakTomorrow = DateTime::createFromFormat('H:i', trim($tomorrowRow['imsak']));
        if ($imsakTomorrow) {
            $imsakTomorrow->setDate($tomY, $tomM, $tomD);
            return $imsakTomorrow->format('c');
        }
    }

    $imsakTomorrow = DateTime::createFromFormat('H:i', trim($todayRow['imsak']));
    if ($imsakTomorrow) {
        $imsakTomorrow->setDate($tomY, $tomM, $tomD);
        return $imsakTomorrow->format('c');
    }

    return null;
}

/**
 * Anasayfa imsak geri sayımı ve bugünün namaz vakitleri için veri döndürür.
 * Varsayılan şehir kullanılır. Tek istekte hem hedef hem vakitler alınır.
 *
 * @return array{hedef: string|null, vakitler: array<string, string>|null} 'hedef' => bir sonraki imsak (ISO 8601), 'vakitler' => ['İmsak'=>'05:23', ...]
 */
function get_anasayfa_imsak_ve_vakitler()
{
    $city = normalizeString(strtoupper(mevzu_get_current_city()));
    $sehirler = diyanet_sehirler();
    if (!is_array($sehirler)) {
        return array('hedef' => null, 'iftar_hedef' => null, 'vakitler' => null, 'gorunecek_hedef' => null, 'gorunecek_etiket' => '');
    }
    $cityId = array_search($city, $sehirler, true);
    if ($cityId === false) {
        return array('hedef' => null, 'iftar_hedef' => null, 'vakitler' => null, 'gorunecek_hedef' => null, 'gorunecek_etiket' => '');
    }
    $prayerTimes = get_prayer_times_data($cityId);
    if (empty($prayerTimes) || isset($prayerTimes['error']) || !is_array($prayerTimes)) {
        return array('hedef' => null, 'iftar_hedef' => null, 'vakitler' => null, 'gorunecek_hedef' => null, 'gorunecek_etiket' => '');
    }

    $aylar = array(
        'Ocak' => 1,
        'Şubat' => 2,
        'Mart' => 3,
        'Nisan' => 4,
        'Mayıs' => 5,
        'Haziran' => 6,
        'Temmuz' => 7,
        'Ağustos' => 8,
        'Eylül' => 9,
        'Ekim' => 10,
        'Kasım' => 11,
        'Aralık' => 12
    );

    $today = new DateTime('@' . current_time('timestamp'));
    $today->setTime(0, 0, 0);
    $todayY = (int) $today->format('Y');
    $todayM = (int) $today->format('n');
    $todayD = (int) $today->format('j');

    $tomorrow = (clone $today)->modify('+1 day');
    $tomY = (int) $tomorrow->format('Y');
    $tomM = (int) $tomorrow->format('n');
    $tomD = (int) $tomorrow->format('j');

    $todayRow = null;
    $tomorrowRow = null;

    foreach ($prayerTimes as $row) {
        if (!isset($row['miladi_tarih'], $row['imsak'])) {
            continue;
        }
        if (preg_match('/^\s*(\d{1,2})\s+(\S+)\s+(\d{4})/u', $row['miladi_tarih'], $m)) {
            $gun = (int) $m[1];
            $ayAdi = $m[2];
            $yil = (int) $m[3];
            if (!isset($aylar[$ayAdi])) {
                continue;
            }
            $ay = $aylar[$ayAdi];
            if ($yil === $todayY && $ay === $todayM && $gun === $todayD) {
                $todayRow = $row;
            }
            if ($yil === $tomY && $ay === $tomM && $gun === $tomD) {
                $tomorrowRow = $row;
            }
            if ($todayRow && $tomorrowRow) {
                break;
            }
        }
    }

    if (!$todayRow) {
        $todayRow = isset($prayerTimes[0]) ? $prayerTimes[0] : null;
    }
    if (!$todayRow) {
        return array('hedef' => null, 'iftar_hedef' => null, 'vakitler' => null, 'gorunecek_hedef' => null, 'gorunecek_etiket' => '');
    }

    $vakitler = array(
        'İmsak' => trim($todayRow['imsak']),
        'Güneş' => trim($todayRow['gunes']),
        'Öğle' => trim($todayRow['ogle']),
        'İkindi' => trim($todayRow['ikindi']),
        'Akşam' => trim($todayRow['aksam']),
        'Yatsı' => trim($todayRow['yatsi']),
    );

    $now = new DateTime('@' . current_time('timestamp'));

    $aksamToday = DateTime::createFromFormat('H:i', $vakitler['Akşam']);
    if ($aksamToday) {
        $aksamToday->setDate($todayY, $todayM, $todayD);
        $iftar_hedef = ($now < $aksamToday) ? $aksamToday->format('c') : null;
        if ($iftar_hedef === null && $tomorrowRow) {
            $aksamTomorrow = DateTime::createFromFormat('H:i', trim($tomorrowRow['aksam']));
            if ($aksamTomorrow) {
                $aksamTomorrow->setDate($tomY, $tomM, $tomD);
                $iftar_hedef = $aksamTomorrow->format('c');
            }
        }
        if ($iftar_hedef === null && $aksamToday) {
            $aksamTomorrow = clone $aksamToday;
            $aksamTomorrow->modify('+1 day');
            $iftar_hedef = $aksamTomorrow->format('c');
        }
    } else {
        $iftar_hedef = null;
    }

    $imsakToday = DateTime::createFromFormat('H:i', $vakitler['İmsak']);
    if (!$imsakToday) {
        $gorunecek_hedef = $iftar_hedef;
        $gorunecek_etiket = $iftar_hedef ? 'İftara son' : '';
        return array('hedef' => null, 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $gorunecek_hedef, 'gorunecek_etiket' => $gorunecek_etiket);
    }
    $imsakToday->setDate($todayY, $todayM, $todayD);

    if ($now < $imsakToday) {
        return array('hedef' => $imsakToday->format('c'), 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $imsakToday->format('c'), 'gorunecek_etiket' => 'İmsaka son');
    }

    if ($now < $aksamToday) {
        return array('hedef' => null, 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $iftar_hedef, 'gorunecek_etiket' => 'İftara son');
    }

    $sonraki_imsak = null;
    if ($tomorrowRow) {
        $imsakTomorrow = DateTime::createFromFormat('H:i', trim($tomorrowRow['imsak']));
        if ($imsakTomorrow) {
            $imsakTomorrow->setDate($tomY, $tomM, $tomD);
            $sonraki_imsak = $imsakTomorrow->format('c');
        }
    }
    if ($sonraki_imsak === null) {
        $imsakTomorrow = DateTime::createFromFormat('H:i', $vakitler['İmsak']);
        if ($imsakTomorrow) {
            $imsakTomorrow->setDate($tomY, $tomM, $tomD);
            $sonraki_imsak = $imsakTomorrow->format('c');
        }
    }

    if ($tomorrowRow) {
        $imsakTomorrow = DateTime::createFromFormat('H:i', trim($tomorrowRow['imsak']));
        if ($imsakTomorrow) {
            $imsakTomorrow->setDate($tomY, $tomM, $tomD);
            return array('hedef' => $imsakTomorrow->format('c'), 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $sonraki_imsak, 'gorunecek_etiket' => 'İmsaka kalan');
        }
    }

    $imsakTomorrow = DateTime::createFromFormat('H:i', $vakitler['İmsak']);
    if ($imsakTomorrow) {
        $imsakTomorrow->setDate($tomY, $tomM, $tomD);
        return array('hedef' => $imsakTomorrow->format('c'), 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $sonraki_imsak, 'gorunecek_etiket' => 'İmsaka kalan');
    }

    return array('hedef' => null, 'iftar_hedef' => $iftar_hedef, 'vakitler' => $vakitler, 'gorunecek_hedef' => $sonraki_imsak, 'gorunecek_etiket' => 'İmsaka kalan');
}

/**
 * AJAX: Site saat dilimindeki "şu an"ı döndürür (ISO 8601).
 * Önbellekli sayfalarda bile giriş yapmış/yapmamış tüm kullanıcılar aynı saati görsün diye kullanılır.
 */
function mevzu_ajax_server_time()
{
    $time_data = get_anasayfa_imsak_ve_vakitler();
    wp_send_json_success(array(
        'iso' => date('c', current_time('timestamp')),
        'hedef' => isset($time_data['gorunecek_hedef']) ? $time_data['gorunecek_hedef'] : null,
        'etiket' => isset($time_data['gorunecek_etiket']) ? $time_data['gorunecek_etiket'] : ''
    ));
}
add_action('wp_ajax_mevzu_server_time', 'mevzu_ajax_server_time');
add_action('wp_ajax_nopriv_mevzu_server_time', 'mevzu_ajax_server_time');

/**
 * Anasayfada namaz vakitleri + imsak/iftar geri sayım bloğunu çıktılar.
 * Veri yoksa veya gösterecek bir şey yoksa hiçbir şey basılmaz.
 */
function ramazan()
{
    if (get_option('options_ramazan_saatleri') == 1) {
        // Ramazan tarih aralığı kontrolü
        $today = current_time('Y-m-d');
        $yil = (int) current_time('Y');

        // Önce veritabanındaki kullanıcı tanımlı tarihleri kontrol et
        $ramazan_baslangic = get_option('options_ramazan_baslangic', '');
        $ramazan_bitis     = get_option('options_ramazan_bitis', '');

        // Eğer veritabanında tarih yoksa varsayılan tabloya bak
        if (!$ramazan_baslangic || !$ramazan_bitis) {
            $dini_veriler = array(
                2025 => array('ramazan_baslangic' => '2025-03-01', 'ramazan_bitis' => '2025-03-29'),
                2026 => array('ramazan_baslangic' => '2026-02-18', 'ramazan_bitis' => '2026-03-19'),
                2027 => array('ramazan_baslangic' => '2027-02-08', 'ramazan_bitis' => '2027-03-08'),
                2028 => array('ramazan_baslangic' => '2028-01-28', 'ramazan_bitis' => '2028-02-25'),
                2029 => array('ramazan_baslangic' => '2029-01-16', 'ramazan_bitis' => '2029-02-13'),
                2030 => array('ramazan_baslangic' => '2030-01-06', 'ramazan_bitis' => '2030-02-03'),
            );
            $dini = isset($dini_veriler[$yil]) ? $dini_veriler[$yil] : $dini_veriler[2026];
            $ramazan_baslangic = $dini['ramazan_baslangic'];
            $ramazan_bitis     = $dini['ramazan_bitis'];
        }

        // Bugün Ramazan aralığında değilse gösterme
        if ($today < $ramazan_baslangic || $today > $ramazan_bitis) {
            return;
        }

        if (!function_exists('get_anasayfa_imsak_ve_vakitler')) {
            return;
        }
        $data = get_anasayfa_imsak_ve_vakitler();
        $gorunecek_hedef = isset($data['gorunecek_hedef']) ? $data['gorunecek_hedef'] : null;
        $gorunecek_etiket = isset($data['gorunecek_etiket']) ? $data['gorunecek_etiket'] : '';
        $vakitler = isset($data['vakitler']) && is_array($data['vakitler']) ? $data['vakitler'] : null;

        if (!$gorunecek_hedef && !$vakitler) {
            return;
        }

        $namaz_sayfa = get_permalink(get_page_by_path('namaz-vakitleri'));
        $namaz_sehir = mevzu_get_current_city();
        if (empty($namaz_sehir)) {
            $namaz_sehir = 'Karabük';
        }

        $template_url = get_bloginfo('template_url');
        $wp_now = esc_attr(date('c', current_time('timestamp')));
        $server_time_url = esc_url(admin_url('admin-ajax.php?action=mevzu_server_time'));
        ?>
        <div class="tema-widget my-3 page" id="imsak-geri-sayim-wrapper">
            <div class="widget_namaz_vakitleri_widget shadow-sm rounded-3 p-3">
                <div class="row align-items-center g-3 flex-wrap">
                    <div class="col-auto small">
                        <span
                            class="bg-white text-dark text-uppercase fw-semibold small rounded-3 p-2"><?php echo esc_html($namaz_sehir); ?></span>
                    </div>
                    <?php if ($vakitler && count($vakitler) > 0): ?>
                        <div class="col-12 col-md order-3 order-md-0 overflow-hidden">
                            <div class="swiper swiper-vakitler">
                                <div class="swiper-wrapper align-items-center">
                                    <?php foreach ($vakitler as $ad => $saat): ?>
                                        <div class="swiper-slide w-auto small pe-3">
                                            <span class="text-white text-opacity-75"><?php echo esc_html($ad); ?></span>
                                            <span class="text-white fw-semibold ms-1"><?php echo esc_html($saat); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    new Swiper('.swiper-vakitler', {
                                        slidesPerView: "auto",
                                        spaceBetween: 10,
                                        loop: true,
                                        autoplay: {
                                            delay: 2500,
                                            disableOnInteraction: false,
                                        }
                                    });
                                });
                            </script>
                        </div>
                    <?php endif; ?>
                    <div class="col-auto ms-md-auto d-flex align-items-center gap-2 gap-md-3 ms-auto flex-wrap">
                        <?php if ($gorunecek_hedef && $gorunecek_etiket): ?>
                            <div class="d-flex align-items-center gap-1 small">
                                <span class="text-white text-opacity-75"><?php echo esc_html($gorunecek_etiket); ?></span>
                                <strong class="text-white" id="vakit-geri-sayim"
                                    data-hedef="<?php echo esc_attr($gorunecek_hedef); ?>" data-wp-now="<?php echo $wp_now; ?>"
                                    data-server-time-url="<?php echo $server_time_url; ?>">--:--</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($gorunecek_hedef): ?>
            <script>
                (function () {
                    var el = document.getElementById('vakit-geri-sayim');
                    if (!el) return;
                    var hedefStr = el.getAttribute('data-hedef');
                    var wpNowStr = el.getAttribute('data-wp-now');
                    var serverTimeUrl = el.getAttribute('data-server-time-url');
                    if (!hedefStr) return;
                    var hedef = new Date(hedefStr).getTime();
                    var ofset = 0;
                    var intervalId;

                    function formatKalan(fark) {
                        if (fark <= 0) return '0sn';
                        var saat = Math.floor(fark / (1000 * 60 * 60));
                        var dakika = Math.floor((fark % (1000 * 60 * 60)) / (1000 * 60));
                        var saniye = Math.floor((fark % (1000 * 60)) / 1000);
                        var parcalar = [];
                        if (saat > 0) parcalar.push(saat + 's');
                        if (dakika > 0) parcalar.push(dakika + 'dk');
                        parcalar.push(saniye + 'sn');
                        return parcalar.join(' ');
                    }
                    function guncelle() {
                        var simdi = Date.now() + ofset;
                        var fark = hedef - simdi;
                        if (fark <= 0) {
                            el.textContent = '0sn';
                            if (intervalId) clearInterval(intervalId);
                            if (serverTimeUrl) {
                                // süre dolduğunda sayfayı yenilemek yerine AJAX ile sıradaki vakti al
                                fetchServerTimeAndStart();
                            } else {
                                location.reload();
                            }
                            return;
                        }
                        el.textContent = formatKalan(fark);
                    }
                    function basla() {
                        if (intervalId) clearInterval(intervalId);
                        guncelle();
                        intervalId = setInterval(guncelle, 1000);
                    }
                    function fetchServerTimeAndStart() {
                        fetch(serverTimeUrl).then(function (r) { return r.json(); }).then(function (res) {
                            if (res.success && res.data) {
                                if (res.data.iso) {
                                    ofset = new Date(res.data.iso).getTime() - Date.now();
                                }
                                if (res.data.hedef) {
                                    hedef = new Date(res.data.hedef).getTime();
                                    if (el.previousElementSibling && res.data.etiket) {
                                        el.previousElementSibling.textContent = res.data.etiket;
                                    }
                                }
                            } else if (wpNowStr) {
                                ofset = new Date(wpNowStr).getTime() - Date.now();
                            }
                            basla();
                        }).catch(function () {
                            if (wpNowStr) ofset = new Date(wpNowStr).getTime() - Date.now();
                            basla();
                        });
                    }

                    if (serverTimeUrl) {
                        fetchServerTimeAndStart();
                    } else {
                        if (wpNowStr) ofset = new Date(wpNowStr).getTime() - Date.now();
                        basla();
                    }
                })();
            </script>
        <?php endif;
    }
}

// Namaz Vakitleri

// Namaz Vakitleri Widget
class NamazVakitleri_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'namaz_vakitleri_widget', // Widget ID
            __('Mevzu² — Namaz Vakitleri', 'mevzu2'), // Widget adı
            array('description' => __('O günün namaz vakitlerini gösterir.', 'mevzu2')) // Widget açıklaması
        );
    }

    // Widget'ın ön yüzde görüntülenecek içeriği
    public function widget($args, $instance)
    {
        // Şehir bilgisini al. Öncelik: Varsayılan Seçimi (widget ayarı) > Çerez > Bileşen ayarı > Varsayılan şehir
        $use_default = isset($instance['use_default_city']) ? (bool)$instance['use_default_city'] : true;

        if ($use_default) {
            // Her zaman site varsayılan şehri kullan
            $city_raw = mevzu_get_current_city();
        } elseif (isset($_COOKIE['mevzu_hava_sehir']) && !empty($_COOKIE['mevzu_hava_sehir'])) {
            $city_raw = sanitize_text_field($_COOKIE['mevzu_hava_sehir']);
        } elseif (!empty($instance['city'])) {
            $city_raw = $instance['city'];
        } else {
            $city_raw = mevzu_get_current_city();
        }
        $city = $city_raw;

        if ($city) {
            $cityId = array_search(normalizeString($city), diyanet_sehirler(), true);

            echo $args['before_widget'];

            $title = apply_filters('widget_title', $instance['title']);
            ?>
            <div class="row align-items-center g-0">
                <?php if (!empty($title)): ?>
                    <div class="col">
                        <h2><?php echo esc_html($title); ?></h2>
                    </div>
                <?php endif; ?>
                <div class="col-auto pe-2">
                    <span class="bg-dark bg-opacity-10 fw-bold fz-12 py-1 px-3 rounded-pill text-dark">
                        <?php echo mb_convert_case(turkce_karakter($city), MB_CASE_TITLE, "UTF-8"); ?>
                    </span>
                </div>
            </div>
            <?php
            if ($cityId) {
                $prayerTimes = get_prayer_times_data($cityId);
                if (!isset($prayerTimes['error'])) {
                    if (!empty($prayerTimes)) {
                        $firstPrayerTime = $prayerTimes[0];

                        // Miladi tarih parçalama
                        preg_match('/(\d{2}) (\w+) (\d{4}) (\w+)/u', $firstPrayerTime['miladi_tarih'], $m);
                        $gun = ltrim($m[1], '0');
                        $ay_adi = $m[2];
                        $yil = $m[3];
                        $hafta_gunu = $m[4];
                        $ay_numarasi = date('n', strtotime("01 $m[2] $m[3]"));

                        // Hicri tarih parçalama
                        preg_match('/(\d+) (\w+) (\d{4})/u', $firstPrayerTime['hicri_tarih'], $h);
                        $hicri_gun = $h[1];
                        $hicri_ay = $h[2];
                        $hicri_yil = $h[3];

                        // Vakit bilgileri
                        $vakitler = [
                            'İmsak' => ['zaman' => 'imsak', 'gorsel' => 'horizon.svg'],
                            'Güneş' => ['zaman' => 'gunes', 'gorsel' => 'fog-day.svg'],
                            'Öğle' => ['zaman' => 'ogle', 'gorsel' => 'dust-day.svg'],
                            'İkindi' => ['zaman' => 'ikindi', 'gorsel' => 'clear-day.svg'],
                            'Akşam' => ['zaman' => 'aksam', 'gorsel' => 'partly-cloudy-night.svg'],
                            'Yatsı' => ['zaman' => 'yatsi', 'gorsel' => 'clear-night.svg']
                        ];

                        // Vakit saatlerine doğru tarih + timezone ata
                        $tz = new DateTimeZone(get_option('timezone_string') ?: 'Europe/Istanbul');
                        $bugun = new DateTime('now', $tz);
                        $simdi = new DateTime('now', $tz);
                        $vakitSaatleri = [];
                        foreach ($vakitler as $etiket => $veri) {
                            $saat = DateTime::createFromFormat('H:i', $firstPrayerTime[$veri['zaman']], $tz);
                            // Bugunun tarihini ata
                            $saat->setDate((int)$bugun->format('Y'), (int)$bugun->format('n'), (int)$bugun->format('j'));
                            $vakitSaatleri[] = ['etiket' => $etiket, 'saat' => $saat];
                        }

                        // Şu anki vakti tespit et
                        $simdikiVakit = null;
                        $sonrakiVakit = null;
                        for ($i = 0; $i < count($vakitSaatleri); $i++) {
                            $baslangic = $vakitSaatleri[$i]['saat'];
                            if (isset($vakitSaatleri[$i + 1])) {
                                $bitis = $vakitSaatleri[$i + 1]['saat'];
                            } else {
                                // Yatsıdan sonra ertesi gün İmsak
                                $bitis = clone $vakitSaatleri[0]['saat'];
                                $bitis->modify('+1 day');
                            }

                            if ($simdi >= $baslangic && $simdi < $bitis) {
                                $simdikiVakit = $vakitSaatleri[$i]['etiket'];
                                if (isset($vakitSaatleri[$i + 1])) {
                                    $sonrakiVakit = $vakitSaatleri[$i + 1];
                                } else {
                                    // Yatsı vaktindeyiz, ertesi gün İmsak
                                    $imsak_yarin = clone $vakitSaatleri[0]['saat'];
                                    $imsak_yarin->modify('+1 day');
                                    $sonrakiVakit = ['etiket' => $vakitSaatleri[0]['etiket'], 'saat' => $imsak_yarin];
                                }
                                break;
                            }
                        }

                        if ($simdikiVakit === null) {
                            $simdikiVakit = end($vakitSaatleri)['etiket'];
                            // Gece yatsıdan sonra, ertesi gün imsak
                            $imsak_yarin = clone $vakitSaatleri[0]['saat'];
                            $imsak_yarin->modify('+1 day');
                            $sonrakiVakit = ['etiket' => $vakitSaatleri[0]['etiket'], 'saat' => $imsak_yarin];
                        }

                        ?>
                        <div class="row fw-normal align-items-center">
                            <div class="col-2 fz-40 fw-bold text-end ps-4"><?= $gun ?></div>
                            <div class="col-auto small">
                                <span class="fw-semibold"><?= $ay_adi ?></span> <?= $hafta_gunu ?>
                                <div class="row small">
                                    <div class="col-auto"><?= $hicri_yil ?> HİCRİ <?= $hicri_ay ?>                         <?= $hicri_gun ?></div>
                                </div>
                            </div>
                            <div class="col text-center">
                                <?php if ($simdikiVakit): ?>
                                    <div class="row align-items-center small">
                                        <div class="col-auto">
                                            <?php $iconPath = get_template_directory_uri() . '/img/assets/weatherV2/line/' . $vakitler[$simdikiVakit]['gorsel']; ?>
                                            <img src="<?= $iconPath ?>" alt="<?= $simdikiVakit ?> Vakti" width="60" height="60" class="w-60">
                                        </div>
                                        <div class="col ps-0 text-start fw-semibold">
                                            <div class="text-body fw-normal small">Şuanki vakit</div>
                                            <?= $simdikiVakit ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row small fw-normal g-0 mt-3" id="vakitlerListesi">
                            <?php foreach ($vakitler as $etiket => $anahtar):
                                // Eğer şu anki vakit Güneş ise, bir sonraki vakit için geri sayımı Öğle'ye ekliyoruz
                                $aktifSinif = ($etiket === $simdikiVakit) ? 'primary' : 'dark';
                                ?>
                                <?php if ($etiket === $sonrakiVakit['etiket']): ?>
                                    <div class="col-12 py-2 px-3 text-uppercase bg-opacity-10 bg-primary">
                                        <div class="row align-items-center">
                                            <div class="col-8 small-2"><?= $sonrakiVakit['etiket'] ?> vaktine kalan süre</div>
                                            <div class="col-4 px-3 fw-bold small"><span class="bg-primary rounded-3 me-2">&nbsp;</span><span
                                                    id="geriSayim" data-hedef="<?= $sonrakiVakit['saat']->format('c') ?>"></span></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-5 py-2 px-3<?= ($etiket === $simdikiVakit) ? ' fw-semibold' : '' ?>">
                                    <span class="bg-<?= $aktifSinif ?> rounded-3 me-2">&nbsp;</span>
                                    <?= ucfirst($etiket) ?>
                                </div>
                                <div class="col-7 py-2 px-3<?= ($etiket === $simdikiVakit) ? ' fw-semibold' : '' ?>">
                                    <?= $firstPrayerTime[$anahtar['zaman']] ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const hedefZaman = document.getElementById('geriSayim')?.getAttribute('data-hedef');
                                if (!hedefZaman) return;

                                const hedef = new Date(hedefZaman).getTime();
                                const sayaç = document.getElementById('geriSayim');

                                function geriSay() {
                                    const simdi = new Date().getTime();
                                    const fark = hedef - simdi;

                                    if (fark <= 0) {
                                        sayaç.textContent = '00:00:00';
                                        return;
                                    }

                                    const saat = Math.floor((fark / (1000 * 60 * 60)) % 24);
                                    const dakika = Math.floor((fark / (1000 * 60)) % 60);
                                    const saniye = Math.floor((fark / 1000) % 60);

                                    sayaç.textContent =
                                        String(saat).padStart(2, '0') + ':' +
                                        String(dakika).padStart(2, '0') + ':' +
                                        String(saniye).padStart(2, '0');
                                }

                                geriSay();
                                setInterval(geriSay, 1000);
                            });
                        </script>
                        <?php
                    }
                } else {
                    echo '<div class="alert alert-danger">' . esc_html($prayerTimes['error']) . '</div>';
                }
            }

        } else {
            echo '<div class="alert alert-warning">Lütfen bir şehir seçin.</div>';
        }

        echo $args['after_widget'];
    }

    // Widget ayarlarını yönetme formu
    public function form($instance)
    {
        $title    = !empty($instance['title']) ? $instance['title'] : __('Namaz Vakitleri', 'mevzu2');
        $city     = !empty($instance['city'])  ? $instance['city']  : '';
        $use_default = !empty($instance['use_default_city']) ? (bool)$instance['use_default_city'] : true;
        $default_city_name = mevzu_get_current_city();
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Başlık:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox"
                    id="<?php echo $this->get_field_id('use_default_city'); ?>"
                    name="<?php echo $this->get_field_name('use_default_city'); ?>"
                    value="1"
                    <?php checked($use_default, true); ?>
                    style="width:16px;height:16px;"
                    onchange="(function(cb){
                        var sel = document.getElementById('<?php echo $this->get_field_id('city'); ?>_wrap');
                        if(sel) sel.style.display = cb.checked ? 'none' : 'block';
                    })(this)">
                <span>Varsayılan şehri seç <strong>(<?php echo esc_html(mb_convert_case($default_city_name, MB_CASE_TITLE, 'UTF-8')); ?>)</strong></span>
            </label>
        </p>
        <p id="<?php echo $this->get_field_id('city'); ?>_wrap"
           style="<?php echo $use_default ? 'display:none' : ''; ?>">
            <label for="<?php echo $this->get_field_id('city'); ?>"><?php _e('Şehir:'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('city'); ?>"
                name="<?php echo $this->get_field_name('city'); ?>">
                <option value="">-- Şehir Seçin --</option>
                <?php
                foreach (diyanet_sehirler() as $id => $name) {
                    $selected = (strtoupper($city) === $name) ? 'selected' : '';
                    echo '<option value="' . esc_attr($name) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }

    // Widget ayarlarını güncelleme
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title']             = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['use_default_city']  = !empty($new_instance['use_default_city']) ? 1 : 0;
        $instance['city']              = (!empty($new_instance['city'])) ? strip_tags($new_instance['city']) : '';
        return $instance;
    }
}

// Widget'ı kaydet
function register_namaz_vakitleri_widget()
{
    register_widget('NamazVakitleri_Widget');
}
add_action('widgets_init', 'register_namaz_vakitleri_widget');
// Namaz Vakitleri Widget


// TransientAPI Editor Bildirim
function ozel_gutenberg_bildirim_script()
{
    wp_enqueue_script(
        'custom-editor-notice',
        get_template_directory_uri() . '/gutenberg-custom-notice.js', // JS dosyasının yolu
        array('wp-data', 'wp-notices'),
        null,
        true
    );
}
add_action('enqueue_block_editor_assets', 'ozel_gutenberg_bildirim_script');


// TransientAPI Editor Bildirim


// Doviz v2
function get_doviz_data()
{
    $transient_key = 'doviz_verisi';

    if (false === ($doviz_data = get_transient($transient_key))) {
        $response = wp_remote_get('https://finance.truncgil.com/api/today.json', array(
            'timeout' => 15 // Timeout eklendi - API asılma önlemi
        ));

        if (is_wp_error($response)) {
            return 'Veri alınamadı';
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return 'API hatası: ' . $response_code; // HTTP hata kontrolü
        }

        $body = wp_remote_retrieve_body($response);
        $doviz_data = json_decode($body, true);

        if (!is_array($doviz_data)) {
            return 'Geçersiz veri';
        }

        // 🔴 AZN'yi kaldır
        unset($doviz_data['Rates']['AZN']);

        set_transient($transient_key, $doviz_data, 600);
    }
    return $doviz_data;
}


function kur_ulke($kur)
{
    $kur_ulkeleri = [
        'USD' => 'US',    // Amerikan Doları
        'EUR' => 'EU',    // Euro (Avrupa Birliği)
        'GBP' => 'GB',    // İngiltere
        'CHF' => 'CH',    // İsviçre
        'CAD' => 'CA',    // Kanada
        'AUD' => 'AU',    // Avustralya
        'DKK' => 'DK',    // Danimarka
        'SEK' => 'SE',    // İsveç
        'NOK' => 'NO',    // Norveç
        'JPY' => 'JP',    // Japonya
        'SAR' => 'SA',    // Suudi Arabistan
        'KWD' => 'KW',    // Kuveyt
        'BGN' => 'BG',    // Bulgaristan
        'RON' => 'RO',    // Romanya
        'IRR' => 'IR',    // İran
        'CNY' => 'CN',    // Çin
        'PKR' => 'PK',    // Pakistan
        'QAR' => 'QA',    // Katar
        'RUB' => 'RU',    // Rusya
        'AED' => 'AE',    // Birleşik Arap Emirlikleri
        'TRY' => 'TR',    // Türkiye
        'ZAR' => 'ZA',    // Güney Afrika
        'BRL' => 'BR',    // Brezilya
        'IDR' => 'ID',    // Endonezya
        'INR' => 'IN',    // Hindistan
        'MXN' => 'MX',    // Meksika
        'MYR' => 'MY',    // Malezya
        'SGD' => 'SG',    // Singapur
        'KRW' => 'KR',    // Güney Kore
        'THB' => 'TH',    // Tayland
        'HKD' => 'HK',    // Hong Kong
        'PLN' => 'PL',    // Polonya
        'CZK' => 'CZ',    // Çekya
        'HUF' => 'HU',    // Macaristan
        'ILS' => 'IL',    // İsrail
        'EGP' => 'EG',    // Mısır
        'UAH' => 'UA',    // Ukrayna
        'JOD' => 'JO',    // Ürdün
        'LBP' => 'LB',    // Lübnan
        'LYD' => 'LY',    // Libya
        'TND' => 'TN',    // Tunus
        'MAD' => 'MA',    // Fas
        'DZD' => 'DZ',    // Cezayir
        'BHD' => 'BH',    // Bahreyn
        'OMR' => 'OM',    // Umman
        'AZN' => 'AZ',    // Azerbaycan
        'GEL' => 'GE',    // Gürcistan
        'IQD' => 'IQ',    // Irak
        'TMT' => 'TM',    // Türkmenistan
        'UZS' => 'UZ',    // Özbekistan
        'AFN' => 'AF',    // Afganistan
        'NZD' => 'NZ',
        'ARS' => 'AR',
        'ALL' => 'AL',
        'BAM' => 'BA',
        'CLP' => 'CL',
        'COP' => 'CO',
        'CRC' => 'CR',
        'ISK' => 'IS',
        'KZT' => 'KZ',
        'LKR' => 'LK',
        'MDL' => 'MD',
        'MKD' => 'MK',
        'PEN' => 'PE',
        'PHP' => 'PH',
        'RSD' => 'RS',
        'SYP' => 'SY',
        'TWD' => 'TW',
        'UYU' => 'UY',
    ];
    return isset($kur_ulkeleri[$kur]) ? $kur_ulkeleri[$kur] : 0;
}
// Doviz v2

function kur_name($kur)
{
    $kur_names = [
        'GRAMALTIN' => 'Altın (TL/GR)',
        'GUMUS' => 'Gümüş',
        'GRAMHASALTIN' => 'Has Altın',
        'CEYREKALTIN' => 'Çeyrek Altın',
        'YARIMALTIN' => 'Yarım Altın',
        'TAMALTIN' => 'Tam Altın',
        'CUMHURIYETALTINI' => 'Cumhuriyet Altını',
        'ATAALTIN' => 'Ata Altın',
        '14AYARALTIN' => '14 Ayar Altın TL/Gr',
        '18AYARALTIN' => '18 Ayar Altın TL/Gr',
        '22AYARBILEZIK' => '22 Ayar Bilezik',
        'IKIBUCUKALTIN' => '2,5 Altın',
        'BESLIALTIN' => 'Beşli Altın',
        'GREMSEALTIN' => 'Gremse Altın',
        'RESATALTIN' => 'Reşat Altın',
        'HAMITALTIN' => 'Hamit Altın'
    ];
    return isset($kur_names[$kur]) ? $kur_names[$kur] : 0;
}

// Bizi sosyal medyada takip edin
function sayfa_parent()
{
    $current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
    $parsed_url = parse_url($current_url);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $parts = explode('/', trim($path, '/'));
    if (isset($parts[0])) {
        $base_path = '/' . $parts[0] . '/';
    } else {
        $base_path = '/';
    }
    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $base_path;
    return $base_url;
}

function takipedin_shortcode()
{
    $sosyal_medya_hesaplari = get_option('options_gosterilecek_sosyal_medya_hesaplari');

    $class = 'ripple py-2 px-3 rounded-3 border border-2 btn-border-hover text-dark d-flex align-items-center';

    if (!$sosyal_medya_hesaplari || !is_array($sosyal_medya_hesaplari)) {
        return '';
    }

    $links_output = '';

    // Facebook Hesabı Kontrolü
    if (in_array('facebook', $sosyal_medya_hesaplari)) {
        $facebook = get_option('options_facebook');
        if ($facebook) {
            $links_output .= '
            <div class="col col-md-auto">
                <a href="' . esc_url($facebook) . '" target="_blank" class="' . $class . '">
                    <i class="ri-facebook-circle-fill h5 m-0 me-2 text-facebook"></i>
                    Facebook
                </a>
            </div>';
        }
    }

    // Twitter Hesabı Kontrolü
    if (in_array('twitter', $sosyal_medya_hesaplari)) {
        $twitter = get_option('options_twitter');
        if ($twitter) {
            $links_output .= '
            <div class="col col-md-auto">
                <a href="' . esc_url($twitter) . '" target="_blank" class="' . $class . '">
                    <i class="ri-twitter-x-fill h5 m-0 me-2 text-twitter"></i>
                    Twitter
                </a>
            </div>';
        }
    }

    // Instagram Hesabı Kontrolü
    if (in_array('instagram', $sosyal_medya_hesaplari)) {
        $instagram = get_option('options_instagram');
        if ($instagram) {
            $links_output .= '
            <div class="col col-md-auto">
                <a href="' . esc_url($instagram) . '" target="_blank" class="' . $class . '">
                    <i class="ri-instagram-line h5 m-0 me-2 text-instagram"></i>
                    Instagram
                </a>
            </div>';
        }
    }

    // YouTube Hesabı Kontrolü
    if (in_array('youtube', $sosyal_medya_hesaplari)) {
        $youtube = get_option('options_youtube');
        if ($youtube) {
            $links_output .= '
            <div class="col col-md-auto">
                <a href="' . esc_url($youtube) . '" target="_blank" class="' . $class . '">
                    <i class="ri-youtube-fill h5 m-0 me-2 text-youtube"></i>
                    YouTube
                </a>
            </div>';
        }
    }


    // Whatsapp Hesabı Kontrolü
    if (in_array('whatsapp', $sosyal_medya_hesaplari)) {
        $whatsapp = get_option('options_whatsapp');
        if ($whatsapp) {
            $links_output .= '
            <div class="col col-md-auto">
                <a href="' . esc_url($whatsapp) . '" target="_blank" class="' . $class . '">
                    <svg class="d-block d-md-inline-block mx-auto me-md-2 text-whatsapp" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222c0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222c0-59.3-25.2-115-67.1-157m-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4l-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2c0-101.7 82.8-184.5 184.6-184.5c49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6m101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18c-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4c-32.6-16.3-54-29.1-75.5-66c-5.7-9.8 5.7-9.1 16.3-30.3c1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5c-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8c35.2 15.2 49 16.5 66.6 13.9c10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6"></path></svg>
                    WhatsApp
                </a>
            </div>';
        }
    }

    // Hiç link yoksa gösterme
    if (empty($links_output)) {
        return '';
    }

    $output = '<div class="border-top mt-3 mt-lg-4 fw-semibold text-center">
        <h6 class="my-3 fw-semibold">Bizi sosyal medyadan takip edin</h6>
        <div class="row align-items-center justify-content-center text-center fz-14 g-3">';
    $output .= $links_output;
    $output .= '</div></div>';

    return $output;
}
add_shortcode('takipedin', 'takipedin_shortcode');

function header_takipedin_shortcode()
{
    $sosyal_medya_hesaplari = get_option('options_gosterilecek_sosyal_medya_hesaplari');

    $class = 'ripple px-2 rounded border border-2 btn-border-hover text-dark fw-semibold d-flex align-items-center';

    if ($sosyal_medya_hesaplari) {
        $output = '
            <div class="row align-items-center justify-content-end text-center g-3 small">';

        // Facebook Hesabı Kontrolü
        if (in_array('facebook', $sosyal_medya_hesaplari)) {
            $facebook = get_option('options_facebook');
            if ($facebook) {
                $output .= '
                <div class="col col-md-auto">
                    <a href="' . esc_url($facebook) . '" target="_blank" class="' . $class . '">
                        <i class="ri-facebook-circle-fill text-facebook h5 m-0 me-1"></i>
                        Facebook
                    </a>
                </div>';
            }
        }

        // Twitter Hesabı Kontrolü
        if (in_array('twitter', $sosyal_medya_hesaplari)) {
            $twitter = get_option('options_twitter');
            if ($twitter) {
                $output .= '
                <div class="col col-md-auto">
                    <a href="' . esc_url($twitter) . '" target="_blank" class="' . $class . '">
                        <i class="ri-twitter-x-fill text-twitter h5 m-0 me-1"></i>
                        Twitter
                    </a>
                </div>';
            }
        }

        // Instagram Hesabı Kontrolü
        if (in_array('instagram', $sosyal_medya_hesaplari)) {
            $instagram = get_option('options_instagram');
            if ($instagram) {
                $output .= '
                <div class="col col-md-auto">
                    <a href="' . esc_url($instagram) . '" target="_blank" class="' . $class . '">
                        <i class="ri-instagram-fill text-instagram h5 m-0 me-1"></i>
                        Instagram
                    </a>
                </div>';
            }
        }

        // YouTube Hesabı Kontrolü
        if (in_array('youtube', $sosyal_medya_hesaplari)) {
            $youtube = get_option('options_youtube');
            if ($youtube) {
                $output .= '
                <div class="col col-md-auto">
                    <a href="' . esc_url($youtube) . '" target="_blank" class="' . $class . '">
                        <i class="ri-youtube-fill text-youtube h5 m-0 me-1"></i>
                        YouTube
                    </a>
                </div>';
            }
        }


        // Whatsapp Hesabı Kontrolü
        if (in_array('whatsapp', $sosyal_medya_hesaplari)) {
            $whatsapp = get_option('options_whatsapp');
            if ($whatsapp) {
                $output .= '
                <div class="col col-md-auto">
                    <a href="' . esc_url($whatsapp) . '" target="_blank" class="' . $class . '">
                        <svg class="d-block d-md-inline-block mx-auto me-md-2 text-whatsapp" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222c0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222c0-59.3-25.2-115-67.1-157m-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4l-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2c0-101.7 82.8-184.5 184.6-184.5c49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6m101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18c-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4c-32.6-16.3-54-29.1-75.5-66c-5.7-9.8 5.7-9.1 16.3-30.3c1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5c-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8c35.2 15.2 49 16.5 66.6 13.9c10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6"></path></svg>
                        WhatsApp
                    </a>
                </div>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    // Eğer sosyal medya hesapları yoksa hiçbir şey döndürme
    return '';
}
add_shortcode('header_takipedin', 'header_takipedin_shortcode');
// Bizi sosyal medyada takip edin

// Doviz Cevirici
class DovizCeviriciWidget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'doviz_cevirici_widget',
            __('Mevzu² — Döviz Çevirici', 'text_domain'),
            ['description' => __('Canlı döviz kuru çevirici.', 'text_domain')]
        );
        // add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    // public function enqueue_assets()
    // {
    //     wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    //     wp_enqueue_style('select2-bootstrap5-css', 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');
    //     wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
    // }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title'] ?? 'Döviz Çevirici');
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];

        
            if (!function_exists('get_doviz_data')) {
                echo '<p>Döviz verileri bulunamadı.</p>';
                echo $args['after_widget'];
                return;
            }

            $data = get_doviz_data();
            if (!isset($data['Rates']) || !is_array($data['Rates'])) {
                echo '<p>Döviz kuru bilgisi alınamadı.</p>';
                echo $args['after_widget'];
                return;
            }

            $rates = $data['Rates'];
            $from_selected = strtoupper(get_query_var('kur') ?: 'USD');
            $to_selected = 'TRY';

            ?>

            <div class="doviz-cevirici-widget">
                <div class="row m-0 my-3 justify-content-center">
                    <div class="col-10 col-md">
                        <div class="row gy-2">
                            <div class="col-12">
                                <select id="fromCurrency" class="form-control form-control-sm select2-bootstrap5">
                                    <option value="1" data-buying="1" data-selling="1" <?php echo ($from_selected == 'TRY') ? 'selected' : ''; ?>>Türk Lirası (TRY)</option>
                                    <?php foreach ($rates as $code => $rate):
                                        // Currency tipinde Name alanı olmaz, kodu ad olarak kullan
                                        if (kur_ulke($code) != 0): ?>
                                            <option value="<?php echo esc_attr($code); ?>"
                                                data-buying="<?php echo esc_attr($rate['Buying'] ?? 0); ?>"
                                                data-selling="<?php echo esc_attr($rate['Selling'] ?? 0); ?>" <?php echo ($from_selected == $code) ? 'selected' : ''; ?>>
                                                <?php
                                                $display_name = isset($rate['Name']) ? $rate['Name'] : $code;
                                                echo esc_html($display_name . ' (' . $code . ')');
                                                ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <select id="toCurrency" class="form-control form-control-sm select2-bootstrap5">
                                    <option value="1" data-buying="1" data-selling="1" <?php echo ($to_selected == 'TRY') ? 'selected' : ''; ?>>Türk Lirası (TRY)</option>
                                    <?php foreach ($rates as $code => $rate):
                                        // Currency tipinde Name alanı olmaz, kodu ad olarak kullan
                                        if (kur_ulke($code) != 0): ?>
                                            <option value="<?php echo esc_attr($code); ?>"
                                                data-buying="<?php echo esc_attr($rate['Buying'] ?? 0); ?>"
                                                data-selling="<?php echo esc_attr($rate['Selling'] ?? 0); ?>" <?php echo ($to_selected == $code) ? 'selected' : ''; ?>>
                                                <?php
                                                $display_name = isset($rate['Name']) ? $rate['Name'] : $code;
                                                echo esc_html($display_name . ' (' . $code . ')');
                                                ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-2 col-md-auto ps-lg-1">
                        <button class="btn btn-sm btn-outline-dark swap-icon py-2 px-2 border-2 shadow-sm h-100 fs-5"
                            id="swapCurrencies">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>

                <div class="row m-0 my-3 justify-content-center align-items-center">
                    <div class="col">
                        <input class="form-control form-control-sm py-2" type="number" id="amount" placeholder="Miktar" value="1" min="0"
                            step="0.01">
                    </div>
                    <div class="col-auto text-center fs-5">
                        <i class="ri-equal-line text-body"></i>
                    </div>
                    <div class="col">
                        <input class="form-control form-control-sm py-2" type="text" id="result" placeholder="Sonuç">
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const fromCurrency = document.getElementById('fromCurrency');
                    const toCurrency = document.getElementById('toCurrency');
                    const amountInput = document.getElementById('amount');
                    const resultInput = document.getElementById('result');
                    const swapButton = document.getElementById('swapCurrencies');

                    // select2 yoksa native select kullanıyoruz
                    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                        jQuery('#fromCurrency').select2({ theme: 'bootstrap-5' });
                        jQuery('#toCurrency').select2({ theme: 'bootstrap-5' });
                    }

                    function calculate() {
                        const fromRate = parseFloat(fromCurrency.selectedOptions[0].dataset.selling);
                        const toRate = parseFloat(toCurrency.selectedOptions[0].dataset.selling);
                        const amount = parseFloat(amountInput.value);

                        if (!isNaN(fromRate) && !isNaN(toRate) && !isNaN(amount)) {
                            const result = (fromRate / toRate) * amount;
                            resultInput.value = result.toFixed(4);
                        }
                    }

                    fromCurrency.addEventListener('change', calculate);
                    toCurrency.addEventListener('change', calculate);
                    amountInput.addEventListener('input', calculate);

                    swapButton.addEventListener('click', function () {
                        const tempIndex = fromCurrency.selectedIndex;
                        fromCurrency.selectedIndex = toCurrency.selectedIndex;
                        toCurrency.selectedIndex = tempIndex;

                        jQuery('#fromCurrency').trigger('change');
                        jQuery('#toCurrency').trigger('change');
                        calculate();
                    });

                    calculate();
                });
            </script>
            <?php

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Döviz Çevirici', 'mevzu2');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Başlık:', 'mevzu2'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : 'Döviz Çevirici';
        return $instance;
    }
}

function register_doviz_cevirici_widget()
{
    register_widget('DovizCeviriciWidget');
}
add_action('widgets_init', 'register_doviz_cevirici_widget');
// Doviz Cevirici

// 1. Yeni URL yapısını tanımla
function ozel_rewrite()
{
    add_rewrite_rule(
        '^finans/([^/]+)/?$',
        'index.php?pagename=finans&kur=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^hava-durumu/([^/]+)/?$',
        'index.php?pagename=hava-durumu&sehir=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^nobetci-eczaneler/([^/]+)/?$',
        'index.php?pagename=nobetci-eczaneler&sehir=$matches[1]',
        'top'
    );
}
add_action('init', 'ozel_rewrite');

// 2. 'kur' query değişkenini tanıt
function ozel_doviz_query_vars($vars)
{
    $vars[] = 'kur';
    return $vars;
}
add_filter('query_vars', 'ozel_doviz_query_vars');

function havadurumu_query_vars($vars)
{
    $vars[] = 'sehir';
    return $vars;
}
add_filter('query_vars', 'havadurumu_query_vars');
// 1. Yeni URL yapısını tanımla

// WIDGET : Anlık Kur
class widget_anlikkur extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'anlik_kur_widget',
            'Mevzu² — Anlık Kur Widgetı',
            ['description' => 'Canlı döviz bilgilerini gösteren widget']
        );
    }
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        $title = apply_filters('widget_title', $instance['title'] ?? 'Serbest Piyasa');
        $adet = !empty($instance['adet']) ? absint($instance['adet']) : 200;
        $gizle = !empty($instance['gizle']) ? absint($instance['gizle']) : 0;
        $veriler = array_slice(get_doviz_data()['Rates'], 0, $adet, true);
        $meta = get_doviz_data()['Meta_Data'];
        if (is_array($veriler)) {
            ?>
            <div class="row align-items-center my-2">
                <div class="col">
                    <h2 class="m-0"><?php echo '<a href="' . get_bloginfo('url') . '/finans" class="text-link">' . esc_html($title) . '</a>' ?>
                    </h2>
                </div>
                <div class="col-auto ms-md-auto small fw-normal">
                    <span class="pe-3">
                        <?php
                        $post_time = strtotime($meta['Update_Date']); // veya zaten timestamp ise direkt kullan
                        $current_time = current_time('timestamp'); // WP local time
            
                        $diff_seconds = abs($current_time - $post_time);
                        if ($diff_seconds < 60) {
                            echo floor($diff_seconds) . 'sn önce';
                        } elseif ($diff_seconds < 3600) {
                            echo floor($diff_seconds / 60) . 'dk önce';
                        } else {
                            echo floor($diff_seconds / 3600) . ' saat önce';
                        }
                        ?>
                        <span class="text-body"> güncellendi</span>
                    </span>
                </div>
            </div>
            <div class="table-responsive" style="max-height:696px">
                <table class="table table-stripped table-hover m-0">
                    <thead>
                        <tr>
                            <th class="p-2 fw-semibold">Kur</th>
                            <th class="p-2 fw-semibold">Alış</th>
                            <th class="p-2 fw-semibold">Satış</th>
                            <th class="p-2 fw-semibold">Değişim</th>
                        </tr>
                    </thead>
                    <tbody class="small fw-light">
                        <?php foreach ($veriler as $kur_uzun => $kur) {
                            if (isset($kur['Buying'], $kur['Selling']) && kur_ulke($kur_uzun) !== '0' && kur_ulke($kur_uzun) !== 0 && $kur['Buying'] > 0) {
                                $degisim_class = $kur['Change'] > 0 ? ' text-success' : ' text-danger';
                                $degisim = $kur['Change'] > 0
                                    ? '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 13V8h-5"/><path d="m20 8l-5 5c-.883.883-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373s-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373l-3 3"/></g></svg>'
                                    : '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 11v5h-5"/><path d="m20 16l-5-5c-.883-.883-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373s-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373L4 8"/></g></svg>';
                                ?>
                                <tr class="small">
                                    <td class="p-2">
                                        <img loading="lazy" class="me-2 rounded-circle" width="24"
                                            src="https://s3-symbol-logo.tradingview.com/country/<?php echo kur_ulke($kur_uzun); ?>.svg">
                                        <a class="text-link" href="<?php echo bloginfo('url') . '/finans/' . $kur_uzun; ?>">
                                            <b><?php echo esc_html($kur_uzun); ?></b>
                                            <span
                                                class="text-secondary<?= ($gizle != 0) ? ' d-none' : ''; ?>"><?php echo esc_html($kur['Name'] ?? ''); ?></span>
                                        </a>
                                    </td>
                                    <td class="p-2"><?php echo esc_html($kur['Buying']); ?></td>
                                    <td class="p-2"><?php echo esc_html($kur['Selling']); ?></td>
                                    <td class="p-2<?php echo $degisim_class; ?>"><?php echo $degisim . esc_html($kur['Change']); ?>%
                                    </td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Anlık Kur', 'mevzu2');
        $adet = !empty($instance['adet']) ? $instance['adet'] : 15;
        $gizle = !empty($instance['gizle']) ? $instance['gizle'] : 0;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('adet'); ?>">Kaç kur gösterilsin?</label>
            <input class="widefat" id="<?php echo $this->get_field_id('adet'); ?>"
                name="<?php echo $this->get_field_name('adet'); ?>" type="number" value="<?php echo esc_attr($adet); ?>" min="1"
                max="100">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        return [
            'title' => sanitize_text_field($new_instance['title']),
            'adet' => (!empty($new_instance['adet'])) ? absint($new_instance['adet']) : 15,
            'gizle' => (!empty($new_instance['gizle'])) ? absint($new_instance['gizle']) : 15,
        ];
    }
}

add_action('widgets_init', function () {
    register_widget('widget_anlikkur');
});
// WIDGET : Anlık Kur

// WIDGET : Altın
class widget_anlikaltin extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'anlik_maden_widget',
            'Mevzu² — Altın Widgetı',
            ['description' => 'Canlı değerli madenler bilgilerini gösteren widget']
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        // Başlık
        $title = apply_filters('widget_title', $instance['title'] ?? 'Altın Piyasası');

        $adet = !empty($instance['adet']) ? absint($instance['adet']) : 200;
        $gizle = !empty($instance['gizle']) ? absint($instance['gizle']) : 0;
        $doviz_data_altin = get_doviz_data();
        if (!is_array($doviz_data_altin) || !isset($doviz_data_altin['Rates'])) {
            echo '<p class="p-3 text-muted small">Altın verileri alınamadı.</p>';
            echo $args['after_widget'];
            return;
        }
        // Sadece altın, platin ve palyadyum tiplerine göre filtrele, sonra adet kadar alıştir
        $gold_types = array('Gold', 'Platinum', 'Palladium');
        $all_rates = $doviz_data_altin['Rates'];
        $veriler = [];
        foreach ($all_rates as $kod => $kur) {
            if (isset($kur['Type'], $kur['Name'], $kur['Buying'], $kur['Selling'])
                && in_array($kur['Type'], $gold_types)
                && $kur['Buying'] > 0) {
                $veriler[$kod] = $kur;
                if (count($veriler) >= $adet) break;
            }
        }
        $meta = $doviz_data_altin['Meta_Data'] ?? array('Update_Date' => current_time('mysql'));

        $finans_url = get_option('options_finans_sayfasi') ? get_permalink(get_option('options_finans_sayfasi')) : get_bloginfo('url') . '/finans';
        if (!empty($veriler)) {
            ?>
            <div class="row align-items-center my-2">
                <div class="col">
                    <h2 class="m-0"><?php echo '<a href="' . esc_url($finans_url) . '" class="text-link">' . esc_html($title) . '</a>' ?>
                    </h2>
                </div>
                <div class="col-auto ms-md-auto small fw-normal">
                    <span class="pe-3">
                        <?php
                        $post_time = strtotime($meta['Update_Date']); // veya zaten timestamp ise direkt kullan
                        $current_time = current_time('timestamp'); // WP local time
            
                        $diff_seconds = abs($current_time - $post_time);
                        if ($diff_seconds < 60) {
                            echo floor($diff_seconds) . 'sn önce';
                        } elseif ($diff_seconds < 3600) {
                            echo floor($diff_seconds / 60) . 'dk önce';
                        } else {
                            echo floor($diff_seconds / 3600) . ' saat önce';
                        }
                        ?>
                        <span class="text-body"> güncellendi</span>
                    </span>
                </div>
            </div>
            <div class="table-responsive" style="max-height:696px">
                <table class="table table-stripped table-hover m-0">
                    <thead>
                        <tr>
                            <th class="p-2 fw-semibold">Maden</th>
                            <th class="p-2 fw-semibold">Alış</th>
                            <th class="p-2 fw-semibold">Satış</th>
                            <th class="p-2 fw-semibold">Değişim</th>
                        </tr>
                    </thead>
                    <tbody class="small fw-light">
                        <?php foreach ($veriler as $kur_uzun => $kur) {
                            // Filtre zaten yukarıda yapıldı, doğrudan render
                            if ($kur['Change'] > 0) {
                                $degisim_class = ' text-success';
                                $degisim = '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 13V8h-5"/><path d="m20 8l-5 5c-.883.883-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373s-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373l-3 3"/></g></svg>';
                            } else {
                                $degisim_class = ' text-danger';
                                $degisim = '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 11v5h-5"/><path d="m20 16l-5-5c-.883-.883-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373s-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373L4 8"/></g></svg>';
                            }
                            ?>
                            <tr class="small">
                                <td class="p-2">
                                    <b class="satir-1"><?php echo kur_name(esc_html($kur['Name'])); ?></b>
                                </td>
                                <td class="p-2"><?php echo esc_html($kur['Buying']) ?></td>
                                <td class="p-2"><?php echo esc_html($kur['Selling']) ?></td>
                                <td class="p-2<?php echo $degisim_class ?>"><?php echo $degisim . esc_html($kur['Change']) ?>%</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Altın Piyasası', 'mevzu2');
        $adet = !empty($instance['adet']) ? $instance['adet'] : 15;
        $gizle = !empty($instance['gizle']) ? $instance['gizle'] : 0;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('adet'); ?>">Kaç kur gösterilsin?</label>
            <input class="widefat" id="<?php echo $this->get_field_id('adet'); ?>"
                name="<?php echo $this->get_field_name('adet'); ?>" type="number" value="<?php echo esc_attr($adet); ?>" min="1"
                max="100">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        return [
            'title' => sanitize_text_field($new_instance['title']),
            'adet' => (!empty($new_instance['adet'])) ? absint($new_instance['adet']) : 15,
            'gizle' => (!empty($new_instance['gizle'])) ? absint($new_instance['gizle']) : 15,
        ];
    }
}

add_action('widgets_init', function () {
    register_widget('widget_anlikaltin');
});
// WIDGET : Altın


// WIDGET : Kripto
class widget_anlikkripto extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'anlik_kripto_widget',
            'Mevzu² — Kripto Widgetı',
            ['description' => 'Canlı kripto bilgilerini gösteren widget']
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        // Başlık
        $title = apply_filters('widget_title', $instance['title'] ?? 'Kripto Borsası');

        $adet = !empty($instance['adet']) ? absint($instance['adet']) : 200;
        $gizle = !empty($instance['gizle']) ? absint($instance['gizle']) : 0;
        $doviz_data_kripto = get_doviz_data();
        if (!is_array($doviz_data_kripto) || !isset($doviz_data_kripto['Rates'])) {
            echo '<p class="p-3 text-muted small">Kripto verileri alınamadı.</p>';
            echo $args['after_widget'];
            return;
        }
        // Sadece Currency tipini filtrele, sonra adet kadar al
        $all_rates_k = $doviz_data_kripto['Rates'];
        $veriler = [];
        foreach ($all_rates_k as $kod => $kur) {
            if (isset($kur['Type'], $kur['Selling'])
                && $kur['Type'] === 'Currency'
                && ($kur['Selling'] ?? 0) > 0) {
                $veriler[$kod] = $kur;
                if (count($veriler) >= $adet) break;
            }
        }
        $meta = $doviz_data_kripto['Meta_Data'] ?? array('Update_Date' => current_time('mysql'));

        $finans_url = get_option('options_finans_sayfasi') ? get_permalink(get_option('options_finans_sayfasi')) : get_bloginfo('url') . '/finans';
        if (!empty($veriler)) {
            ?>
            <div class="row align-items-center my-2">
                <div class="col">
                    <h2 class="m-0"><?php echo '<a href="' . esc_url($finans_url) . '" class="text-link">' . esc_html($title) . '</a>' ?>
                    </h2>
                </div>
                <div class="col-auto ms-md-auto small fw-normal">
                    <span class="pe-3">
                        <?php
                        $post_time = strtotime($meta['Update_Date']);
                        $current_time = current_time('timestamp');
            
                        $diff_seconds = abs($current_time - $post_time);
                        if ($diff_seconds < 60) {
                            echo floor($diff_seconds) . 'sn önce';
                        } elseif ($diff_seconds < 3600) {
                            echo floor($diff_seconds / 60) . 'dk önce';
                        } else {
                            echo floor($diff_seconds / 3600) . ' saat önce';
                        }
                        ?>
                        <span class="text-body"> güncellendi</span>
                    </span>
                </div>
            </div>
            <div class="table-responsive" style="max-height:943px">
                <table class="table table-stripped table-hover m-0">
                    <thead>
                        <tr>
                            <th class="p-2 fw-semibold">Döviz</th>
                            <th class="p-2 fw-semibold">Alış</th>
                            <th class="p-2 fw-semibold">Satış</th>
                            <th class="p-2 fw-semibold">Değişim</th>
                        </tr>
                    </thead>
                    <tbody class="small fw-light">
                        <?php foreach ($veriler as $kur_uzun => $kur) {
                            // API'da CryptoCurrency tipi yok; bu widget Currency tipini gösterir
                            if (isset($kur['Type'], $kur['Selling'])
                                && $kur['Type'] === 'Currency'
                                && ($kur['Selling'] ?? 0) > 0) {
                                if ($kur['Change'] > 0) {
                                    $degisim_class = ' text-success';
                                    $degisim = '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 13V8h-5"/><path d="m20 8l-5 5c-.883.883-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373s-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373l-3 3"/></g></svg>';
                                } else {
                                    $degisim_class = ' text-danger';
                                    $degisim = '<svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 11v5h-5"/><path d="m20 16l-5-5c-.883-.883-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373s-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373L4 8"/></g></svg>';
                                }
                                $kur_adi = isset($kur['Name']) ? $kur['Name'] : $kur_uzun;
                                ?>
                                <tr class="small">
                                    <td class="p-2">
                                        <b><?php echo esc_html($kur_adi); ?></b>
                                        <span class="text-secondary small ms-1"><?php echo esc_html($kur_uzun); ?></span>
                                    </td>
                                    <td class="p-2"><?php echo esc_html(number_format($kur['Buying'] ?? 0, 4)) ?></td>
                                    <td class="p-2"><?php echo esc_html(number_format($kur['Selling'], 4)) ?></td>
                                    <td class="p-2<?php echo $degisim_class ?>"><?php echo $degisim . esc_html($kur['Change']) ?>%</td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Kripto Borsası', 'mevzu2');
        $adet = !empty($instance['adet']) ? $instance['adet'] : 15;
        $gizle = !empty($instance['gizle']) ? $instance['gizle'] : 0;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('adet'); ?>">Kaç kur gösterilsin?</label>
            <input class="widefat" id="<?php echo $this->get_field_id('adet'); ?>"
                name="<?php echo $this->get_field_name('adet'); ?>" type="number" value="<?php echo esc_attr($adet); ?>" min="1"
                max="100">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        return [
            'title' => sanitize_text_field($new_instance['title']),
            'adet' => (!empty($new_instance['adet'])) ? absint($new_instance['adet']) : 15,
            'gizle' => (!empty($new_instance['gizle'])) ? absint($new_instance['gizle']) : 15,
        ];
    }
}

add_action('widgets_init', function () {
    register_widget('widget_anlikkripto');
});
// WIDGET : Kripto




function diyanet_sehirler()
{
    $cities = [
        9146 => "ADANA",
        9158 => "ADIYAMAN",
        9167 => "AFYONKARAHISAR",
        9185 => "AĞRI",
        9193 => "AKSARAY",
        9198 => "AMASYA",
        9206 => "ANKARA",
        9225 => "ANTALYA",
        9238 => "ARDAHAN",
        9246 => "ARTVIN",
        9252 => "AYDIN",
        9270 => "BALIKESIR",
        9285 => "BARTIN",
        9288 => "BATMAN",
        9295 => "BAYBURT",
        9297 => "BILECIK",
        9303 => "BINGOL",
        9311 => "BITLIS",
        9315 => "BOLU",
        9327 => "BURDUR",
        9335 => "BURSA",
        9352 => "CANAKKALE",
        9359 => "CANKIRI",
        9370 => "CORUM",
        9392 => "DENIZLI",
        9402 => "DIYARBAKIR",
        9414 => "DUZCE",
        9419 => "EDIRNE",
        9432 => "ELAZIG",
        9440 => "ERZINCAN",
        9451 => "ERZURUM",
        9470 => "ESKISEHIR",
        9479 => "GAZIANTEP",
        9494 => "GIRESUN",
        9501 => "GUMUSHANE",
        9507 => "HAKKARI",
        20089 => "HATAY",
        9522 => "IĞDIR",
        9528 => "ISPARTA",
        9541 => "ISTANBUL",
        9560 => "IZMIR",
        9577 => "KAHRAMANMARAS",
        9581 => "KARABUK",
        9587 => "KARAMAN",
        9594 => "KARS",
        9609 => "KASTAMONU",
        9620 => "KAYSERI",
        9629 => "KILIS",
        9635 => "KIRIKKALE",
        9638 => "KIRKLARELI",
        9646 => "KIRSEHIR",
        9654 => "KOCAELI",
        9676 => "KONYA",
        9689 => "KUTAHYA",
        9703 => "MALATYA",
        9716 => "MANISA",
        9726 => "MARDIN",
        9737 => "MERSIN",
        9747 => "MUGLA",
        9755 => "MUS",
        9760 => "NEVSEHIR",
        9766 => "NIGDE",
        9782 => "ORDU",
        9788 => "OSMANIYE",
        9799 => "RIZE",
        9807 => "SAKARYA",
        9819 => "SAMSUN",
        9831 => "SANLIURFA",
        9839 => "SIIRT",
        9847 => "SINOP",
        9854 => "SIRNAK",
        9868 => "SIVAS",
        9879 => "TEKIRDAG",
        9887 => "TOKAT",
        9905 => "TRABZON",
        9914 => "TUNCELI",
        9919 => "USAK",
        9930 => "VAN",
        9935 => "YALOVA",
        9949 => "YOZGAT",
        9955 => "ZONGULDAK"
    ];

    return $cities;
}



// Şehir listesi ve plaka kodları
function sehirler($selected_city = '')
{
    $sehirler = [
        '01' => ['name' => 'adana', 'name_tr' => 'adana', 'lat' => 37.0, 'lon' => 35.3213],
        '02' => ['name' => 'adiyaman', 'name_tr' => 'adıyaman', 'lat' => 37.7641, 'lon' => 38.2766],
        '03' => ['name' => 'afyonkarahisar', 'name_tr' => 'afyonkarahisar', 'lat' => 38.7584, 'lon' => 30.5561],
        '04' => ['name' => 'agri', 'name_tr' => 'ağrı', 'lat' => 39.7189, 'lon' => 43.0707],
        '05' => ['name' => 'amasya', 'name_tr' => 'amasya', 'lat' => 40.6513, 'lon' => 35.8298],
        '06' => ['name' => 'ankara', 'name_tr' => 'ankara', 'lat' => 39.9334, 'lon' => 32.8597],
        '07' => ['name' => 'antalya', 'name_tr' => 'antalya', 'lat' => 36.8876, 'lon' => 30.7075],
        '08' => ['name' => 'artvin', 'name_tr' => 'artvin', 'lat' => 41.1829, 'lon' => 41.8181],
        '09' => ['name' => 'aydin', 'name_tr' => 'aydın', 'lat' => 37.8661, 'lon' => 27.8486],
        '10' => ['name' => 'balikesir', 'name_tr' => 'balıkesir', 'lat' => 39.6495, 'lon' => 27.8824],
        '11' => ['name' => 'bilecik', 'name_tr' => 'bilecik', 'lat' => 40.1447, 'lon' => 29.9795],
        '12' => ['name' => 'bingol', 'name_tr' => 'bingöl', 'lat' => 39.0674, 'lon' => 40.4840],
        '13' => ['name' => 'bitlis', 'name_tr' => 'bitlis', 'lat' => 38.3950, 'lon' => 42.1074],
        '14' => ['name' => 'bolu', 'name_tr' => 'bolu', 'lat' => 40.7387, 'lon' => 31.6068],
        '15' => ['name' => 'burdur', 'name_tr' => 'burdur', 'lat' => 37.7210, 'lon' => 30.2905],
        '16' => ['name' => 'bursa', 'name_tr' => 'bursa', 'lat' => 40.1957, 'lon' => 29.0609],
        '17' => ['name' => 'canakkale', 'name_tr' => 'çanakkale', 'lat' => 40.1535, 'lon' => 26.4094],
        '18' => ['name' => 'cankiri', 'name_tr' => 'çankırı', 'lat' => 40.6000, 'lon' => 33.6136],
        '19' => ['name' => 'corum', 'name_tr' => 'çorum', 'lat' => 40.5941, 'lon' => 34.9551],
        '20' => ['name' => 'denizli', 'name_tr' => 'denizli', 'lat' => 37.7767, 'lon' => 29.1355],
        '21' => ['name' => 'diyarbakir', 'name_tr' => 'diyarbakır', 'lat' => 37.9138, 'lon' => 40.2298],
        '22' => ['name' => 'edirne', 'name_tr' => 'edirne', 'lat' => 41.6771, 'lon' => 26.5550],
        '23' => ['name' => 'elazig', 'name_tr' => 'elazığ', 'lat' => 38.6800, 'lon' => 39.2234],
        '24' => ['name' => 'erzincan', 'name_tr' => 'erzincan', 'lat' => 39.7481, 'lon' => 39.0754],
        '25' => ['name' => 'erzurum', 'name_tr' => 'erzurum', 'lat' => 39.9334, 'lon' => 41.2674],
        '26' => ['name' => 'eskisehir', 'name_tr' => 'eskişehir', 'lat' => 39.7767, 'lon' => 30.5206],
        '27' => ['name' => 'gaziantep', 'name_tr' => 'gaziantep', 'lat' => 37.0662, 'lon' => 37.3833],
        '28' => ['name' => 'giresun', 'name_tr' => 'giresun', 'lat' => 40.9150, 'lon' => 38.3896],
        '29' => ['name' => 'gumushane', 'name_tr' => 'gümüşhane', 'lat' => 40.4633, 'lon' => 39.4808],
        '30' => ['name' => 'hakkari', 'name_tr' => 'hakkâri', 'lat' => 37.5713, 'lon' => 43.7415],
        '31' => ['name' => 'hatay', 'name_tr' => 'hatay', 'lat' => 36.7267, 'lon' => 36.4192],
        '32' => ['name' => 'isparta', 'name_tr' => 'isparta', 'lat' => 37.7644, 'lon' => 30.5582],
        '33' => ['name' => 'mersin', 'name_tr' => 'mersin', 'lat' => 36.8053, 'lon' => 34.6400],
        '34' => ['name' => 'istanbul', 'name_tr' => 'istanbul', 'lat' => 41.0082, 'lon' => 28.9784],
        '35' => ['name' => 'izmir', 'name_tr' => 'izmir', 'lat' => 38.4192, 'lon' => 27.1287],
        '36' => ['name' => 'kars', 'name_tr' => 'kars', 'lat' => 40.6167, 'lon' => 43.1000],
        '37' => ['name' => 'kastamonu', 'name_tr' => 'kastamonu', 'lat' => 41.3796, 'lon' => 33.7866],
        '38' => ['name' => 'kayseri', 'name_tr' => 'kayseri', 'lat' => 38.7249, 'lon' => 35.4901],
        '39' => ['name' => 'kirklareli', 'name_tr' => 'kırklareli', 'lat' => 41.7333, 'lon' => 27.2267],
        '40' => ['name' => 'kirsehir', 'name_tr' => 'kırşehir', 'lat' => 39.1417, 'lon' => 34.1667],
        '41' => ['name' => 'kocaeli', 'name_tr' => 'kocaeli', 'lat' => 40.8667, 'lon' => 29.9097],
        '42' => ['name' => 'konya', 'name_tr' => 'konya', 'lat' => 37.8667, 'lon' => 32.4997],
        '43' => ['name' => 'kutahya', 'name_tr' => 'kütahya', 'lat' => 39.4197, 'lon' => 29.9833],
        '44' => ['name' => 'malatya', 'name_tr' => 'malatya', 'lat' => 38.3551, 'lon' => 38.3090],
        '45' => ['name' => 'manisa', 'name_tr' => 'manisa', 'lat' => 38.4647, 'lon' => 27.1287],
        '46' => ['name' => 'kahramanmaras', 'name_tr' => 'kahramanmaraş', 'lat' => 37.5734, 'lon' => 36.9385],
        '47' => ['name' => 'mardin', 'name_tr' => 'mardin', 'lat' => 37.3192, 'lon' => 40.7521],
        '48' => ['name' => 'mugla', 'name_tr' => 'muğla', 'lat' => 37.2132, 'lon' => 28.4111],
        '49' => ['name' => 'mus', 'name_tr' => 'muş', 'lat' => 38.9430, 'lon' => 41.7354],
        '50' => ['name' => 'nevsehir', 'name_tr' => 'nevşehir', 'lat' => 38.6267, 'lon' => 34.6856],
        '51' => ['name' => 'nigde', 'name_tr' => 'niğde', 'lat' => 37.9667, 'lon' => 34.6847],
        '52' => ['name' => 'ordu', 'name_tr' => 'ordu', 'lat' => 40.9833, 'lon' => 37.8764],
        '53' => ['name' => 'rize', 'name_tr' => 'rize', 'lat' => 41.0251, 'lon' => 40.5152],
        '54' => ['name' => 'sakarya', 'name_tr' => 'sakarya', 'lat' => 40.7765, 'lon' => 30.3989],
        '55' => ['name' => 'samsun', 'name_tr' => 'samsun', 'lat' => 41.2867, 'lon' => 36.33],
        '56' => ['name' => 'siirt', 'name_tr' => 'siirt', 'lat' => 37.9333, 'lon' => 41.9453],
        '57' => ['name' => 'sinop', 'name_tr' => 'sinop', 'lat' => 42.0090, 'lon' => 35.1433],
        '58' => ['name' => 'sivas', 'name_tr' => 'sivas', 'lat' => 39.7500, 'lon' => 37.0150],
        '59' => ['name' => 'tekirdag', 'name_tr' => 'tekirdağ', 'lat' => 40.9783, 'lon' => 27.5111],
        '60' => ['name' => 'tokat', 'name_tr' => 'tokat', 'lat' => 40.3167, 'lon' => 36.5500],
        '61' => ['name' => 'trabzon', 'name_tr' => 'trabzon', 'lat' => 41.0053, 'lon' => 39.7334],
        '62' => ['name' => 'tunceli', 'name_tr' => 'tunceli', 'lat' => 39.1083, 'lon' => 39.5493],
        '63' => ['name' => 'sanliurfa', 'name_tr' => 'şanlıurfa', 'lat' => 37.1774, 'lon' => 38.7906],
        '64' => ['name' => 'usak', 'name_tr' => 'uşak', 'lat' => 38.6822, 'lon' => 29.4089],
        '65' => ['name' => 'van', 'name_tr' => 'van', 'lat' => 38.3000, 'lon' => 43.4000],
        '66' => ['name' => 'yozgat', 'name_tr' => 'yozgat', 'lat' => 39.8190, 'lon' => 34.8066],
        '67' => ['name' => 'zonguldak', 'name_tr' => 'zonguldak', 'lat' => 41.4550, 'lon' => 31.7881],
        '68' => ['name' => 'aksaray', 'name_tr' => 'aksaray', 'lat' => 38.2700, 'lon' => 34.0300],
        '69' => ['name' => 'bayburt', 'name_tr' => 'bayburt', 'lat' => 40.2546, 'lon' => 40.2259],
        '70' => ['name' => 'karaman', 'name_tr' => 'karaman', 'lat' => 37.1806, 'lon' => 33.2156],
        '71' => ['name' => 'kirikkale', 'name_tr' => 'kırıkkale', 'lat' => 39.8500, 'lon' => 33.6333],
        '72' => ['name' => 'batman', 'name_tr' => 'batman', 'lat' => 37.8878, 'lon' => 41.1385],
        '73' => ['name' => 'sirnak', 'name_tr' => 'şırnak', 'lat' => 37.5151, 'lon' => 42.4553],
        '74' => ['name' => 'bartin', 'name_tr' => 'bartın', 'lat' => 41.6354, 'lon' => 32.3373],
        '75' => ['name' => 'ardahan', 'name_tr' => 'ardahan', 'lat' => 41.1101, 'lon' => 42.7025],
        '76' => ['name' => 'igdir', 'name_tr' => 'ığdır', 'lat' => 40.0394, 'lon' => 44.0478],
        '77' => ['name' => 'yalova', 'name_tr' => 'yalova', 'lat' => 40.6510, 'lon' => 29.2760],
        '78' => ['name' => 'karabuk', 'name_tr' => 'karabük', 'lat' => 41.2, 'lon' => 32.6344],
        '79' => ['name' => 'kilis', 'name_tr' => 'kilis', 'lat' => 36.7167, 'lon' => 37.1167],
        '80' => ['name' => 'osmaniye', 'name_tr' => 'osmaniye', 'lat' => 37.0750, 'lon' => 36.2380],
        '81' => ['name' => 'duzce', 'name_tr' => 'düzce', 'lat' => 40.8381, 'lon' => 31.1570]
    ];

    if ($selected_city) {
        // Eğer bir şehir ismi verilmişse, o şehri döndür
        foreach ($sehirler as $code => $data) {
            if ($data['name'] === $selected_city) {
                return $data;
            }
        }
        return null;  // Şehir bulunamazsa null döndürüyoruz
    }

    return $sehirler;
}
function turkce_karakter($string)
{
    $search = ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'];
    $replace = ['c', 'c', 'g', 'g', 'i', 'i', 'o', 'o', 's', 's', 'u', 'u'];
    $string = str_replace($search, $replace, $string);
    return strtolower($string);
}

/**
 * Mevcut seçili şehri döndürür (Çerez veya Varsayılan Ayar)
 */
function mevzu_get_current_city() {
    $varsayilan = get_option('options_varsayilan_sehir');
    if (empty($varsayilan)) {
        $varsayilan = 'KARABUK';
    }

    if (isset($_COOKIE['mevzu_hava_sehir']) && !empty($_COOKIE['mevzu_hava_sehir'])) {
        return sanitize_text_field($_COOKIE['mevzu_hava_sehir']);
    }
    return $varsayilan;
}


function get_city_data_by_name($city_name)
{
    $cities = sehirler();  // Tüm şehirleri al
    foreach ($cities as $code => $data) {
        if ($data['name'] === $city_name) {
            return $city_name;
        }
    }
    return false;
}





// Hava durumu verisini 2 saatlik transient ile cache eden fonksiyon
function get_weather_data($city)
{
    $api_key = mevzu_key('weather_api_key');

    // Şehri isimden bul
    $city_data = sehirler($city);
    if (!$city_data) {
        return false;
    }

    $lat = $city_data['lat'];
    $lon = $city_data['lon'];

    $transient_key = 'weather_data_' . sanitize_title($city);
    $cached_data = get_transient($transient_key);

    if ($cached_data !== false) {
        return $cached_data;
    }

    $weather_url = "https://api.openweathermap.org/data/2.5/forecast/daily?lat=$lat&lon=$lon&units=metric&exclude=minutely,hourly,alerts&appid=$api_key&lang=tr&units=metric&cnt=17";

    $response = wp_remote_get($weather_url);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['list'])) {
        return false;
    }

    set_transient($transient_key, $data, 2 * HOUR_IN_SECONDS);
    // delete_transient($transient_key);
    return $data;
}


// reklam Ekleme
function register_reklam_post_type()
{
    $labels = array(
        'name' => 'Reklam',
        'singular_name' => 'Reklam',
        'menu_name' => 'Reklamlı Haberler',
        'name_admin_bar' => 'Reklam',
        'add_new' => 'Yeni Ekle',
        'add_new_item' => 'Yeni Reklam Ekle',
        'edit_item' => 'Reklamı Düzenle',
        'new_item' => 'Yeni Reklam',
        'view_item' => 'Reklamı Görüntüle',
        'search_items' => 'Reklam Ara',
        'not_found' => 'Reklam bulunamadı',
        'not_found_in_trash' => 'Çöp kutusunda reklam yok',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_position' => 30, // Posts = 5, Medya = 10 → Yazılar'dan hemen sonra
        'menu_icon' => 'dashicons-money-alt', // İsteğe bağlı ikon
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_menu' => true,
        'show_in_rest' => true, // Gutenberg desteği
    );

    register_post_type('reklam', $args);
}
add_action('init', 'register_reklam_post_type');
function reklam_thumbnail_size_restriction($file)
{
    // Sadece admin'de, yazı ekleme ekranında çalışsın
    if (is_admin() && isset($_REQUEST['post_id'])) {
        $post_id = intval($_REQUEST['post_id']);
        $post_type = get_post_type($post_id);

        // Sadece 'reklam' post tipi için kontrol et
        if ($post_type === 'reklam' && strpos($file['type'], 'image/') === 0) {
            $image = getimagesize($file['tmp_name']);

            if ($image) {
                $width = $image[0];
                $height = $image[1];

                // 1176x330 değilse hata ver
                if ($width != 1176 || $height != 330) {
                    $file['error'] = 'Reklam görseli tam olarak 1176x330 piksel olmalıdır.';
                }
            }
        }
    }

    return $file;
}
add_filter('wp_handle_upload_prefilter', 'reklam_thumbnail_size_restriction');
// reklam Ekleme


function ilginizi_cekebilir($post)
{
    $current_post_id = $post;
    $current_categories = wp_get_post_categories($post);

    if (!empty($current_categories)) {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 1,
            'post__not_in' => array($current_post_id),
            'orderby' => 'date',
            'order' => 'DESC',
            'category__in' => $current_categories,
        );

        $related_posts_query = new WP_Query($args);

        if ($related_posts_query->have_posts()): ?>
            <div class="ilgini_cekebilir bg-primary my-3 rounded">
                <?php while ($related_posts_query->have_posts()):
                    $related_posts_query->the_post(); ?>
                    <a href="<?php the_permalink() ?>" class="ripple text-white d-flex p-2" data-bs-ripple-color="light">
                        <div class="row align-items-center py-1 py-md-0">
                            <div class="col-12 col-md">
                                <div class="text-uppercase small-2 fw-semibold opacity-75 ms-1 mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                        <path fill="currentColor"
                                            d="M6.803 18.998c-.194-.127 3.153-7.16 3.038-7.469S6.176 10.093 6.003 9.55S13.01.843 13.199 1.001c.188.158-3.129 7.238-3.039 7.469c.091.23 3.728 1.404 3.838 1.979c.111.575-7.002 8.676-7.195 8.549" />
                                    </svg>
                                    İlginizi Çekebilir
                                </div>
                                <h3 class="ms-2 mb-0 fz-16 fw-semibold satir-1"><?php the_title(); ?></h3>
                            </div>
                            <div class="col-12 col-md-2 d-none d-md-flex">
                                <?php the_post_thumbnail('gorsel-thumbnail-widget', ['title' => get_the_title(), 'loading' => 'lazy', 'class' => 'onecikarilmisgorsel rounded']); ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Benzer <?php (in_category('kose-yazilari') ? 'yazı' : 'haber') ?> bulunamadı.</p>
        <?php endif;
        wp_reset_postdata();
    }
}

function mevzu_class()
{
    return 'tema-widget bg-white shadow-sm rounded';
}

// ==================== CACHE VE COMPRESSION OPTİMİZASYONLARI ====================
// Browser cache iptali (Dinamik sayfaların anında güncellenmesi için)
function add_cache_headers()
{
    if (!is_admin()) {
        // Tarayıcının HTML sayfasını önbelleğe almasını engeller
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT'); // Geçmiş bir tarih
    }
}
add_action('send_headers', 'add_cache_headers');

// GZIP compression etkinleştir
function enable_gzip_compression()
{
    if (!is_admin() && extension_loaded('zlib') && !headers_sent()) {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
}
add_action('init', 'enable_gzip_compression');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe group option getter
 */
function get_opt_g($option_name, $key, $default = '') {
    // Önce ACF tarzı düz alt alan (options_grup_altalan): tema ayarları AJAX kaydı buraya yazar.
    // Eski `options_manset` vb. grup dizileriyle çakışınca önceden dizi her zaman kazanıyordu.
    $individual_key = $option_name . '_' . $key;
    $individual     = get_option( $individual_key );
    if ( $individual !== false ) {
        return $individual;
    }

    $opt = get_option( $option_name );
    if ( is_array( $opt ) && array_key_exists( $key, $opt ) ) {
        return $opt[ $key ];
    }

    return $default;
}

/**
 * Kategori / etiket vb. arşiv sayfası üst manşet (Swiper) ayarları.
 *
 * @return array{show:bool,count:int}
 */
function mevzu2_archive_manset_slider_config() {
    $show  = (int) get_opt_g( 'options_archive_manset', 'goster', 1 ) === 1;
    $count = (int) get_opt_g( 'options_archive_manset', 'slider_sayisi', 15 );
    $count = max( 1, min( 50, $count ) );
    return array(
        'show'  => $show,
        'count' => $count,
    );
}

/**
 * Safe image URL formatter
 */
function mevzu_format_img($value, $default = '') {
    if (!$value) return $default;

    if (is_string($value)) {
        $value = trim($value);
    }

    if (is_numeric($value) && $value > 0) {
        $url = wp_get_attachment_url($value);
        return $url ? $url : $default;
    }

    if (is_array($value) && isset($value['url'])) {
        return $value['url'];
    }

    return $value;
}

/**
 * Safe image URL getter
 */
function get_opt_img($option_name, $default = '') {
    // get_field('field', 'option') converts attachment IDs to URLs automatically in compat.php
    $field_name = (strpos($option_name, 'options_') === 0) ? substr($option_name, 8) : $option_name;
    $value = function_exists('get_field') ? get_field($field_name, 'option') : get_option($option_name);
    
    return mevzu_format_img($value, $default);
}

/**
 * Temanın logo çıktısını verir (Açık ve Koyu mod desteği ile)
 */
function render_mevzu_logo($context = 'desktop', $height = '48px', $class = '') {
    $logo_id = ($context === 'mobil') ? 'options_logo_mobil' : 'options_logo';
    $logo_dark_id = ($context === 'mobil') ? 'options_logo_mobil_dark' : 'options_logo_dark';

    $logo = get_opt_img($logo_id);
    $logo_dark = get_opt_img($logo_dark_id);

    // Mobil özel logo yoksa desktoppan al
    if ($context === 'mobil' && !$logo) {
        $logo = get_opt_img('options_logo');
        $logo_dark = get_opt_img('options_logo_dark');
    }

    $alt = get_bloginfo('name');
    $style = $height ? ' style="max-height:' . $height . '; height:auto; width:auto"' : '';
    $height_int = $height ? intval($height) : 48;
    $dims = ' width="200" height="' . esc_attr($height_int) . '"';

    if ($logo) {
        echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr($alt) . '" class="logo-light ' . esc_attr($class) . '"' . $dims . $style . '>';
        if ($logo_dark) {
            echo '<img src="' . esc_url($logo_dark) . '" alt="' . esc_attr($alt) . '" class="logo-dark ' . esc_attr($class) . '"' . $dims . $style . '>';
        } else {
            // Eğer koyu mod logosu yoksa, açık mod logosu koyu modda da görünür kalsın (veya filter eklenebilir)
            echo '<img src="' . esc_url($logo) . '" alt="' . esc_attr($alt) . '" class="logo-dark ' . esc_attr($class) . '"' . $dims . $style . '>';
        }
    } else {
        echo '<div class="p-2 rounded-3 bg-light fs-4">
            <span class="fw-bold"><b class="text-primary font-bolder">:</b>mevzu<b class="text-primary">²</b></span>
        </div>';
    }
}

// Veritabanı sorgu cache'i optimize et
function optimize_database_queries()
{
    if (!defined('WP_CACHE')) {
        define('WP_CACHE', true);
    }
}
add_action('init', 'optimize_database_queries');
// ==================== CACHE OPTİMİZASYONLARI SONU ====================

// ==================== MEMORY VE PERFORMANS OPTİMİZASYONU ====================
// Memory limit'i optimize et
function optimize_memory_usage()
{
    if (!is_admin()) {
        // Frontend'de memory kullanımını sınırla
        ini_set('memory_limit', '256M');

        // Garbage collection'ı optimize et
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }
}
add_action('init', 'optimize_memory_usage');

// Büyük widget'lar için lazy loading
function optimize_widget_loading()
{
    // Ağır widget'ları sayfa sonunda yükle
    add_action('wp_footer', function () {
        if (!is_admin()) {
            echo '<script>
                // Widget lazy loading
                document.addEventListener("DOMContentLoaded", function() {
                    const widgets = document.querySelectorAll(".widget");
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add("loaded");
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    widgets.forEach(widget => observer.observe(widget));
                });
            </script>';
        }
    });
}
add_action('wp_enqueue_scripts', 'optimize_widget_loading');

// Transient cleanup - eski cache'leri temizle
function cleanup_old_transients()
{
    global $wpdb;

    // 1 hafta önce expire olmuş transient'ları temizle
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_timeout_%' 
        AND option_value < %d
    ", time() - WEEK_IN_SECONDS));

    // Orphaned transient'ları temizle
    $wpdb->query("
        DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_%'
        AND option_name NOT LIKE '_transient_timeout_%'
        AND REPLACE(option_name, '_transient_', '_transient_timeout_') NOT IN (
            SELECT option_name FROM {$wpdb->options} o2 WHERE o2.option_name LIKE '_transient_timeout_%'
        )
    ");
}

// Haftada bir cleanup çalıştır
if (!wp_next_scheduled('cleanup_transients_hook')) {
    wp_schedule_event(time(), 'weekly', 'cleanup_transients_hook');
}
add_action('cleanup_transients_hook', 'cleanup_old_transients');

// Yeni post eklendiğinde veya güncellendiğinde transient temizleme
function clear_custom_post_transients($post_id)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if ( ! in_array( get_post_type( $post_id ), array( 'post', 'resmi-ilanlar' ), true ) ) {
        return;
    }

    delete_transient('anasayfa_manset_sorgusu');
    delete_transient('anasayfa_ust_mansetler_sorgusu');
    delete_transient('anasayfa_ust_reklam_sorgusu');
    delete_transient('anasayfa_alt_manset_sorgusu');
    delete_transient('sonhaberler');
    delete_transient('first_video_post');
    delete_transient('other_video_posts');
    delete_transient('son_dakika_haberleri');

    // Blokların (şablon 1, 2, 3 vs) ve yazar alanının oluşturduğu dinamik transientleri db üzerinden toptan siliyoruz
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sablon%_sorgusu_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_author_latest_post_%'");
}
add_action('save_post', 'clear_custom_post_transients');
add_action('deleted_post', 'clear_custom_post_transients');

// // Son performans kontrolleri
// function final_performance_checks() {
//     if (!is_admin()) {
//         // Buffer output'u optimize et
//         if (!ob_get_level()) {
//             ob_start();
//         }

//         // Late scripts'leri footer'a taşı
//         remove_action('wp_head', 'wp_print_scripts');
//         remove_action('wp_head', 'wp_print_head_scripts', 9);
//         add_action('wp_footer', 'wp_print_scripts', 5);
//         add_action('wp_footer', 'wp_print_head_scripts', 5);
//     }
// }
// add_action('init', 'final_performance_checks');
// // ==================== MEMORY OPTİMİZASYONU SONU ====================
// Membership ve TTS artık Modül Yöneticisi üzerinden yükleniyor (inc/theme-settings/init.php)


/**
 * Özel sayfaları WordPress yönetim panelindeki Sayfalar listesinde etiketlerle işaretler.
 */
/**
 * Tema Etkinleştirildiğinde Varsayılan Sayfaları Oluştur
 */
function mevzu_create_default_pages() {
    $pages = array(
        'kunye_sayfasi'          => array('title' => 'Künye', 'slug' => 'kunye'),
        'iletisim_sayfasi'       => array('title' => 'İletişim', 'slug' => 'iletisim'),
        'gizlilik_politikasi_sayfasi' => array('title' => 'Gizlilik Politikası', 'slug' => 'gizlilik-politikasi'),
        'akis_sayfasi'           => array('title' => 'Akış', 'slug' => 'akis'),
        'finans_sayfasi'         => array('title' => 'Finans', 'slug' => 'finans'),
        'havadurumu_sayfasi'     => array('title' => 'Hava Durumu', 'slug' => 'hava-durumu'),
        'namaz_vakitleri_sayfasi' => array('title' => 'Namaz Vakitleri', 'slug' => 'namaz-vakitleri'),
        'sondakika_sayfasi'       => array('title' => 'Son Dakika', 'slug' => 'sondakika'),
        'arsiv_sayfasi'          => array('title' => 'Arşiv', 'slug' => 'arsiv'),
        'yazarlar_sayfasi'       => array('title' => 'Yazarlar', 'slug' => 'yazarlar'),
        'yol_durumu_sayfasi'     => array('title' => 'Yol Durumu', 'slug' => 'yol-durumu'),
        'nobetci_eczaneler_sayfasi' => array('title' => 'Nöbetçi Eczaneler', 'slug' => 'nobetci-eczaneler'),
    );

    foreach ($pages as $option_key => $page_data) {
        $option_name = 'options_' . $option_key;
        $existing_id = get_option($option_name);

        if (!$existing_id || !get_post($existing_id)) {
            $page_obj = get_page_by_path($page_data['slug']);
            if (!$page_obj) {
                $new_page_id = wp_insert_post(array(
                    'post_title'   => $page_data['title'],
                    'post_name'    => $page_data['slug'],
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ));
                if ($new_page_id && !is_wp_error($new_page_id)) {
                    update_option($option_name, $new_page_id);
                }
            } else {
                update_option($option_name, $page_obj->ID);
            }
        }
    }

    mevzu_setup_menus_and_widgets();
}

/**
 * İlk kurulumda Menü ve Bileşenleri Otomatik Kur
 */
function mevzu_setup_menus_and_widgets() {
    if (get_option('mevzu_initial_setup_done')) return;
    update_option('mevzu_initial_setup_done', 1); // Çift çalışmayı önlemek için hemen işaretle

    // --- MENÜLER ---
    $locations = get_nav_menu_locations();
    $menu_names = [
        'main-menu'   => 'Ana Menü',
        'ust-menu'    => 'Üst Menü',
        'mobil-menu'  => 'Mobil Menü',
        'footer-menu-1' => 'Footer Menü 1',
        'footer-menu-2' => 'Footer Menü 2',
        'footer-menu-3' => 'Footer Menü 3',
        'footer-menu-4' => 'Footer Menü 4',
    ];
    
    $updated_locations = false;
    foreach ($menu_names as $loc => $name) {
        // Eğer bu konum zaten doluysa veya menü zaten varsa dokunma
        if (!empty($locations[$loc])) continue;

        $menu_obj = wp_get_nav_menu_object($name);
        if (!$menu_obj) {
            $menu_id = wp_create_nav_menu($name);
            if (is_wp_error($menu_id)) continue;
        } else {
            $menu_id = $menu_obj->term_id;
        }
        
        $locations[$loc] = $menu_id;
        $updated_locations = true;
    }
    
    if ($updated_locations) {
        set_theme_mod('nav_menu_locations', $locations);
    }

    // Kategori menülerini doldur (Ana ve Mobil)
    $top_cats = get_categories(['orderby' => 'count', 'order' => 'DESC', 'number' => 5, 'hide_empty' => false]);
    foreach (['main-menu', 'mobil-menu'] as $loc) {
        $m_id = $locations[$loc];
        $items = wp_get_nav_menu_items($m_id);
        if (empty($items)) {
            foreach ($top_cats as $cat) {
                wp_update_nav_menu_item($m_id, 0, [
                    'menu-item-title'   => $cat->name,
                    'menu-item-object'  => 'category',
                    'menu-item-object-id' => $cat->term_id,
                    'menu-item-type'    => 'taxonomy',
                    'menu-item-status'  => 'publish'
                ]);
            }
        }
    }

    // --- BİLEŞENLER (WIDGETS) ---
    // Mevcut bileşenleri sıfırla
    $sidebars_widgets = [
        'wp_inactive_widgets' => [],
        'sidebar-anasayfa'    => [],
        'sidebar-single'      => [],
        'sidebar-archive'     => [],
        'sidebar-koseyazilari' => []
    ];

    // Haftalık Gündem ayarları
    $widget_gundem = get_option('widget_bilesen_haftalik_gundem', []);
    $widget_gundem[1] = ['title' => 'Haftalık Gündem'];
    update_option('widget_bilesen_haftalik_gundem', $widget_gundem);

    // Namaz Vakitleri ayarları
    $widget_namaz = get_option('widget_namazvakitleri_widget', []);
    $widget_namaz[1] = ['title' => 'Namaz Vakitleri'];
    update_option('widget_namazvakitleri_widget', $widget_namaz);

    // Döviz/Kur ayarları
    $widget_doviz = get_option('widget_widget_anlikkur', []);
    $widget_doviz[1] = ['title' => 'Piyasa Durumu'];
    update_option('widget_widget_anlikkur', $widget_doviz);

    // Reklam ayarları
    $widget_reklam = get_option('widget_bilesen_reklam', []);
    $widget_reklam[1] = ['title' => 'Reklam'];
    update_option('widget_bilesen_reklam', $widget_reklam);

    // Bize Katılın ayarları
    $widget_katilin = get_option('widget_mevzu_bize_katilin', []);
    $widget_katilin[1] = ['title' => 'Bizi Takip Edin'];
    update_option('widget_mevzu_bize_katilin', $widget_katilin);

    // Sidebars Doldur
    $sidebars_widgets['sidebar-anasayfa'] = ['bilesen_haftalik_gundem-1', 'namazvakitleri_widget-1', 'mevzu_bize_katilin-1', 'bilesen_reklam-1'];
    $sidebars_widgets['sidebar-single']   = ['widget_anlikkur-1', 'namazvakitleri_widget-1', 'mevzu_bize_katilin-1', 'bilesen_reklam-1'];
    $sidebars_widgets['sidebar-archive']  = ['bilesen_haftalik_gundem-1', 'widget_anlikkur-1', 'mevzu_bize_katilin-1'];
    $sidebars_widgets['sidebar-koseyazilari'] = ['bilesen_haftalik_gundem-1', 'namazvakitleri_widget-1', 'mevzu_bize_katilin-1'];

    update_option('sidebars_widgets', $sidebars_widgets);
}
add_action('after_switch_theme', 'mevzu_create_default_pages');

/**
 * Tema GÜNCELLENDİĞİNDE eksik varsayılan sayfaları oluştur.
 *
 * 'after_switch_theme' yalnızca tema ilk kez etkinleştirildiğinde çalışır;
 * dosya güncellemesiyle (tema update) tetiklenmez. Bu yüzden burada bir
 * "sürüm göçü" (migration) yapıyoruz: varsayılan sayfa listesi her değiştiğinde
 * aşağıdaki sürüm numarasını bir artırın, böylece admin panele ilk girişte
 * eksik sayfalar (ör. Arşiv) otomatik oluşturulur. Fonksiyon idempotenttir;
 * yalnızca eksik olanları ekler, mevcutlara dokunmaz.
 */
function mevzu_maybe_run_page_migrations() {
    $current_version = '2'; // Varsayılan sayfa listesi değiştikçe artırın
    $stored_version  = get_option('mevzu_default_pages_version');

    if ($stored_version === $current_version) {
        return; // Zaten güncel — hiçbir şey yapma
    }

    mevzu_create_default_pages();
    update_option('mevzu_default_pages_version', $current_version);
}
add_action('admin_init', 'mevzu_maybe_run_page_migrations');

/**
 * Zorunlu kategorileri oluştur ve varsayılan tema ayarlarını set et.
 * Tema ilk aktif edildiğinde çalışır.
 */
function mevzu_setup_default_categories_and_options() {
    // Varsayılan option değerlerini ata (daha önce set edilmemişse)
    $defaults = [
        // Üst Manşet
        'options_ust_manset_yeni' => [
            'goster' => 1,
        ],
        'options_ust_manset_yeni_slider_sayisi' => '5',
        'options_anasayfa_son_haberler_sayisi' => '9',
        'options_son_dakika_goster' => '1',
        // Sıcak Gündem
        'options_ust_manset' => [
            'ust_manset_ayarlari' => 1,
            'slider_sayisi'       => 12,
        ],
        // Ana Manşet
        'options_manset' => [
            'slider_sayisi' => 1,
            'slider_modeli' => 'default',
            'slider_renk'   => 'slider-beyaz',
        ],
        // Arşiv (kategori / etiket vb.) üst manşet — ana manşet ile aynı alt alan yapısı
        'options_archive_manset' => [
            'goster'             => 1,
            'slider_sayisi'      => 15,
            'slider_modeli'      => 'default',
            'slider_renk'        => 'slider-beyaz',
            'slider_basliklari'  => 1,
            'baslik_boyutu'      => 'fz-16',
            'baslik_hizasi'      => 'text-center',
        ],
        'options_yapay_zeka_manseti' => [
            'goster'            => 1,
            'baslik'            => 'Günün Manşetleri',
            'baslangic_cumlesi' => 'SITE_ADI Yapay zeka gündemine hoşgeldiniz. Bugünün öne çıkan haberleri şunlar',
            'bitis_cumlesi'     => 'Günün haberleri bu kadardı. SITE_ADI iyi günler diler.',
        ],
        // Alt Manşet
        'options_alt_manset' => [
            'alt_manseti_goster' => 1,
            'slider_sayisi'      => 6,
        ],
        // Yan Manşet
        'options_yan_manset' => [
            'tip' => 'haftalik_gundem',
        ],
    ];

    foreach ( $defaults as $option_key => $values ) {
        $existing_opt = get_option( $option_key );
        if ( ! $existing_opt ) {
            update_option( $option_key, $values );
        } else {
            // Eksik alt anahtarları doldur, mevcutlara dokunma
            $merged = array_merge( $values, (array) $existing_opt );
            update_option( $option_key, $merged );
        }
    }
}
add_action( 'after_switch_theme', 'mevzu_setup_default_categories_and_options' );

/**
 * Sayfa Listesinde Özel Sayfa Etiketleri (Page States)
 */
function mevzu_display_special_page_states($post_states, $post) {
    if ($post->post_type !== 'page') return $post_states;

    $special_pages = array(
        'kunye_sayfasi'            => 'Künye Sayfası',
        'iletisim_sayfasi'         => 'İletişim Sayfası',
        'gizlilik_politikasi_sayfasi' => 'Gizlilik Politikası Sayfası',
        'akis_sayfasi'             => 'Akış Sayfası',
        'finans_sayfasi'           => 'Finans Sayfası',
        'havadurumu_sayfasi'       => 'Hava Durumu Sayfası',
        'namaz_vakitleri_sayfasi'  => 'Namaz Vakitleri Sayfası',
        'sondakika_sayfasi'        => 'Son Dakika Sayfası',
        'arsiv_sayfasi'            => 'Arşiv Sayfası',
        'yazarlar_sayfasi'         => 'Yazarlar Sayfası',
        'yol_durumu_sayfasi'       => 'Yol Durumu Sayfası',
        'nobetci_eczaneler_sayfasi' => 'Nöbetçi Eczaneler Sayfası'
    );

    foreach ($special_pages as $opt_key => $label) {
        $target_id = get_option('options_' . $opt_key);
        if ($target_id && $post->ID == $target_id) {
            $post_states[] = $label;
        }
    }

    return $post_states;
}
add_filter('display_post_states', 'mevzu_display_special_page_states', 10, 2);

/**
 * İletişim Formu AJAX İşleyicisi
 */
function mevzu_contact_form_handler() {
    check_ajax_referer('mevzu_contact_nonce', 'security');

    $name    = sanitize_text_field($_POST['name']);
    $email   = sanitize_email($_POST['email']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = nl2br(sanitize_textarea_field($_POST['message']));
    $post_id = intval($_POST['post_id']);

    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error('Lütfen tüm zorunlu alanları doldurun.');
    }

    // Alıcı e-posta adresini sayfadan al veya varsayılana düş
    $to = get_post_meta($post_id, 'iletisim_formu_eposta', true);
    if (empty($to)) {
        $to = get_option('admin_email');
    }

    $mail_subject = 'İletişim Formu: ' . $subject;
    $body = "
        <html>
        <body>
            <h2>Yeni İletişim Formu Mesajı</h2>
            <p><strong>Gönderen:</strong> {$name} ({$email})</p>
            <p><strong>Konu:</strong> {$subject}</p>
            <p><strong>Mesaj:</strong><br>{$message}</p>
            <hr>
            <p>Bu mesaj " . get_bloginfo('name') . " sitesindeki iletişim formundan gönderilmiştir.</p>
        </body>
        </html>
    ";

    $headers = array('Content-Type: text/html; charset=UTF-8');
    $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $to . '>';
    $headers[] = 'Reply-To: ' . $email;

    if (wp_mail($to, $mail_subject, $body, $headers)) {
        wp_send_json_success('Mesajınız başarıyla gönderildi. Teşekkür ederiz!');
    } else {
        wp_send_json_error('Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.');
    }
}
add_action('wp_ajax_mevzu_contact_form', 'mevzu_contact_form_handler');
add_action('wp_ajax_nopriv_mevzu_contact_form', 'mevzu_contact_form_handler');

/**
 * Özel sayfalarda (Künye, İletişim, Hava Durumu vb.) yazı editörünü (TinyMCE / Gutenberg) KÖKTEN kapatır.
 */
function mevzu_remove_editor_completely() {
    global $post;
    if ( ! is_admin() || ! $post || $post->post_type !== 'page' ) return;
    
    // Şablon kontrolü
    $template = get_post_meta( $post->ID, '_wp_page_template', true );
    
    // Ayarlardan seçilen özel sayfaların ID kontrolleri
    $special_page_keys = array(
        'kunye_sayfasi', 'iletisim_sayfasi',
        'akis_sayfasi', 'finans_sayfasi', 'havadurumu_sayfasi',
        'namaz_vakitleri_sayfasi', 'sondakika_sayfasi', 'arsiv_sayfasi', 'yazarlar_sayfasi',
        'yol_durumu_sayfasi', 'nobetci_eczaneler_sayfasi'
    );
    
    $special_page_ids = array();
    foreach ($special_page_keys as $key) {
        $id = get_option('options_' . $key);
        if ($id) $special_page_ids[] = $id;
    }

    $special_templates = array(
        'page-kunye.php', 'page-iletisim.php', 'page-akis.php', 'page-finans.php',
        'page-havadurumu.php', 'page-namaz-vakitleri.php', 'page-nobetci-eczaneler.php',
        'page-sondakika.php', 'page-yazarlar.php', 'page-yol-durumu.php'
    );

    $is_special_page = (
        in_array( $template, $special_templates ) || 
        in_array( $post->ID, $special_page_ids )
    );

    if ( $is_special_page ) {
        // Editör desteğini pasif yapalım
        remove_post_type_support( 'page', 'editor' );
        
        // CSS ile her türlü editör kalıntısını (TinyMCE ve Gutenberg) gizleyelim
        echo '<style>
            /* Klasik Editör Gizleme */
            #postdivrich, 
            #wp-content-wrap, 
            #post-status-info,
            .wp-editor-expand,
            /* Gutenberg Gizleme */
            .edit-post-layout__content, 
            .editor-styles-wrapper, 
            .block-editor-writing-flow,
            .interface-interface-skeleton__content,
            .edit-post-visual-editor { 
                display: none !important; 
            }
            /* Sayfa Bilgileri kutusunu en tepeye yaklaştıralım */
            #mevzu-sayfa-tablo { margin-top: 0 !important; }
            #post-body-content {margin-bottom:1rem;}
        </style>';
    }
}
add_action( 'admin_head', 'mevzu_remove_editor_completely' );

// Gutenberg'i PHP seviyesinde de bu sayfalar için deaktif edelim
add_filter('use_block_editor_for_post', function($use, $post) {
    if ( ! $post || $post->post_type !== 'page' ) return $use;

    $template = get_post_meta( $post->ID, '_wp_page_template', true );
    $special_templates = array(
        'page-kunye.php', 'page-iletisim.php', 'page-akis.php', 'page-finans.php',
        'page-havadurumu.php', 'page-namaz-vakitleri.php', 'page-nobetci-eczaneler.php',
        'page-sondakika.php', 'page-yazarlar.php', 'page-yol-durumu.php'
    );

    if ( in_array( $template, $special_templates ) ) {
        return false;
    }

    $special_page_keys = array(
        'kunye_sayfasi', 'iletisim_sayfasi',
        'akis_sayfasi', 'finans_sayfasi', 'havadurumu_sayfasi',
        'namaz_vakitleri_sayfasi', 'sondakika_sayfasi', 'arsiv_sayfasi', 'yazarlar_sayfasi',
        'yol_durumu_sayfasi', 'nobetci_eczaneler_sayfasi'
    );

    foreach ($special_page_keys as $key) {
        if ( $post->ID == get_option('options_' . $key) ) {
            return false;
        }
    }

    return $use;
}, 100, 2);

/**
 * Kullanıcı avatar URL'sini döndüren yardımcı fonksiyon
 */
function mevzu_get_user_avatar_url($user_id) {
    if (!$user_id) return '';

    // 1. ACF/Yazar Avatarları (yazar_avatari)
    $acf_avatar = get_user_meta($user_id, 'yazar_avatari', true);
    if ($acf_avatar) {
        $url = is_numeric($acf_avatar) ? wp_get_attachment_url($acf_avatar) : $acf_avatar;
        if ($url) return $url;
    }

    // 2. Panel üzerinden yüklenen avatar (mevzu_user_avatar)
    $p_avatar = get_user_meta($user_id, 'mevzu_user_avatar', true);
    if ($p_avatar) {
        $url = is_numeric($p_avatar) ? wp_get_attachment_url($p_avatar) : $p_avatar;
        if ($url) return $url;
    }

    // 3. Varsayılan WordPress Avatarı (Gravatar)
    return get_avatar_url($user_id);
}

function icerik_yok($tur = 'haber', $sidebar = null, $icon = '<i class="ri-file-image-line"></i>', $title = 'N/A', $desc = null) {
    
    $turkce_harfler = array('ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü', ' ');
    $ingilizce_harfler = array('c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u', '-');
    
    $temizle = str_replace($turkce_harfler, $ingilizce_harfler, $title);
    $temizle = preg_replace('/[^A-Za-z0-9\-]/', '', $temizle);
    $temizle = strtolower($temizle);

    $default_desc = 'Eklemek istediğiniz ' . $tur . 'leri admin panelinden ilgili ayarları yaparak ekleyebilirsiniz.';
    $final_desc = $desc ?: $default_desc;

    switch ($tur) {
        case 'sidebar':
            $tur_class = 'secondary';
            break;
        
        default:
            $tur_class = 'primary';
            break;
    }

    $customize_link = '';
    if ($sidebar) {
        $current_url = urlencode(set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
        $customize_url = admin_url('customize.php?url=' . $current_url . '&autofocus[section]=sidebar-widgets-' . $sidebar);
        $customize_link = '<a href="'.$customize_url.'" class="btn btn-sm btn-outline-'.$tur_class.' border-2 rounded-3 px-2 bg-white d-inline-flex align-items-center gap-2 fw-semibold"><i class="ri-settings-3-fill fs-6"></i> Bileşen Ekle</a>';
    }

    return '
    <div class="bg-white p-1 rounded-3 shadow-sm h-100 icerikyok-'.$temizle.'">
        <div class="bg-'.$tur_class. ' bg-opacity-10 border-1 border-'.$tur_class.' p-3 shadow-sm rounded-3 h-100 d-flex justify-content-center align-items-center flex-column gap-3 text-center">
            <div class="bg-'.$tur_class.' p-3 rounded-circle d-flex align-items-center justify-content-center fs-5 text-white" style="width: 48px; height: 48px; min-width:48px;">
                '.$icon.'
            </div>
            <div class="small fw-semibold bg-white py-2 px-3 rounded shadow-sm">
                '.$title.'
            </div>
            <p class="fw-normal small m-0">
                ' . ($tur == "haber" ? '<span class="fw-semibold d-block mb-2">Henüz bir '.$tur.' bulunmuyor.</span>' : '') . '
                <small class="text-muted">'.$final_desc.'</small>
            </p>' .
            $customize_link
            . '</div>
    </div>';
}
// Admin Bar\'a Bileşenleri Düzenle Butonu Ekleme
add_action('admin_bar_menu', 'mevzu_add_widget_edit_to_admin_bar', 100);
function mevzu_add_widget_edit_to_admin_bar($admin_bar) {
    if (is_admin()) {
        return;
    }

    $sidebar = '';

    if (is_home() || is_front_page()) {
        $sidebar = 'sidebar-anasayfa';
    } elseif (is_single()) {
        if (in_category('kose-yazilari')) {
            $sidebar = 'sidebar-koseyazilari';
        } else {
            $sidebar = 'sidebar-single';
        }
    } elseif (is_archive() || is_category() || is_tag() || is_search()) {
        $sidebar = 'sidebar-archive';
    }

    if ($sidebar) {
        $current_url = urlencode(set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
        $customize_url = admin_url('customize.php?url=' . $current_url . '&autofocus[section]=sidebar-widgets-' . $sidebar);

        $admin_bar->add_node(array(
            'id'    => 'mevzu-edit-sidebar',
            'title' => '<span class="ab-icon dashicons dashicons-admin-customizer" style="margin-top:2px"></span><span class="ab-label">Bileşenleri Düzenle</span>',
            'href'  => $customize_url,
            'meta'  => array(
                'title' => 'Bu sayfanın bileşenlerini düzenle',
            ),
        ));
    }
}

/**
 * AlSat Haber Puan Entegrasyonu — functions.php snippet
 *
 * WordPress yönetici panelinden "Ayarlar > AlSat Entegrasyon" menüsünden
 * AlSat adresini girin. Hem site kök adresi (https://siteadresiniz.com)
 * hem de tam claim endpoint adresi (https://siteadresiniz.com/api/news/claim)
 * kabul edilir.
 *
 * Kodu temanızın functions.php dosyasının en altına yapıştırın.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* 1. Admin ayar sayfası */
add_action('admin_menu', 'alsat_register_settings_page');
function alsat_register_settings_page() {
    add_options_page(
        'AlSat Entegrasyon',
        'AlSat Entegrasyon',
        'manage_options',
        'alsat-entegrasyon',
        'alsat_render_settings_page'
    );
}

add_action('admin_init', 'alsat_register_settings');
function alsat_register_settings() {
    register_setting('alsat_options_group', 'alsat_api_url', 'esc_url_raw');
}

function alsat_render_settings_page() {
    $api_url = get_option('alsat_api_url', '');
    ?>
    <div class="wrap">
        <h1>AlSat Haber Puan Entegrasyonu</h1>
        <form method="post" action="options.php">
            <?php settings_fields('alsat_options_group'); ?>
            <?php do_settings_sections('alsat_options_group'); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">AlSat Claim URL</th>
                        <td>
                            <input
                                type="url"
                                name="alsat_api_url"
                                value="<?php echo esc_attr($api_url); ?>"
                                class="regular-text"
                                placeholder="https://siteadresiniz.com/api/news/claim"
                            >
                            <p class="description">
                                Site kök adresi (örn. <code>https://siteadresiniz.com</code>) veya tam claim endpoint adresi (örn. <code>https://siteadresiniz.com/api/news/claim</code>) girebilirsiniz.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('Kaydet'); ?>
        </form>
    </div>
    <?php
}

/* 2. single.php/single post sayfalarında kullanılacak meta etiketleri */
add_action('wp_head', 'alsat_inject_claim_meta');
function alsat_inject_claim_meta() {
    if (!is_singular('post')) {
        return;
    }

    $token = isset($_GET['puankazan']) ? sanitize_text_field($_GET['puankazan']) : '';
    if (!$token) {
        return;
    }

    $api_url = get_option('alsat_api_url', '');
    if (!$api_url) {
        return;
    }

    echo '<meta name="alsat-claim-token" content="' . esc_attr($token) . '">' . "\n";
    echo '<meta name="alsat-claim-api" content="' . esc_attr($api_url) . '">' . "\n";
}

/* 3. (Opsiyonel) WordPress üzerinden CORS'lu proxy endpoint */
/*
 * Eğer tarayıcı direkt AlSat API'sine istek atamazsa,
 * WordPress REST API üzerinden aşağıdaki endpoint kullanılabilir:
 * /wp-json/alsat/v1/claim?puankazan=TOKEN
 */
add_action('rest_api_init', function () {
    register_rest_route('alsat/v1', '/claim', [
        'methods'             => 'GET',
        'callback'            => 'alsat_rest_claim_callback',
        'permission_callback' => '__return_true',
    ]);
});

function alsat_rest_claim_callback(WP_REST_Request $request) {
    $token   = $request->get_param('puankazan');
    $api_url = get_option('alsat_api_url', '');

    if (!$token) {
        return new WP_Error('no_token', 'puankazan parametresi eksik.', ['status' => 400]);
    }
    if (!$api_url) {
        return new WP_Error('no_api', 'AlSat Claim URL ayarlanmamış.', ['status' => 500]);
    }

    $response = wp_remote_get(
        add_query_arg('puankazan', rawurlencode($token), $api_url),
        ['timeout' => 15]
    );

    if (is_wp_error($response)) {
        return new WP_Error('request_failed', $response->get_error_message(), ['status' => 502]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data)) {
        return new WP_Error('invalid_response', 'AlSat API geçersiz yanıt döndü.', ['status' => 502]);
    }

    return $data;
}

/**
 * 1. Alanı Sayfa Ayarları Metabox'ının İçine Taşıma (JS Yöntemi)
 */
add_action('admin_footer', function() {
    global $post, $pagenow;
    
    // Sadece yazı ekleme ve düzenleme ekranlarında çalışsın
    if (!in_array($pagenow, ['post.php', 'post-new.php']) || !$post) {
        return;
    }

    $alsat_show = get_post_meta($post->ID, '_alsat_platform_show', true);
    
    // Güvenlik nonce alanını oluşturuyoruz
    wp_nonce_field('mevzu_alsat_action', 'mevzu_alsat_nonce');
    ?>
    
    <!-- Sayfada gizli bir alanda checkbox'ı oluştur -->
    <div id="mevzu-alsat-hidden-container" style="display: none;">
        <label style="display:flex;align-items:center;gap:6px;margin-bottom:6px;cursor:pointer;border-top:1px solid #dcdcde;padding-top:10px;margin-top:10px;">
            <input type="checkbox" name="alsat_platform_show" value="1" <?php checked($alsat_show, '1'); ?>>
            <span class="badge rounded-pill text-bg-primary me-1">YENİ</span><?php _e('AlSat Platformunda Göster', 'mevzu2'); ?>
        </label>
    </div>

    <!-- Script ile checkbox'ı 'Sayfa Ayarları' metabox'ına taşı -->
    <script>
    jQuery(document).ready(function($) {
        // Mevzu Sayfa Ayarları kutusunun içindeki ana kapsayıcıyı bul
        var $hedefMetabox = $('#mevzu-sayfa-ayarlari .mevzu-metabox');
        
        if ($hedefMetabox.length) {
            // Gizli kutudaki içeriği al ve Sayfa Ayarları'nın sonuna ekle
            var $icerik = $('#mevzu-alsat-hidden-container').children();
            $hedefMetabox.append($icerik);
            
            // Nonce alanını da forma düzgünce dahil et
            $hedefMetabox.append($('input[name="mevzu_alsat_nonce"]'));
        }
    });
    </script>
    <?php
});

/**
 * 2. Checkbox Verisini Kaydetme
 */
add_action('save_post', function ($post_id) {
    // Nonce kontrolü (Güvenlik)
    if (!isset($_POST['mevzu_alsat_nonce']) || !wp_verify_nonce($_POST['mevzu_alsat_nonce'], 'mevzu_alsat_action')) {
        return;
    }

    // Otomatik kayıt sırasında işlem yapma
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Kullanıcının yetkisi var mı kontrol et
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Veriyi güncelle veya sil
    if (isset($_POST['alsat_platform_show'])) {
        update_post_meta($post_id, '_alsat_platform_show', '1');
    } else {
        delete_post_meta($post_id, '_alsat_platform_show');
    }
});

/**
 * 3. RSS Feed İşlemleri
 */
add_action('rss2_ns', function () {
    echo 'xmlns:alsat="https://alsat.kkerem.com/ns" ';
});

add_action('rss2_item', function () {
    global $post;
    $show = (bool) get_post_meta($post->ID, '_alsat_platform_show', true);
    echo '<alsat:show>' . ($show ? '1' : '0') . '</alsat:show>';
});

function jfif_dosya_destegi( $mime_types ) {
    $mime_types['jfif'] = 'image/jpeg';
    return $mime_types;
}
add_filter( 'upload_mimes', 'jfif_dosya_destegi' );


function ozel_yayin_rolleri_ekle() {
    $editor_rolu = get_role( 'editor' );
    if ( $editor_rolu ) {
        $editor_yetkileri = $editor_rolu->capabilities;
        add_role( 'yazi_isleri_muduru', 'Yazı İşleri Müdürü', $editor_yetkileri );
        add_role( 'internet_editoru', 'İnternet Editörü', $editor_yetkileri );
        add_role( 'muhabir', 'Muhabir', $editor_yetkileri );
    }
}
add_action( 'init', 'ozel_yayin_rolleri_ekle' );