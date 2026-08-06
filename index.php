<?php get_header(); ?>

<div class="container position-relative">
    <?php get_template_part("sablon/reklamlar"); ?>
    <?php do_action( 'mevzu_homepage_after_top_promos' ); ?>
</div>


<?php
/* ── ÜST MANŞET (meta ile yönetilir) ── */
if ( get_opt_g('options_ust_manset_yeni', 'goster') == 1 ) :
    $ust_manset_count = absint( get_option( 'options_ust_manset_yeni_slider_sayisi', 5 ) );
    if ( $ust_manset_count < 1 ) {
        $ust_manset_count = 5;
    }
    $q_ust = new WP_Query([
        'post_type'      => [ 'post', 'resmi-ilanlar' ],
        'posts_per_page' => $ust_manset_count,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [[ 'key' => 'mevzu_manset_konumlari', 'value' => 'ust_manset', 'compare' => 'LIKE' ]],
    ]);
?>
    <div class="container ust-manset-govde mb-3 mb-lg-4">
        <?php if ( $q_ust->have_posts() ) : ?>
            <div class="swiper rounded shadow-sm position-relative" id="swiper-ust-manset">
                <div class="swiper-wrapper">
                    <?php while ( $q_ust->have_posts() ) : $q_ust->the_post();
                        $ust_img = get_post_meta( get_the_ID(), 'ust_manset_gorseli_url', true );
                    ?>
                    <div class="swiper-slide position-relative">
                        <a href="<?php the_permalink(); ?>" class="d-block" aria-label="<?php the_title_attribute(); ?>">
                            <?php if ( $ust_img ) : ?>
                            <img src="<?php echo esc_url( $ust_img ); ?>" alt="<?php the_title_attribute(); ?>"
                                class="rounded-0 w-100" loading="lazy">
                            <?php else : ?>
                            <div class="w-100 bg-secondary d-flex flex-column align-items-center justify-content-center text-white ust-manset-hazirlaniyor">
                                <div class="opacity-75 mb-3 text-center px-3 border-muted"><?php the_title(); ?></div>
                                <i class="ri-image-2-line opacity-50 fz-32 m-0"></i>
                                <span class="small opacity-75">Görsel Hazırlanıyor</span>
                            </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        <?php else : 
        echo '<div class="mt-3 mt-lg-4">' . icerik_yok(title: 'Üst Manşet', desc: 'Eklemek istediğiniz haberleri admin panelinde <b>Sayfa Ayarları</b> içerisinden \'<b>Üst manşette göster</b>\' olarak işaretlemeniz gerekiyor.<br><br>İsterseniz bu bölümü <b>Mevzu² Ayarları <i class="ri-arrow-right-double-line"></i> Anasayfa Ayarları</b> sayfasından devre dışı bırakabilirsiniz.') . '</div>'; 
        endif; ?>
    </div>
<?php endif; ?>


<div class="container position-relative mb-3 mb-lg-4">
    <?php Mevzu_Ads_Manager::render_swiper(); ?>

    <?php echo anasayfa_reklam('ust_reklam'); ?>

    <?php echo ramazan(); ?>

</div>


