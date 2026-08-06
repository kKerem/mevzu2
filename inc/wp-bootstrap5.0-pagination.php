<?php
/**
 * @param WP_Query|null $wp_query
 * @param bool $echo
 * @param array $params
 *
 * @return string|null
 * 
 * Using Bootstrap 4? see https://gist.github.com/mtx-z/f95af6cc6fb562eb1a1540ca715ed928
 * 
 * Accepts a WP_Query instance to build pagination (for custom wp_query()),
 * or nothing to use the current global $wp_query (eg: taxonomy term page)
 * - Tested on WP 5.7.1
 * - Tested with Bootstrap 5.0 (https://getbootstrap.com/docs/5.0/components/pagination/)
 * - Tested on Sage 9.0.9
 *
 * INSTALLATION:
 * add this file content to your theme function.php or equivalent
 *
 * USAGE:
 *     <?php echo bootstrap_pagination(); ?> //uses global $wp_query
 * or with custom WP_Query():
 *     <?php
 *      $query = new \WP_Query($args);
 *       ... while(have_posts()), $query->posts stuff ... endwhile() ...
 *       echo bootstrap_pagination($query);
 *     ?>
 */
function bootstrap_pagination( \WP_Query $wp_query = null, $echo = true, $params = [] ) {
    if ( null === $wp_query ) {
        global $wp_query;
    }

    // Archive sayfalarında max_num_pages bazen 0 olabiliyor, yeniden hesapla
    if ( isset( $wp_query->max_num_pages ) && $wp_query->max_num_pages == 0 ) {
        // found_posts değerini kontrol et
        $found_posts = isset( $wp_query->found_posts ) ? intval( $wp_query->found_posts ) : 0;
        
        // Eğer found_posts 0 ise ama post_count > 0 ise, archive sayfası için özel query yap
        if ( $found_posts == 0 && isset( $wp_query->post_count ) && $wp_query->post_count > 0 ) {
            // Archive sayfalarında found_posts bazen doğru set edilmemiş olabilir
            // Bu durumda, archive sayfası için özel bir query yaparak toplam post sayısını bul
            if ( is_category() || is_tag() || is_tax() ) {
                $term = get_queried_object();
                if ( $term ) {
                    $count_args = array(
                        'post_type' => 'post',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    );
                    
                    if ( is_category() ) {
                        $count_args['cat'] = $term->term_id;
                    } elseif ( is_tag() ) {
                        $count_args['tag_id'] = $term->term_id;
                    } else {
                        // Taxonomy için
                        $count_args['tax_query'] = array(
                            array(
                                'taxonomy' => $term->taxonomy,
                                'field' => 'term_id',
                                'terms' => $term->term_id,
                            )
                        );
                    }
                    
                    $count_query = new WP_Query($count_args);
                    $found_posts = $count_query->found_posts;
                    wp_reset_postdata();
                }
            } elseif ( is_author() ) {
                $author_id = get_queried_object_id();
                $count_query = new WP_Query(array(
                    'post_type' => 'post',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'author' => $author_id,
                ));
                $found_posts = $count_query->found_posts;
                wp_reset_postdata();
            } elseif ( is_date() ) {
                $count_query = new WP_Query(array(
                    'post_type' => 'post',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'year' => get_query_var('year'),
                    'monthnum' => get_query_var('monthnum'),
                    'day' => get_query_var('day'),
                ));
                $found_posts = $count_query->found_posts;
                wp_reset_postdata();
            } elseif ( is_search() ) {
                // Search sayfaları için
                $search_query = get_search_query();
                $count_query = new WP_Query(array(
                    's' => $search_query,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ));
                $found_posts = $count_query->found_posts;
                wp_reset_postdata();
            }
        }
        
        if ( $found_posts > 0 ) {
            // posts_per_page değerini al
            $posts_per_page = 0;
            if ( isset( $wp_query->query_vars['posts_per_page'] ) && $wp_query->query_vars['posts_per_page'] > 0 ) {
                $posts_per_page = intval( $wp_query->query_vars['posts_per_page'] );
            } elseif ( isset( $wp_query->query_vars['number'] ) && $wp_query->query_vars['number'] > 0 ) {
                $posts_per_page = intval( $wp_query->query_vars['number'] );
            } else {
                $posts_per_page = intval( get_option('posts_per_page') );
            }
            
            if ( $posts_per_page > 0 ) {
                $wp_query->max_num_pages = ceil( $found_posts / $posts_per_page );
                $wp_query->found_posts = $found_posts; // found_posts'u da set et
            }
        }
    }

    // Sayfalama gerekmiyorsa null döndür
    if ( ! isset( $wp_query->max_num_pages ) || $wp_query->max_num_pages < 1 ) {
        return null;
    }

    $add_args = [];

    //add query (GET) parameters to generated page URLs
    /*if (isset($_GET[ 'sort' ])) {
        $add_args[ 'sort' ] = (string)$_GET[ 'sort' ];
    }*/

    // Archive sayfaları için base URL'i düzelt
    $paged = max(1, get_query_var('paged'));
    global $wp_rewrite;
    $using_permalinks = $wp_rewrite->using_permalinks();
    
    // Archive sayfaları için özel base URL oluştur
    $base = '';
    $format = '';
    
    // Archive sayfaları için mevcut sayfa URL'ini al ve paged parametresini kaldır
    $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = remove_query_arg('paged', $current_url);
    
    if (is_category() || is_tag() || is_tax()) {
        $archive_link = get_term_link(get_queried_object());
        if (!is_wp_error($archive_link)) {
            if ($using_permalinks) {
                $base = trailingslashit($archive_link) . user_trailingslashit('page/%#%', 'paged');
                $format = '';
            } else {
                // Query string kullanılıyorsa - mevcut URL'i kullan
                $base = trailingslashit($archive_link);
                $format = '?paged=%#%';
            }
        } else {
            // Hata durumunda mevcut URL'i kullan
            $base = trailingslashit($current_url);
            $format = '?paged=%#%';
        }
    } elseif (is_author()) {
        $author_link = get_author_posts_url(get_queried_object_id());
        if ($using_permalinks) {
            $base = trailingslashit($author_link) . user_trailingslashit('page/%#%', 'paged');
            $format = '';
        } else {
            // Query string kullanılıyorsa
            $base = trailingslashit($author_link);
            $format = '?paged=%#%';
        }
    } elseif (is_date()) {
        if ($using_permalinks) {
            if (get_query_var('day')) {
                $archive_link = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
            } elseif (get_query_var('monthnum')) {
                $archive_link = get_month_link(get_query_var('year'), get_query_var('monthnum'));
            } else {
                $archive_link = get_year_link(get_query_var('year'));
            }
            $base = trailingslashit($archive_link) . user_trailingslashit('page/%#%', 'paged');
            $format = '';
        } else {
            // Query string kullanılıyorsa date archive için de base'i ayarla
            if (get_query_var('day')) {
                $archive_link = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
            } elseif (get_query_var('monthnum')) {
                $archive_link = get_month_link(get_query_var('year'), get_query_var('monthnum'));
            } else {
                $archive_link = get_year_link(get_query_var('year'));
            }
            $base = trailingslashit($archive_link);
            $format = '?paged=%#%';
        }
    } elseif ( is_search() ) {
        // Search sayfaları için
        $search_link = get_search_link();
        if ($using_permalinks) {
            $base = trailingslashit($search_link) . user_trailingslashit('page/%#%', 'paged');
            $format = '';
        } else {
            // Query string kullanılıyorsa
            $base = trailingslashit($search_link);
            $format = '?paged=%#%';
        }
    } else {
        // Normal sayfalar için varsayılan base URL
        $base = str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999)));
        $format = '?paged=%#%';
    }

    // paginate_links parametreleri
    $paginate_args = array_merge([
        'base' => $base,
        'format' => $format,
        'current' => $paged,
        'total' => $wp_query->max_num_pages,
        'type' => 'array',
        'show_all' => false,
        'end_size' => 1,
        'mid_size' => 1,
        'prev_next' => true,
        'prev_text' => __('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="currentColor" d="M12.452 4.516c.446.436.481 1.043 0 1.576L8.705 10l3.747 3.908c.481.533.446 1.141 0 1.574c-.445.436-1.197.408-1.615 0c-.418-.406-4.502-4.695-4.502-4.695a1.095 1.095 0 0 1 0-1.576s4.084-4.287 4.502-4.695s1.17-.436 1.615 0"/></svg>'),
        'next_text' => __('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="currentColor" d="M9.163 4.516c.418.408 4.502 4.695 4.502 4.695a1.095 1.095 0 0 1 0 1.576s-4.084 4.289-4.502 4.695c-.418.408-1.17.436-1.615 0c-.446-.434-.481-1.041 0-1.574L11.295 10L7.548 6.092c-.481-.533-.446-1.141 0-1.576c.445-.436 1.197-.409 1.615 0"/></svg>'),
        'add_args' => $add_args,
        'add_fragment' => '',
    ], $params);

    $pages = paginate_links($paginate_args);

    // paginate_links() boş string veya false döndürebilir, bunu kontrol et
    if ( ! is_array( $pages ) || empty( $pages ) ) {
        return null;
    }

    //$current_page = ( get_query_var( 'page' ) == 0 ) ? 1 : get_query_var( 'page' );
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center my-3 mb-lg-0 shadow-sm rounded-3 bg-white">';

    foreach ( $pages as $page ) {
        $pagination .= '<li class="page-item m-1' . (strpos($page, 'current') !== false ? ' active' : '') . '"> ' . str_replace('page-numbers', 'page-link border-0 rounded text-link', $page) . '</li>';
    }

    $pagination .= '</ul></nav>';

    if ( $echo ) {
        echo $pagination;
    } else {
        return $pagination;
    }
}

/**
 * Notes:
 * AJAX:
 * - When used with wp_ajax (generate pagination HTML from ajax) you'll need to provide base URL (or it'll be admin-ajax URL)
 * - Example for a term page: bootstrap_pagination( $query, false, ['base' => get_term_link($term) . '?page=%#%'] )
 *
 * Images as next/prev:
 * - You can use image as next/prev buttons
 * - Example: 'prev_text' => '<img src="' . get_stylesheet_directory_uri() . '/assets/images/prev-arrow.svg">',
 *
 * Add query parameters to page URLs
 * - If you need custom URL parameters on your page URLS, use the "add_args" attribute
 * - Example (before paginate_links() call):
 * $arg = [];
 * if (isset($_GET[ 'sort' ])) {
 *  $args[ 'sort' ] = (string)$_GET[ 'sort' ];
 * }
 * ...
 * 'add_args'     => $args,
 */