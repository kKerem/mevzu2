<?php
/**
 * Firma Rehberi — WordPress Widget'ları
 *
 * - Firma_Kategoriler_Widget  : Firma kategorileri listesi
 * - Firma_Sehirler_Widget     : Firma şehirleri listesi
 * - Firma_Benzer_Widget       : Benzer firmalar (single-firma sayfasında)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================================================================
 * 1. Firma Kategorileri Widget
 * ====================================================================== */

class Firma_Kategoriler_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'firma_kategoriler_widget',
            'Mevzu² — Firma Kategorileri',
            [ 'description' => 'Firma rehberi kategorilerini alt kategorilerle accordion olarak listeler.' ]
        );
    }

    public function widget( $args, $instance ) {
        $title      = apply_filters( 'widget_title', $instance['title'] ?? 'Kategoriler' );
        $show_count = ! empty( $instance['show_count'] );
        $cache_key  = 'firma_widget_kategoriler_v2_' . ( $show_count ? '1' : '0' );

        $html = get_transient( $cache_key );

        if ( false === $html ) {
            $ustler = get_terms( [
                'taxonomy'   => 'firma-kategori',
                'hide_empty' => false,
                'parent'     => 0,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ] );

            if ( is_wp_error( $ustler ) || empty( $ustler ) ) {
                set_transient( $cache_key, '', 5 * MINUTE_IN_SECONDS );
                return;
            }

            ob_start();
            $acc_id = 'firmaKatAccordion';
            echo '<div class="accordion accordion-flush small p-3 pt-0" id="' . esc_attr( $acc_id ) . '">';

            foreach ( $ustler as $idx => $kat ) {
                $altlar = get_terms( [
                    'taxonomy'   => 'firma-kategori',
                    'hide_empty' => true,
                    'parent'     => $kat->term_id,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ] );

                $has_children = ! is_wp_error( $altlar ) && ! empty( $altlar );

                // Parent'ın gerçek count'u: kendi direkt postları + child post toplamı
                $child_total  = $has_children ? array_sum( wp_list_pluck( $altlar, 'count' ) ) : 0;
                $parent_count = intval( $kat->count ) + $child_total;

                // Post'u olmayan ve child'ı da olmayan parent'ları atla
                if ( $parent_count === 0 && ! $has_children ) continue;

                $item_id = 'fkCat' . $kat->term_id;

                echo '<div class="accordion-items">';

                if ( $has_children ) {
                    // Açılır/kapanır başlık — parent kategori linki + toggle
                    echo '<h3 class="accordion-header m-0">';
                    echo '<button class="accordion-button collapsed py-2 px-0 fz-14 fw-semibold bg-transparent shadow-none text-body"
                               type="button" data-bs-toggle="collapse"
                               data-bs-target="#' . esc_attr( $item_id ) . '"
                               aria-expanded="false">';
                    echo '<span class="flex-grow-1">' . esc_html( $kat->name ) . '</span>';
                    // if ( $show_count ) {
                    //     echo '<span class="badge text-bg-light border me-2">' . $parent_count . '</span>';
                    // }
                    echo '</button></h3>';

                    echo '<div id="' . esc_attr( $item_id ) . '" class="accordion-collapse collapse"
                               data-bs-parent="#' . esc_attr( $acc_id ) . '">';
                    echo '<ul class="list-unstyled mb-0 ps-0 pb-1">';
                    // Parent'ın kendi direkt postları varsa "Tümü" linki göster
                    if ( intval( $kat->count ) > 0 ) {
                        echo '<li class="py-1 border-bottom">';
                        echo '<a href="' . esc_url( get_term_link( $kat ) ) . '"
                                class="d-flex justify-content-between align-items-center text-link small fw-normal">';
                        echo '<span>— ' . esc_html( $kat->name ) . '</span>';
                        if ( $show_count ) {
                            echo '<span class="badge text-bg-light border">' . $parent_count . '</span>';
                        }
                        echo '</a></li>';
                    }
                    foreach ( $altlar as $alt ) {
                        echo '<li class="py-1">';
                        echo '<a href="' . esc_url( get_term_link( $alt ) ) . '"
                                class="d-flex justify-content-between align-items-center text-link small fw-normal">';
                        echo '<span>' . esc_html( $alt->name ) . '</span>';
                        if ( $show_count ) {
                            echo '<span class="badge text-bg-light border">' . intval( $alt->count ) . '</span>';
                        }
                        echo '</a></li>';
                    }
                    echo '</ul></div>';

                } else {
                    // Alt kategorisi yok — düz link
                    echo '<a href="' . esc_url( get_term_link( $kat ) ) . '"
                            class="d-flex justify-content-between align-items-center py-2 px-0 text-dark text-decoration-none small fw-semibold">';
                    echo '<span>' . esc_html( $kat->name ) . '</span>';
                    if ( $show_count ) {
                        echo '<span class="badge text-bg-light border">' . $parent_count . '</span>';
                    }
                    echo '</a>';
                }

                echo '</div>';
            }

            echo '</div>';
            $html = ob_get_clean();

            set_transient( $cache_key, $html, 5 * MINUTE_IN_SECONDS );
        }

        if ( empty( $html ) ) return;

        echo $args['before_widget'];
        if ( $title ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        echo $html;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = $instance['title']      ?? 'Kategoriler';
        $show_count = $instance['show_count'] ?? 1;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('show_count'); ?>"
                   name="<?php echo $this->get_field_name('show_count'); ?>" value="1"
                   <?php checked( $show_count, 1 ); ?>>
            <label for="<?php echo $this->get_field_id('show_count'); ?>">Firma sayısını göster</label>
        </p>
        <?php
    }

    public function update( $new, $old ) {
        delete_transient( 'firma_widget_kategoriler_v2_0' );
        delete_transient( 'firma_widget_kategoriler_v2_1' );
        return [
            'title'      => sanitize_text_field( $new['title'] ?? '' ),
            'show_count' => ! empty( $new['show_count'] ) ? 1 : 0,
        ];
    }
}

