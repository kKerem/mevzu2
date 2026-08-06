<?php
/**
 * Firma Rehberi — CPT ve Taxonomy Tanımları
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Firma'nın şu an gerçekten öne çıkarılmış olup olmadığını kontrol eder.
 * _firma_featured=1 + (tarih aralığı yoksa veya bugün aralık içindeyse)
 */
function firma_is_featured( $post_id ) {
    if ( ! get_post_meta( $post_id, '_firma_featured', true ) ) return false;
    $today = date('Y-m-d');
    $start = get_post_meta( $post_id, '_firma_featured_start', true );
    $end   = get_post_meta( $post_id, '_firma_featured_end',   true );
    if ( $start && $today < $start ) return false;
    if ( $end   && $today > $end   ) return false;
    return true;
}

class Firma_CPT {

    public function __construct() {
        add_action( 'init',                  [ $this, 'register_cpt' ] );
        add_action( 'init',                  [ $this, 'register_taxonomies' ] );
        add_action( 'init',                  [ $this, 'maybe_seed_cities' ] );
        add_action( 'init',                  [ $this, 'maybe_setup_pages' ], 30 );
        add_action( 'template_include',      [ $this, 'template_include' ] );
        add_filter( 'manage_firma_posts_columns',       [ $this, 'add_columns' ] );
        add_action( 'manage_firma_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'manage_edit-firma_sortable_columns', [ $this, 'sortable_columns' ] );
        add_action( 'dashboard_glance_items', [ $this, 'glance_items' ] );
        add_action( 'pre_get_posts',         [ $this, 'filter_archive_query' ] );
        add_filter( 'display_post_states',   [ $this, 'display_post_states' ], 10, 2 );
        add_action( 'admin_head-post.php',   [ $this, 'lock_page_editor' ] );
        add_action( 'post_updated',          [ $this, 'sync_firmalar_slug' ], 10, 3 );
    }

    /* ------------------------------------------------------------------ */
    /* CPT Kaydı                                                            */
    /* ------------------------------------------------------------------ */

    public function register_cpt() {
        register_post_type( 'firma', [
            'labels' => [
                'name'               => 'Firmalar',
                'singular_name'      => 'Firma',
                'add_new'            => 'Yeni Firma',
                'add_new_item'       => 'Yeni Firma Ekle',
                'edit_item'          => 'Firmayı Düzenle',
                'view_item'          => 'Firmayı Görüntüle',
                'search_items'       => 'Firma Ara',
                'not_found'          => 'Firma bulunamadı.',
                'not_found_in_trash' => 'Çöp kutusunda firma yok.',
                'all_items'          => 'Tüm Firmalar',
                'menu_name'          => 'Firma Rehberi',
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'firmalar', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => 'firmalar',
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-store',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'comments' ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /* Taxonomy Kaydı                                                       */
    /* ------------------------------------------------------------------ */

    public function register_taxonomies() {
        // Firma Kategorisi — hiyerarşik
        register_taxonomy( 'firma-kategori', 'firma', [
            'labels' => [
                'name'          => 'Firma Kategorileri',
                'singular_name' => 'Kategori',
                'add_new_item'  => 'Yeni Kategori Ekle',
                'edit_item'     => 'Kategoriyi Düzenle',
                'search_items'  => 'Kategori Ara',
                'all_items'     => 'Tüm Kategoriler',
                'parent_item'   => 'Üst Kategori',
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => false,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'firma-kategori' ],
        ] );

        // Şehir/İlçe — düz liste
        register_taxonomy( 'firma-sehir', 'firma', [
            'labels' => [
                'name'          => 'Şehir / İlçe',
                'singular_name' => 'Şehir',
                'add_new_item'  => 'Yeni Şehir Ekle',
                'edit_item'     => 'Şehri Düzenle',
                'search_items'  => 'Şehir Ara',
                'all_items'     => 'Tüm Şehirler',
            ],
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => false,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'firma-sehir' ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /* Sayfa Kurulumu — Firmalar + Firma Başvurusu otomatik oluştur        */
    /* ------------------------------------------------------------------ */

    public function maybe_setup_pages() {
        $this->setup_basvuru_page();
        $this->setup_firmalar_page();
    }

    private function page_is_valid( $page_id, $shortcode = '' ) {
        if ( ! $page_id ) return false;
        if ( get_post_status( $page_id ) !== 'publish' ) return false;
        if ( $shortcode ) {
            $content = get_post_field( 'post_content', $page_id );
            if ( strpos( $content, $shortcode ) === false ) return false;
        }
        return true;
    }

    private function setup_basvuru_page() {
        $settings = get_option( Firma_Admin::OPT, [] );
        $page_id  = (int) ( $settings['basvuru_sayfasi'] ?? 0 );

        if ( $this->page_is_valid( $page_id, '[firma_basvuru]' ) ) return;

        // DB'de shortcode içeren mevcut sayfa var mı?
        global $wpdb;
        $found = (int) $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type='page' AND post_status='publish'
             AND post_content LIKE '%[firma_basvuru]%' LIMIT 1"
        );
        if ( $found ) {
            $settings['basvuru_sayfasi'] = $found;
            update_option( Firma_Admin::OPT, $settings );
            return;
        }

        // Yoksa oluştur
        $new_id = wp_insert_post( [
            'post_title'   => 'Firma Başvurusu',
            'post_name'    => 'firma-ekle',
            'post_content' => '[firma_basvuru]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
            'meta_input'   => [ '_firma_page_type' => 'basvuru' ],
        ] );
        if ( $new_id && ! is_wp_error( $new_id ) ) {
            $settings['basvuru_sayfasi'] = $new_id;
            update_option( Firma_Admin::OPT, $settings );
        }
    }

    private function setup_firmalar_page() {
        $saved_id = (int) get_option( 'firma_rehberi_firmalar_page_id', 0 );

        if ( $this->page_is_valid( $saved_id ) ) {
            $this->maybe_add_to_menu( $saved_id );
            return;
        }

        // DB'de _firma_page_type = 'firmalar' olan sayfa var mı?
        global $wpdb;
        $found = (int) $wpdb->get_var(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key='_firma_page_type' AND meta_value='firmalar' LIMIT 1"
        );
        if ( $found && get_post_status( $found ) === 'publish' ) {
            update_option( 'firma_rehberi_firmalar_page_id', $found );
            $this->maybe_add_to_menu( $found );
            return;
        }

        // Yoksa oluştur
        $new_id = wp_insert_post( [
            'post_title'   => 'Firmalar',
            'post_name'    => 'firmalar',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
            'meta_input'   => [ '_firma_page_type' => 'firmalar' ],
        ] );
        if ( $new_id && ! is_wp_error( $new_id ) ) {
            update_option( 'firma_rehberi_firmalar_page_id', $new_id );
            flush_rewrite_rules();
            $this->maybe_add_to_menu( $new_id );
        }
    }

    private function maybe_add_to_menu( $page_id ) {
        // Daha önce eklendiyse tekrar ekleme
        if ( get_option( 'firma_rehberi_menu_item_added' ) ) return;

        $locations = get_nav_menu_locations();
        $menu_id   = $locations['ust-menu'] ?? 0;
        if ( ! $menu_id ) return;

        $items = wp_get_nav_menu_items( $menu_id );
        if ( ! $items ) $items = [];

        foreach ( $items as $item ) {
            if ( (int) $item->object_id === $page_id ) {
                update_option( 'firma_rehberi_menu_item_added', 1 );
                return; // Zaten var
            }
        }

        wp_update_nav_menu_item( $menu_id, 0, [
            'menu-item-title'     => 'Firmalar',
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ] );
        update_option( 'firma_rehberi_menu_item_added', 1 );
    }

    /* ------------------------------------------------------------------ */
    /* Post State — Pages listesinde etiket göster                         */
    /* ------------------------------------------------------------------ */

    public function display_post_states( $states, $post ) {
        if ( $post->post_type !== 'page' ) return $states;

        $type = get_post_meta( $post->ID, '_firma_page_type', true );
        if ( $type === 'firmalar' ) {
            $states[] = 'Firmalar Sayfası';
        } elseif ( $type === 'basvuru' ) {
            $states[] = 'Firma Başvurusu Sayfası';
        } else {
            // Eski yöntemle oluşturulanlar için shortcode kontrolü
            $settings = get_option( Firma_Admin::OPT, [] );
            if ( (int) ( $settings['basvuru_sayfasi'] ?? 0 ) === $post->ID ) {
                $states[] = 'Firma Başvurusu Sayfası';
            }
        }
        return $states;
    }

    /* ------------------------------------------------------------------ */
    /* Editor Kilidi — Firmalar sayfasında içerik düzenleme devre dışı     */
    /* ------------------------------------------------------------------ */

    public function lock_page_editor() {
        global $post;
        if ( ! $post ) return;
        $type = get_post_meta( $post->ID, '_firma_page_type', true );
        if ( $type !== 'firmalar' ) return;
        echo '<style>
            #postdivrich, #postdiv, .wp-editor-wrap,
            #wp-content-editor-tools, #content-html, #content-tmce { display:none!important; }
        </style>
        <div class="notice notice-info inline" style="margin:10px 0">
            <p><strong>Firmalar Sayfası:</strong> İçerik alanı bu sayfa için kullanılmıyor.
            Slug değişikliği kaydedildiğinde firma listesi URL\'si de otomatik güncellenir.</p>
        </div>';
    }

    /* ------------------------------------------------------------------ */
    /* Slug Sync — Firmalar sayfasının slug'ı değişince CPT'yi güncelle    */
    /* ------------------------------------------------------------------ */

    public function sync_firmalar_slug( $post_id, $post_after, $post_before ) {
        if ( $post_after->post_type !== 'page' ) return;
        $type = get_post_meta( $post_id, '_firma_page_type', true );
        if ( $type !== 'firmalar' ) return;
        if ( $post_after->post_name === $post_before->post_name ) return;
        // Slug değişti → rewrite rules'u flush et (CPT slug'ı has_archive ile yönetiliyor)
        flush_rewrite_rules();
    }

    /* ------------------------------------------------------------------ */
    /* Türkiye İl Listesi — tek seferlik seed                              */
    /* ------------------------------------------------------------------ */

    public function maybe_seed_cities() {
        if ( get_option( 'firma_rehberi_cities_seeded' ) ) return;

        $iller = [
            'Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara','Antalya',
            'Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman','Bayburt','Bilecik',
            'Bingöl','Bitlis','Bolu','Burdur','Bursa','Çanakkale','Çankırı','Çorum',
            'Denizli','Diyarbakır','Düzce','Edirne','Elâzığ','Erzincan','Erzurum','Eskişehir',
            'Gaziantep','Giresun','Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul',
            'İzmir','Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri','Kilis',
            'Kırıkkale','Kırklareli','Kırşehir','Kocaeli','Konya','Kütahya','Malatya','Manisa',
            'Mardin','Mersin','Muğla','Muş','Nevşehir','Niğde','Ordu','Osmaniye','Rize',
            'Sakarya','Samsun','Şanlıurfa','Siirt','Sinop','Şırnak','Sivas','Tekirdağ',
            'Tokat','Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak',
        ];

        foreach ( $iller as $il ) {
            if ( ! term_exists( $il, 'firma-sehir' ) ) {
                wp_insert_term( $il, 'firma-sehir' );
            }
        }

        // Örnek kategoriler
        $kategoriler = [
            'Yeme & İçme'   => [ 'Restoran', 'Kafe', 'Fast Food', 'Pastane' ],
            'Sağlık'         => [ 'Eczane', 'Doktor', 'Hastane', 'Diş Kliniği' ],
            'Alışveriş'      => [ 'Market', 'Giyim', 'Teknoloji', 'Kitapçı' ],
            'Hizmetler'      => [ 'Kuaför', 'Güzellik Salonu', 'Kuru Temizleme', 'Tamirhane' ],
            'Otomotiv'       => [ 'Oto Yıkama', 'Lastikçi', 'Oto Servis', 'Oto Galeri' ],
            'Konaklama'      => [ 'Otel', 'Pansiyon', 'Apart' ],
            'Eğitim'         => [ 'Dershane', 'Kurs Merkezi', 'Okul Öncesi' ],
            'Finans & Hukuk' => [ 'Muhasebe', 'Hukuk Bürosu', 'Sigorta' ],
        ];

        foreach ( $kategoriler as $ust => $altlar ) {
            $parent = wp_insert_term( $ust, 'firma-kategori' );
            if ( ! is_wp_error( $parent ) ) {
                $parent_id = $parent['term_id'];
                foreach ( $altlar as $alt ) {
                    if ( ! term_exists( $alt, 'firma-kategori' ) ) {
                        wp_insert_term( $alt, 'firma-kategori', [ 'parent' => $parent_id ] );
                    }
                }
            }
        }

        update_option( 'firma_rehberi_cities_seeded', 1 );
    }

    /* ------------------------------------------------------------------ */
    /* Template Yönlendirme                                                 */
    /* ------------------------------------------------------------------ */

    public function template_include( $template ) {
        if ( is_singular( 'firma' ) ) {
            $t = FIRMA_REHBERI_PATH . 'templates/single-firma.php';
            if ( file_exists( $t ) ) return $t;
        }
        if ( is_post_type_archive( 'firma' ) || is_tax( 'firma-kategori' ) || is_tax( 'firma-sehir' ) ) {
            $t = FIRMA_REHBERI_PATH . 'templates/archive-firma.php';
            if ( file_exists( $t ) ) return $t;
        }
        return $template;
    }

    /* ------------------------------------------------------------------ */
    /* Admin Liste Kolonları                                                */
    /* ------------------------------------------------------------------ */

    public function add_columns( $cols ) {
        $new = [];
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( $k === 'title' ) {
                $new['firma_kategori'] = 'Kategori';
                $new['firma_sehir']    = 'Şehir';
                $new['firma_telefon']  = 'Telefon';
                $new['firma_featured'] = '⭐';
            }
        }
        unset( $new['date'] );
        $new['date'] = 'Tarih';
        return $new;
    }

    public function render_column( $col, $post_id ) {
        switch ( $col ) {
            case 'firma_kategori':
                $terms = get_the_terms( $post_id, 'firma-kategori' );
                echo $terms && ! is_wp_error( $terms )
                    ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
                    : '—';
                break;
            case 'firma_sehir':
                $terms = get_the_terms( $post_id, 'firma-sehir' );
                echo $terms && ! is_wp_error( $terms )
                    ? esc_html( wp_list_pluck( $terms, 'name' )[0] )
                    : '—';
                break;
            case 'firma_telefon':
                $tel = get_post_meta( $post_id, '_firma_telefon', true );
                echo $tel ? '<a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $tel ) . '</a>' : '—';
                break;
            case 'firma_featured':
                echo get_post_meta( $post_id, '_firma_featured', true ) ? '⭐' : '';
                break;
        }
    }

    public function sortable_columns( $cols ) {
        $cols['firma_featured'] = 'firma_featured';
        return $cols;
    }

    /* ------------------------------------------------------------------ */
    /* Archive Filtre — URL param'larını main query'e ekle                  */
    /* ------------------------------------------------------------------ */

    public function filter_archive_query( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) return;
        if ( ! is_post_type_archive( 'firma' ) && ! is_tax( 'firma-kategori' ) && ! is_tax( 'firma-sehir' ) ) return;

        $kat   = sanitize_text_field( $_GET['firma_kat']   ?? '' );
        $sehir = sanitize_text_field( $_GET['firma_sehir'] ?? '' );

        if ( ! $kat && ! $sehir ) return;

        $tax_query = (array) $query->get( 'tax_query' );

        if ( $kat ) {
            $tax_query[] = [
                'taxonomy'         => 'firma-kategori',
                'field'            => 'slug',
                'terms'            => $kat,
                'include_children' => true,
            ];
        }
        if ( $sehir ) {
            $tax_query[] = [
                'taxonomy' => 'firma-sehir',
                'field'    => 'slug',
                'terms'    => $sehir,
            ];
        }
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $query->set( 'tax_query', $tax_query );
    }

    public function glance_items( $items ) {
        $count = wp_count_posts( 'firma' );
        $total = intval( $count->publish );
        $pend  = intval( $count->pending );
        $items[] = sprintf(
            '<a href="%s">%d Firma</a>',
            admin_url( 'edit.php?post_type=firma' ),
            $total
        );
        if ( $pend > 0 ) {
            $items[] = sprintf(
                '<a href="%s" style="color:#d63638">%d Onay Bekleyen</a>',
                admin_url( 'edit.php?post_type=firma&post_status=pending' ),
                $pend
            );
        }
        return $items;
    }
}
