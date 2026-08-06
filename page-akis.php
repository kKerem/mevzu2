<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
if (!$is_ajax) {
    get_header();
}

if ( ! is_user_logged_in() ) {
    if ($is_ajax) {
        exit;
    }
    ob_start();
    ?>
    <div class="container my-5 py-5 text-center">
        <i class="ri-lock-2-line text-muted d-block mb-3" style="font-size: 64px;"></i>
        <h2 class="fw-bold mb-3">Akışınızı Görmek İçin Giriş Yapın</h2>
        <p class="text-muted mb-4">Sadece size özel olarak hazırlanan haber akışını görmek için lütfen giriş yapın veya kayıt olun.</p>
        <a href="<?php echo esc_url( home_url('/hesabim/giris') ); ?>" class="btn btn-primary rounded-pill px-4 me-2 fw-medium">Giriş Yap</a>
        <a href="<?php echo esc_url( home_url('/hesabim/kayit') ); ?>" class="btn btn-outline-primary rounded-pill px-4 fw-medium">Kayıt Ol</a>
    </div>
    <?php
    echo ob_get_clean();
    get_footer();
    exit;
}

$user_id = get_current_user_id();

// 1. Kullanıcının takip ettiği kategoriler
$followed_cats = get_user_meta($user_id, 'mevzu_subscribed_categories', true);
$followed_cats = is_array($followed_cats) ? $followed_cats : [];

// 2. Kullanıcının takip ettiği yazarlar
$followed_authors = get_user_meta($user_id, 'mevzu_subscribed_authors', true);
$followed_authors = is_array($followed_authors) ? $followed_authors : [];

// 3. Etkileşime girdiği (beğendiği, kaydettiği veya yorum yaptığı) gönderilerin kategorileri
$liked_posts = get_user_meta($user_id, 'mevzu_liked_posts', true);
$liked_posts = is_array($liked_posts) ? $liked_posts : [];

$bookmarked_posts = get_user_meta($user_id, 'mevzu_bookmarked_posts', true);
$bookmarked_posts = is_array($bookmarked_posts) ? $bookmarked_posts : [];

global $wpdb;
$commented_posts = $wpdb->get_col($wpdb->prepare("SELECT comment_post_ID FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = '1'", $user_id));
$commented_posts = is_array($commented_posts) ? $commented_posts : [];

$all_interactive_posts = array_unique(array_merge($liked_posts, $bookmarked_posts, $commented_posts));

$dynamic_cats = [];
if (!empty($all_interactive_posts)) {
    foreach ($all_interactive_posts as $pid) {
        $cats = wp_get_post_categories($pid);
        if (!empty($cats) && !is_wp_error($cats)) {
            $dynamic_cats = array_merge($dynamic_cats, $cats);
        }
    }
}

// Bütün alakalı kategorileri birleştir
$all_relevant_cats = array_unique(array_merge($followed_cats, $dynamic_cats));

$args = array(
    'post_type' => 'post',
    'posts_per_page' => 15,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
);

$has_preferences = !empty($all_relevant_cats) || !empty($followed_authors);

if ($has_preferences) {
    global $wpdb;
    $where_clauses = [];
    
    if (!empty($all_relevant_cats)) {
        $cat_ids = implode(',', array_map('intval', $all_relevant_cats));
        // Kategori ID'lerinden term_relationships üzerinden post ID'lerini bul
        $where_clauses[] = "ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ($cat_ids) AND taxonomy='category'))";
    }
    
    if (!empty($followed_authors)) {
        $author_ids = implode(',', array_map('intval', $followed_authors));
        $where_clauses[] = "post_author IN ($author_ids)";
    }
    
    $where_sql = implode(' OR ', $where_clauses);
    
    // Algoritmanın bulduğu en güncel 200 haberi çekiyoruz (Böylece pagination da çalışabilir)
    $feed_post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND ($where_sql) ORDER BY post_date DESC LIMIT 200");
    
    if (empty($feed_post_ids)) {
        $args['post__in'] = [0]; // Hiçbir şey bulunamadı
    } else {
        $args['post__in'] = $feed_post_ids;
    }
} 
// Eğer adamın hiçbir etkileşimi yoksa fallback olarak en son haberler varsayılan akış olarak gösterilecek. (Tıpkı X'deki For You standartı gibi)

$feed_query = new WP_Query($args);