<div class="container position-relative">

    <?php if(get_opt_g('options_ust_manset', 'ust_manset_ayarlari') == 1) : ?>
        <?php 
        $ust_manset_transient_key = 'anasayfa_ust_mansetler_sorgusu';
        $q_manset = get_transient($ust_manset_transient_key);

        if (false === $q_manset) {
            $args = array(
                'post_type'      => [ 'post', 'resmi-ilanlar' ],
                'posts_per_page' => get_opt_g('options_ust_manset', 'slider_sayisi', 12),
                'post_status'    => array( 'publish' ),
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( 'key' => 'mevzu_manset_konumlari', 'value' => 'sicak_gundem', 'compare' => 'LIKE' ),
                    array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
                ),
            );
            $q_manset = new WP_Query($args);
            set_transient($ust_manset_transient_key, $q_manset, 0);
        } ?>


        <div class="mb-3 mb-lg-4">
        
        <?php if ($q_manset->have_posts()) : $count_manset=0; ?>
            <div class="swiper swiper-yatay tema-widget h-100" id="swiper-sicak-gundem">
                <div class="swiper-wrapper h-100">
                    <?php 
                    while ($q_manset->have_posts()) : 
                        $q_manset->the_post(); 
                        $count_manset++; 
                        $aria_label = "Slayt " . $count_manset;
                    ?>
                        <div class="swiper-slide pb-1 h-100" aria-label="<?php echo esc_attr($aria_label); ?>">
                            <?php get_template_part("sablon/sablon-2-nobox"); ?>
                        </div>
                    <?php endwhile; $count_manset = 0; wp_reset_postdata(); ?>
                </div>
            </div>
        <?php else : ?>
            <?php echo '<div class="mt-3 mt-lg-4">' . icerik_yok(title: 'Sıcak Gündem', desc: 'Eklemek istediğiniz haberleri admin panelinde <b>Sayfa Ayarları</b> içerisinden \'<b>Sıcak gündemde göster</b>\' olarak işaretlemeniz gerekiyor.<br><br>İsterseniz bu bölümü <b>Mevzu² Ayarları <i class="ri-arrow-right-double-line"></i> Anasayfa Ayarları</b> sayfasından devre dışı bırakabilirsiniz.') . '</div>';  ?>
        <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-lg-8 d-md-flex flex-md-column justify-content-md-between mb-0 mb-md index-slider">
            <?php
            $manset_transient_key = 'anasayfa_manset_sorgusu';
            $q = get_transient($manset_transient_key);
            
            if (false === $q) {
                $args = array(
                    'post_type'      => [ 'post', 'resmi-ilanlar' ],
                    'posts_per_page' => get_opt_g('options_manset', 'slider_sayisi', '1'),
                    'post_status'    => array( 'publish' ),
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array( 'key' => 'mevzu_manset_konumlari', 'value' => '"manset"', 'compare' => 'LIKE' ),
                        array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
                    ),
                );
                $q = new WP_Query($args);
                set_transient($manset_transient_key, $q, 0); // Süresiz, save_post ile silinecek
            }
            if ($q->have_posts()) : ?>
                <?php
                $slider_modeli = get_opt_g('options_manset', 'slider_modeli', 'default');
                $slider_renk = get_opt_g('options_manset', 'slider_renk', 'slider-beyaz');
                ?>
                <div id="manset-<?php echo esc_attr($slider_modeli); ?>" class="swiper swiper-yatay slider-1 rounded-3 widget">
                    <div class="swiper-wrapper ">
                        <?php while ($q->have_posts()) : $q->the_post(); ?>
                            <div class="swiper-slide">
                                <a href="<?php the_permalink() ?>" class="position-relative">
                                    <?php the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'loading'=>'lazy', 'class'=>'rounded-0 h-auto']); ?>
                                <?php
                                $yazi_ayarlari = get_post_meta(get_the_ID(), 'yazi_ayarlari', true);
                                if (get_opt_g('options_manset', 'slider_basliklari') == 1 && (!is_array($yazi_ayarlari) || !in_array('manset_var', $yazi_ayarlari))) :
                                ?>
                                    <h3 class="swiper-title <?php echo get_opt_g('options_manset', 'baslik_boyutu', 'fz-16') . ' ' . get_opt_g('options_manset', 'baslik_hizasi', 'text-center') ?>">
                                        <?php the_title(); ?>
                                    </h3>
                                <?php endif; ?>
                                </a>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    <div class="swiper-pagination rounded-md-bottom swiper-pagination-swiper-manset swiper-pagination-swiper-<?php echo esc_attr($slider_modeli); ?>"></div>
                    <div class="swiper-button-prev start-0 rounded-end">
                        <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122l-5.66-5.658Z"/></g></svg>
                    </div>
                    <div class="swiper-button-next end-0 rounded-start">
                        <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z"/></g></svg>
                    </div>
                </div>
            <?php else : ?>
                <?php echo icerik_yok(title: 'Ana Manşet', desc: 'Eklemek istediğiniz haberleri admin panelinde <b>Sayfa Ayarları</b> içerisinden \'<b>Manşette göster</b>\' olarak işaretlemeniz gerekiyor.'); ?>
            <?php endif; ?>

        </div>
        <div class="col-12 col-lg-4 index-sag">
            <?php
            $yan_tip = get_option('options_yan_manset_tip', '');
            if ( $yan_tip === 'yan_manset' ) :
                // Meta ile işaretli son 2 haber
                $q_yan = new WP_Query([
                    'post_type'      => [ 'post', 'resmi-ilanlar' ],
                    'posts_per_page' => 2,
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'meta_query'     => [[ 'key' => 'mevzu_manset_konumlari', 'value' => 'yan_manset', 'compare' => 'LIKE' ]],
                ]);
                if ( $q_yan->have_posts() ) :
            ?>
            <div class="yan-manset-kategori-wrap tema-widget" id="swiper-yan-manset">
                <?php while ( $q_yan->have_posts() ) : $q_yan->the_post(); ?>
                <div class="yan-manset-item bg-white shadow-sm rounded-3">
                    <a href="<?php the_permalink(); ?>" class="ripple text-link" data-bs-ripple-color="light">
                        <?php if ( get_post_thumbnail_id() ) : ?>
                            <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading' => 'lazy']); ?>
                        <?php else : ?>
                            <?php echo m_default(NULL); ?>
                        <?php endif; ?>
                    </a>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php else : 
                echo icerik_yok(title: 'Yan Manşet', desc: 'Eklemek istediğiniz haberleri admin panelinde <b>Sayfa Ayarları</b> içerisinden \'<b>Yan manşette göster</b>\' olarak işaretlemeniz gerekiyor.<br><br>İsterseniz bu bölümü <b>Mevzu² Ayarları <i class="ri-arrow-right-double-line"></i> Anasayfa Ayarları</b> sayfasından değiştirebilirsiniz.');
            endif; endif; ?>
            <?php if ( $yan_tip === 'haftalik_gundem' ) : ?>
            <?php
            $args = array(
                'before_widget' => '<section class="widget">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2>',
                'after_title'   => '</h2>',
            );
            $instance = array(
                'offset'      => 0,
                'title'       => 'Haftanın Gündemi',
                'order_by'    => 'meta_value_num',
                'post_count'  => 5,
                'template'    => 'populer-basliklar-2',
            );
            $widget = new bilesen_haftalik_gundem();
            echo $widget->widget($args, $instance);
            ?>
            <?php endif; ?>
    </div>
    </div>

    <?php echo anasayfa_reklam('anasayfa_reklam_1'); ?>

    <!-- yazarlar -->
    <?php
    if ( get_option( 'options_yazar_kosesi_goster', '1' ) === '1' ) :
        $authors = get_users( array(
            'role__in' => array( 'author' ),
        ) );
        if ( ! empty( $authors ) ) :
    ?>
        <div class="tema-widget tema-widget-primary bg-white shadow-sm rounded-3 p-3 pb-0 mt-3 mt-lg-4">
            <div class="row align-items-center mb-3 mb-lg-4">
                <div class="col">
                    <h2 class="m-0">Yazar Köşesi</h2>
                </div>
                <div class="col-auto ms-md-auto">
                    <a href="<?php bloginfo('url');?>/yazarlar" class="bg-hepsinigoster bg-white text-dark">
                        Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                    </a>
                </div>
            </div>
            
            <div class="swiper tema-widget my-3 pb-3 pb-md-0 rounded" id="swiper-yazarlar">
                <div class="swiper-wrapper">
                <?php
                $author_cards = array();
                foreach ( $authors as $author ) {
                    $author_id = $author->ID;
                    if (get_user_meta($author_id, 'kullaniciyi_gizle', true) == 1) {continue;}

                    $author_name = $author->display_name;
                    $avatar_url_raw = mevzu_get_user_avatar_url($author_id);
                    $avatar_img = '<img class="avatar rounded-circle w-40 h-40" src="' . esc_url($avatar_url_raw) . '" loading="lazy" alt="' . esc_attr($author_name) . '">';

                    $transient_key = 'author_latest_post_v2_' . $author_id;
                    $latest_post = get_transient( $transient_key );

                    if ( false === $latest_post ) {
                        $args = array(
                            'post_type'      => 'post',
                            'post_status'    => array( 'publish' ),
                            'author'         => $author_id,
                            'posts_per_page' => 1,
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                        );
                        $q = new WP_Query( $args );
                        if ( $q->have_posts() ) {
                            while ( $q->have_posts() ) {
                                $q->the_post();
                                $latest_post = array(
                                    'permalink'    => get_the_permalink(),
                                    'title'        => get_the_title(),
                                    'content'      => apply_filters('the_content', get_the_content()),
                                    'thumbnail'    => get_the_post_thumbnail( get_the_ID(), 'gorsel-thumbnail-col-3', array( 'title' => get_the_title(), 'loading'=>'lazy', 'class' => 'img-hover gorsel-thumbnail-col-3 shadow-sm' ) ),
                                    'post_date_ts' => (int) get_post_time('U', true, get_the_ID()),
                                );
                            }
                            wp_reset_postdata();
                            set_transient( $transient_key, $latest_post, 0 );
                        }
                    }

                    if ( $latest_post ) {
                        $author_cards[] = array(
                            'author_id'   => $author_id,
                            'author_name' => $author_name,
                            'avatar_img'  => $avatar_img,
                            'latest_post' => $latest_post,
                        );
                    }
                } // foreach $authors

                if ( ! empty( $author_cards ) ) {
                    usort( $author_cards, function ( $a, $b ) {
                        return (int) $b['latest_post']['post_date_ts'] <=> (int) $a['latest_post']['post_date_ts'];
                    } );
                }

                foreach ( $author_cards as $author_card ) :
                    $author_id = $author_card['author_id'];
                    $author_name = $author_card['author_name'];
                    $avatar_img = $author_card['avatar_img'];
                    $latest_post = $author_card['latest_post'];
                    $author_name_trim = trim( (string) $author_name );
                    $author_name_parts = preg_split( '/\s+/u', $author_name_trim );
                    if ( count( $author_name_parts ) >= 2 ) {
                        $author_surname = array_pop( $author_name_parts );
                        $author_given   = implode( ' ', $author_name_parts );
                        $author_name_html = '<span class="text-body text-opacity-75">' . esc_html( $author_given ) . '</span> <b>' . esc_html( $author_surname ) . '</b>';
                    } else {
                        $author_name_html = esc_html( $author_name_trim );
                    }
                ?>
                    <div class="swiper-slide h-100">
                        <div class="bg-white p-2 mb-2 rounded overflow-hidden" style="height: 320px">
                            <div class="row align-items-center mb-2">
                                <div class="col-auto pe-0">
                                    <a href="<?php echo $latest_post['permalink']; ?>">
                                        <?php echo $avatar_img; ?>
                                    </a>
                                </div>
                                <div class="col yazar fw-semibold small">
                                    <a href="<?php echo get_author_posts_url( $author_id ); ?>" class="text-link"><?php echo $author_name_html; ?></a>
                                </div>
                            </div>
                            <a href="<?php echo $latest_post['permalink']; ?>" class="ripple">
                                <?php echo $latest_post['thumbnail']; ?>
                            </a>
                            <div class="d-flex flex-column align-items-between">
                                <div class="satir-2 mt-2 mb-1 fz-16">
                                    <a href="<?php echo $latest_post['permalink']; ?>" class="fw-semibold d-block link-hover"><?php echo $latest_post['title']; ?></a>
                                </div>
                                <div class="satir-4 fz-13 fw-normal text-body">
                                    <?php
                                    $content = strip_tags($latest_post['content']); 
                                    echo mb_substr($content, 0, 180) . '...'; 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; endif; ?>
    <!-- yazarlar -->

    <?php anasayfa_reklam('anasayfa_reklam_2'); ?>

    <?php
    // Ana Kategori Blokları
    $ana_kat_count = intval( get_option('options_ana_kat_bloklar', 0) );
    for ( $ak = 0; $ak < $ana_kat_count; $ak++ ) :
        $ak_sablon         = get_option('options_ana_kat_' . $ak . '_sablon', 'sablon1');
        $ak_baslik         = get_option('options_ana_kat_' . $ak . '_baslik', '');
        $ak_haberler_metni = get_option('options_ana_kat_' . $ak . '_haberler_metni', '0');
        $ak_kategori_id    = intval( get_option('options_ana_kat_' . $ak . '_kategori', 0) );
        $ak_haber_sayisi   = intval( get_option('options_ana_kat_' . $ak . '_haber_sayisi', 6) );
    ?>

    <?php
        $ak_parent_cat = $ak_kategori_id ? get_category($ak_kategori_id) : null;
        if ( $ak_parent_cat ) :
            $ak_cat_name   = esc_html($ak_parent_cat->name);
            $ak_tab_label  = ( $ak_sablon === 'sablon1' && $ak_baslik ) ? $ak_baslik : $ak_cat_name;
    ?>
        <div class="tema-widget mt-3 mt-md-4">
            <h2 class="mt-0 mb-2 mx-0"><?php
                echo $ak_cat_name;
                if ($ak_haberler_metni === '1') echo ' Haberleri';
            ?></h2>

            <?php if ( $ak_sablon === 'sablon1' ) : ?>
            <!-- Şablon 1: Nav Tabs + AJAX -->
            <ul class="nav nav-pills mb-3 coklu gap-3 small">
                <?php
                echo '<li class="nav-item">';
                echo '<a class="nav-link nav-link-coklu border rounded-3 py-1 px-4 active"'
                    . ' data-bs-toggle="tab" data-bs-target="#ak-tab-' . $ak . '"'
                    . ' type="button" role="tab" aria-selected="true"'
                    . ' data-category-id="' . esc_attr($ak_kategori_id) . '"'
                    . ' data-haber-sayisi="' . esc_attr($ak_haber_sayisi) . '"'
                    . ' href="#">' . esc_html($ak_tab_label) . '</a>';
                echo '</li>';
                foreach ( get_categories(['parent' => $ak_kategori_id, 'hide_empty' => false]) as $ak_sub ) {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link nav-link-coklu border rounded-3 py-1 px-4"'
                        . ' data-bs-toggle="tab" data-bs-target="#ak-tab-' . $ak . '"'
                        . ' type="button" role="tab" aria-selected="false"'
                        . ' data-category-id="' . esc_attr($ak_sub->term_id) . '"'
                        . ' data-haber-sayisi="' . esc_attr($ak_haber_sayisi) . '"'
                        . ' href="#">' . esc_html($ak_sub->name) . '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
            <div class="tab-content">
                <div id="ak-tab-<?php echo $ak; ?>"></div>
            </div>

            <?php else : ?>
            <!-- Şablon 2: Direkt post grid, nav yok -->
            <?php
            $ak_posts = new WP_Query([
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'cat'            => $ak_kategori_id,
                'posts_per_page' => $ak_haber_sayisi,
                'meta_key'       => '_thumbnail_id',
            ]);
            if ( $ak_posts->have_posts() ) : ?>
            <div class="row g-3">
                <?php while ( $ak_posts->have_posts() ) : $ak_posts->the_post(); ?>
                <div class="col-12 col-md-3">
                    <div class="bg-white shadow-sm rounded-3 h-100">
                        <a href="<?php the_permalink(); ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
                            <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['loading' => 'lazy', 'title' => get_the_title()]); ?>
                            <h3 class="m-2 satir-2"><?php the_title(); ?></h3>
                        </a>
                    </div>
                </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php endfor; ?>
    
    <?php $sidebar_goster = get_option('options_sidebar_goster', '1') === '1'; ?>
    <?php echo intval(get_option('options_bloklar', 0)) > 0 || $sidebar_goster == 1 ? '<div class="row mt-3 mt-lg-4">' : '' ; ?>
        <div class="col-12 <?php echo $sidebar_goster ? 'col-lg-8' : 'col-lg-12'; ?>">
            <?php
            if(get_opt_g('options_alt_manset', 'alt_manseti_goster')) :
                $alt_manset_transient_key = 'anasayfa_alt_manset_sorgusu';
                $q = get_transient($alt_manset_transient_key);

                if (false === $q) {
                    $args = array(
                        'post_type'      => [ 'post', 'resmi-ilanlar' ],
                        'posts_per_page' => get_opt_g('options_alt_manset', 'slider_sayisi', '14'),
                        'post_status'    => array( 'publish' ),
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'meta_query'     => array(
                            'relation' => 'AND',
                            array( 'key' => 'mevzu_manset_konumlari', 'value' => 'alt_manset', 'compare' => 'LIKE' ),
                            array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
                        ),
                    );
                    $q = new WP_Query($args);
                    set_transient($alt_manset_transient_key, $q, 0);
                }
            if ($q->have_posts()) : ?>
                <?php
                $slider_modeli = get_opt_g('options_alt_manset', 'slider_modeli', 'default');
                $slider_renk = get_opt_g('options_alt_manset', 'slider_renk', 'slider-beyaz');
                ?>
                <div class="mb-3 mb-lg-4">
                    <div id="manset-<?php echo esc_attr($slider_modeli); ?>" class="swiper swiper-yatay slider-1 rounded-md-top">
                        <div class="swiper-wrapper ">
                            <?php while ($q->have_posts()) : $q->the_post(); ?>
                                <div class="swiper-slide">
                                    <a href="<?php the_permalink() ?>" class="position-relative">
                                        <?php the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'loading'=>'lazy', 'class'=>'rounded-0 h-auto']); ?>
                                    <?php
                                    $yazi_ayarlari = get_post_meta(get_the_ID(), 'yazi_ayarlari', true);
                                    if (get_opt_g('options_alt_manset', 'slider_basliklari') == 1 && (!is_array($yazi_ayarlari) || !in_array('manset_var', $yazi_ayarlari))) :
                                    ?>
                                        <h3 class="swiper-title <?php echo esc_attr( get_opt_g('options_alt_manset', 'baslik_boyutu', 'fz-16') . ' ' . get_opt_g('options_alt_manset', 'baslik_hizasi', 'text-center') ) ?>">
                                            <?php the_title(); ?>
                                        </h3>
                                    <?php endif; ?>
                                    </a>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                        <div class="swiper-pagination rounded-md-bottom swiper-pagination-swiper-manset swiper-pagination-swiper-<?php echo esc_attr($slider_modeli); ?>"></div>
                        <div class="swiper-button-prev start-0 rounded-end">
                            <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122l-5.66-5.658Z"/></g></svg>
                        </div>
                        <div class="swiper-button-next end-0 rounded-start">
                            <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z"/></g></svg>
                        </div>
                    </div>
                </div>
            <?php endif;
            else : 
            echo icerik_yok(title: 'Alt Manşet', desc: 'Eklemek istediğiniz haberleri admin panelinde <b>Sayfa Ayarları</b> içerisinden \'<b>Alt manşette göster</b>\' olarak işaretlemeniz gerekiyor.<br><br>İsterseniz bu bölümü <b>Mevzu² Ayarları <i class="ri-arrow-right-double-line"></i> Anasayfa Ayarları</b> sayfasından devre dışı bırakabilirsiniz.');
            endif; ?>

            <?php 
            $blok_sayisi = intval(get_option('options_bloklar', 0));
            if($blok_sayisi == 0) :
                for ($temp_sayi=0; $temp_sayi<1; $temp_sayi++)
                    echo '<div'. ($temp_sayi==0 ? ' class="'. ($sidebar_goster ? (get_opt_g('options_alt_manset', 'alt_manseti_goster') ? 'mt-3 mt-lg-4' : '') : 'mt-3 mt-lg-4') . '"' : '') .'>' . icerik_yok(title: 'Anasayfa Blok Alanı', desc: 'Bu alanda <b>Anasayfa Blokları</b> bölümünde eklediğiniz kategorilerin haberleri gösterilecek.', icon: '<i class="ri-dropdown-list"></i>') . '</div>';
            else :
            $say = 0; 
            $r = 3;
            for ($i = 0; $i < $blok_sayisi; $i++) : 
                $say++;
                $sablon = get_option('options_bloklar_' . $i . '_goruntuleme_sablonu', '');
                $tekli_blok = get_option('options_bloklar_' . $i . '_tekli_blok', '');
                $haber_sayisi = get_option('options_bloklar_' . $i . '_haber_sayisi', '3');
                $ikili_blok = get_option('options_bloklar_' . $i . '_ikili_blok', array());
                
                // Allow get_template_part to access ikili_blok by setting it as query var for sablon
                set_query_var('ikili_blok', $ikili_blok);
            ?>
                    <?php if( $sablon == 'sablon1') : 
                        $term = get_term($tekli_blok, 'category');
                        if ($term && !is_wp_error($term)) :
                            $cat_id = $term->term_id;
                            $cat_color = get_term_meta($cat_id, 'cat_renk', true);
                    ?>
                        <div class="my-3 my-lg-4 tema-widget<?php echo ($cat_color ? ' bg-sekil-' . esc_attr($cat_color) : ''); ?>">
                            <div class="row align-items-center mb-3">
                                <div class="col">
                                    <h2 class="m-0">
                                        <?php echo esc_html($term->name); ?> Haberleri
                                    </h2>
                                </div>
                                <div class="col-auto ms-lg-auto">
                                    <a href="<?php echo get_term_link( get_term($tekli_blok, 'category')->term_id ); ?>" class="bg-hepsinigoster">
                                        Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                                    </a>
                                </div>
                            </div>
                            <div class="row g-3">
                                <?php
                                $transient_key = 'sablon1_sorgusu_' . $tekli_blok;
                                $q = get_transient($transient_key);
                                if (false === $q) {
                                    $args = array(
                                        'post_type'      => 'post',
                                        'posts_per_page' => ($haber_sayisi ? $haber_sayisi : '3'),
                                        'post_status' => array( 'publish' ),
                                        'orderby'        => 'date',
                                        'order'          => 'DESC',
                                        'cat'  => $tekli_blok,
                                        'meta_query'     => array(
                                            array(
                                                'key'     => '_thumbnail_id',
                                                'compare' => 'EXISTS',
                                            ),
                                        ),
                                    );
                                    $q = new WP_Query($args);
                                    set_transient($transient_key, $q, 0);
                                }
                                if ($q->have_posts()) : $count=0; ?>
                                    <?php while ($q->have_posts()) : $q->the_post(); ?>
                                        <div class="col-12 col-md-4 pb-1">
                                            <a href="<?php the_permalink() ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
                                                <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                                <h3 class="satir-2 pt-2"><?php the_title(); ?></h3>
                                            </a>
                                        </div>
                                    <?php
                                    $count++; endwhile; $count=0;
                                    wp_reset_postdata();
                                endif;
                                ?>
                            </div>
                        </div>
                        <?php endif; // End if term check ?>
                        <?php anasayfa_reklam('anasayfa_reklam_' . $r); $r++; ?>
                    <?php elseif( $sablon == 'sablon2') : 
                        $term = get_term($tekli_blok, 'category');
                        if ($term && !is_wp_error($term)) :
                    ?>
                        <?php $kategori_adi = $term->name; ?>
                        <div class="my-3 my-lg-4 tema-widget<?php if (strpos($kategori_adi, 'Spor') !== false) { echo ' bg-sekil-outline-green'; } ?>">
                            <div class="row align-items-center mb-3">
                                <div class="col">
                                    <h2 class="m-0">
                                        <?php echo esc_html($term->name); ?>
                                    </h2>
                                </div>
                                <div class="col-auto ms-lg-auto">
                                    <a href="<?php echo get_term_link( $term->term_id ); ?>" class="bg-hepsinigoster">
                                        Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                                    </a>
                                </div>
                            </div>
                            
                            <?php
                            $transient_key = 'sablon2_sorgusu_' . $tekli_blok;
                            $q = get_transient($transient_key);
                            if (false === $q) {
                                $args = array(
                                    'post_type'      => 'post',
                                    'post_status' => array( 'publish' ),
                                    'posts_per_page' => 3,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                    'cat'  => $tekli_blok,
                                    'meta_query'     => array(
                                        array(
                                            'key'     => '_thumbnail_id',
                                            'compare' => 'EXISTS',
                                        ),
                                    ),
                                );
                                $q = new WP_Query($args);
                                set_transient($transient_key, $q, 0);
                            }
                            if ($q->have_posts()) : $count=0; ?>
                                <div class="row">
                                <?php if($count==0) echo '<div class="col-12 col-md-8 py-1">'; ?>
                                    <?php while ($q->have_posts()) : $q->the_post(); ?>
                                        <?php if($count==0) : ?>
                                            <a href="<?php the_permalink() ?>" class="ripple text-link bg-white shadow-sm rounded-3 h-100 d-block p-1" data-bs-ripple-color="light">
                                                <?php the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                                <h3 class="satir-2 p-2 pb-0"><?php the_title(); ?></h3>
                                                <p class="m-2 mb-0 text-muted small satir-3">
                                                <?php
                                                $content = strip_tags(get_the_content());
                                                echo mb_substr($content, 0, 300) . '...';
                                                ?>
                                                </p>
                                            </a>
                                        <?php else : ?>
                                            <?php if($count==1) echo '</div><div class="col-12 col-md-4 d-flex flex-column justify-content-between gap-3 gx-4 py-1">'; ?>
                                            <div class="w-100">
                                                <a href="<?php the_permalink() ?>" class="ripple text-link bg-white shadow-sm rounded-3 h-100 d-block p-1" data-bs-ripple-color="light">
                                                    <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                                    <h3 class="satir-2 m-2 bg-white"><?php the_title(); ?></h3>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                <?php
                                $count++; endwhile; echo '</div></div>'; $count=0;
                                wp_reset_postdata();
                            endif;
                            ?>
                        </div>
                        <?php endif; // End if term check ?>
                        <?php anasayfa_reklam('anasayfa_reklam_' . $r); $r++; ?>
                    <?php elseif( $sablon == 'sablon3') : 
                        $term = get_term($tekli_blok, 'category');
                        if ($term && !is_wp_error($term)) :
                    ?>
                        <div class="my-3 my-lg-4 tema-widget bg-sekil-outline-blue">
                            <div class="row align-items-center mb-3">
                                <div class="col">
                                    <h2 class="m-0">
                                        <?php echo esc_html($term->name); ?>
                                    </h2>
                                </div>
                                <div class="col-auto ms-lg-auto">
                                    <a href="<?php echo get_term_link( $term->term_id ); ?>" class="bg-hepsinigoster">
                                        Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                                    </a>
                                </div>
                            </div>
                            <div class="row g-3">
                                <?php
                                $transient_key = 'sablon3_sorgusu_' . $tekli_blok;
                                $q = get_transient($transient_key);
                                if (false === $q) {
                                    $args = array(
                                        'post_type'      => 'post',
                                        'posts_per_page' => ($haber_sayisi ? $haber_sayisi : '3'),
                                        'post_status' => array( 'publish' ),
                                        'orderby'        => 'date',
                                        'order'          => 'DESC',
                                        'cat'  => $tekli_blok,
                                        'meta_query'     => array(
                                            array(
                                                'key'     => '_thumbnail_id',
                                                'compare' => 'EXISTS',
                                            ),
                                        ),
                                    );
                                    $q = new WP_Query($args);
                                    set_transient($transient_key, $q, 0);
                                }
                                if ($q->have_posts()) : $count=0; ?>
                                    <?php while ($q->have_posts()) : $q->the_post(); ?>
                                        <div class="col-12 col-md-4 pb-1">
                                            <div class="bg-white shadow-sm rounded-3 h-100">
                                                <a href="<?php the_permalink() ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
                                                    <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                                    <div class="card-body p-2">
                                                        <h3 class="title satir-2"><?php the_title(); ?></h3>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    <?php
                                    $count++; endwhile; $count=0;
                                    wp_reset_postdata();
                                endif;
                                ?>
                            </div>
                        </div>
                        <?php endif; // End if term check ?>
                        <?php anasayfa_reklam('anasayfa_reklam_' . $r); $r++; ?>
                    <?php elseif( $sablon == 'ikilisablon') : // BURASININ THUMBNAILLERI AYARLANMADI ?>
                        <?php get_template_part('sablon/bolum-ikili'); ?>
                        <?php anasayfa_reklam('anasayfa_reklam_' . $r); $r++; ?>
                    <?php elseif( $sablon == 'resmiilanlar') : ?>
                        <?php
                        $transient_key = 'resmi_ilanlar_sorgusu';
                        $q = get_transient($transient_key);
                        // delete_transient($transient_key);
                        if (false === $q) {
                            $args = array(
                                'post_type'      => 'resmi-ilanlar',
                                'posts_per_page' => 9,
                                'post_status' => array( 'publish' ),
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                                'date_query'     => array(
                                    array(
                                        'after'     => '00:00:00',     // Başlangıç zamanı
                                        'before'    => '23:59:59',       // Bitiş zamanı
                                        'inclusive' => true,            // İki zaman aralığı da dahil edilecek
                                    ),
                                    ),
                            );
                            $q = new WP_Query($args);
                            set_transient($transient_key, $q, 2 * MINUTE_IN_SECONDS);
                        }
                        if ($q->have_posts()) : ?>
                            <div class="my-3 my-lg-4 tema-widget" data-control="resmi-ilanlar">
                                <h2>
                                    <a href="<?php bloginfo('url'); ?>/resmi-ilanlar">Resmi İlanlar</a>
                                </h2>
                                <div class="row g-3">
                                    <?php while ($q->have_posts()) : $q->the_post(); ?>
                                        <div class="col-12 col-md-4">
                                            <div class="bg-white bg-sekil overflow-hidden rounded-3 shadow-sm bik-ilan mb-1" id="bik-ilan-<?php if(get_post_meta(get_the_ID(), 'ilan_numarasi', true)) echo esc_html(get_post_meta(get_the_ID(), 'ilan_numarasi', true)); ?>">
                                                <a href="<?php the_permalink() ?>" class="ripple text-link d-block p-1 bik-ilan" data-bs-ripple-color="light" id="bik-ilan-<?php if(get_post_meta(get_the_ID(), 'ilan_numarasi', true)) echo esc_html(get_post_meta(get_the_ID(), 'ilan_numarasi', true)); ?>">
                                                    <?php the_post_thumbnail('gorsel-305-171', ['class'=>'card-img-top rounded-bottom-0', 'loading'=>'lazy', 'title' => get_the_title()]); ?>
                                                    <h3 class="title satir-2 p-2 pb-0"><?php the_title(); ?></h3>
                                                    <div class="details p-2">
                                                        <?php echo get_the_date(); ?>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                </div>
                            </div>
                            <?php anasayfa_reklam('anasayfa_reklam_' . $r); $r++; ?>
                        <?php endif; ?>
                    <?php endif; ?>
            <?php endfor; ?>
            <?php endif; ?>
        </div>
        <?php if ($sidebar_goster): ?>
        <div class="col-12 col-lg-4">
            <div class="sticky-top h-100" style="top:6rem">
                <?php 
                if ( is_active_sidebar( 'sidebar-anasayfa' ) ) {
                    dynamic_sidebar('sidebar-anasayfa'); 
                } else {
                    echo icerik_yok(tur: 'sidebar', sidebar: 'sidebar-anasayfa', title: 'Kenar Çubuğu: Anasayfa', desc: 'Bu alanda henüz hiçbir bileşen(widget) yok.', icon: '<i class="ri-layout-right-line"></i>');
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    <?php echo intval(get_option('options_bloklar', 0)) > 0 || $sidebar_goster == 1 ? '</div>' : '' ; ?>
            
    <?php if ( get_option( 'options_video_haberleri_goster', '1' ) === '1' ) : ?>
    <!-- Video Galeri Alani -->
    <div class="shadow-sm rounded index-videolar p-3 my-3 my-lg-4">
        <div class="row align-items-center tema-widget mb-3">
            <div class="col">
                <h2 class="text-white m-0"><?php echo get_term((get_option('options_video_kategorisi') ? get_option('options_video_kategorisi') : 1), 'category')->name; ?></h2>
            </div>
            <div class="col-auto ms-md-auto">
                <a href="<?php echo esc_url(get_category_link((get_option('options_video_kategorisi') ? get_option('options_video_kategorisi') : 1))); ?>" class="bg-hepsinigoster">
                    Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-8">
                <?php 
                $transient_key_first = 'first_video_post';
                $first_post_query = get_transient($transient_key_first);
                if (false === $first_post_query) {
                    $args = array(
                        'post_type' => 'post',
                        'posts_per_page' => 1,
                        'cat'  => get_option('options_video_kategorisi'),
                        'orderby' => 'date',
                        'order' => 'DESC'
                    );
                    $first_post_query = new WP_Query($args);
                    set_transient($transient_key_first, $first_post_query, 0);
                }
                if ($first_post_query->have_posts()) {
                    while ($first_post_query->have_posts()) { 
                        $first_post_query->the_post(); ?>
                        <a href="<?php the_permalink() ?>" class="d-block position-relative">
                            <?php the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'class' => 'rounded w-100', 'loading'=>'lazy']); ?>
                            <span class="play">
                                <i class="ri-play-fill h2 m-0"></i>
                            </span>
                            <div class="text-title mt-3 mt-lg-4">
                                <h3 class="text-white satir-1 h5 fw-bold mb-0"><?php the_title(); ?></h3>
                                <span class="small-2 text-white text-opacity-75"><?php echo get_the_date(); ?></span>
                            </div>
                        </a>
                    <?php }
                    wp_reset_postdata();
                }
                ?>
            </div>
            <div class="col-12 col-md sag mt-3 mt-lg-0 tema-widget d-flex flex-column justify-content-between gap-3">
                <?php
                $transient_key_others = 'other_video_posts';
                $other_posts_query = get_transient($transient_key_others);
                delete_transient($transient_key_others);
                if (false === $other_posts_query) {
                    $args = array(
                        'post_type' => 'post',
                        'posts_per_page' => 5,
                        'cat'  => get_option('options_video_kategorisi'),
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'offset' => 1
                    );
                    $other_posts_query = new WP_Query($args);
                    set_transient($transient_key_others, $other_posts_query, 0);
                }
                if ($other_posts_query->have_posts()) {
                    while ($other_posts_query->have_posts()) { 
                        $other_posts_query->the_post(); ?>
                        <a href="<?php the_permalink() ?>">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <div class="position-relative">
                                        <?php the_post_thumbnail('gorsel-thumbnail-col-4', ['title' => get_the_title(), 'class' => 'rounded', 'loading'=>'lazy']); ?>
                                        <span class="play"><i class="ri-play-fill h4 m-0"></i></span>
                                    </div>
                                </div>
                                <div class="col"><h3 class="text-white satir-3"><?php the_title(); ?></h3></div>
                            </div>
                        </a>
                    <?php } wp_reset_postdata();
                } ?>
            </div>
        </div>
    </div>
    <!-- Video Galeri Alani -->
    <?php endif; ?>

    <?php echo anasayfa_reklam('alt_reklam'); ?>

    <?php get_template_part('sablon/bolum-uclu') ?>
    
    <?php if ( get_option('options_anasayfa_son_haberler') == 1 ) : ?>
    <div class="tema-widget tema-widget-dark bg-white shadow-sm rounded-3 p-3 my-3 my-lg-4">
        <h2 class="mt-0 mb-3">Son Haberler</h2>
        <?php
        $transient_key_son_haberler = 'sonhaberler';
        $cached_posts = get_transient($transient_key_son_haberler);
        if (false === $cached_posts) {
            $son_haberler_count = absint( get_option( 'options_anasayfa_son_haberler_sayisi', 9 ) );
            if ( $son_haberler_count < 1 ) {
                $son_haberler_count = 9;
            }
            $args = array(
                'post_type'      => 'post',
                'posts_per_page' => $son_haberler_count,
                'post_status' => array( 'publish' ),
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_thumbnail_id',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            $cached_posts = new WP_Query($args);
            set_transient($transient_key_son_haberler, $cached_posts, 0);
        }
        if ($cached_posts->have_posts()) : $count = 0; ?>
            <div class="row g-3">
                <?php $shown_posts = 0; while ($cached_posts->have_posts()) : $cached_posts->the_post(); ?>
                    <div class="col-12 col-md-3">
                        <?php get_template_part("sablon/sablon-1-nobox"); ?>
                    </div>
                <?php $shown_posts++; $count++; endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; // anasayfa_son_haberler ?>
    <?php if(get_option('options_ilangovtr') == 1) : ?>
        <div class="reklam-full my-3">
            <iframe class="rounded-3 overflow-hidden shadow-sm" title="BIKADV" name="BIKADV" src="<?php echo (get_option('options_ilangovtr_embed')) ? get_option('options_ilangovtr_embed') : ''; ?>" width="100%" height="100%" frameborder="0" scrolling="no"></iframe>
        </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>