/* ======================================================================
 * 2. Firma Şehirleri Widget
 * ====================================================================== */

class Firma_Sehirler_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'firma_sehirler_widget',
            'Mevzu² — Firma Şehirleri',
            [ 'description' => 'Firma olan şehirleri listeler.' ]
        );
    }

    public function widget( $args, $instance ) {
        $title     = apply_filters( 'widget_title', $instance['title'] ?? 'Şehirler' );
        $limit     = absint( $instance['limit'] ?? 10 );
        $cache_key = 'firma_widget_sehirler_' . $limit;

        $html = get_transient( $cache_key );

        if ( false === $html ) {
            $sehirler = get_terms( [
                'taxonomy'   => 'firma-sehir',
                'hide_empty' => true,
                'orderby'    => 'count',
                'order'      => 'DESC',
                'number'     => $limit,
            ] );

            if ( is_wp_error( $sehirler ) || empty( $sehirler ) ) {
                set_transient( $cache_key, '', 5 * MINUTE_IN_SECONDS );
                return;
            }

            ob_start();
            echo '<div class="d-flex flex-wrap gap-2 g-3 p-3 pt-0">';
            foreach ( $sehirler as $sehir ) {
                $link = get_term_link( $sehir );
                echo '<a href="' . esc_url( $link ) . '" class="btn btn-primary fw-normal small py-1 px-2">';
                echo esc_html( $sehir->name );
                echo ' <span class="opacity-50">' . intval( $sehir->count ) . '</span>';
                echo '</a>';
            }
            echo '</div>';
            $html = ob_get_clean();

            set_transient( $cache_key, $html, 5 * MINUTE_IN_SECONDS );
        }

        if ( empty( $html ) ) return;

        echo $args['before_widget'];
        if ( $title ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        echo $html;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? 'Şehirler';
        $limit = $instance['limit'] ?? 10;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Gösterilecek şehir sayısı:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>"
                   name="<?php echo $this->get_field_name('limit'); ?>"
                   type="number" value="<?php echo esc_attr( $limit ); ?>" min="1" max="81">
        </p>
        <?php
    }

    public function update( $new, $old ) {
        $limit = absint( $new['limit'] ?? 10 );
        delete_transient( 'firma_widget_sehirler_' . absint( $old['limit'] ?? 10 ) );
        delete_transient( 'firma_widget_sehirler_' . $limit );
        return [
            'title' => sanitize_text_field( $new['title'] ?? '' ),
            'limit' => $limit,
        ];
    }
}

/* ======================================================================
 * 3. Benzer Firmalar Widget
 * ====================================================================== */