?>
<div class="container my-3">
    <div class="row justify-content-between mt-lg-3">
        
        <!-- Akış Sütunu -->
        <div class="col-12 col-md-8">


            <div class="d-flex align-items-center mb-3">
                <h1 class="m-0 fs-5 fw-bold"><i class="ri-flashlight-fill me-1 text-primary"></i> Akış</h1>
            </div>

            <div class="p-3 small bg-light rounded-3 mb-3">
                <p class="text-muted small mb-0 lh-base">Bu akış tamamen takip ettiğiniz kategorilerin, yazarların, beğendiğiniz içeriklerin, yorum yaptığınız ve kaydettiğiniz haberlerin analizine göre size özel hazırlanmaktadır.</p>
            </div>
            
            <div class="feed-container">
            <?php
            if ($is_ajax) {
                ob_clean();
            }

            if ($feed_query->have_posts() && (!isset($args['post__in']) || $args['post__in'][0] !== 0)) {
                while ($feed_query->have_posts()) {
                    $feed_query->the_post();
                    $author_id = get_the_author_meta('ID');
                    $author_name = get_the_author_meta('display_name');
                    $author_avatar_url = mevzu_get_user_avatar_url($author_id);
                    if ($author_avatar_url) {
                         $avatar_url = '<img class="w-42 h-42 rounded-circle m-0 object-fit-cover" src="' . esc_url($author_avatar_url) . '" width="42" height="42" style="object-fit:cover;">';
                    } else {
                        $avatar_url = m_default('avatar');
                        $avatar_url = str_replace('width="100"', 'width="42"', $avatar_url);
                        $avatar_url = str_replace('height="100"', 'height="42"', $avatar_url);
                        $avatar_url = str_replace('class="', 'class="w-42 h-42 rounded-circle m-0 object-fit-cover ', $avatar_url);
                    }
                    ?>
                    <div class="tweet-card bg-white border-bottom p-3 mb-3 rounded border-0" onclick="if(!event.target.closest('a') && !event.target.closest('button') && !event.target.closest('.action-btn')) window.location.href='<?php the_permalink(); ?>';">
                        <div class="d-flex w-100">
                            <!-- Profil Resmi -->
                            <div class="flex-shrink-0 me-3">
                                <a href="<?php echo get_author_posts_url($author_id); ?>" class="d-block position-relative z-index-1">
                                    <?php echo $avatar_url; ?>
                                </a>
                            </div>
                            
                            <!-- İçerik -->
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-truncate small" style="max-width: 60%;">
                                        <a href="<?php echo get_author_posts_url($author_id); ?>" class="text-dark text-decoration-none text-hover-primary position-relative z-index-1"><?php echo $author_name; ?></a>
                                    </h6>
                                    <span class="mx-1 text-muted">·</span>
                                    <a class="text-muted small text-decoration-none text-hover-primary position-relative z-index-1" href="<?php the_permalink(); ?>">
                                        <?php echo (function_exists('time_ago') && (time() - strtotime(get_the_date('Y-m-d H:i:s')) < 30*24*60*60)) ? time_ago(get_the_date('Y-m-d H:i:s')) : get_the_date('j M'); ?>
                                    </a>
                                </div>
                                
                                <div class="mb-2">
                                    <p class="text-body mb-2 fw-medium lh-sm" style="font-size: 15.5px;">
                                        <?php echo wp_trim_words(strip_tags(get_the_content()), 50, '... <a href="'.get_permalink().'" class="text-primary text-decoration-none position-relative z-index-1">devamı</a>'); ?>
                                    </p>
                                </div>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>" class="d-block mb-3 rounded-3 overflow-hidden border position-relative z-index-1">
                                        <?php the_post_thumbnail('large', ['class' => 'img-fluid w-100', 'style' => 'object-fit:cover; max-height:400px;']); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center justify-content-between mt-2 position-relative z-index-1 gap-2">
                                    <!-- Yorum -->
                                    <div class="d-flex align-items-center text-muted action-btn">
                                        <a href="<?php comments_link(); ?>" class="d-inline-flex align-items-center gap-1 text-muted text-decoration-none text-hover-primary p-2 ms-n2 rounded-pill" data-bs-toggle="tooltip" data-bs-title="Yorum Yap">
                                            <i class="ri-chat-1-line fs-5"></i> 
                                            <span class="small count fw-medium ms-1"><?php echo get_comments_number() ?: ''; ?></span>
                                        </a>
                                    </div>
                                    
                                    <!-- Beğen -->
                                    <div class="d-flex align-items-center text-muted action-btn">
                                        <?php 
                                        if ( function_exists('mevzu_render_like_button') ) {
                                            ob_start();
                                            mevzu_render_like_button( get_the_ID(), 'post' );
                                            $like_btn = ob_get_clean();
                                            $like_btn = str_replace('fz-18', 'fs-5', $like_btn);
                                            // Make counter bold
                                            $like_btn = str_replace('count">', 'count fw-medium ms-1">', $like_btn);
                                            echo $like_btn;
                                        }
                                        ?>
                                    </div>
                                    
                                    <!-- Paylaş -->
                                    <div class="d-flex align-items-center text-muted action-btn">
                                        <button type="button" class="btn btn-link shadow-none p-2 text-muted text-hover-primary d-inline-flex align-items-center justify-content-center rounded-circle" data-bs-toggle="modal" data-bs-target="#paylas-<?php the_ID(); ?>" title="Paylaş" style="text-decoration:none; width: 38px; height: 38px;">
                                            <i class="ri-share-forward-line fs-5 m-0 position-relative" style="top: -2px;"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Kaydet -->
                                    <div class="d-flex align-items-center text-muted action-btn ms-auto">
                                        <?php 
                                        if ( function_exists('mevzu_render_bookmark_button') ) {
                                            ob_start();
                                            mevzu_render_bookmark_button( get_the_ID() );
                                            $bookmark = ob_get_clean();
                                            $bookmark = preg_replace('/<span.*?<\/span>/', '', $bookmark); // "Kaydet" metnini sil
                                            $bookmark = str_replace('fz-18', 'fs-5', $bookmark);
                                            echo '<div class="bookmark-feed-btn" data-bs-toggle="tooltip" data-bs-title="Kaydet">' . $bookmark . '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paylaş Modal -->
                        <div class="modal fade" id="paylas-<?php the_ID(); ?>" tabindex="-1" aria-hidden="true" onclick="event.stopPropagation();">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content p-lg-3 rounded-3">
                                    <div class="modal-header border-0 pb-0">
                                        <h1 class="modal-title fw-bolder fs-5">Bu İçeriği Paylaş</h1>
                                        <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body pb-4">
                                        <?php echo do_shortcode('[social id="'.get_the_ID().'"]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                
                if ($is_ajax) {
                    exit;
                }
                
                echo '<div class="mt-4 mb-5 pb-5 feed-pagination d-none">';
                echo bootstrap_pagination($feed_query);
                echo '</div>';
                
                // Infinite scroll loading spinner
                echo '<div class="text-center py-4 infinite-scroll-spinner mb-4" style="display:none;">
                        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                      </div>';
                
                wp_reset_postdata();
            } else {
                if ($is_ajax) exit;
                ?>
                <div class="text-center py-5 my-5 border rounded-3 bg-light">
                    <i class="ri-bubble-chart-line text-primary mb-3 d-block" style="opacity: 0.5; font-size: 64px;"></i>
                    <h3 class="fw-bold fs-4">Buralar biraz sessiz</h3>
                    <p class="text-muted px-4">Algoritmamızın size özel bir akış oluşturabilmesi için etkileşimde bulunun. Haberleri beğenin, kaydedin veya sevdiğiniz kategorileri/yazarları takip edin.</p>
                    <a href="<?php echo home_url('/yazarlar'); ?>" class="btn btn-primary rounded-pill px-4 mt-2 mb-2">Yazarları Keşfet</a><br>
                    <a href="<?php echo home_url(); ?>" class="btn btn-outline-dark rounded-pill px-4">Ana Sayfaya Dön</a>
                </div>
                <?php
            }
            ?>
            </div>
        </div>
        
        <!-- Sağ Sütun -->
        <div class="col-12 col-lg-4 ">
            <div class="sticky-top" style="top: 100px;">
                
                <div class="widget mt-3 mt-lg-4">
                    <h2>Kimi Takip Etmeli?</h2>
                    <div class="widget-body px-3 pb-3">
                        <?php
                            $exclude_authors = array_merge([$user_id], $followed_authors);
                            $args = array(
                                'role__in' => array('author', 'editor'),
                                'exclude' => $exclude_authors,
                                'number' => 20,
                                'orderby' => 'registered',
                                'order' => 'DESC'
                            );
                            $authors = get_users($args);
                            
                            if (!empty($authors)) {
                                shuffle($authors);
                                $authors = array_slice($authors, 0, 2);
                                echo '<div class="list-group list-group-flush bg-transparent gap-2">';
                                foreach ($authors as $author) {
                                    $a_id = $author->ID;
                                    $a_name = $author->display_name;
                                    $a_avatar_url = mevzu_get_user_avatar_url($a_id);
                                    if ($a_avatar_url) {
                                        $a_url = '<img class="w-42 h-42 rounded-circle m-0 object-fit-cover" src="' . esc_url($a_avatar_url) . '" width="40" height="40" style="object-fit:cover;">';
                                    } else {
                                        $a_url = m_default('avatar');
                                        $a_url = str_replace('width="100"', 'width="40"', $a_url);
                                        $a_url = str_replace('height="100"', 'height="40"', $a_url);
                                        $a_url = str_replace('class="', 'class="w-42 h-42 rounded-circle m-0 object-fit-cover ', $a_url);
                                    }
                                    ?>
                                    <div class="list-group-item bg-transparent px-0 py-1 border-0 d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2">
                                            <a href="<?php echo get_author_posts_url($a_id); ?>">
                                                <?php echo $a_url; ?>
                                            </a>
                                        </div>
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <h6 class="mb-0 fw-bold text-truncate small">
                                                <a href="<?php echo get_author_posts_url($a_id); ?>" class="text-dark text-decoration-none text-hover-primary"><?php echo $a_name; ?></a>
                                            </h6>
                                            <div class="text-muted small-2 fw-normal">Yazar</div>
                                        </div>
                                        <div class="flex-shrink-0 ms-2">
                                            <?php if ( function_exists('mevzu_render_author_follow_button') ) mevzu_render_author_follow_button($a_id); ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            } else {
                                echo '<p class="text-muted small fw-normal mb-0">Tüm yazarları takip ediyorsunuz!</p>';
                            }
                        ?>
                    </div>
                </div>

                <div class="widget mt-3 mt-lg-4 mt-3">
                    <h2>İlgini Çekebilir</h2>
                    <div class="widget-body px-3 pb-3">
                        <?php
                            $args = array(
                                'taxonomy' => 'category',
                                'hide_empty' => true,
                                'exclude' => $followed_cats,
                                'number' => 20,
                                'orderby' => 'count',
                                'order' => 'DESC'
                            );
                            $cats = get_terms($args);
                            
                            if (!empty($cats) && !is_wp_error($cats)) {
                                shuffle($cats);
                                $cats = array_slice($cats, 0, 4);
                                echo '<div class="list-group list-group-flush bg-transparent gap-2">';
                                foreach ($cats as $cat) {
                                    ?>
                                    <div class="list-group-item bg-transparent px-0 py-1 border-0 d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold" style="width:38px;height:38px;font-size:18px;">
                                                #
                                            </div>
                                        </div>
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <h6 class="mb-0 fw-bold text-truncate small">
                                                <a href="<?php echo get_category_link($cat->term_id); ?>" class="text-dark text-decoration-none text-hover-primary"><?php echo esc_html($cat->name); ?></a>
                                            </h6>
                                            <div class="text-muted small-2 fw-normal"><?php echo $cat->count; ?> Haber</div>
                                        </div>
                                        <div class="flex-shrink-0 ms-2">
                                            <?php if ( function_exists('mevzu_render_category_follow_button') ) mevzu_render_category_follow_button($cat->term_id, true); ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                echo '</div>';
                            } else {
                                echo '<p class="text-muted small px-2 mb-0">Tüm kategorileri takip ediyorsunuz!</p>';
                            }
                        ?>
                    </div>
                </div>

                <?php
                $args = array(
                    'before_widget' => '<section class="widget mt-3 mt-lg-4">',
                    'after_widget'  => '</section>',
                    'before_title'  => '<h2>',
                    'after_title'   => '</h2>',
                );
                $instance = array(
                );
                $widget = new Mevzu_Bize_Katilin_Widget();
                echo $widget->widget($args, $instance);
                ?>

                <div class="small-2 text-center mt-3">
                    <?php $array = array(get_option('options_gizlilik_politikasi_sayfasi'), get_option('options_kunye_sayfasi'), get_option('options_iletisim_sayfasi'));
                    $count = 0;
                    foreach ($array as $yazdir) {
                        echo ($count != 0 ? '<span class="text-muted opacity-50 cursor-default px-3">•</span>' : '') . '<a class="ripple text-link d-block p-1" data-bs-ripple-color="light"  href="' . get_permalink($yazdir) . '" title="' . get_the_title($yazdir) . '">' . get_the_title($yazdir) . '</a>';
                        $count++;
                    }
                    ?>
                    <div class="text-muted">
                        <small>©</small> <?php echo get_option('options_footer_unvan'); ?> - <?php echo date("Y", time()) ?> <?php bloginfo('title') ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<style>
.feed-nav .nav-link { padding: 12px 20px; transition: background-color 0.2s; }
.feed-nav .nav-link:hover { background-color: rgba(var(--mevzu-primary-rgb), 0.1) !important; color: var(--mevzu-primary) !important; }

.tweet-card { transition: background-color 0.2s; cursor: pointer; }
.tweet-card:hover { background-color: #f6f8fb; }
.z-index-1 { position: relative; z-index: 1; }

.action-btn a, .action-btn button, .bookmark-feed-btn a { transition: all 0.2s; border-radius: 50px !important; display: inline-flex; align-items: center; justify-content: center; }
.action-btn a:hover { background-color: rgba(var(--mevzu-primary-rgb), 0.1); color: var(--mevzu-primary) !important; }

.tweet-card .mevzu-toggle-like.text-danger { background-color: transparent; color: #dc3545 !important; }
.tweet-card .mevzu-toggle-like.text-danger:hover { background-color: rgba(220,53,69,0.1); }
.tweet-card .mevzu-toggle-like:not(.text-danger) { background-color: transparent; border-radius: 50px; padding: 5px 12px; }
.tweet-card .mevzu-toggle-like:not(.text-danger):hover { background-color: rgba(220,53,69,0.1); color: #dc3545 !important; }

.bookmark-feed-btn .mevzu-toggle-bookmark { padding: 8px 10px !important; }
.bookmark-feed-btn .mevzu-toggle-bookmark:hover { background-color: rgba(var(--mevzu-primary-rgb), 0.1) !important; color: var(--mevzu-primary) !important; }
.bookmark-feed-btn .mevzu-toggle-bookmark.text-primary { background-color: transparent; color: var(--mevzu-primary) !important;}
.bookmark-feed-btn .mevzu-toggle-bookmark.text-primary:hover { background-color: rgba(var(--mevzu-primary-rgb), 0.1); }

.feed-pagination .pagination { display: flex; justify-content: center; gap: 8px; }
.feed-pagination .page-link { border-radius: 50% !important; border: none; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--mevzu-dark); background: #f8f9fa; transition: 0.2s;}
.feed-pagination .page-link:hover { background: rgba(var(--mevzu-primary-rgb), 0.1); color: var(--mevzu-primary); }
.feed-pagination .page-item.active .page-link { background: var(--mevzu-primary); color: #fff; box-shadow: 0 4px 10px rgba(var(--mevzu-primary-rgb), 0.3); }
</style>

<?php if (!$is_ajax): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tooltipleri sayfa açılışında global olarak başlat (özellikle Önerilenler rozeti gibi statik elemanlar için)
    if (typeof mevzu2 !== 'undefined') {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new mevzu2.Tooltip(tooltipTriggerEl));
    }

    let isLoading = false;
    let container = document.querySelector('.feed-container');
    let spinner = document.querySelector('.infinite-scroll-spinner');
    let hasMore = true;
    let pagination = document.querySelector('.feed-pagination .pagination');

    // Get max pages from pagination
    let maxPages = 1;
    let currentPage = 1;
    
    if (pagination) {
        let pages = pagination.querySelectorAll('.page-link');
        pages.forEach(p => {
            let num = parseInt(p.innerText);
            if (!isNaN(num) && num > maxPages) {
                maxPages = num;
            }
        });
        
        let activePage = pagination.querySelector('.page-item.active .page-link');
        if (activePage) {
            currentPage = parseInt(activePage.innerText) || 1;
        }
    } else {
        hasMore = false;
    }
    
    if (currentPage >= maxPages) {
        hasMore = false;
    }

    function loadMore() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        spinner.style.display = 'block';
        currentPage++;
        
        let url = new URL(window.location.href);
        // Clean URL properly - remove any page/2 parts if they exist locally
        let baseUrl = url.origin + url.pathname;
        if (baseUrl.includes('/page/')) {
            baseUrl = baseUrl.split('/page/')[0] + '/';
        }
        
        // Fetch specific page via query param to bypass potential rewrite misses
        let fetchUrl = baseUrl + '?paged=' + currentPage + '&ajax=1';
        
        fetch(fetchUrl)
            .then(res => res.text())
            .then(html => {
                if (html.trim() === '') {
                    hasMore = false;
                } else {
                    spinner.insertAdjacentHTML('beforebegin', html);
                    
                    // Update active tooltip if any new elements have them
                    if (typeof mevzu2 !== 'undefined') {
                        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new mevzu2.Tooltip(tooltipTriggerEl));
                    }
                }
            })
            .catch(err => {
                console.error("Akış yükleme hatası:", err);
            })
            .finally(() => {
                isLoading = false;
                spinner.style.display = 'none';
                if (currentPage >= maxPages) hasMore = false;
            });
    }

    // Scroll listener listener
    window.addEventListener('scroll', function() {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 800) {
            loadMore();
        }
    });
});
</script>
<?php get_footer(); endif; ?>
