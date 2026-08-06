<?php
/**
 * Mevzu² Görsel Düzenleyici
 * WordPress Customizer benzeri canlı önizleme sayfası
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Visual_Editor {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        // Frontend preview modunda admin bar gizle
        add_action('init', array($this, 'maybe_preview_mode'));
        add_filter('show_admin_bar', array($this, 'filter_admin_bar'));
        // Preview AJAX endpoints
        add_action('wp_ajax_mevzu_ve_preview_save', array($this, 'ajax_preview_save'));
        add_action('wp_ajax_mevzu_ve_preview_clear', array($this, 'ajax_preview_clear'));
    }

    public function add_menu_page() {
        add_submenu_page(
            'mevzu-ayarlar',
            'Mevzu² Stüdyo',
            'Mevzu² Stüdyo',
            'manage_options',
            'mevzu-studyo',
            array($this, 'render_page')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'mevzu-studyo') === false) return;

        wp_enqueue_style('mevzu-ve-css', MEVZU_SETTINGS_URL . 'assets/visual-editor.css', array(), MEVZU_THEME_VERSION);
        wp_enqueue_style('mevzu-ve-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_script('mevzu-ve-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_script('mevzu-ve-js', MEVZU_SETTINGS_URL . 'assets/visual-editor.js', array('jquery', 'wp-color-picker', 'mevzu-ve-select2-js'), MEVZU_THEME_VERSION, true);
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        // Kategori listesi JSON olarak aktar
        $settings = new Mevzu_Settings_Page();
        $cats = $settings->get_categories_array();

        wp_localize_script('mevzu-ve-js', 'mevzuVE', array(
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('mevzu_settings_nonce'),
            'previewUrl' => add_query_arg('mevzu_preview', '1', home_url('/')),
            'categories' => $cats,
        ));
    }

    /**
     * Frontend: ?mevzu_preview=1 ise admin bar gizle, x-frame-options ayarla
     * ve transient'ten preview değerlerini yükle
     */
    public function maybe_preview_mode() {
        if (!isset($_GET['mevzu_preview']) || $_GET['mevzu_preview'] !== '1') return;
        if (!current_user_can('manage_options')) return;

        remove_action('login_init', 'send_frame_options_header');
        remove_action('admin_init', 'send_frame_options_header');
        add_filter('x_frame_options', function() { return 'SAMEORIGIN'; });

        // Iframe içindeki header margin ayarını sıfırla
        add_action('wp_head', function() {
            echo '<style>:root { --mevzu-offcanvas-sticky: 0px !important; }</style>';
        });

        // Iframe içindeki sidebar alanlarına düzenleme butonu ekle
        add_action('wp_footer', function() {
            $sidebar_id = 'sidebar-anasayfa';
            if (is_single()) {
                if (in_category('kose-yazilari')) {
                    $sidebar_id = 'sidebar-koseyazilari';
                } else {
                    $sidebar_id = 'sidebar-single';
                }
            } elseif (is_archive() || is_category() || is_search() || is_author() || is_tag()) {
                $sidebar_id = 'sidebar-archive';
            }
            ?>
            <style>
                .mevzu-ve-sidebar-edit-btn {
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: #2271b1;
                    color: #fff !important;
                    height: 26px;
                    padding: 0 10px;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 11px;
                    font-weight: 600;
                    z-index: 9999;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.2s;
                    text-decoration: none !important;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                    transform: translateY(5px);
                }
                .mevzu-ve-sidebar-edit-btn svg { width: 12px; height: 12px; fill: currentColor; margin-right: 4px; }
                .mevzu-ve-sidebar-edit-btn:hover { background: #135e96; }
                .mevzu-ve-sidebar-container:hover .mevzu-ve-sidebar-edit-btn {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0);
                }
                /* position: relative; kaldirildi çünkü sticky-top özelliğini bozuyordu */
                .mevzu-ve-sidebar-container::after {
                    content: '';
                    position: absolute;
                    inset: 0;
                    border: 2px dashed #2271b1;
                    border-radius: 8px;
                    pointer-events: none;
                    z-index: 9998;
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.col-lg-4 .sticky-top, .col-lg-3 .sticky-top, .col-md-4 .sticky-top').forEach(function(sidebarContainer) {
                        sidebarContainer.classList.add('mevzu-ve-sidebar-container');
                        
                        var a = document.createElement('a');
                        var currentUrl = encodeURIComponent(window.location.href);
                        a.href = '<?php echo admin_url('customize.php'); ?>?url=' + currentUrl + '&autofocus[section]=sidebar-widgets-<?php echo $sidebar_id; ?>';
                        a.target = '_parent';
                        a.className = 'mevzu-ve-sidebar-edit-btn';
                        a.title = 'Sidebar\'ı Düzenle';
                        a.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.414 16L16.556 5.858l-1.414-1.414L5 14.586V16h1.414zm.829 2H3v-4.243L14.435 2.322a1 1 0 0 1 1.414 0l2.829 2.829a1 1 0 0 1 0 1.414L7.243 18zM3 20h18v2H3v-2z"/></svg> Kenar Çubuğunu Düzenle';
                        
                        sidebarContainer.appendChild(a);
                    });
                    
                    // Iframe içindeki linklerde mevzu_preview=1 parametresini koru
                    document.addEventListener('click', function(e) {
                        var a = e.target.closest('a');
                        if (a && a.href && a.href.indexOf(window.location.host) !== -1 && a.target !== '_parent' && a.target !== '_blank' && !a.classList.contains('mevzu-ve-sidebar-edit-btn')) {
                            try {
                                var url = new URL(a.href);
                                if (!url.searchParams.has('mevzu_preview')) {
                                    url.searchParams.set('mevzu_preview', '1');
                                    a.href = url.href;
                                }
                            } catch (err) {}
                        }
                    });
                });
            </script>
            <?php
        });

        // Transient'ten preview değerlerini yükle (canlı önizleme)
        $user_id = get_current_user_id();
        $preview_data = get_transient('mevzu_ve_preview_' . $user_id);
        if (is_array($preview_data) && !empty($preview_data)) {
            // Her preview değeri için geçici olarak option'ı override et
            foreach ($preview_data as $key => $value) {
                add_filter('pre_option_options_' . $key, function() use ($value) {
                    return $value;
                }, 999);
            }

            // site_rengi için theme_mod'u da override et (header.php öncelikli olarak theme_mod okur)
            if (isset($preview_data['site_rengi']) && !empty($preview_data['site_rengi'])) {
                $color = $preview_data['site_rengi'];
                add_filter('theme_mod_mevzu_primary_color', function() use ($color) {
                    return $color;
                }, 999);
            }
        }
    }

    /**
     * Preview modunda admin bar'ı gizle
     */
    public function filter_admin_bar($show) {
        if (isset($_GET['mevzu_preview']) && $_GET['mevzu_preview'] === '1') {
            return false;
        }
        return $show;
    }

    /**
     * AJAX: Canlı önizleme için geçici kayıt (transient)
     */
    public function ajax_preview_save() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetki yok');

        $data = isset($_POST['mevzu']) ? wp_unslash($_POST['mevzu']) : array();
        if (!is_array($data)) $data = array();

        $preview = array();
        foreach ($data as $key => $value) {
            $preview[sanitize_text_field($key)] = is_array($value) ? $value : sanitize_text_field($value);
        }

        $user_id = get_current_user_id();
        set_transient('mevzu_ve_preview_' . $user_id, $preview, 300); // 5 dakika

        wp_send_json_success();
    }

    /**
     * AJAX: Transient'i temizle
     */
    public function ajax_preview_clear() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetki yok');
        delete_transient('mevzu_ve_preview_' . get_current_user_id());
        wp_send_json_success();
    }

    /**
     * Ana sayfa render
     */
    public function render_page() {
        if (isset($_GET['preview_url']) && !empty($_GET['preview_url'])) {
            $base_url = esc_url_raw(urldecode($_GET['preview_url']));
        } else {
            $base_url = home_url('/');
        }
        $preview_url = add_query_arg('mevzu_preview', '1', $base_url);
        $settings = new Mevzu_Settings_Page();
        $cats = $settings->get_categories_array();
        $pages = $this->get_pages();
        $presets = mevzu_get_site_primary_color_presets();
        
        $latest_post = get_posts(array('numberposts' => 1));
        $latest_post_url = !empty($latest_post) ? get_permalink($latest_post[0]->ID) : home_url('/');
        
        $latest_cat = get_categories(array('number' => 1));
        $latest_cat_url = !empty($latest_cat) ? get_category_link($latest_cat[0]->term_id) : home_url('/');
        
        $latest_page = get_posts(array('post_type' => 'page', 'numberposts' => 1));
        $latest_page_url = !empty($latest_page) ? get_permalink($latest_page[0]->ID) : home_url('/');
        
        $context = 'anasayfa';
        if (isset($_GET['preview_url']) && !empty($_GET['preview_url'])) {
            $p_url = esc_url_raw(urldecode($_GET['preview_url']));
            $base = rtrim(explode('?', $p_url)[0], '/');
            $home = rtrim(home_url(), '/');
            if ($base !== $home) {
                $post_id = url_to_postid($p_url);
                if ($post_id) {
                    $context = get_post_type($post_id) === 'page' ? 'sayfa_ayarlari' : 'haber_sayfasi';
                } else {
                    $context = 'arsiv_sayfasi';
                }
            }
        }
        ?>
        <div class="mevzu-ve-wrap" data-initial-context="<?php echo esc_attr($context); ?>">
            <!-- Sol Panel -->
            <div class="mevzu-ve-sidebar">
                <div class="mevzu-ve-sidebar-header">
                    <?php 
                    $back_url = isset($_GET['preview_url']) && !empty($_GET['preview_url']) ? esc_url_raw(urldecode($_GET['preview_url'])) : admin_url('admin.php?page=mevzu-ayarlar');
                    $back_url = remove_query_arg('mevzu_preview', $back_url);
                    ?>
                    <a href="<?php echo esc_url($back_url); ?>" class="mevzu-ve-back px-0" title="Geri Dön">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>
                    <h2>Mevzu² Stüdyo</h2>

                    <div class="mevzu-ve-live-toggle">
                        <label class="mevzu-ve-switch" title="Canlı Önizleme">
                            <input type="checkbox" id="mevzu-ve-live-preview" checked>
                            <span class="mevzu-ve-switch-slider"></span>
                        </label>
                        <span class="mevzu-ve-live-label">Canlı Önizleme</span>
                    </div>
                </div>

                <div class="mevzu-ve-sidebar-body">
                    <form id="mevzu-ve-form" method="post">
                        <?php wp_nonce_field('mevzu_settings_nonce', 'mevzu_nonce'); ?>

                        <!-- GENEL AYARLAR -->
                        <?php $this->render_section('genel', 'Genel Ayarlar', 'dashicons-admin-settings', function() use ($presets, $cats, $pages) { ?>
                            <?php $this->ve_image_field('logo', 'Site Logosu'); ?>
                            <?php $this->ve_image_field('logo_dark', 'Site Logosu (Karanlık Mod)'); ?>
                            <?php $this->ve_image_field('logo_mobil', 'Mobil Logo'); ?>
                            <?php $this->ve_image_field('logo_mobil_dark', 'Mobil Logo (Karanlık Mod)'); ?>
                            <?php $this->ve_image_field('favicon', 'Favicon'); ?>
                            <?php $this->ve_color_field('site_rengi', 'Site Ana Rengi', $presets); ?>
                            <?php $this->ve_select_field('varsayilan_sehir', 'Varsayılan Şehir', $this->get_cities()); ?>
                            <?php $this->ve_select_field('kose_yazilari_kategorisi', 'Köşe Yazıları Kategorisi', array('' => '— Seçiniz —') + $cats); ?>
                            <?php $this->ve_select_field('video_kategorisi', 'Video Kategorisi', array('' => '— Seçiniz —') + $cats); ?>
                            <?php $this->ve_number_field('ilginizi_cekebilecek_haber_sayisi', 'İlginizi Çekebilecek Haber Sayısı'); ?>
                            <?php $this->ve_text_field('google_news', 'Google News URL'); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->ve_select_field('kunye_sayfasi', 'Künye Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('iletisim_sayfasi', 'İletişim Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('gizlilik_politikasi_sayfasi', 'Gizlilik Politikası Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->ve_select_field('akis_sayfasi', 'Akış Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('finans_sayfasi', 'Finans Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('havadurumu_sayfasi', 'Hava Durumu Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('namaz_vakitleri_sayfasi', 'Namaz Vakitleri Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('sondakika_sayfasi', 'Son Dakika Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('yazarlar_sayfasi', 'Yazarlar Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('yol_durumu_sayfasi', 'Yol Durumu Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                            <?php $this->ve_select_field('nobetci_eczaneler_sayfasi', 'Nöbetçi Eczaneler Sayfası', array('' => '— Seçiniz —') + $pages); ?>
                        <?php }); ?>

                        <!-- HEADER AYARLARI -->
                        <?php $this->render_section('header', 'Header Ayarları', 'dashicons-table-row-before', function() { ?>
                            <?php $this->ve_select_field('header_sablon', 'Header Şablonu', array(
                                'sablon1' => 'Şablon 1', 'sablon2' => 'Şablon 2', 'sablon3' => 'Şablon 3'
                            )); ?>
                            <?php $this->ve_icon_radio_field('header_menu', 'Menü Pozisyonu', array(
                                'me-auto'  => array('dashicons-align-left', 'Sol'),
                                'mx-auto'  => array('dashicons-align-center', 'Orta'),
                                'ms-auto'  => array('dashicons-align-right', 'Sağ'),
                            )); ?>
                            <?php $this->ve_checkbox_group('kur_secimi', 'Gösterilecek Kurlar', array(
                                'USD' => 'Dolar', 'EUR' => 'Euro', 'GBP' => 'Sterlin',
                                'GA' => 'Gram Altın', 'C' => 'Çeyrek Altın', 'ONS' => 'Ons Altın',
                            )); ?>
                            <?php $this->ve_toggle_group( 'son_dakika_goster', 'Son Dakika Göster', function () {
                                $this->ve_number_field( 'son_dakika_haber_sayisi', 'Son Dakika Haber Sayısı', '8' );
                            } ); ?>
                            <?php $this->ve_textarea_field('header_alan', 'Header HTML Alanı'); ?>
                        <?php }); ?>

                        <!-- ANASAYFA AYARLARI -->
                        <?php $this->render_section('anasayfa', 'Anasayfa Ayarları', 'dashicons-admin-home', function() use ($cats) { ?>
                            <?php
                            $yzm_ve = function_exists( 'mevzu_tts_get_yzm_setting_display' ) ? 'mevzu_tts_get_yzm_setting_display' : null;
                            $yzm_site_adi_desc = sprintf(
                                /* translators: 1: placeholder SITE_ADI, 2: current site name */
                                __( 'Başlangıç ve bitiş cümlelerinde %1$s yazarsanız, metin ve sesli okumada otomatik olarak sitenizin adıyla değiştirilir (örnek: %2$s).', 'mevzu2' ),
                                'SITE_ADI',
                                get_bloginfo( 'name' )
                            );
                            $this->ve_toggle_group( 'yapay_zeka_manseti_goster', 'Yapay Zeka Manşetini Göster', function () use ( $yzm_ve, $yzm_site_adi_desc ) {
                                $this->ve_text_field( 'yapay_zeka_manseti_baslik', 'Çubuk başlığı', $yzm_ve ? $yzm_ve( 'baslik' ) : 'Günün Manşetleri' );
                                echo '<p class="description mb-2">' . esc_html( $yzm_site_adi_desc ) . '</p>';
                                $this->ve_textarea_field( 'yapay_zeka_manseti_baslangic_cumlesi', 'Başlangıç cümlesi', $yzm_ve ? $yzm_ve( 'baslangic_cumlesi' ) : 'SITE_ADI Yapay zeka gündemine hoşgeldiniz. Bugünün öne çıkan haberleri şunlar' );
                                $this->ve_textarea_field( 'yapay_zeka_manseti_bitis_cumlesi', 'Bitiş cümlesi', $yzm_ve ? $yzm_ve( 'bitis_cumlesi' ) : 'Günün haberleri bu kadardı. SITE_ADI iyi günler diler.' );
                            } );
                            ?>

                            <?php $this->ve_toggle_group( 'ust_manset_yeni_goster', 'Üst Manşet', function () {
                                $this->ve_number_field( 'ust_manset_yeni_slider_sayisi', 'Haber Sayısı', '5' );
                            } ); ?>
                            <?php $this->ve_toggle_group( 'ust_manset_ust_manset_ayarlari', 'Sıcak Gündem', function () {
                                $this->ve_number_field( 'ust_manset_slider_sayisi', 'Haber Sayısı' );
                            } ); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <span class="fw-semibold mb-2 d-block">Ana Manşet Ayarları</span>
                            <?php $this->ve_number_field('manset_slider_sayisi', 'Haber Sayısı'); ?>
                            <div class="row justify-content-between mb-3">
                                <div class="col">
                                    <?php $this->ve_select_field('manset_slider_modeli', 'Ana Manşet Modeli', array(
                                        'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2'
                                    )); ?>
                                </div>
                                <div class="col">
                                    <?php $this->ve_select_field('manset_slider_renk', 'Slider Rengi', array(
                                        'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                    )); ?>
                                </div>
                            </div>
                            <?php $this->ve_toggle_group( 'manset_slider_basliklari', 'Slider Başlıkları', function () {
                                $this->ve_select_field( 'manset_baslik_boyutu', 'Başlık Boyutu', array(
                                    'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                    'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                ) );
                                $this->ve_select_field( 'manset_baslik_hizasi', 'Başlık Hizası', array(
                                    'text-center' => 'Ortala', 'text-start' => 'Sola Yasla', 'text-end' => 'Sağa Yasla',
                                ) );
                            } ); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->ve_select_field('yan_manset_tip', 'Yan Manşet Tipi', array(
                                'haftalik_gundem' => 'Haftalık Gündemi Göster',
                                'yan_manset'      => 'Yan Manşetleri Göster',
                            )); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->ve_toggle_group( 'alt_manset_alt_manseti_goster', 'Alt Manşet', function () {
                                $this->ve_number_field( 'alt_manset_slider_sayisi', 'Haber Sayısı' );
                                ?>
                                <div class="row justify-content-between mb-3">
                                    <div class="col">
                                        <?php $this->ve_select_field( 'alt_manset_slider_modeli', 'Alt Manşet Modeli', array(
                                            'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2',
                                        ) ); ?>
                                    </div>
                                    <div class="col">
                                        <?php $this->ve_select_field( 'alt_manset_slider_renk', 'Slider Rengi', array(
                                            'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                        ) ); ?>
                                    </div>
                                </div>
                                <?php
                                $this->ve_toggle_group( 'alt_manset_slider_basliklari', 'Slider Başlıkları', function () {
                                    $this->ve_select_field( 'alt_manset_baslik_boyutu', 'Başlık Boyutu', array(
                                        'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                        'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                    ) );
                                    $this->ve_select_field( 'alt_manset_baslik_hizasi', 'Başlık Hizası', array(
                                        'text-center' => 'Ortala', 'text-start' => 'Sola Yasla', 'text-end' => 'Sağa Yasla',
                                    ) );
                                } );
                            } ); ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->ve_toggle_field('sidebar_goster', 'Kenar Çubuğu Bileşenlerini Göster'); ?>
                            <?php $this->ve_toggle_field('yazar_kosesi_goster', 'Yazar Köşesi'); ?>
                            <?php $this->ve_toggle_field('video_haberleri_goster', 'Video Haberleri'); ?>
                            
                            <!-- 3 Bloklu Alan — toggle + kategori seçimleri -->
                            <?php $this->ve_toggle_group( 'bolum_uclu_goster', '3 Bloklu Alan', function () use ( $cats ) {
                                for ( $i = 1; $i <= 3; $i++ ) {
                                    $this->ve_select_field( 'bolum_uclu_kat_' . $i, $i . '. Blok Kategorisi', array( '' => '— Seçiniz —' ) + $cats );
                                }
                            } ); ?>

                            <?php $this->ve_toggle_group( 'anasayfa_son_haberler', 'Son Haberler', function () {
                                $this->ve_number_field( 'anasayfa_son_haberler_sayisi', 'Haber Sayısı', '9' );
                            } ); ?>
                            
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <h4 class="fw-bold fs-6 mb-2">ilan.gov.tr</h4>
                            <?php $this->ve_toggle_group( 'ilangovtr', 'ilan.gov.tr Alanını Göster', function () {
                                $this->ve_text_field( 'ilangovtr_embed', 'ilan.gov.tr Embed URL' );
                            } ); ?>

                            <!-- ANASAYFA ÜST BLOKLARI -->
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->render_ve_ana_kat_blocks($cats); ?>

                            <!-- ANASAYFA BLOKLARI -->
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <?php $this->render_ve_blocks($cats); ?>
                        <?php }, home_url('/')); ?>

                        <!-- HABER SAYFASI AYARLARI -->
                        <?php $this->render_section('haber_sayfasi', 'Haber Sayfası Ayarları', 'dashicons-media-document', function() use ($cats) { ?>
                            <div class="mevzu-ve-field">
                                <label>Haberler Şablonu</label>
                                <div class="mevzu-template-selector" style="display:flex;gap:10px;margin-top:5px;flex-direction:column">
                                    <?php 
                                    $sablon_val = mevzu_normalize_haber_sablon_option( (string) get_option( 'options_sablon', '2' ) );
                                    $preview = get_transient('mevzu_ve_preview_' . get_current_user_id());
                                    if ($preview && isset($preview['sablon'])) {
                                        $sablon_val = mevzu_normalize_haber_sablon_option( (string) $preview['sablon'] );
                                    }
                                    $news_templates = array(
                                        '1' => 'Şablon 1 — Klasik Liste',
                                        '2' => 'Şablon 2 — Modern Kart',
                                        'sade' => 'Sade — Sidebarsız',
                                    );
                                    foreach ($news_templates as $val => $label):
                                    ?>
                                        <label class="mevzu-template-card" style="border:1px solid #c3c4c7;border-radius:4px;padding:10px;cursor:pointer;background:#fff;position:relative;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="mevzu[sablon]" value="<?php echo esc_attr($val); ?>" <?php checked($sablon_val, $val); ?>>
                                                <span class="template-title fw-semibold"><?php echo esc_html($label); ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php $this->ve_checkbox_group('detaylar', 'Haber Detayları', array(
                                'tarih'    => 'Tarih',
                                'yorum'    => 'Yorum Sayısı',
                                'okunma'   => 'Okunma Sayısı',
                                'sure'     => 'Okuma Süresi',
                                'yazar'    => 'Yazar',
                                'like'     => 'Beğen Düğmesi',
                                'paylas'   => 'Paylaş Düğmesi',
                                'bookmark' => 'Kaydet Düğmesi',
                                'sesli_dinle' => 'Sesli Dinle',
                            )); ?>
                            
                            <?php $this->ve_checkbox_group('detaylar_koseyazisi', 'Köşe Yazıları Detayları', array(
                                'tarih'    => 'Tarih',
                                'yorum'    => 'Yorum Sayısı',
                                'okunma'   => 'Okunma Sayısı',
                                'sure'     => 'Okuma Süresi',
                                'yazar'    => 'Yazar',
                                'like'     => 'Beğen Düğmesi',
                                'paylas'   => 'Paylaş Düğmesi',
                                'bookmark' => 'Kaydet Düğmesi',
                                'sesli_dinle' => 'Sesli Dinle',
                            )); ?>
                            
                            <?php $this->ve_toggle_field('sonsuz_kaydirma', 'Haberlerde Sonsuz Kaydırma'); ?>
                            <?php $this->ve_toggle_field('haberlerde_etiket_gosterimi', 'Haberlerde Etiket Gösterimi'); ?>
                            
                            <?php $this->ve_toggle_field('bizi_takip_edin_bolumu', 'Bizi Takip Edin Bölümü'); ?>
                            <?php $this->ve_checkbox_group('gosterilecek_sosyal_medya_hesaplari', 'Gösterilecek Sosyal Medya', array(
                                'facebook'  => 'Facebook',
                                'twitter'   => 'Twitter',
                                'instagram' => 'Instagram',
                                'youtube'   => 'Youtube',
                                'whatsapp'  => 'WhatsApp',
                            )); ?>

                            <?php $this->ve_toggle_field('ramazan_saatleri', 'Ramazan Saatleri'); ?>
                            
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <h4 class="fw-bold fs-6 mb-2">Sayfa Yenileme</h4>
                            <?php $this->ve_toggle_group( 'yenileme', 'Otomatik Yenileme', function () {
                                $this->ve_number_field( 'yenileme_suresi', 'Yenileme Süresi (sn)' );
                            } ); ?>
                        <?php }, $latest_post_url); ?>
                        
                        <!-- ARŞİV SAYFASI AYARLARI -->
                        <?php $this->render_section('arsiv_sayfasi', 'Arşiv Sayfası Ayarları', 'dashicons-category', function() { ?>
                            <p class="description">Her kategori için farklı renk teması atamaları yapılabilir. Bunun için WordPress menüsünden <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=category')); ?>" target="_blank"><strong>Kategoriler</strong></a> bölümüne gidip istediğiniz kategoriyi düzenleyebilirsiniz.</p>
                            
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <h4 class="fw-bold fs-6 mb-2">Arşiv Manşeti</h4>
                            <?php $this->ve_toggle_group( 'archive_manset_goster', 'Arşiv Manşetini Göster', function () {
                                $this->ve_number_field( 'archive_manset_slider_sayisi', 'Haber Sayısı', '10' );
                                ?>
                                <div class="row justify-content-between mb-3">
                                    <div class="col">
                                        <?php $this->ve_select_field( 'archive_manset_slider_modeli', 'Manşet Modeli', array(
                                            'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2',
                                        ) ); ?>
                                    </div>
                                    <div class="col">
                                        <?php $this->ve_select_field( 'archive_manset_slider_renk', 'Slider Rengi', array(
                                            'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                        ) ); ?>
                                    </div>
                                </div>
                                <?php
                                $this->ve_toggle_group( 'archive_manset_slider_basliklari', 'Slider Başlıkları', function () {
                                    $this->ve_select_field( 'archive_manset_baslik_boyutu', 'Başlık Boyutu', array(
                                        'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                        'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                    ) );
                                    $this->ve_select_field( 'archive_manset_baslik_hizasi', 'Başlık Hizası', array(
                                        'text-center' => 'Ortala', 'text-start' => 'Sola Yasla', 'text-end' => 'Sağa Yasla',
                                    ) );
                                } );
                            } ); ?>
                        <?php }, $latest_cat_url); ?>

                        <!-- FOOTER AYARLARI -->
                        <?php $this->render_section('footer', 'Footer Ayarları', 'dashicons-table-row-after', function() { ?>
                            <?php
                            $menus = array('' => '-- Menü Seçin --');
                            $all_menus = wp_get_nav_menus();
                            $menu_names = array();
                            if ($all_menus) {
                                foreach ($all_menus as $m) {
                                    $menus[$m->term_id] = $m->name;
                                    $menu_names[$m->term_id] = $m->name;
                                }
                            }
                            ?>
                            <?php
                            $footer_menus_url = admin_url( 'nav-menus.php' );
                            $footer_menus_hint = sprintf(
                                __( 'Sütun başlıkları menü adından gelir. Adı değiştirmek için %s sayfasındaki «Menu Name» alanını düzenleyin.', 'mevzu2' ),
                                '<a href="' . esc_url( $footer_menus_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Görünüm → Menüler', 'mevzu2' ) . '</a>'
                            );
                            ?>
                            <p class="description"><?php echo wp_kses_post( $footer_menus_hint ); ?></p>
                            <?php for ($i = 1; $i <= 4; $i++):
                                $saved_menu = get_option('options_footer_menu_' . $i, '');
                                $menu_name = '';
                                if ($saved_menu && isset($menu_names[intval($saved_menu)])) {
                                    $menu_name = $menu_names[intval($saved_menu)];
                                }
                            ?>
                                <div class="mevzu-ve-footer-col">
                                    <div class="mevzu-ve-field-group-header"><?php echo $i; ?>. Sütun</div>
                                    <?php $this->ve_select_field('footer_menu_' . $i, 'Menü', $menus); ?>
                                    <div class="mevzu-ve-field mevzu-ve-menu-name-display">
                                        <label>Menü Adı (önizleme)</label>
                                        <input type="text" value="<?php echo esc_attr($menu_name); ?>" readonly class="mevzu-ve-readonly" data-menu-name-for="mevzu[footer_menu_<?php echo $i; ?>]">
                                    </div>
                                </div>
                            <?php endfor; ?>
                            <hr style="border-color:#dcdcde;margin:12px 0">
                            <?php $this->ve_textarea_field('footer_text', 'Footer Metin'); ?>
                            <?php $this->ve_number_field('footer_unvan', 'Kuruluş Yılı'); ?>
                            <?php $this->ve_textarea_field('footer_alan', 'Footer HTML Alanı'); ?>
                        <?php }); ?>

                        <!-- SİDEBAR BİLEŞENLERİ -->
                        <?php $this->render_section('sidebar', 'Kenar Çubuğu Bileşenleri', 'dashicons-screenoptions', function() { ?>
                            <hr style="border-color:#dcdcde;margin:14px 0">
                            <p class="description" style="margin-bottom:10px">Kenar Çubuğu bileşenlerini ilgili sayfanın önizlemesi eşliğinde düzenleyebilirsiniz:</p>
                            
                            <?php 
                            $home_url = urlencode(set_url_scheme(home_url('/')));
                            $post = get_posts(array('numberposts' => 1));
                            $single_url = urlencode(set_url_scheme($post ? get_permalink($post[0]->ID) : home_url('/')));
                            $cats = get_categories(array('number' => 1));
                            $cat_url = urlencode(set_url_scheme($cats ? get_category_link($cats[0]->term_id) : home_url('/')));
                            $kose = get_posts(array('category_name' => 'kose-yazilari', 'numberposts' => 1));
                            $kose_url = urlencode(set_url_scheme($kose ? get_permalink($kose[0]->ID) : home_url('/')));
                            ?>
                            
                            <a href="<?php echo admin_url('customize.php?url=' . $home_url . '&autofocus[section]=sidebar-widgets-sidebar-anasayfa'); ?>" target="_blank" class="button button-small text-start d-block my-2">
                                <span class="dashicons dashicons-admin-home" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;margin-right:4px"></span>
                                Anasayfa Kenar Çubuğu
                            </a>
                            <a href="<?php echo admin_url('customize.php?url=' . $single_url . '&autofocus[section]=sidebar-widgets-sidebar-single'); ?>" target="_blank" class="button button-small text-start d-block my-2">
                                <span class="dashicons dashicons-media-document" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;margin-right:4px"></span>
                                Haber Sayfası Kenar Çubuğu
                            </a>
                            <a href="<?php echo admin_url('customize.php?url=' . $cat_url . '&autofocus[section]=sidebar-widgets-sidebar-archive'); ?>" target="_blank" class="button button-small text-start d-block my-2">
                                <span class="dashicons dashicons-category" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;margin-right:4px"></span>
                                Kategori (Arşiv) Kenar Çubuğu
                            </a>
                            <a href="<?php echo admin_url('customize.php?url=' . $kose_url . '&autofocus[section]=sidebar-widgets-sidebar-koseyazilari'); ?>" target="_blank" class="button button-small text-start d-block my-2">
                                <span class="dashicons dashicons-edit-large" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;margin-right:4px"></span>
                                Köşe Yazıları Kenar Çubuğu
                            </a>
                            
                            <a href="<?php echo admin_url('widgets.php'); ?>" target="_blank" class="button button-small button-link" style="width:100%;text-align:center">
                                Tüm Bileşenleri Klasik Yönet
                            </a>
                        <?php }); ?>
                    </form>
                </div>

                <div class="mevzu-ve-sidebar-footer">
                    <button type="button" class="mevzu-ve-save">Kaydet</button>
                    <span class="mevzu-ve-status"></span>

                    <div class="mevzu-ve-toolbar">
                        <button type="button" class="mevzu-ve-mode active" data-mode="desktop" title="Masaüstü">
                            <span class="dashicons dashicons-desktop" style="font-size:16px;width:16px;height:16px"></span>
                        </button>
                        <button type="button" class="mevzu-ve-mode" data-mode="tablet" title="Tablet">
                            <span class="dashicons dashicons-tablet" style="font-size:16px;width:16px;height:16px"></span>
                        </button>
                        <button type="button" class="mevzu-ve-mode" data-mode="mobile" title="Mobil">
                            <span class="dashicons dashicons-smartphone" style="font-size:16px;width:16px;height:16px"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Önizleme Alanı -->
            <div class="mevzu-ve-preview mode-desktop">
                <iframe src="<?php echo esc_url($preview_url); ?>"></iframe>
                <div class="mevzu-ve-preview-loading flex-column fw-normal small">
                    <i class="ri-loader-4-line donen-ikon fs-3 mt-2"></i>
                    Yükleniyor...
                    <div class="rounded-3 bg-secondary py-1 px-2 fs-4 mt-3">
                        <span class="fw-semibold"><b class="text-primary font-bolder">:</b>mevzu<b class="text-primary">²</b></span>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* WP admin chrome'u gizle */
            #wpadminbar, #adminmenumain, #wpfooter, .update-nag, .notice { display: none !important; }
            #wpcontent, #wpbody, #wpbody-content { margin: 0 !important; padding: 0 !important; }
            html.wp-toolbar { padding-top: 0 !important; }
        </style>
        <?php
    }

    /**
     * Accordion section wrapper
     */
    private function render_section($id, $title, $icon, $callback, $url = '') {
        ?>
        <div class="mevzu-ve-section" data-section="<?php echo esc_attr($id); ?>" <?php if($url) echo 'data-url="'.esc_url($url).'"'; ?>>
            <div class="mevzu-ve-section-header">
                <div class="row w-100">
                    <div class="col-2 pe-0"><span class="dashicons <?php echo esc_attr($icon); ?>"></span></div>
                    <div class="col ps-0"><?php echo esc_html($title); ?></div>
                </div>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="mevzu-ve-section-body">
                <?php $callback(); ?>
            </div>
        </div>
        <?php
    }

    /* ── Field helpers ── */

    private function ve_text_field($key, $label, $empty_default = '') {
        $value = get_option('options_' . $key, '');
        if ( trim( (string) $value ) === '' && $empty_default !== '' ) {
            $value = $empty_default;
        }
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'    => 'text',
            'id'      => Mevzu_Admin_Fields::field_id( $key, 've' ),
            'name'    => 'mevzu[' . $key . ']',
            'label'   => $label,
            'value'   => $value,
            'context' => 've',
        ) );
    }

    private function ve_number_field($key, $label, $default = '') {
        $value = get_option('options_' . $key, $default);
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'    => 'number',
            'id'      => Mevzu_Admin_Fields::field_id( $key, 've' ),
            'name'    => 'mevzu[' . $key . ']',
            'label'   => $label,
            'value'   => $value,
            'context' => 've',
            'attrs'   => 'min="1" max="50"',
        ) );
    }

    private function ve_select_field($key, $label, $options) {
        Mevzu_Admin_Fields::render_floating_select( array(
            'id'           => Mevzu_Admin_Fields::field_id( $key, 've' ),
            'name'         => 'mevzu[' . $key . ']',
            'label'        => $label,
            'value'        => get_option( 'options_' . $key, '' ),
            'options'      => $options,
            'select_class' => 'form-select mevzu-ve-select2',
            'context'      => 've',
        ) );
    }

    private function ve_toggle_field($key, $label) {
        $value = get_option('options_' . $key, '1');
        ?>
        <div class="mevzu-ve-field mevzu-ve-field-toggle">
            <label class="mevzu-ve-toggle-row">
                <span><?php echo esc_html($label); ?></span>
                <span class="mevzu-ve-switch">
                    <input type="hidden" name="mevzu[<?php echo esc_attr($key); ?>]" value="0">
                    <input type="checkbox" name="mevzu[<?php echo esc_attr($key); ?>]" value="1" <?php checked($value, '1'); ?>>
                    <span class="mevzu-ve-switch-slider"></span>
                </span>
            </label>
        </div>
        <?php
    }

    /**
     * Toggle + koşullu alt alanları tek çerçevede gruplar (.mevzu-ve-toggle-group).
     *
     * @param string   $key   options_ anahtarı.
     * @param string   $label Toggle etiketi.
     * @param callable $body  Checkbox açıkken görünen alanlar.
     */
    private function ve_toggle_group( $key, $label, callable $body ) {
        ?>
        <div class="mevzu-ve-toggle-group">
            <?php $this->ve_toggle_field( $key, $label ); ?>
            <div class="mevzu-ve-conditional" data-depends="mevzu[<?php echo esc_attr( $key ); ?>]" data-value="1">
                <?php $body(); ?>
            </div>
        </div>
        <?php
    }

    private function ve_icon_radio_field($key, $label, $options) {
        $value = get_option('options_' . $key, '');
        if (!$value) $value = array_key_first($options);
        ?>
        <div class="mevzu-ve-field mevzu-ve-field-icon-radio">
            <label><?php echo esc_html($label); ?></label>
            <div class="mevzu-ve-icon-radios">
                <?php foreach ($options as $val => $opt): ?>
                    <label class="mevzu-ve-icon-radio-item <?php echo $value === $val ? 'active' : ''; ?>" title="<?php echo esc_attr($opt[1]); ?>">
                        <input type="radio" name="mevzu[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($val); ?>" <?php checked($value, $val); ?>>
                        <span class="dashicons <?php echo esc_attr($opt[0]); ?>"></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function ve_textarea_field($key, $label, $empty_default = '') {
        $value = get_option('options_' . $key, '');
        if ( trim( (string) $value ) === '' && $empty_default !== '' ) {
            $value = $empty_default;
        }
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'    => 'textarea',
            'id'      => Mevzu_Admin_Fields::field_id( $key, 've' ),
            'name'    => 'mevzu[' . $key . ']',
            'label'   => $label,
            'value'   => $value,
            'context' => 've',
            'rows'    => 3,
        ) );
    }

    private function ve_color_field($key, $label, $presets = array()) {
        $value = get_option('options_' . $key, '#e90808');
        $id    = Mevzu_Admin_Fields::field_id( $key, 've' );
        ?>
        <div class="mevzu-ve-field mevzu-ve-field-color mb-3">
            <div class="form-floating">
                <input type="text" id="<?php echo esc_attr( $id ); ?>" name="mevzu[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="form-control mevzu-ve-color-picker" placeholder="<?php echo esc_attr( Mevzu_Admin_Fields::FLOAT_PLACEHOLDER ); ?>">
                <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
            </div>
            <?php if (!empty($presets)): ?>
            <div class="mevzu-ve-color-presets">
                <?php foreach ($presets as $color): ?>
                    <span class="mevzu-ve-preset-color" data-color="<?php echo esc_attr($color); ?>" style="background-color:<?php echo esc_attr($color); ?>" title="<?php echo esc_attr($color); ?>"></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function ve_image_field($key, $label) {
        $value = get_option('options_' . $key, '');
        $image_url = '';
        if ($value && is_numeric($value)) {
            $image_url = wp_get_attachment_url($value);
        }
        ?>
        <div class="mevzu-ve-field mevzu-ve-field-image">
            <label><?php echo esc_html($label); ?></label>
            <div class="mevzu-ve-image-preview" style="margin-bottom:6px">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:100%;max-height:60px;border-radius:4px">
                <?php endif; ?>
            </div>
            <input type="hidden" name="mevzu[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" class="mevzu-ve-image-id">
            <button type="button" class="button button-small mevzu-ve-image-select">Görsel Seç</button>
            <button type="button" class="button button-small mevzu-ve-image-remove" <?php echo !$value ? 'style="display:none"' : ''; ?>>Kaldır</button>
        </div>
        <?php
    }

    private function ve_checkbox_group($key, $label, $options) {
        $value = get_option('options_' . $key, array());
        if (!is_array($value)) $value = array();
        ?>
        <div class="mevzu-ve-field mevzu-ve-field-checkbox-group">
            <label><?php echo esc_html($label); ?></label>
            <div class="mevzu-ve-checkboxes">
                <?php foreach ($options as $val => $text): ?>
                    <label class="mevzu-ve-checkbox-item">
                        <input type="checkbox" name="mevzu[<?php echo esc_attr($key); ?>][]" value="<?php echo esc_attr($val); ?>" <?php checked(in_array($val, $value)); ?>>
                        <?php echo esc_html($text); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function get_cities() {
        $settings = new Mevzu_Settings_Page();
        return $settings->get_cities_array();
    }

    private function get_pages() {
        $pages = array();
        $all_pages = get_pages(array('sort_column' => 'menu_order'));
        foreach ($all_pages as $page) {
            $pages[$page->ID] = $page->post_title;
        }
        return $pages;
    }

    /**
     * Anasayfa Üst Blokları — kompakt yönetim
     */
    private function render_ve_ana_kat_blocks($cats) {
        $count = intval(get_option('options_ana_kat_bloklar', 0));
        $bloklar = array();
        for ($i = 0; $i < $count; $i++) {
            $bloklar[] = array(
                'kategori'    => get_option('options_ana_kat_' . $i . '_kategori', ''),
                'sablon'      => get_option('options_ana_kat_' . $i . '_sablon', 'sablon2'),
                'baslik'      => get_option('options_ana_kat_' . $i . '_baslik', ''),
                'haber_sayisi'=> get_option('options_ana_kat_' . $i . '_haber_sayisi', '6'),
                'haberler_metni' => get_option('options_ana_kat_' . $i . '_haberler_metni', '0'),
            );
        }
        ?>
        <div class="mevzu-ve-block-manager" id="mevzu-ve-ana-kat">
            <div class="mevzu-ve-field-group-header" style="display:flex;align-items:center;justify-content:space-between">
                <span>Anasayfa Üst Blokları</span>
                <button type="button" class="button button-small mevzu-ve-add-ana-kat" title="Yeni Üst Blok Ekle" style="padding:0 6px;line-height:22px;min-height:22px">+</button>
            </div>
            <div class="mevzu-ve-block-list" id="mevzu-ve-ana-kat-list">
                <?php if (empty($bloklar)): ?>
                    <p class="description mevzu-ve-empty-msg" style="text-align:center;padding:8px 0">Henüz üst blok eklenmemiş.</p>
                <?php else: ?>
                    <?php foreach ($bloklar as $i => $blok):
                        $cat_name = isset($cats[$blok['kategori']]) ? $cats[$blok['kategori']] : 'Kategori #' . $blok['kategori'];
                        if (!$blok['kategori']) $cat_name = 'Yeni Blok';
                        $sablon_label = $blok['sablon'] === 'sablon1' ? 'Alt Kategorili' : 'Kategorili';
                    ?>
                    <div class="mevzu-ve-block-item" data-index="<?php echo $i; ?>">
                        <div class="mevzu-ve-block-item-header">
                            <span class="mevzu-ve-block-handle" title="Sürükle">⠿</span>
                            <span class="mevzu-ve-block-title"><?php echo esc_html($cat_name); ?></span>
                            <span class="mevzu-ve-block-badge"><?php echo esc_html($sablon_label); ?></span>
                            <button type="button" class="mevzu-ve-block-toggle" title="Düzenle">▾</button>
                            <button type="button" class="mevzu-ve-block-remove" title="Sil">✕</button>
                        </div>
                        <div class="mevzu-ve-block-item-body" style="display:none">
                            <?php $this->ve_select_field('ana_kat_' . $i . '_kategori', 'Kategori', array('' => '— Seçiniz —') + $cats); ?>
                            <?php $this->ve_select_field('ana_kat_' . $i . '_sablon', 'Şablon', array(
                                'sablon2' => 'Kategorili Şablon',
                                'sablon1' => 'Alt Kategorili Şablon',
                            )); ?>
                            <?php $this->ve_text_field('ana_kat_' . $i . '_baslik', 'Başlık'); ?>
                            <?php $this->ve_number_field('ana_kat_' . $i . '_haber_sayisi', 'Haber Sayısı'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="hidden" name="mevzu[ana_kat_bloklar]" class="mevzu-ve-ana-kat-count" value="<?php echo esc_attr($count); ?>">
            <template id="tmpl-mevzu-ve-ana-kat">
                <div class="mevzu-ve-block-item" data-index="__INDEX__">
                    <div class="mevzu-ve-block-item-header">
                        <span class="mevzu-ve-block-handle" title="Sürükle">⠿</span>
                        <span class="mevzu-ve-block-title">Yeni Blok</span>
                        <span class="mevzu-ve-block-badge">Kategorili</span>
                        <button type="button" class="mevzu-ve-block-toggle" title="Düzenle">▾</button>
                        <button type="button" class="mevzu-ve-block-remove" title="Sil">✕</button>
                    </div>
                    <div class="mevzu-ve-block-item-body" style="display:none">
                        <?php $this->ve_select_field('ana_kat___INDEX___kategori', 'Kategori', array('' => '— Seçiniz —') + $cats); ?>
                        <?php $this->ve_select_field('ana_kat___INDEX___sablon', 'Şablon', array(
                            'sablon2' => 'Kategorili Şablon',
                            'sablon1' => 'Alt Kategorili Şablon',
                        )); ?>
                        <?php $this->ve_text_field('ana_kat___INDEX___baslik', 'Başlık'); ?>
                        <?php $this->ve_number_field('ana_kat___INDEX___haber_sayisi', 'Haber Sayısı'); ?>
                    </div>
                </div>
            </template>
        </div>
        <?php
    }

    /**
     * Anasayfa Blokları — kompakt yönetim
     */
    private function render_ve_blocks($cats) {
        $blocks_count = intval(get_option('options_bloklar', 0));
        $blocks = array();
        for ($i = 0; $i < $blocks_count; $i++) {
            $blocks[] = array(
                'goruntuleme_sablonu' => get_option('options_bloklar_' . $i . '_goruntuleme_sablonu', 'sablon1'),
                'tekli_blok'         => get_option('options_bloklar_' . $i . '_tekli_blok', ''),
                'ikili_blok'         => get_option('options_bloklar_' . $i . '_ikili_blok', array()),
                'haber_sayisi'       => get_option('options_bloklar_' . $i . '_haber_sayisi', '3'),
            );
        }
        ?>
        <div class="mevzu-ve-block-manager" id="mevzu-ve-blocks">
            <div class="mevzu-ve-field-group-header" style="display:flex;align-items:center;justify-content:space-between">
                <span>Anasayfa Blokları</span>
                <button type="button" class="button button-small mevzu-ve-add-block" title="Yeni Blok Ekle" style="padding:0 6px;line-height:22px;min-height:22px">+</button>
            </div>
            <div class="mevzu-ve-block-list" id="mevzu-ve-blocks-list">
                <?php if (empty($blocks)): ?>
                    <p class="description mevzu-ve-empty-msg" style="text-align:center;padding:8px 0">Henüz blok eklenmemiş.</p>
                <?php else: ?>
                    <?php foreach ($blocks as $i => $block):
                        $cat_id = $block['tekli_blok'];
                        $cat_name = isset($cats[$cat_id]) ? $cats[$cat_id] : 'Kategori #' . $cat_id;
                        if (!$cat_id) $cat_name = 'Yeni Blok';
                        $sablon_labels = array(
                            'sablon1' => 'Şablon 1 (Grid)',
                            'sablon2' => 'Şablon 2',
                            'ikilisablon' => 'İkili Bölüm',
                            'resmiilanlar' => 'Resmi İlanlar',
                        );
                        $sablon_label = isset($sablon_labels[$block['goruntuleme_sablonu']]) ? $sablon_labels[$block['goruntuleme_sablonu']] : $block['goruntuleme_sablonu'];
                    ?>
                    <div class="mevzu-ve-block-item" data-index="<?php echo $i; ?>">
                        <div class="mevzu-ve-block-item-header">
                            <span class="mevzu-ve-block-handle" title="Sürükle">⠿</span>
                            <span class="mevzu-ve-block-title"><?php echo esc_html($cat_name); ?></span>
                            <span class="mevzu-ve-block-badge"><?php echo esc_html($sablon_label); ?></span>
                            <button type="button" class="mevzu-ve-block-toggle" title="Düzenle">▾</button>
                            <button type="button" class="mevzu-ve-block-remove" title="Sil">✕</button>
                        </div>
                        <div class="mevzu-ve-block-item-body" style="display:none">
                            <?php $this->ve_select_field('bloklar_' . $i . '_tekli_blok', 'Kategori', array('' => '— Seçiniz —') + $cats); ?>
                            <?php $this->ve_select_field('bloklar_' . $i . '_goruntuleme_sablonu', 'Şablon', array(
                                'sablon1' => 'Şablon 1 (Grid)',
                                'sablon2' => 'Şablon 2',
                                'ikilisablon' => 'İkili Bölüm',
                                'resmiilanlar' => 'Resmi İlanlar',
                            )); ?>
                            <?php $this->ve_number_field('bloklar_' . $i . '_haber_sayisi', 'Haber Sayısı'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="hidden" name="mevzu[bloklar]" class="mevzu-ve-blocks-count" value="<?php echo esc_attr($blocks_count); ?>">
            <template id="tmpl-mevzu-ve-blocks">
                <div class="mevzu-ve-block-item" data-index="__INDEX__">
                    <div class="mevzu-ve-block-item-header">
                        <span class="mevzu-ve-block-handle" title="Sürükle">⠿</span>
                        <span class="mevzu-ve-block-title">Yeni Blok</span>
                        <span class="mevzu-ve-block-badge">Şablon 1 (Grid)</span>
                        <button type="button" class="mevzu-ve-block-toggle" title="Düzenle">▾</button>
                        <button type="button" class="mevzu-ve-block-remove" title="Sil">✕</button>
                    </div>
                    <div class="mevzu-ve-block-item-body" style="display:none">
                        <?php $this->ve_select_field('bloklar___INDEX___tekli_blok', 'Kategori', array('' => '— Seçiniz —') + $cats); ?>
                        <?php $this->ve_select_field('bloklar___INDEX___goruntuleme_sablonu', 'Şablon', array(
                            'sablon1' => 'Şablon 1 (Grid)',
                            'sablon2' => 'Şablon 2',
                            'ikilisablon' => 'İkili Bölüm',
                            'resmiilanlar' => 'Resmi İlanlar',
                        )); ?>
                        <?php $this->ve_number_field('bloklar___INDEX___haber_sayisi', 'Haber Sayısı'); ?>
                    </div>
                </div>
            </template>
            <a href="<?php echo admin_url('admin.php?page=mevzu-ayarlar#bloklar'); ?>" target="_blank" class="mevzu-ve-full-editor-link">
                <span class="dashicons dashicons-external" style="font-size:12px;width:12px;height:12px;vertical-align:text-bottom"></span>
                Tam blok düzenleyicisi
            </a>
        </div>
        <?php
    }
}