class Firma_Benzer_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'firma_benzer_widget',
            'Mevzu² — Benzer Firmalar',
            [ 'description' => 'Firma single sayfasında aynı kategorideki firmaları listeler.' ]
        );
    }

    public function widget( $args, $instance ) {
        if ( ! is_singular( 'firma' ) ) return;

        $title   = apply_filters( 'widget_title', $instance['title'] ?? 'Benzer Firmalar' );
        $limit   = absint( $instance['limit'] ?? 4 );
        $post_id = get_the_ID();
        $cats    = get_the_terms( $post_id, 'firma-kategori' );
        $cat_ids = ( $cats && ! is_wp_error( $cats ) ) ? wp_list_pluck( $cats, 'term_id' ) : [];

        $cache_key  = 'firma_widget_benzer_' . $post_id . '_' . $limit;
        $benzer_ids = get_transient( $cache_key );
        delete_transient($cache_key);

        if ( false === $benzer_ids ) {
            $q = new WP_Query( [
                'post_type'      => 'firma',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'post__not_in'   => [ $post_id ],
                'fields'         => 'ids',
                'tax_query'      => $cat_ids ? [ [
                    'taxonomy' => 'firma-kategori',
                    'field'    => 'term_id',
                    'terms'    => $cat_ids,
                ] ] : [],
            ] );
            $benzer_ids = $q->posts;
            set_transient( $cache_key, $benzer_ids, 5 * MINUTE_IN_SECONDS );
        }

        if ( empty( $benzer_ids ) ) return;

        $benzer = new WP_Query( [
            'post_type'      => 'firma',
            'post_status'    => 'publish',
            'post__in'       => $benzer_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => count( $benzer_ids ),
        ] );

        if ( ! $benzer->have_posts() ) return;

        echo $args['before_widget'];
        if ( $title ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        while ( $benzer->have_posts() ) {
            $benzer->the_post();
            echo '<a href="' . esc_url( get_permalink() ) . '" class="text-link">';
                echo '<div class="row w-100 mx-0 mb-3 align-items-center">';
                    if ( has_post_thumbnail() ) {
                        echo '<div class="col-4 col-md-2 col-lg-3 position-relative">';
                        the_post_thumbnail( 'gorsel-thumbnail-widget', [ 'loading' => 'lazy' ] );
                        echo '</div>';
                    }
                    echo '<div class="col-8 col-md-10 col-lg-7 ps-0"><h3 class="satir-2 fw-normal">' . esc_html( get_the_title() ) . '</h3></div>';
                echo '</div>';
            echo '</a>';
        }
        wp_reset_postdata();

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? 'Benzer Firmalar';
        $limit = $instance['limit'] ?? 4;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Gösterilecek firma sayısı:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>"
                   name="<?php echo $this->get_field_name('limit'); ?>"
                   type="number" value="<?php echo esc_attr( $limit ); ?>" min="1" max="10">
        </p>
        <?php
    }

    public function update( $new, $old ) {
        return [
            'title' => sanitize_text_field( $new['title'] ?? '' ),
            'limit' => absint( $new['limit'] ?? 4 ),
        ];
    }
}

/* ======================================================================
 * Sidebar Kaydı
 * ====================================================================== */

add_action( 'widgets_init', function () {
    // Widget'ları kaydet
    register_widget( 'Firma_Kategoriler_Widget' );
    register_widget( 'Firma_Sehirler_Widget' );
    register_widget( 'Firma_Benzer_Widget' );

    // Firma sidebar'ını kaydet
    register_sidebar( [
        'id'            => 'firma-sidebar',
        'name'          => 'Mevzu² — Firma Sayfası Sidebar',
        'description'   => 'Firma Rehberi single sayfasının sağ sütununda görünür.',
        'before_widget' => '<section class="widget">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2>',
        'after_title'   => '</h2>',
    ] );
} );

/* ======================================================================
 * İlk Aktivasyonda Sidebar'ı Otomatik Doldur
 * ====================================================================== */

add_action( 'init', function () {
    if ( get_option( 'firma_sidebar_seeded' ) ) return;

    // Widget instance'larını oluştur
    $widgets = [
        'firma_kategoriler_widget' => [ 'title' => 'Kategoriler',     'show_count' => 1 ],
        'firma_benzer_widget'      => [ 'title' => 'Benzer Firmalar', 'limit'      => 4 ],
        'firma_sehirler_widget'    => [ 'title' => 'Şehirler',        'limit'      => 10 ],
    ];

    $sidebar_ids = [];

    foreach ( $widgets as $id_base => $settings ) {
        $opt_key   = 'widget_' . $id_base;
        $instances = get_option( $opt_key, [] );

        // Bir sonraki kullanılabilir numerik ID'yi bul
        $numeric_ids = array_filter( array_keys( $instances ), 'is_int' );
        $next_id     = $numeric_ids ? max( $numeric_ids ) + 1 : 2;

        $instances[ $next_id ]      = $settings;
        $instances['_multiwidget']  = 1;
        update_option( $opt_key, $instances );

        $sidebar_ids[] = $id_base . '-' . $next_id;
    }

    // Sidebar'a widget'ları ekle
    $sidebars_widgets                  = get_option( 'sidebars_widgets', [] );
    $sidebars_widgets['firma-sidebar'] = $sidebar_ids;
    update_option( 'sidebars_widgets', $sidebars_widgets );

    update_option( 'firma_sidebar_seeded', 1 );
}, 99 );
