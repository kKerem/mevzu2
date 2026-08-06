<?php
/**
 * Mevzu² Ana Ayar Paneli
 * 
 * ACF 'mevzu-ayarlar' options page'inin yerini alır.
 * Tüm veriler wp_options tablosunda options_ prefix'iyle saklanır (geriye uyumlu).
 */

if (!defined('ABSPATH')) exit;

class Mevzu_Settings_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_menu', array($this, 'reorder_mevzu_submenu'), 9999);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_mevzu_save_settings', array($this, 'ajax_save'));
        add_action('wp_ajax_mevzu_save_blocks', array($this, 'ajax_save_blocks'));
        add_action('wp_ajax_mevzu_save_ana_kat_blocks', array($this, 'ajax_save_ana_kat_blocks'));
        add_action('wp_ajax_mevzu_check_update', array($this, 'ajax_check_update'));
        add_action('wp_ajax_mevzu_r2_test', array($this, 'ajax_r2_test'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('mevzu_theme_update_check_cron', array($this, 'run_theme_update_cron_check'));
        add_action('mevzu_theme_auto_apply_scheduled', array($this, 'run_scheduled_auto_theme_apply'), 10, 1);
        add_action('init', array($this, 'maybe_schedule_theme_update_cron'), 20);
        add_action('admin_init', array($this, 'maybe_poll_theme_update_while_in_admin'), 30);
        add_action('admin_notices', array($this, 'render_theme_update_admin_notice'));
    }
    
    public function add_menu_pages() {
        if (!current_user_can('administrator')) return;
        
        add_menu_page(
            'Mevzu² Ayarları',
            'Mevzu² Ayarları',
            'manage_options',
            'mevzu-ayarlar',
            array($this, 'render_page'),
            'dashicons-admin-generic',
            60
        );

        // İlk alt menü (ana sayfa) adını "Genel Ayarlar" yap
        add_submenu_page(
            'mevzu-ayarlar',
            'Genel Ayarlar',
            'Genel Ayarlar',
            'manage_options',
            'mevzu-ayarlar',
            array($this, 'render_page')
        );
        
        // Eklentiler alt sayfası
        add_submenu_page(
            'mevzu-ayarlar',
            'Tema Eklentileri',
            'Modüller',
            'manage_options',
            'mevzu-eklentiler',
            array($this, 'render_modules_page')
        );
    }

    /**
     * Mevzu² Ayarları alt menü sırası (sol panel).
     */
    public function reorder_mevzu_submenu() {
        global $submenu;
        $parent = 'mevzu-ayarlar';
        if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
            return;
        }
        $desired = array(
            'mevzu-ayarlar',
            'hiz-guvenlik',
            'mevzu-eklentiler',
            'mevzu-changelog',
            'mevzu-setup-wizard',
        );
        $by_slug = array();
        foreach ( $submenu[ $parent ] as $item ) {
            if ( isset( $item[2] ) ) {
                $by_slug[ $item[2] ] = $item;
            }
        }
        $new = array();
        foreach ( $desired as $slug ) {
            if ( isset( $by_slug[ $slug ] ) ) {
                $new[] = $by_slug[ $slug ];
                unset( $by_slug[ $slug ] );
            }
        }
        foreach ( $by_slug as $item ) {
            $new[] = $item;
        }
        $submenu[ $parent ] = $new;
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_mevzu-ayarlar' && $hook !== 'mevzu²-ayarlari_page_mevzu-eklentiler' && strpos($hook, 'mevzu-eklentiler') === false) return;
        
        wp_enqueue_media(); // Medya yükleyici için
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Select2
        $select2_source = mevzu_get_kaynak_source('select2_source', 'local');
        if ($select2_source === 'cdn') {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        } else {
            wp_enqueue_style('select2', get_template_directory_uri() . '/css/vendor/select2.min.css', array(), '4.1.0');
            wp_enqueue_script('select2', get_template_directory_uri() . '/js/vendor/select2.min.js', array('jquery'), '4.1.0', true);
        }

        if ( strpos( $hook, 'mevzu-eklentiler' ) !== false ) {
            wp_enqueue_style(
                'mevzu-remixicon-admin',
                get_template_directory_uri() . '/css/fonts/remixicon.css',
                array(),
                MEVZU_THEME_VERSION
            );
        }

        wp_enqueue_script('mevzu-settings', MEVZU_SETTINGS_URL . 'assets/settings.js', array('jquery', 'wp-color-picker', 'select2'), MEVZU_THEME_VERSION, true);
        wp_localize_script('mevzu-settings', 'mevzuSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mevzu_settings_nonce'),
            'strings' => array(
                'saved'    => 'Ayarlar kaydedildi!',
                'error'    => 'Bir hata oluştu.',
                'selectImage' => 'Görsel Seç',
                'useImage'    => 'Bu Görseli Kullan',
            )
        ));
    }
    
    public function register_settings() {
        // WordPress Settings API ile kayıt
    }
    
    /**
     * Helper: option değerini al (options_ prefix'li)
     */
    private function opt($key, $default = '') {
        return get_option('options_' . $key, $default);
    }
    
    /**
     * Ayarlar sayfasını render et
     */
    public function render_page() {
        $status = Mevzu_License::get_license_status();
        $license_key = Mevzu_License::get_license_key();
        
        $is_locked = false;
        if ($status['status'] === 'banned') {
            $is_locked = true;
        } elseif ($status['status'] === 'inactive' || empty($license_key)) {
            $is_locked = true;
        } elseif ($status['status'] !== 'active' && $status['status'] !== 'unchecked') {
            $is_locked = true;
        }
        ?>
        <div class="wrap mevzu-settings-wrap <?php echo $is_locked ? 'mevzu-settings-locked' : ''; ?>">
            <div class="row">
                <div class="col-12 col-md-12 mevzu-settings-main-col">
                    <div class="mevzu-settings-container">
                        <div class="mevzu-settings-tabs">
                            <?php if (!$is_locked): ?>
                            <a href="#" class="tab-link active" data-tab="genel">Genel Ayarlar</a>
                            <hr class="nav-group-divider my-0">
                            <a href="#" class="tab-link" data-tab="header">Header Ayarları</a>
                            <a href="#" class="tab-link" data-tab="bloklar">Anasayfa Ayarları</a>
                            <a href="#" class="tab-link" data-tab="footer">Footer Ayarları</a>
                            <hr class="nav-group-divider my-0">
                            <a href="#" class="tab-link" data-tab="icerik">İçerik Ayarları</a>
                            <a href="#" class="tab-link" data-tab="arsiv">Arşiv Ayarları</a>
                            <a href="#" class="tab-link" data-tab="sosyal">Sosyal Medya</a>
                            <a href="#" class="tab-link" data-tab="video-depolama">Video Depolama</a>
                            <a href="#" class="tab-link" data-tab="import-export">İçe/Dışa Aktar</a>
                            <?php endif; ?>
                            <?php if (!$is_locked): ?>
                            <hr class="nav-group-divider my-0">
                            <a href="<?php echo admin_url('admin.php?page=mevzu-studyo'); ?>" target="_blank" class="btn btn-outline-dark p-3 text-white text-decoration-none fw-normal" style="padding:.5rem 1rem !important"><span class="badge bg-primary py-1 px-2 me-2">YENI</span><span class="fz-14">Mevzu² Studio</span></a>
                            <hr class="nav-group-divider my-0">
                            <a href="#" class="tab-link" data-tab="hakkinda">Hakkında</a>
                            <?php endif; ?>
                            <a href="#" class="tab-link <?php echo $is_locked ? 'active' : ''; ?>" data-tab="lisans">Lisans & Güncelleme</a>
                        </div>
                        
                        <div class="mevzu-settings-content">
                            <form id="mevzu-settings-form" method="post">
                                <?php wp_nonce_field('mevzu_settings_nonce', 'mevzu_nonce'); ?>
                                
                                <?php if ($is_locked): ?>
                                    <div class="mevzu-notice mevzu-notice-error" style="background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-bottom:20px;border-left:4px solid #f5c6cb;">
                                        <strong>Bilgi:</strong> Lisansınız geçerli olmadığı için temanın ayar bölümleri kullanıma kapatılmıştır. Lütfen aktif bir lisans anahtarı girerek ayarları açın.
                                    </div>
                                <?php else: ?>
                                <!-- GENEL AYARLAR -->
                                <div class="tab-content active" id="tab-genel">
                                    <h2 class="mb-3 pb-3">Genel Ayarlar</h2>
                                    <p class="description mb-3">Logonun en fazla <span class="text-dark">48px</span> yüksekliğe sahip olması tavsiye edilir. Site Logosu (Karanlık Mod) aynı zamanda footerda da kullanılacaktır.</p>
                                    
                                    <div class="row gx-5">
                                        <div class="col-12 col-lg-auto"><?php $this->render_image_field('logo', 'Site Logosu', 'Ana logo görseli'); ?></div>
                                        <div class="col-12 col-lg-auto"><?php $this->render_image_field('logo_dark', 'Site Logosu (Karanlık Mod)', 'Karanlık modda kullanılacak ana logo'); ?></div>
                                    </div>
                                    <hr>

                                    <p class="description mb-3">Logonun en fazla <span class="text-dark">38px</span> yüksekliğe sahip olması tavsiye edilir.</p>
                                    <div class="row gx-5">
                                        <div class="col-12 col-lg-auto"><?php $this->render_image_field('logo_mobil', 'Mobil Logo', 'Mobil cihazlarda kullanılacak logo'); ?></div>
                                        <div class="col-12 col-lg-auto"><?php $this->render_image_field('logo_mobil_dark', 'Mobil Logo (Karanlık Mod)', 'Karanlık modda kullanılacak mobil logo'); ?></div>
                                    </div>


                                    <?php $this->render_image_field('favicon', 'Favicon', 'Tarayıcı sekmesinde görünen ikon'); ?>
                                    
                                    <?php $this->render_color_field('site_rengi', 'Site Ana Rengi', 'Sitenin ana vurgu rengini (Primary Color) belirler.'); ?>
                                    
                                    <?php $this->render_select_field('varsayilan_sehir', 'Varsayılan Şehir', $this->get_cities_array()); ?>
                                    
                                    <?php $this->render_select_field('kose_yazilari_kategorisi', 'Köşe Yazıları Kategorisi', $this->get_categories_array()); ?>
                                    <?php $this->render_select_field('video_kategorisi', 'Video Kategorisi', $this->get_categories_array()); ?>
                                    
                                    <?php $this->render_number_field('ilginizi_cekebilecek_haber_sayisi', 'İlginizi Çekebilecek Haber Sayısı'); ?>
                                    
                                    <?php $this->render_text_field('google_news', 'Google News URL', 'Google Haberler bağlantınız'); ?>
                                    
                                    <?php $this->render_select_field('kunye_sayfasi', 'Künye Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('iletisim_sayfasi', 'İletişim Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('gizlilik_politikasi_sayfasi', 'Gizlilik Politikası Sayfası', $this->get_pages_array()); ?>
                                    <hr>
                                    <?php $this->render_select_field('akis_sayfasi', 'Akış Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('finans_sayfasi', 'Finans Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('havadurumu_sayfasi', 'Hava Durumu Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('namaz_vakitleri_sayfasi', 'Namaz Vakitleri Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('sondakika_sayfasi', 'Son Dakika Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('yazarlar_sayfasi', 'Yazarlar Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('yol_durumu_sayfasi', 'Yol Durumu Sayfası', $this->get_pages_array()); ?>
                                    <?php $this->render_select_field('nobetci_eczaneler_sayfasi', 'Nöbetçi Eczaneler Sayfası', $this->get_pages_array()); ?>
                                </div>
                                
                                <!-- HEADER AYARLARI -->
                                <div class="tab-content" id="tab-header">
                                    <h2 class="mb-3 pb-3">Header Ayarları</h2>
                                    
                                    <div class="mevzu-field">
                                        <label><strong>Header Şablonu</strong></label>
                                        <div class="mevzu-template-selector">
                                            <?php 
                                            $header_val = $this->opt('header_sablon', 'sablon2');
                                            $header_templates = array(
                                                'sablon1' => 'Şablon 1 — Klasik Gazete',
                                                'sablon2' => 'Şablon 2 — Modern',
                            'sablon3' => 'Şablon 3 — Minimalist',
                                            );
                                            foreach ($header_templates as $val => $label):
                                            ?>
                                                <label class="mevzu-template-card">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <input type="radio" name="mevzu[header_sablon]" value="<?php echo esc_attr($val); ?>" <?php checked($header_val, $val); ?>>
                                                        <span class="template-title"><?php echo esc_html($label); ?></span>
                                                    </div>
                                                    
                                                    <?php if ($val === 'sablon1'): ?>
                                                    <div class="card-text d-flex gap-1">
                                                        <span class="placeholder col-8 bg-secondary"></span>
                                                        <span class="placeholder col ms-auto bg-primary"></span>
                                                    </div>
                                                    <div class="card-text d-flex gap-1 my-1" style="min-height: 1.4em;">
                                                        <span class="placeholder col-3 bg-primary"></span>
                                                        <span class="placeholder col-2 ms-auto bg-secondary"></span>
                                                        <span class="placeholder col-3"></span>
                                                    </div>
                                                    <div class="card-text d-flex gap-1 my-1">
                                                        <span class="placeholder col-1 bg-secondary"></span>
                                                        <span class="placeholder col"></span>
                                                        <span class="placeholder col-1 ms-auto bg-secondary"></span>
                                                        <span class="placeholder col-1 bg-secondary"></span>
                                                    </div>
                                                    <?php elseif ($val === 'sablon2'): ?>
                                                    <div class="card-text d-flex gap-1">
                                                        <span class="placeholder col-8 bg-secondary"></span>
                                                        <span class="placeholder col ms-auto bg-primary"></span>
                                                    </div>
                                                    <div class="card-text d-flex gap-1 my-1" style="min-height: 1.4em;">
                                                        <span class="placeholder col-1"></span>
                                                        <span class="placeholder col-3 bg-primary"></span>
                                                        <span class="placeholder col-2 ms-auto bg-secondary"></span>
                                                        <span class="placeholder col-2"></span>
                                                        <span class="placeholder col-1 bg-secondary"></span>
                                                    </div>
                                                    <div class="card-text d-flex gap-1 my-1">
                                                        <span class="placeholder col-1 bg-secondary"></span>
                                                        <span class="placeholder col"></span>
                                                        <span class="placeholder col-1 ms-auto bg-secondary"></span>
                                                        <span class="placeholder col-1 bg-secondary"></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <?php $this->render_select_field('header_menu', 'Header Menü Pozisyonu', array(
                                        'me-auto' => 'Sola Yasla',
                                        'mx-auto' => 'Ortala',
                                        'ms-auto' => 'Sağa Yasla',
                                    ), '', 'me-auto'); ?>
                                    
                                    <?php $this->render_checkbox_group('kur_secimi', 'Gösterilecek Kurlar', array(
                                        'USD' => 'Dolar',
                                        'EUR' => 'Euro',
                                        'GBP' => 'Sterlin',
                                        'GA' => 'Gram Altın',
                                        'C' => 'Çeyrek Altın',
                                        'ONS' => 'Ons Altın'
                                    )); ?>
                                    
                                    <?php $this->render_toggle_field( 'son_dakika_goster', 'Son Dakika Göster', '', '1' ); ?>
                                    <div id="sonDakikaDetail" class="row mt-2">
                                        <div class="col col-md-1">
                                            <?php $this->render_number_field( 'son_dakika_haber_sayisi', 'Son Dakika Haber Sayısı', '', array( 'min' => 1, 'max' => 30, 'default' => 8 ) ); ?>
                                        </div>
                                    </div>
                                    
                                    <?php $this->render_textarea_field('header_alan', 'Header HTML Alanı', 'Header\'a eklenecek özel HTML/script'); ?>
                                </div>
                                
                                <!-- İÇERİK AYARLARI -->
                                <div class="tab-content" id="tab-icerik">
                                    <h2 class="mb-3 pb-3">İçerik Ayarları</h2>
                                    
                                    <div class="mevzu-field">
                                        <label><strong>Haberler Şablonu</strong></label>
                                        <div class="mevzu-template-selector">
                                            <?php 
                                            $sablon_val = mevzu_normalize_haber_sablon_option( $this->opt( 'sablon', '2' ) );
                                            $news_templates = array(
                                                '1' => 'Şablon 1 — Klasik Liste',
                                                '2' => 'Şablon 2 — Modern Kart',
                                                'sade' => 'Sade — Sidebarsız',
                                            );
                                            foreach ($news_templates as $val => $label):
                                            ?>
                                                <label class="mevzu-template-card">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <input type="radio" name="mevzu[sablon]" value="<?php echo esc_attr($val); ?>" <?php checked($sablon_val, $val); ?>>
                                                        <span class="template-title"><?php echo esc_html($label); ?></span>
                                                    </div>
                                                    
                                                    <?php if ($val == '1'): ?>
                                                    <!-- Klasik Liste Görünümü -->
                                                    <div class="card-text gap-2">
                                                        <span class="placeholder placeholder-lg col-3 mb-2"></span>
                                                        <span class="placeholder placeholder-lg col-12 mb-2 bg-primary"></span>
                                                        <span class="placeholder col-12 bg-secondary" style="height: 100px;"></span>
                                                        <div class="card-text d-flex gap-1 my-2" style="min-height: 1.4em;">
                                                            <span class="placeholder placeholder-lg col-1 bg-primary rounded-circle" style="width:18.2px"></span>
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-1 ms-auto"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                        </div>
                                                        <div class="card-text d-flex gap-1 my-2 border-bottom pb-2" style="min-height: 1.4em;">
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-3 ms-auto"></span>
                                                        </div>
                                                        <span class="placeholder placeholder-lg col-9"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-11"></span>
                                                    </div>
                                                    <?php elseif ($val == '2'): ?>
                                                    <!-- Modern Kart Görünümü -->
                                                    <div class="card-text gap-2">
                                                        <span class="placeholder placeholder-lg col-3 mb-2"></span>
                                                        <span class="placeholder col-12 mb-2 bg-secondary" style="height: 100px;"></span>
                                                        <div class="card-text d-flex gap-1 mb-2" style="min-height: 1.4em;">
                                                            <span class="placeholder placeholder-lg col-1 bg-primary rounded-circle" style="width:18.2px"></span>
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-1 ms-auto"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                            <span class="placeholder placeholder-lg col-1"></span>
                                                        </div>
                                                        <div class="card-text d-flex gap-1 mb-2 border-bottom pb-2" style="min-height: 1.4em;">
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-2"></span>
                                                            <span class="placeholder placeholder-lg col-3 ms-auto"></span>
                                                        </div>
                                                        <span class="placeholder placeholder-lg col-12 mb-2 bg-primary"></span>
                                                        <span class="placeholder placeholder-lg col-9"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-11"></span>
                                                    </div>
                                                    <?php elseif ($val == 'sade'): ?>
                                                    <!-- Sade Görünümü -->
                                                    <div class="card-text gap-2">
                                                        <span class="placeholder placeholder-lg col-12 mb-2 bg-primary"></span>
                                                        <span class="placeholder placeholder-lg col-4 mb-2"></span>
                                                        <span class="placeholder col-12 bg-secondary" style="height: 72px;"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-12"></span>
                                                        <span class="placeholder col-10"></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <?php $this->render_checkbox_group('detaylar', 'Haber Detayları', array(
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
                                    
                                    <?php $this->render_checkbox_group('detaylar_koseyazisi', 'Köşe Yazıları Detayları', array(
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
                                    
                                    <?php $this->render_toggle_field('sonsuz_kaydirma', 'Haberlerde Sonsuz Kaydırma'); ?>
                                    <?php $this->render_toggle_field('haberlerde_etiket_gosterimi', 'Haberlerde Etiket Gösterimi'); ?>
                                    
                                    <?php $this->render_toggle_field('bizi_takip_edin_bolumu', 'Bizi Takip Edin Bölümü'); ?>
                                    <?php $this->render_checkbox_group('gosterilecek_sosyal_medya_hesaplari', 'Gösterilecek Sosyal Medya', array(
                                        'facebook'  => 'Facebook',
                                        'twitter'   => 'Twitter',
                                        'instagram' => 'Instagram',
                                        'youtube'   => 'Youtube',
                                        'whatsapp'  => 'WhatsApp',
                                    )); ?>
                                    

                                    <?php $this->render_toggle_field('ramazan_saatleri', 'Ramazan Saatleri'); ?>
                                    

                                    
                                    <h3>Sayfa Yenileme</h3>
                                    <?php $this->render_toggle_field('yenileme', 'Otomatik Yenileme'); ?>
                                    <?php $this->render_number_field('yenileme_suresi', 'Yenileme Süresi (saniye)'); ?>
                                    
                                    <h3>Finans sayfası</h3>
                                    <p class="description">Üst şeritte kayan döviz ve altın kurları <strong>Header Ayarları → Gösterilecek Kurlar</strong> listesinden seçilir. Finans sayfasının site içi adresi <strong>Genel Ayarlar → Finans Sayfası</strong> alanındadır; sayfa içeriği bu bölümdeki şablonla oluşturulur.</p>
                                </div>

                                <div class="tab-content" id="tab-arsiv">
                                    <h2 class="mb-3 pb-3">Arşiv Ayarları</h2>
                                    
                                    <div class="mevzu-field">
                                        <!-- ARŞİV ÜST MANŞET (kategori, etiket, yazar, tarih vb.) -->
                                        <h3>Arşiv manşeti</h3>
                                        <p class="description">Kategori, etiket ve diğer arşiv sayfalarında listenin üstünde gösterilen Swiper manşet. Ana sayfadaki ana manşet ile aynı görsel seçenekleri kullanılır; haber sayısı ayrıca belirlenir.</p>
                                        <?php $this->render_toggle_field( 'archive_manset_goster', 'Manşeti göster', '', '1' ); ?>
                                        <div id="archiveMansetDetail" class="row">
                                            <div class="col-auto">
                                                <?php $this->render_number_field( 'archive_manset_slider_sayisi', 'Manşet haber sayısı', '', array( 'min' => 1, 'max' => 50, 'default' => 15 ) ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'archive_manset_slider_modeli', 'Slider modeli', array(
                                                    'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2',
                                                ) ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'archive_manset_slider_renk', 'Slider rengi', array(
                                                    'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                                ) ); ?>
                                            </div>
                                            <div class="col-12">
                                                <?php $this->render_toggle_field( 'archive_manset_slider_basliklari', 'Slider başlıkları' ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'archive_manset_baslik_boyutu', 'Başlık boyutu', array(
                                                    'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                                    'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                                ) ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'archive_manset_baslik_hizasi', 'Başlık hizası', array(
                                                    'text-center' => 'Ortala', 'text-start' => 'Sola yasla', 'text-end' => 'Sağa yasla',
                                                ) ); ?>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                </div>
                                <!-- FOOTER AYARLARI -->
                                <div class="tab-content" id="tab-footer">
                                    <h2 class="mb-3 pb-3">Footer Ayarları</h2>
                                    
                                    <?php
                                    $footer_menus_url = admin_url( 'nav-menus.php' );
                                    $footer_menus_hint = sprintf(
                                        /* translators: %s: Görünüm → Menüler admin URL */
                                        __( 'Sütun başlıkları, seçtiğiniz menünün adından gelir. Menü adını (Menu Name) değiştirmek için %s sayfasını kullanın.', 'mevzu2' ),
                                        '<a href="' . esc_url( $footer_menus_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Görünüm → Menüler', 'mevzu2' ) . '</a>'
                                    );
                                    ?>
                                    <p class="description"><?php echo wp_kses_post( $footer_menus_hint ); ?></p>
                                    <div class="row gx-4">
                                        <div class="col-md">
                                            <h4 class="fw-bold mb-3">1. Sütun</h4>
                                            <?php $this->render_select_field('footer_menu_1', 'Menü', $this->get_menus_array()); ?>
                                        </div>
                                        <div class="col-md">
                                            <h4 class="fw-bold mb-3">2. Sütun</h4>
                                            <?php $this->render_select_field('footer_menu_2', 'Menü', $this->get_menus_array()); ?>
                                        </div>
                                        <div class="col-md">
                                            <h4 class="fw-bold mb-3">3. Sütun</h4>
                                            <?php $this->render_select_field('footer_menu_3', 'Menü', $this->get_menus_array()); ?>
                                        </div>
                                        <div class="col-md">
                                            <h4 class="fw-bold mb-3">4. Sütun</h4>
                                            <?php $this->render_select_field('footer_menu_4', 'Menü', $this->get_menus_array()); ?>
                                        </div>
                                    </div>

                                    <hr class="mb-4">
                                    
                                    <?php $this->render_textarea_field('footer_text', 'Footer Metin', '5. sütunda gösterilecek açıklama'); ?>
                                    <?php $this->render_number_field('footer_unvan', 'Kuruluş Yılı', 'Copyright yanında gösterilecek ünvan'); ?>
                                    <?php $this->render_textarea_field('footer_alan', 'Footer HTML Alanı', 'Footer\'a eklenecek özel HTML/script'); ?>
                                </div>
                                
                                <!-- SOSYAL MEDYA -->
                                <div class="tab-content" id="tab-sosyal">
                                    <h2 class="mb-3 pb-3">Sosyal Medya Hesapları</h2>
                                    
                                    <?php $this->render_url_field('facebook', 'Facebook'); ?>
                                    <?php $this->render_url_field('twitter', 'X (Twitter)'); ?>
                                    <?php $this->render_url_field('instagram', 'Instagram'); ?>
                                    <?php $this->render_url_field('youtube', 'Youtube'); ?>
                                    <?php $this->render_text_field('whatsapp', 'WhatsApp Numarası'); ?>
                                </div>
                                
                                <!-- ANASAYFA AYARLARI -->
                                <div class="tab-content" id="tab-bloklar">
                                    <h2 class="mb-3 pb-3">Anasayfa Ayarları</h2>
                                    <p class="description">Anasayfadaki blok sıralaması ve ayarları. Öne çıkarılmış görseli olmayan haberler anasayfada gözükmez.</p>
                                    <div class="alert alert-primary" role="alert">
                                        <span class="badge text-bg-primary">YENI</span>
                                    </div>
                                    
                                    <!-- ÜST MANŞET -->
                                    <h3>Üst Manşet</h3>
                                    <?php $this->render_toggle_field('ust_manset_yeni_goster', 'Üst Manşet Bölümünü Göster'); ?>
                                    <div id="ustMansetYeniDetail" class="row mt-2">
                                        <div class="col col-md-1">
                                            <?php $this->render_number_field( 'ust_manset_yeni_slider_sayisi', 'Haber Sayısı', '', array( 'min' => 1, 'max' => 20, 'default' => 5 ) ); ?>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- SICAK GÜNDEM -->
                                    <h3>Sıcak Gündem</h3>
                                    <?php $this->render_toggle_field('ust_manset_ust_manset_ayarlari', 'Sıcak Gündem Bölümünü Göster'); ?>
                                    <div class="row" id="ustManset">
                                        <div class="col col-md-1">
                                            <?php $this->render_number_field('ust_manset_slider_sayisi', 'Haber Sayısı', '', array('min' => 4, 'max' => 16, 'default' => 12)); ?>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- ANA MANŞET -->
                                    <h3>Ana Manşet</h3>
                                    <div class="row">
                                        <div class="col-auto">
                                            <?php $this->render_number_field('manset_slider_sayisi', 'Haber Sayısı'); ?>
                                        </div>
                                        <div class="col-auto">
                                            <?php $this->render_select_field('manset_slider_modeli', 'Slider Modeli', array(
                                                'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2',
                                            )); ?>
                                        </div>
                                        <div class="col-auto">
                                            <?php $this->render_select_field('manset_slider_renk', 'Slider Rengi', array(
                                                'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                            )); ?>
                                        </div>
                                    </div>
                                    <?php $this->render_toggle_field('manset_slider_basliklari', 'Slider Başlıkları'); ?>
                                    <div class="row" id="mansetText">
                                        <div class="col-auto">
                                            <?php $this->render_select_field('manset_baslik_boyutu', 'Başlık Boyutu', array(
                                                'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                                'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                            )); ?>
                                        </div>
                                        <div class="col-auto">
                                            <?php $this->render_select_field('manset_baslik_hizasi', 'Başlık Hizası', array(
                                                'text-center' => 'Ortala', 'text-start' => 'Sola Yasla', 'text-end' => 'Sağa Yasla',
                                            )); ?>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- YAPAY ZEKA MANŞETİ (TTS) -->
                                    <h3>Yapay Zeka Manşeti</h3>
                                    <p class="description">Anasayfada sesli özet çubuğu. Bugün «Yapay Zeka manşetinde göster» işaretli haberler modalda sırayla okunur.</p>
                                    <?php $this->render_toggle_field( 'yapay_zeka_manseti_goster', 'Yapay Zeka Manşetini Göster', '', '0' ); ?>
                                    <div id="yzmMansetDetail">
                                    <?php
                                    $yzm_display = function_exists( 'mevzu_tts_get_yzm_setting_display' ) ? 'mevzu_tts_get_yzm_setting_display' : null;
                                    $yzm_site_adi_desc = sprintf(
                                        /* translators: 1: placeholder SITE_ADI, 2: current site name */
                                        __( 'Başlangıç ve bitiş cümlelerinde %1$s yazarsanız, metin ve sesli okumada otomatik olarak sitenizin adıyla değiştirilir (örnek: %2$s).', 'mevzu2' ),
                                        'SITE_ADI',
                                        get_bloginfo( 'name' )
                                    );
                                    $this->render_text_field( 'yapay_zeka_manseti_baslik', 'Çubuk başlığı', 'Örn: Günün Manşetleri', $yzm_display ? $yzm_display( 'baslik' ) : 'Günün Manşetleri' );
                                    ?>
                                    <p class="description mb-3"><?php echo esc_html( $yzm_site_adi_desc ); ?></p>
                                    <?php
                                    $this->render_textarea_field( 'yapay_zeka_manseti_baslangic_cumlesi', 'Başlangıç cümlesi', 'Modal karşılama slaytında okunur.', $yzm_display ? $yzm_display( 'baslangic_cumlesi' ) : 'SITE_ADI Yapay zeka gündemine hoşgeldiniz. Bugünün öne çıkan haberleri şunlar' );
                                    $this->render_textarea_field( 'yapay_zeka_manseti_bitis_cumlesi', 'Bitiş cümlesi', 'Son haberden sonra kapanış slaytında okunur.', $yzm_display ? $yzm_display( 'bitis_cumlesi' ) : 'Günün haberleri bu kadardı. SITE_ADI iyi günler diler.' );
                                    ?>
                                    </div>
                                    <hr>

                                    <!-- ALT MANŞET -->
                                    <h3>Alt Manşet</h3>
                                    <?php $this->render_toggle_field( 'alt_manset_alt_manseti_goster', 'Alt Manşet Bölümünü Göster' ); ?>
                                    <div id="altManset">
                                        <div class="row">
                                            <div class="col-auto">
                                                <?php $this->render_number_field( 'alt_manset_slider_sayisi', 'Haber Sayısı' ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'alt_manset_slider_modeli', 'Slider Modeli', array(
                                                    'default' => 'Varsayılan', 'model1' => 'Model 1', 'model2' => 'Model 2',
                                                ) ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'alt_manset_slider_renk', 'Slider Rengi', array(
                                                    'slider-beyaz' => 'Beyaz', 'slider-siyah' => 'Siyah',
                                                ) ); ?>
                                            </div>
                                        </div>
                                        <?php $this->render_toggle_field( 'alt_manset_slider_basliklari', 'Slider Başlıkları' ); ?>
                                        <div class="row" id="altMansetText">
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'alt_manset_baslik_boyutu', 'Başlık Boyutu', array(
                                                    'fz-14' => '14px', 'fz-16' => '16px', 'fz-18' => '18px',
                                                    'fz-20' => '20px', 'fz-22' => '22px', 'fz-24' => '24px', 'fz-26' => '26px',
                                                ) ); ?>
                                            </div>
                                            <div class="col-auto">
                                                <?php $this->render_select_field( 'alt_manset_baslik_hizasi', 'Başlık Hizası', array(
                                                    'text-center' => 'Ortala', 'text-start' => 'Sola Yasla', 'text-end' => 'Sağa Yasla',
                                                ) ); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>

                                    <!-- YAN MANŞET -->
                                    <h3>Yan Manşet</h3>
                                    <?php $this->render_select_field('yan_manset_tip', 'Yan Manşet Tipi', array(
                                        'haftalik_gundem' => 'Haftalık Gündemi Göster',
                                        'yan_manset'      => 'Yan Manşetleri Göster',
                                    )); ?>

                                    <hr>
                                    
                                    <?php
                                    $yazar_kosesi_goster = get_option( 'options_yazar_kosesi_goster', '1' );
                                    ?>
                                    <div class="mevzu-field mevzu-field-toggle mb-3">
                                        <label>
                                            <input type="hidden" name="mevzu[yazar_kosesi_goster]" value="0">
                                            <input type="checkbox" id="mevzu_yazar_kosesi_goster" name="mevzu[yazar_kosesi_goster]" value="1" <?php checked( $yazar_kosesi_goster, '1' ); ?>>
                                            Yazar Köşesini göster
                                        </label>
                                    </div>

                                    <h3>Anasayfa Üst Blokları</h3>
                                    <?php $this->render_ana_kategori_manager(); ?>

                                    <hr class="my-4">
                                    <h3>Anasayfa Blokları</h3>
                                    <?php $this->render_blocks_manager(); ?>

                                    <hr class="my-4">
                                    <?php
                                    $video_haberleri_goster = get_option( 'options_video_haberleri_goster', '1' );
                                    ?>
                                    <div class="mevzu-field mevzu-field-toggle mb-3">
                                        <label>
                                            <input type="hidden" name="mevzu[video_haberleri_goster]" value="0">
                                            <input type="checkbox" id="mevzu_video_haberleri_goster" name="mevzu[video_haberleri_goster]" value="1" <?php checked( $video_haberleri_goster, '1' ); ?>>
                                            'Video Haberleri' alanını göster
                                        </label>
                                        <p class="description">Bu alan <a href="<?php echo esc_url( admin_url( 'admin.php?page=mevzu-ayarlar#mevzu_video_kategorisi' ) ); ?>" target="_blank">Video Kategorisi</a> ayarından gelir.</p>

                                        <div class="tema-widget h-100 bg-dark p-3 rounded">
                                            <div class="row align-items-center tema-widget mb-3">
                                                <div class="col">
                                                    <h2 class="text-white m-0 border-0">Video Haber</h2>
                                                </div>
                                                <div class="col-auto ms-md-auto">
                                                    <a class="bg-hepsinigoster">
                                                        Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="row g-4">
                                                <div class="col-12 col-md-9">
                                                    <div class="d-flex flex-column h-100 justify-content-between">
                                                        <div class="bg-gray-400 rounded-3 w-100" style="height: 80%;"></div>
                                                        <div class="bg-gray-400 rounded-3 opacity-50 w-100" style="height: 10%;"></div>
                                                        <div class="bg-gray-400 rounded-3 opacity-50" style="height: 5%;width:10%"></div>
                                                    </div>
                                                </div>
                                                <div class="col opacity-50">
                                                    <div class="row g-2">
                                                        <div class="col-4"><div class="bg-gray-400 rounded-3" style="height:70px"></div></div>
                                                        <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:25px"></div></div>
                                                    </div>
                                                    <div class="row g-2 mt-1">
                                                        <div class="col-4"><div class="bg-gray-400 rounded-3" style="height:70px"></div></div>
                                                        <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:25px"></div></div>
                                                    </div>
                                                    <div class="row g-2 mt-1">
                                                        <div class="col-4"><div class="bg-gray-400 rounded-3" style="height:70px"></div></div>
                                                        <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:25px"></div></div>
                                                    </div>
                                                    <div class="row g-2 mt-1">
                                                        <div class="col-4"><div class="bg-gray-400 rounded-3" style="height:70px"></div></div>
                                                        <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:25px"></div></div>
                                                    </div>
                                                    <div class="row g-2 mt-1">
                                                        <div class="col-4"><div class="bg-gray-400 rounded-3" style="height:70px"></div></div>
                                                        <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:25px"></div></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                    $bolum_uclu_goster = get_option( 'options_bolum_uclu_goster', '1' );
                                    ?>
                                    <div class="mevzu-field mevzu-field-toggle mb-2">
                                        <label>
                                            <input type="hidden" name="mevzu[bolum_uclu_goster]" value="0">
                                            <input type="checkbox" id="mevzu_bolum_uclu_goster" name="mevzu[bolum_uclu_goster]" value="1" <?php checked( $bolum_uclu_goster, '1' ); ?>>
                                            <strong>3 Bloklu Alan</strong>
                                        </label>
                                    </div>
                                    <div id="bolumUcluIcerik">
                                    <p class="description mb-3">Anasayfada yan yana görünen 3 kolonlu bölümde gösterilecek kategoriler.</p>
                                    <div class="row g-3 mb-3">
                                        <?php
                                        $cats = $this->get_categories_array();
                                        for ( $i = 1; $i <= 3; $i++ ) :
                                            $saved = get_option( 'options_bolum_uclu_kat_' . $i, '' );
                                        ?>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <select id="mevzu_bolum_uclu_kat_<?php echo (int) $i; ?>" name="bolum_uclu_kat_<?php echo (int) $i; ?>" class="form-select mevzu-select2" aria-label="<?php echo esc_attr( $i . '. Blok Kategorisi' ); ?>">
                                                    <option value="">— Seçiniz —</option>
                                                    <?php foreach ( $cats as $val => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $saved, $val ); ?>><?php echo esc_html( $label ); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="tema-widget h-100">
                                                <h2 class="mt-3 mb-2 p-0 border-0 alt-kategori-metin">Kategori Adı</h2>
                                                <div class="bg-gray-400 rounded-3" style="height:130px"></div>
                                                <div class="row gx-2 mt-1">
                                                    <div class="col-3"><div class="bg-gray-400 rounded-3 opacity-75" style="height:50px"></div></div>
                                                    <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:50%"></div></div>
                                                </div>
                                                <div class="row gx-2 mt-1">
                                                    <div class="col-3"><div class="bg-gray-400 rounded-3 opacity-75" style="height:50px"></div></div>
                                                    <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:50%"></div></div>
                                                </div>
                                                <div class="row gx-2 mt-1">
                                                    <div class="col-3"><div class="bg-gray-400 rounded-3 opacity-75" style="height:50px"></div></div>
                                                    <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:50%"></div></div>
                                                </div>
                                                <div class="row gx-2 mt-1">
                                                    <div class="col-3"><div class="bg-gray-400 rounded-3 opacity-75" style="height:50px"></div></div>
                                                    <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:50%"></div></div>
                                                </div>
                                                <div class="row gx-2 mt-1">
                                                    <div class="col-3"><div class="bg-gray-400 rounded-3 opacity-75" style="height:50px"></div></div>
                                                    <div class="col"><div class="bg-gray-400 rounded-3 opacity-50" style="height:50%"></div></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                    </div>

                                    <hr class="my-4">
                                    <?php $this->render_toggle_field('anasayfa_son_haberler', 'Son eklenen haberleri anasayfada en altta göster'); ?>
                                    <div id="sonHaberlerDetail" class="row mt-2 mb-3">
                                        <div class="col col-md-1">
                                            <?php $this->render_number_field( 'anasayfa_son_haberler_sayisi', 'Haber Sayısı', '', array( 'min' => 1, 'max' => 24, 'default' => 9 ) ); ?>
                                        </div>
                                    </div>
                                    <div class="tema-widget mb-3">
                                        <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Son Haberler</h2>
                                        <div class="tpl-vis row g-3 ana-kat-preview-grid">
                                            <?php
                                            $son_haber_preview = absint( $this->opt( 'anasayfa_son_haberler_sayisi' ) );
                                            if ( $son_haber_preview < 1 ) {
                                                $son_haber_preview = 9;
                                            }
                                            for ( $i = 0; $i < min( 12, $son_haber_preview ); $i++ ) :
                                            ?>
                                            <div class="col-3"><div></div></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    <h3>ilan.gov.tr</h3>
                                    <?php $this->render_toggle_field('ilangovtr', 'ilan.gov.tr Alanını Göster'); ?>
                                    <div id="ilanGovtrDetail">
                                        <?php $this->render_text_field('ilangovtr_embed', 'ilan.gov.tr Embed URL'); ?>
                                    </div>

                                </div>

                                <!-- İÇE/DIŞA AKTAR -->
                                <div class="tab-content" id="tab-import-export">
                                    <?php Mevzu_Import_Export::render_import_export_section(); ?>
                                </div>

                                <?php endif; ?> <!-- End of is_locked check for previous tabs -->

                                <!-- LİSANS & GÜNCELLEME -->
                                <div class="tab-content <?php echo $is_locked ? 'active' : ''; ?>" id="tab-lisans">
                                    <h2 class="mb-3 pb-3">Lisans & Güncelleme</h2>
                                    <p><strong>Tema Versiyonu:</strong> <?php echo MEVZU_THEME_VERSION; ?></p>
                                    <?php if (!$is_locked): ?>
                                    <div class="update-check-section d-flex align-items-center mb-3">
                                        <button type="button" class="button button-secondary d-flex align-items-center" id="mevzu-check-update">
                                            <i class="ri-loop-right-line dashicons me-1 fs-6 d-flex align-items-center"></i> Tema Güncellemesini Denetle
                                        </button>
                                        <span id="update-check-status" class="ms-2 small"></span>
                                    </div>
                                    <?php
                                    $this->render_switch_field(
                                        'tema_otomatik_guncelle',
                                        __( 'Yeni sürüm çıktığında temayı otomatik güncelle', 'mevzu2' ),
                                        __( 'Periyodik kontrol veya yönetici panelinde oturumdayken deneme yapıldığında güncelleme bulunursa kurulum başlatılır. Geçerli bir lisans gerekir.', 'mevzu2' ),
                                        '0',
                                        true
                                    );
                                    ?>
                                    <?php endif; ?>

                                    <?php Mevzu_License::render_license_section(); ?>
                                </div>

                                <?php if (!$is_locked): ?>
                                <!-- HAKKINDA -->
                                <div class="tab-content" id="tab-hakkinda">
                                    <h2 class="mb-3 pb-3">Hakkında</h2>
                                    <p class="fs-normal">
                                        <span class="fw-semibold text-primary">Mevzu²</span> lisanslı bir WordPress temasıdır.<br>
                                        Yapılan güncellemeler kişisel isteklere göre değil, herkesin yararına olabilecek, performans, erişebilirlik ve güvenlik odaklı iyileştirmelere yöneliktir.<br>
                                        <br>
                                        Uygunsuz davranışlar(lisans kontrolünü aşma vb. çabalar) tema lisansının iptaliyle sonuçlanıp sitenizin temaya erişiminin engellenmesiyle birlikte buna sebep olanlar hakkında yasal işlem başlatılmasına sebep olabilir.<br>
                                    </p>
                                    <p class="fs-normal">
                                        <span>Tasarım & Yazılım:</span> <span class="fw-semibold">Kerem ER</span><br>
                                        <span>Web Sitesi:</span> <a href="https://kkerem.com" target="_blank" class="text-decoration-none fw-semibold">https://kkerem.com</a><br>
                                        <span>Destek & İletişim:</strong> <a href="mailto:kerem.er35@gmail.com" class="text-decoration-none fw-semibold">kerem.er35@gmail.com</a></p>
                                </div>

                                <!-- VIDEO DEPOLAMA -->
                                <div class="tab-content" id="tab-video-depolama">
                                    <h2 class="mb-3 pb-3">Video Depolama</h2>
                                    <p class="description mb-3">Yazılara eklenen videoların nerede barındırılacağını seçin.</p>

                                    <?php $this->render_select_field('video_depolama', 'Depolama Yöntemi', [
                                        'local' => 'Sitenin kendi sunucusunda barındır',
                                        'r2'    => 'Cloudflare R2\'de barındır',
                                    ]); ?>

                                    <div id="mevzu-r2-fields" style="display:none">
                                        <hr>
                                        <h3 class="mb-2">Cloudflare R2 Kurulum Rehberi</h3>
                                        <div class="notice notice-info inline" style="margin:0 0 16px;padding:12px 16px;border-radius:6px;background:#f0f6fc;border-left:4px solid #2271b1">
                                            <p class="mb-2"><strong>1. Bucket oluştur:</strong> <a href="https://dash.cloudflare.com" target="_blank">dash.cloudflare.com</a> → R2 Object Storage → <strong>Create Bucket</strong> → bir isim ver.</p>
                                            <p class="mb-2"><strong>2. Public erişim:</strong> Bucket → Settings → Public Development URL → <strong>Enable</strong>. Açılan <code>pub-xxx.r2.dev</code> adresini aşağıdaki <em>Public URL</em> alanına gir.</p>
                                            <p class="mb-2"><strong>3. S3 Endpoint:</strong> Aynı Settings sayfasında <strong>S3 API</strong> satırındaki adresi kopyala → <em>S3 API Endpoint</em> alanına yapıştır. (EU bucket için adres <code>HESAP_ID.eu.r2.cloudflarestorage.com</code> biçimindedir.)</p>
                                            <p class="mb-2"><strong>4. API Token:</strong> R2 ana sayfası → Account Details → <strong>API Tokens → Manage → Create Account API Token</strong> → "Object Read &amp; Write" → bucket seç. Verilen <strong>Access Key ID</strong> ve <strong>Secret Access Key</strong> değerlerini aşağıya gir.</p>
                                            <p class="mb-0"><strong>5. Account ID:</strong> R2 ana sayfası sağ paneli → Account Details → <strong>Account ID</strong>.</p>
                                        </div>
                                        <h3 class="mb-2">Bağlantı Bilgileri</h3>
                                        <?php $this->render_text_field('r2_account_id', 'Account ID', 'Cloudflare hesap ID numaraniz (sag panelde gorunur)'); ?>
                                        <?php $this->render_text_field('r2_bucket', 'Bucket Adi', 'Olusturdugunuz R2 bucket adi'); ?>
                                        <?php $this->render_text_field('r2_access_key', 'Access Key ID', 'API Token oluştururken verilen erişim anahtarı'); ?>
                                        <?php $this->render_password_field('r2_secret_key', 'Secret Access Key', 'API Token oluştururken verilen gizli anahtar (maskelenmiş gösterilir)'); ?>
                                        <?php $this->render_url_field('r2_s3_endpoint', 'S3 API Endpoint', 'Cloudflare R2 Settings sayfasindaki S3 API adresi. Ornek: https://ID.eu.r2.cloudflarestorage.com'); ?>
                                        <?php $this->render_url_field('r2_public_url', 'Public URL', 'Örn: https://cdn.siten.com veya https://pub-xxx.r2.dev'); ?>
                                        <div class="mevzu-field">
                                            <button type="button" class="button" id="mevzu-r2-test">R2 Bağlantısını Test Et</button>
                                            <span id="mevzu-r2-test-result" class="ms-2 small"></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!$is_locked): ?>
                                <div class="mevzu-settings-actions">
                                    <button type="submit" class="button button-primary button-large" id="mevzu-save-settings">
                                        Değişiklikleri Kaydet
                                    </button>
                                    <span class="mevzu-save-status"></span>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- <div class="mevzu-settings-schema-col d-none">
                    <div class="sticky-top">
                        <div class="bg-white rounded shadow-sm p-3">
                            <div class="fw-semibold text-body">Anasayfa Şeması</div>
                            <div class="row g-1 gap-2 text-center mt-2">
                                <div class="col-12 bg-danger bg-opacity-25 text-danger text-opacity-50 d-flex align-items-center justify-content-center rounded" style="min-height: 3em;">Header Alanı</div>
                                <div class="col-12 bg-primary bg-opacity-25 text-dark d-flex align-items-center justify-content-center rounded" style="min-height: 4em;">Üst Manşet</div>
                                <div class="col-12 d-flex g-1 gap-2 position-relative">
                                    <div class="position-absolute text-dark" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">Sıcak Gündem</div>
                                    <div class="bg-primary bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 3em;"></div>
                                    <div class="bg-primary bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 3em;"></div>
                                    <div class="bg-primary bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 3em;"></div>
                                    <div class="bg-primary bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 3em;"></div>
                                </div>
                                <div class="col-8 bg-primary bg-opacity-25 text-dark d-flex align-items-center justify-content-center rounded" style="min-height: 4em;">Manşet</div>
                                <div class="col d-flex flex-column g-1 gap-2 position-relative" style="min-height: 7em;">
                                    <div class="position-absolute text-dark" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">Yan Manşet</div>
                                    <div class="bg-primary bg-opacity-25 d-flex align-items-center justify-content-center rounded col"></div>
                                </div>
                                <div class="col-12 bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded" style="min-height: 5em;">Yazar Köşesi</div>
                                <div class="col-12 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center rounded" style="min-height: 9em;">Anasayfa Üst Blokları</div>
                            </div>
                            <div class="row g-1 gap-2 text-center mt-2">
                                <div class="col-8 d-flex g-1 gap-2 flex-column">
                                    <div class="bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center rounded" style="min-height: 5em;">Alt Manşet</div>
                                    <div class="bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center rounded" style="min-height: 12em;">Anasayfa Blokları</div>
                                </div>
                                <div class="col h-100">
                                    <div class="bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded" style="min-height: 17.6em;">Sağ Sütun</div>
                                </div>
                                <div class="col-12 bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded" style="min-height: 6em;">Video Haberler</div>
                                <div class="col-12 d-flex g-1 gap-2 position-relative">
                                    <div class="position-absolute text-dark" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">3 Bloklu Alan</div>
                                    <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 11em;"></div>
                                    <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 11em;"></div>
                                    <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded col" style="min-height: 11em;"></div>
                                </div>
                                <div class="col-12 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center rounded" style="min-height: 7em;">Son Eklenen Haberler</div>
                                <div class="col-12 bg-danger bg-opacity-25 text-danger text-opacity-50 d-flex align-items-center justify-content-center rounded" style="min-height: 3em;">Sitenin Alt Alanı</div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
        <?php
    }
    
    // ============================================================
    //  FIELD RENDER HELPERS
    // ============================================================
    
    private function render_text_field($key, $label, $desc = '', $empty_default = '') {
        $value = $this->opt($key);
        if ( trim( (string) $value ) === '' && $empty_default !== '' ) {
            $value = $empty_default;
        }
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'  => 'text',
            'id'    => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'  => 'mevzu[' . $key . ']',
            'label' => $label,
            'value' => $value,
            'desc'  => $desc,
        ) );
    }
    
    private function render_url_field($key, $label, $desc = '') {
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'  => 'url',
            'id'    => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'  => 'mevzu[' . $key . ']',
            'label' => $label,
            'value' => esc_url( $this->opt( $key ) ),
            'desc'  => $desc,
        ) );
    }
    
    private function render_password_field($key, $label, $desc = '') {
        $value = $this->opt($key);
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'  => 'password',
            'id'    => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'  => 'mevzu[' . $key . ']',
            'label' => $label,
            'value' => $value,
            'desc'  => $desc,
            'attrs' => 'autocomplete="new-password"',
        ) );
    }

    private function render_textarea_field($key, $label, $desc = '', $empty_default = '') {
        $value = $this->opt($key);
        if ( trim( (string) $value ) === '' && $empty_default !== '' ) {
            $value = $empty_default;
        }
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'  => 'textarea',
            'id'    => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'  => 'mevzu[' . $key . ']',
            'label' => $label,
            'value' => $value,
            'desc'  => $desc,
            'rows'  => 4,
        ) );
    }
    
    private function render_number_field($key, $label, $desc = '', $opts = array()) {
        $value = $this->opt($key);
        if (($value === '' || $value === false) && isset($opts['default'])) {
            $value = $opts['default'];
        }
        $attrs = '';
        if (isset($opts['min'])) {
            $attrs .= ' min="' . (int) $opts['min'] . '"';
        }
        if (isset($opts['max'])) {
            $attrs .= ' max="' . (int) $opts['max'] . '"';
        }
        if (isset($opts['step'])) {
            $attrs .= ' step="' . esc_attr($opts['step']) . '"';
        }
        Mevzu_Admin_Fields::render_floating_input( array(
            'type'  => 'number',
            'id'    => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'  => 'mevzu[' . $key . ']',
            'label' => $label,
            'value' => $value,
            'desc'  => $desc,
            'attrs' => $attrs,
        ) );
    }
    
    private function render_select_field($key, $label, $options = array(), $desc = '', $default = '') {
        Mevzu_Admin_Fields::render_floating_select( array(
            'id'            => Mevzu_Admin_Fields::field_id( $key, 'settings' ),
            'name'          => 'mevzu[' . $key . ']',
            'label'         => $label,
            'value'         => $this->opt( $key, $default ),
            'options'       => $options,
            'desc'          => $desc,
            'select_class'  => 'form-select mevzu-select2',
        ) );
    }
    
    private function render_toggle_field($key, $label, $desc = '', $default = '') {
        $value = $this->opt($key, $default);
        ?>
        <div class="mevzu-field mevzu-field-toggle">
            <label>
                <input type="hidden" name="mevzu[<?php echo esc_attr($key); ?>]" value="0">
                <input type="checkbox" name="mevzu[<?php echo esc_attr($key); ?>]" value="1" <?php checked($value, '1'); ?>>
                <?php echo esc_html($label); ?>
            </label>
            <?php if ($desc): ?><p class="description"><?php echo esc_html($desc); ?></p><?php endif; ?>
        </div>
        <?php
    }

    /**
     * Switch görünümü; isteğe bağlı tek alan otomatik kayıt (AJAX).
     */
    private function render_switch_field( $key, $label, $desc = '', $default = '', $autosave = false ) {
        $value   = $this->opt( $key, $default );
        $id      = 'mevzu-switch-' . sanitize_key( $key );
        $status_id = 'mevzu-switch-status-' . sanitize_key( $key );
        ?>
        <div class="mevzu-field mevzu-field-switch mb-3">
            <label class="mevzu-settings-toggle-row border-0 p-0" for="<?php echo esc_attr( $id ); ?>">
                <span class="mevzu-settings-switch">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr( $id ); ?>"
                        <?php if ( ! $autosave ) : ?>
                        name="mevzu[<?php echo esc_attr( $key ); ?>]"
                        <?php endif; ?>
                        class="mevzu-settings-switch-input<?php echo $autosave ? ' mevzu-switch-autosave' : ''; ?>"
                        data-option-key="<?php echo esc_attr( $key ); ?>"
                        value="1"
                        <?php checked( $value, '1' ); ?>
                    >
                    <span class="mevzu-settings-switch-slider"></span>
                </span>
                <span class="mevzu-settings-toggle-label">
                    <?php echo esc_html( $label ); ?>
                    <?php if ( $autosave ) : ?>
                        <span id="<?php echo esc_attr( $status_id ); ?>" class="mevzu-switch-save-status" aria-live="polite"></span>
                    <?php endif; ?>
                </span>
            

            </label>
            <?php if ( $desc ) : ?>
                <p class="description mb-0 mt-2"><?php echo esc_html( $desc ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_checkbox_group($key, $label, $options = array()) {
        $value = $this->opt($key);
        $selected = is_array($value) ? $value : (is_string($value) ? maybe_unserialize($value) : array());
        if (!is_array($selected)) $selected = array();
        ?>
        <div class="mevzu-field">
            <label><?php echo esc_html($label); ?></label>
            <div class="mevzu-checkbox-group">
                <?php foreach ($options as $val => $text): ?>
                    <label class="mevzu-checkbox-item">
                        <input type="checkbox" name="mevzu[<?php echo esc_attr($key); ?>][]" 
                               value="<?php echo esc_attr($val); ?>" 
                               <?php echo in_array($val, $selected) ? 'checked' : ''; ?>>
                        <?php echo esc_html($text); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    private function render_color_field($key, $label, $desc = '') {
        $value = $this->opt($key, '#e90808'); // Temanın varsayılan kırmızısı
        $presets = mevzu_get_site_primary_color_presets();
        $id    = Mevzu_Admin_Fields::field_id( $key, 'settings' );
        ?>
        <div class="mevzu-field mevzu-field-color mb-3">
            <div class="form-floating mevzu-color-picker-wrapper">
                <input type="text" id="<?php echo esc_attr( $id ); ?>" 
                       name="mevzu[<?php echo esc_attr($key); ?>]" 
                       value="<?php echo esc_attr($value); ?>" class="form-control mevzu-color-picker" placeholder="<?php echo esc_attr( Mevzu_Admin_Fields::FLOAT_PLACEHOLDER ); ?>">
                <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
            </div>
            <div class="mevzu-color-presets mt-2">
                <?php foreach ($presets as $color): ?>
                    <span class="mevzu-preset-color" data-color="<?php echo $color; ?>" style="background-color: <?php echo $color; ?>" title="<?php echo $color; ?>"></span>
                <?php endforeach; ?>
            </div>
            <?php if ($desc): ?><p class="description"><?php echo esc_html($desc); ?></p><?php endif; ?>
        </div>
        <?php
    }
    
    private function render_image_field($key, $label, $desc = '') {
        $value = $this->opt($key);
        $image_url = '';
        if ($value && is_numeric($value)) {
            $image_url = wp_get_attachment_url($value);
        } elseif ($value && filter_var($value, FILTER_VALIDATE_URL)) {
            $image_url = $value;
        }
        ?>
        <div class="mevzu-field mevzu-field-image">
            <label><?php echo esc_html($label); ?></label>
            <div class="mevzu-image-preview">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:200px;max-height:100px;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="mevzu[<?php echo esc_attr($key); ?>]" 
                   value="<?php echo esc_attr($value); ?>" class="mevzu-image-id">
            <button type="button" class="button mevzu-image-select">Görsel Seç</button>
            <button type="button" class="button mevzu-image-remove" <?php echo !$value ? 'style="display:none"' : ''; ?>>Kaldır</button>
            <?php if ($desc): ?><p class="description"><?php echo esc_html($desc); ?></p><?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Anasayfa blokları yöneticisi
     */
    private function render_blocks_manager() {
        $blocks_count = intval($this->opt('bloklar', 0));
        $blocks = array();
        for ($i = 0; $i < $blocks_count; $i++) {
            $blocks[] = array(
                'goruntuleme_sablonu' => get_option('options_bloklar_' . $i . '_goruntuleme_sablonu', 'sablon1'),
                'tekli_blok'         => get_option('options_bloklar_' . $i . '_tekli_blok', ''),
                'ikili_blok'         => get_option('options_bloklar_' . $i . '_ikili_blok', array()),
                'haber_sayisi'       => get_option('options_bloklar_' . $i . '_haber_sayisi', '3'),
            );
        }
        $categories = $this->get_categories_array();
        ?>
        <div class="row align-items-stretch">
            <div class="col-12 col-md-9">
                <!-- ALT MANŞET -->
                <div class="mevzu-blocks-manager" id="mevzu-blocks-manager">
                    <div class="mevzu-blocks-toolbar">
                        <button type="button" class="button button-primary" id="mevzu-add-block">
                            <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-right:4px"></span>
                            Yeni Blok Ekle
                        </button>
                        <button type="button" class="button button-secondary" id="mevzu-save-blocks" style="margin-left:8px">
                            <span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>
                            Blokları Kaydet
                        </button>
                        <span class="mevzu-blocks-status" id="mevzu-blocks-status"></span>
                    </div>

                    <div class="mevzu-blocks-list" id="mevzu-blocks-list">
                        <?php if (empty($blocks)): ?>
                            <div class="mevzu-blocks-empty" id="mevzu-blocks-empty">
                                <span class="dashicons dashicons-layout" style="font-size:48px;width:48px;height:48px;color:#c3c4c7"></span>
                                <p>Henüz blok eklenmemiş. <strong>"Yeni Blok Ekle"</strong> butonuyla başlayın.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($blocks as $i => $block): ?>
                                <?php $this->render_block_row($i, $block, $categories); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-3 align-items-strech text-center rounded d-none d-md-block">
                <a href="<?php echo admin_url('widgets.php'); ?>" class="text-decoration-none d-block h-100" target="_blank">
                    <div class="bg-secondary bg-opacity-10 h-100 rounded shadow-sm p-4 d-flex flex-column align-items-center justify-content-center gap-2" style="transition:all .2s;border:2px dashed transparent" onmouseover="this.style.borderColor='#2271b1';this.style.background='#f0f6fc'" onmouseout="this.style.borderColor='transparent';this.style.background=''">
                        <span class="dashicons dashicons-admin-customizer opacity-50" style="font-size:28px;width:28px;height:28px"></span>
                        <span class="opacity-50 fw-semibold">Sağ Bileşen Alanı</span>
                        <small class="text-primary">Widget'ları Düzenle →</small>
                    </div>
                </a>
            </div>
        </div>

        <!-- Blok satırı template (JS ile klonlanır) -->
        <template id="mevzu-block-template">
            <?php $this->render_block_row('__INDEX__', array('goruntuleme_sablonu' => 'sablon1', 'tekli_blok' => '', 'ikili_blok' => array('', ''), 'haber_sayisi' => '3'), $categories); ?>
        </template>

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
        jQuery(function($) {
            var $list = $('#mevzu-blocks-list');
            var $empty = $('#mevzu-blocks-empty');
            var $status = $('#mevzu-blocks-status');
            var blockTpl = document.getElementById('mevzu-block-template').innerHTML;

            function reindex() {
                $list.find('.mevzu-block-row').each(function(i) {
                    $(this).find('.mevzu-block-number').text(i + 1);
                    $(this).attr('data-index', i);
                });
                if ($list.find('.mevzu-block-row').length === 0 && !$empty.length) {
                    $list.html('<div class="mevzu-blocks-empty" id="mevzu-blocks-empty">' +
                        '<span class="dashicons dashicons-layout" style="font-size:48px;width:48px;height:48px;color:#c3c4c7"></span>' +
                        '<p>Henüz blok eklenmemiş. <strong>"Yeni Blok Ekle"</strong> butonuyla başlayın.</p></div>');
                }
            }

            // SortableJS
            if ($list.length) {
                Sortable.create($list[0], {
                    handle: '.mevzu-block-handle',
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: function() { reindex(); }
                });
            }
            
            // Şablon Seçimine Göre Alanları Göster/Gizle
            $list.on('change', '.tpl-radio', function() {
                var sablon = $(this).val();
                var $row = $(this).closest('.mevzu-block-row');
                $row.find('.setting-group').hide();
                $row.find('.show-for-' + sablon).show();
                
                if ($row.find('.show-for-' + sablon).length === 0) {
                    $row.find('.mevzu-block-fields').hide();
                } else {
                    $row.find('.mevzu-block-fields').css('display', 'flex');
                }
                
                // Klonda isim çakışmasını önlemek için id güncelle index'e göre name'ler zaten dynamic.
                var idx = $row.attr('data-index');
                $row.find('input[type="radio"]').attr('name', 'block_sablon_' + idx);
            });
            // İlk Yüklemede Şablonları Tetikle
            $('.tpl-radio:checked').trigger('change');

            // Blok Ekle
            $('#mevzu-add-block').on('click', function() {
                $('#mevzu-blocks-empty').remove();
                var idx = $list.find('.mevzu-block-row').length;
                var html = blockTpl.replace(/__INDEX__/g, idx);
                var $newBlock = $(html);
                $list.append($newBlock);
                reindex();
                $newBlock.find('.tpl-radio:checked').trigger('change');
                $newBlock[0].scrollIntoView({behavior:'smooth', block:'center'});
            });

            // Blok Sil
            $list.on('click', '.mevzu-block-remove', function() {
                if (!confirm('Bu bloğu silmek istediğinize emin misiniz?')) return;
                $(this).closest('.mevzu-block-row').slideUp(200, function() {
                    $(this).remove();
                    reindex();
                });
            });

            // Blok Kopyala
            $list.on('click', '.mevzu-block-duplicate', function() {
                var $row = $(this).closest('.mevzu-block-row');
                var $clone = $row.clone();
                $row.after($clone);
                reindex();
                $clone.find('.tpl-radio:checked').trigger('change');
                $clone[0].scrollIntoView({behavior:'smooth', block:'center'});
            });

            // Blokları Kaydet
            $('#mevzu-save-blocks').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Kaydediliyor...');
                var blocks = [];
                $list.find('.mevzu-block-row').each(function() {
                    blocks.push({
                        goruntuleme_sablonu: $(this).find('.tpl-radio:checked').val(),
                        tekli_blok:         $(this).find('.block-kategori').val(),
                        ikili_blok_1:       $(this).find('.block-ikili-1').val(),
                        ikili_blok_2:       $(this).find('.block-ikili-2').val(),
                        haber_sayisi:       $(this).find('.block-sayi').val() || '3'
                    });
                });
                var postData = {
                    action: 'mevzu_save_blocks',
                    nonce:  mevzuSettings.nonce,
                    blocks: JSON.stringify(blocks)
                };
                for (var i = 1; i <= 3; i++) {
                    postData['bolum_uclu_kat_' + i] = $('select[name="bolum_uclu_kat_' + i + '"]').val() || '';
                }
                postData.yazar_kosesi_goster = $('#mevzu_yazar_kosesi_goster').is(':checked') ? '1' : '0';
                postData.video_haberleri_goster = $('#mevzu_video_haberleri_goster').is(':checked') ? '1' : '0';
                $.post(mevzuSettings.ajaxUrl, postData, function(res) {
                    $btn.prop('disabled', false).html(
                        '<span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>Blokları Kaydet'
                    );
                    if (res.success) {
                        $status.text('✓ ' + res.data).addClass('visible');
                        setTimeout(function(){ $status.removeClass('visible'); }, 3000);
                    } else {
                        alert('Hata: ' + res.data);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html(
                        '<span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>Blokları Kaydet'
                    );
                    alert('Bağlantı hatası');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Ana Kategori blok yöneticisi
     */
    private function render_ana_kategori_manager() {
        $count = intval( get_option('options_ana_kat_bloklar', 0) );
        $bloklar = [];
        for ( $i = 0; $i < $count; $i++ ) {
            $bloklar[] = [
                'sablon'        => get_option('options_ana_kat_' . $i . '_sablon', 'sablon1'),
                'baslik'        => get_option('options_ana_kat_' . $i . '_baslik', ''),
                'haberler_metni'=> get_option('options_ana_kat_' . $i . '_haberler_metni', '0'),
                'kategori'      => get_option('options_ana_kat_' . $i . '_kategori', ''),
                'haber_sayisi'  => get_option('options_ana_kat_' . $i . '_haber_sayisi', '6'),
            ];
        }
        $cats = $this->get_categories_array();
        ?>
        <div class="mevzu-blocks-manager" id="mevzu-ana-kat-manager">
            <div class="mevzu-blocks-toolbar">
                <button type="button" class="button button-primary" id="mevzu-add-ana-kat">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-right:4px"></span>
                    Yeni Blok Ekle
                </button>
                <button type="button" class="button button-secondary" id="mevzu-save-ana-kat" style="margin-left:8px">
                    <span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>
                    Kaydet
                </button>
                <span class="mevzu-blocks-status" id="mevzu-ana-kat-status"></span>
            </div>
            <div class="mevzu-blocks-list" id="mevzu-ana-kat-list">
                <?php if ( empty($bloklar) ) : ?>
                <div class="mevzu-blocks-empty" id="mevzu-ana-kat-empty">
                    <span class="dashicons dashicons-category" style="font-size:48px;width:48px;height:48px;color:#c3c4c7"></span>
                    <p>Henüz blok eklenmemiş. <strong>"Yeni Blok Ekle"</strong> butonuyla başlayın.</p>
                </div>
                <?php else : ?>
                    <?php foreach ( $bloklar as $i => $blok ) : ?>
                        <?php $this->render_ana_kat_row($i, $blok, $cats); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <template id="mevzu-ana-kat-template">
            <?php $this->render_ana_kat_row('__IDX__', ['sablon'=>'sablon1','baslik'=>'','haberler_metni'=>'0','kategori'=>'','haber_sayisi'=>'6'], $cats); ?>
        </template>

        <script>
        jQuery(function($) {
            var ajaxUrl = (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl;
            var nonce   = '<?php echo wp_create_nonce("mevzu_settings_nonce"); ?>';
            var $list   = $('#mevzu-ana-kat-list');
            var $status = $('#mevzu-ana-kat-status');
            var tpl     = document.getElementById('mevzu-ana-kat-template').innerHTML;

            function reindex() {
                $list.find('.mevzu-ana-kat-row').each(function(i) {
                    $(this).find('.mevzu-block-number').text(i + 1);
                    $(this).attr('data-index', i);
                });
                if ( $list.find('.mevzu-ana-kat-row').length === 0 ) {
                    $list.html('<div class="mevzu-blocks-empty"><span class="dashicons dashicons-category" style="font-size:48px;width:48px;height:48px;color:#c3c4c7"></span><p>Henüz blok eklenmemiş. <strong>"Yeni Blok Ekle"</strong> butonuyla başlayın.</p></div>');
                }
            }

            if ($list.length) {
                Sortable.create($list[0], {
                    handle: '.mevzu-block-handle', animation: 200,
                    ghostClass: 'sortable-ghost', dragClass: 'sortable-drag',
                    onEnd: function() { reindex(); }
                });
            }

            function syncSablon($row) {
                var sablon = $row.find('.ana-kat-sablon-radio:checked').val();
                $row.find('.ana-kat-field').hide();
                $row.find('.show-for-' + sablon).show();
            }

            function syncAnaKatPreview($row) {
                var sablon = $row.find('.ana-kat-sablon-radio:checked').val();
                var $selectedCategory = $row.find('.ana-kat-kategori option:selected');
                var kategoriText = ($selectedCategory.length ? $selectedCategory.text().trim() : '') || 'Kategori Adı';
                kategoriText = kategoriText.replace(/\s*\(ID:\s*\d+\)\s*$/i, '').trim();
                if (kategoriText.indexOf('—') === 0) {
                    kategoriText = 'Kategori Adı';
                }

                if (sablon === 'sablon1' && $row.find('.ana-kat-haberler-metni').is(':checked')) {
                    kategoriText += ' Haberleri';
                }

                var baslik = ($row.find('.ana-kat-baslik').val() || '').trim();
                $row.find('.alt-kategori-haberler').text(kategoriText);
                $row.find('.alt-kategori-baslik').text(baslik || 'Alt Kategori 1');

                var haberSayisi = parseInt($row.find('.ana-kat-haber-sayisi').val(), 10);
                if (isNaN(haberSayisi) || haberSayisi < 1) {
                    haberSayisi = 1;
                }
                if (haberSayisi > 24) {
                    haberSayisi = 24;
                }
                var $previewGrid = $row.find('.ana-kat-preview-grid');
                if ($previewGrid.length) {
                    var cardsHtml = '';
                    for (var i = 0; i < haberSayisi; i++) {
                        cardsHtml += '<div class="col-3"><div></div></div>';
                    }
                    $previewGrid.html(cardsHtml);
                }
            }

            $list.on('change', '.ana-kat-sablon-radio', function() {
                var $row = $(this).closest('.mevzu-ana-kat-row');
                syncSablon($row);
                syncAnaKatPreview($row);
            });
            $list.on('input change', '.ana-kat-baslik, .ana-kat-haberler-metni, .ana-kat-kategori, .ana-kat-haber-sayisi', function() {
                syncAnaKatPreview($(this).closest('.mevzu-ana-kat-row'));
            });
            $list.find('.mevzu-ana-kat-row').each(function() {
                var $row = $(this);
                syncSablon($row);
                syncAnaKatPreview($row);
            });

            $('#mevzu-add-ana-kat').on('click', function() {
                $('#mevzu-ana-kat-empty').remove();
                var idx = $list.find('.mevzu-ana-kat-row').length;
                var html = tpl.replace(/__IDX__/g, idx);
                var $row = $(html);
                $list.append($row);
                reindex();
                syncSablon($row);
                syncAnaKatPreview($row);
                $row[0].scrollIntoView({behavior:'smooth', block:'center'});
            });

            $list.on('click', '.mevzu-block-remove', function() {
                if (!confirm('Bu bloğu silmek istediğinize emin misiniz?')) return;
                $(this).closest('.mevzu-ana-kat-row').slideUp(200, function() { $(this).remove(); reindex(); });
            });

            $list.on('click', '.mevzu-block-duplicate', function() {
                var $row = $(this).closest('.mevzu-ana-kat-row');
                var $clone = $row.clone();
                $row.after($clone);
                reindex();
                syncSablon($clone);
                syncAnaKatPreview($clone);
                $clone[0].scrollIntoView({behavior:'smooth', block:'center'});
            });

            $('#mevzu-save-ana-kat').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Kaydediliyor...');
                var bloklar = [];
                $list.find('.mevzu-ana-kat-row').each(function() {
                    bloklar.push({
                        sablon:         $(this).find('.ana-kat-sablon-radio:checked').val(),
                        baslik:         $(this).find('.ana-kat-baslik').val(),
                        haberler_metni: $(this).find('.ana-kat-haberler-metni').is(':checked') ? '1' : '0',
                        kategori:       $(this).find('.ana-kat-kategori').val(),
                        haber_sayisi:   $(this).find('.ana-kat-haber-sayisi').val() || '8'
                    });
                });
                $.post(ajaxUrl, {
                    action: 'mevzu_save_ana_kat_blocks',
                    nonce:  nonce,
                    bloklar: JSON.stringify(bloklar),
                    yazar_kosesi_goster: $('#mevzu_yazar_kosesi_goster').is(':checked') ? '1' : '0',
                    video_haberleri_goster: $('#mevzu_video_haberleri_goster').is(':checked') ? '1' : '0'
                }, function(res) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>Kaydet');
                    if (res.success) {
                        $status.text('✓ ' + res.data).addClass('visible');
                        setTimeout(function(){ $status.removeClass('visible'); }, 3000);
                    } else {
                        alert('Hata: ' + res.data);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="vertical-align:middle;margin-right:4px"></span>Kaydet');
                    alert('Bağlantı hatası');
                });
            });
        });
        </script>
        <?php
    }

    private function render_ana_kat_row($index, $blok, $cats) {
        $radio_name = 'ana_kat_sablon_' . $index;
        ?>
        <div class="mevzu-block-row mevzu-ana-kat-row" data-index="<?php echo esc_attr($index); ?>">

            <!-- Alanlar -->
            <div class="mevzu-block-fields align-items-start">
                <!-- Tek kategori seçimi — her iki şablon için ortak -->
                <div class="setting-group ana-kat-field show-for-sablon1 show-for-sablon2">
                    <label>Kategori</label>
                    <select class="ana-kat-kategori">
                        <option value="">— Seçiniz —</option>
                        <?php foreach ( $cats as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($blok['kategori'], $val); ?>><?php echo esc_html($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="d-flex align-items-center fw-normal mt-2" style="text-transform:inherit">
                        <input type="checkbox" class="ana-kat-haberler-metni" <?php checked($blok['haberler_metni'], '1'); ?>>
                        Sonuna "Haberleri" Ekle
                    </label>
                </div>
                <div class="setting-group ana-kat-field show-for-sablon1">
                    <label>Ana Kategori Başlığı</label>
                    <input type="text" class="ana-kat-baslik" value="<?php echo esc_attr($blok['baslik']); ?>" placeholder="Örn: Merkez"><br>
                </div>
                <div class="setting-group ana-kat-field show-for-sablon1 show-for-sablon2">
                    <label>Haber Sayısı</label>
                    <input type="number" name="ana_kat_haber_sayisi_<?php echo esc_attr($index); ?>" class="ana-kat-haber-sayisi" value="<?php echo esc_attr($blok['haber_sayisi']); ?>" min="4" max="24" step="4" style="width:80px">
                </div>
            </div>
            <div class="mevzu-block-row-header">
                <div class="mevzu-block-handle" title="Sürükle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="10" r="1.5"/><circle cx="15" cy="10" r="1.5"/><circle cx="9" cy="15" r="1.5"/><circle cx="15" cy="15" r="1.5"/><circle cx="9" cy="20" r="1.5"/><circle cx="15" cy="20" r="1.5"/></svg>
                </div>
                <div class="mevzu-block-actions">
                    <div class="mevzu-block-number mt-0"><?php echo is_numeric($index) ? $index + 1 : '#'; ?></div>
                    <button type="button" class="mevzu-block-duplicate" title="Kopyala"><span class="dashicons dashicons-admin-page fs-6"></span></button>
                    <button type="button" class="mevzu-block-remove" title="Sil"><span class="dashicons dashicons-trash fs-6"></span></button>
                </div>
                <!-- Şablon Seçimi -->
                <div class="tpl-vis-group">
                    <label>
                        <input type="radio" class="tpl-radio ana-kat-sablon-radio" name="<?php echo esc_attr($radio_name); ?>" value="sablon2" <?php checked($blok['sablon'], 'sablon2'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Kategori Adı</h2>
                            <div class="tpl-vis row g-3 ana-kat-preview-grid">
                                <?php
                                $preview_haber_sayisi = isset($blok['haber_sayisi']) ? absint($blok['haber_sayisi']) : 8;
                                if ( $preview_haber_sayisi < 1 ) $preview_haber_sayisi = 1;
                                if ( $preview_haber_sayisi > 24 ) $preview_haber_sayisi = 24;
                                for ( $i = 0; $i < $preview_haber_sayisi; $i++ ) :
                                ?>
                                <div class="col-3"><div></div></div>
                                <?php endfor; ?>
                            </div>
                            <div class="tpl-title pt-3">Kategorili Şablon</div>
                        </div>
                    </label>
                    <label>
                        <input type="radio" class="tpl-radio ana-kat-sablon-radio" name="<?php echo esc_attr($radio_name); ?>" value="sablon1" <?php checked($blok['sablon'], 'sablon1'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin alt-kategori-haberler">Kategori Adı</h2>
                            <ul class="nav nav-pills coklu gap-2" role="tablist">
                                <li class="nav-item"><a class="nav-link fz-10 border rounded-3 py-1 px-3 active alt-kategori-baslik">Alt Kategori 1</a></li>
                                <li class="nav-item"><a class="nav-link fz-10 border rounded-3 py-1 px-3">Alt Kat. 2</a></li>
                                <li class="nav-item"><a class="nav-link fz-10 border rounded-3 py-1 px-3">Alt Kat. 3</a></li>
                            </ul>
                            <div class="tpl-vis row g-3 ana-kat-preview-grid">
                                <?php
                                $preview_haber_sayisi = isset($blok['haber_sayisi']) ? absint($blok['haber_sayisi']) : 8;
                                if ( $preview_haber_sayisi < 1 ) $preview_haber_sayisi = 1;
                                if ( $preview_haber_sayisi > 24 ) $preview_haber_sayisi = 24;
                                for ( $i = 0; $i < $preview_haber_sayisi; $i++ ) :
                                ?>
                                <div class="col-3"><div></div></div>
                                <?php endfor; ?>
                            </div>
                            <div class="tpl-title">Alt Kategorili Şablon</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tek blok satırı render (index hem PHP hem JS template için kullanılır)
     */
    private function render_block_row($index, $block, $categories) {
        $ikili1 = isset($block['ikili_blok'][0]) ? $block['ikili_blok'][0] : '';
        $ikili2 = isset($block['ikili_blok'][1]) ? $block['ikili_blok'][1] : '';
        // Template içindeysek varsayılan bir isim, PHP isek index'li isim:
        $radio_name = 'block_sablon_' . $index;
        ?>
        <div class="mevzu-block-row" data-index="<?php echo esc_attr($index); ?>">
            <div class="mevzu-block-row-header">
                <div class="mevzu-block-handle" title="Sürükle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="10" r="1.5"/><circle cx="15" cy="10" r="1.5"/><circle cx="9" cy="15" r="1.5"/><circle cx="15" cy="15" r="1.5"/><circle cx="9" cy="20" r="1.5"/><circle cx="15" cy="20" r="1.5"/></svg>
                </div>
                
                <div class="mevzu-block-actions">
                    <div class="mevzu-block-number mt-0"><?php echo is_numeric($index) ? $index + 1 : '#'; ?></div>
                    <button type="button" class="mevzu-block-duplicate" title="Kopyala">
                        <span class="dashicons dashicons-admin-page fs-6"></span>
                    </button>
                    <button type="button" class="mevzu-block-remove" title="Sil">
                        <span class="dashicons dashicons-trash fs-6"></span>
                    </button>
                </div>
                
                <!-- GÖRSEL ŞABLON SEÇİMİ -->
                <div class="tpl-vis-group">
                    <!-- ŞABLON 1 -->
                    <label>
                        <input type="radio" class="tpl-radio" name="<?php echo esc_attr($radio_name); ?>" value="sablon1" <?php checked($block['goruntuleme_sablonu'], 'sablon1'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Kategori Adı</h2>
                            <div class="tpl-vis row g-1 ana-kat-preview-grid">
                                <?php
                                $preview_haber_sayisi = isset($blok['haber_sayisi']) ? absint($blok['haber_sayisi']) : 3;
                                if ( $preview_haber_sayisi < 1 ) $preview_haber_sayisi = 1;
                                if ( $preview_haber_sayisi > 15 ) $preview_haber_sayisi = 15;
                                for ( $i = 0; $i < $preview_haber_sayisi; $i++ ) :
                                ?>
                                <div class="col-4"><div style="height:40px"></div></div>
                                <?php endfor; ?>
                            </div>
                            <div class="tpl-title">Şablon 1 (Grid)</div>
                        </div>
                    </label>
                    <!-- ŞABLON 2 -->
                    <label>
                        <input type="radio" class="tpl-radio" name="<?php echo esc_attr($radio_name); ?>" value="sablon2" <?php checked($block['goruntuleme_sablonu'], 'sablon2'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Kategori Adı</h2>
                            <div class="tpl-vis row g-1 ana-kat-preview-grid">
                                <div class="col-8"><div style="height:74px"></div></div>
                                <div class="col-4">
                                    <div style="height:35px" class="mb-1"></div>
                                    <div style="height:35px"></div>
                                </div>
                            </div>
                            <div class="tpl-title">Şablon 2</div>
                        </div>
                    </label>
                    <?php /*
                    <!-- ŞABLON 3 -->
                    <label>
                        <input type="radio" class="tpl-radio" name="<?php echo esc_attr($radio_name); ?>" value="sablon3" <?php checked($block['goruntuleme_sablonu'], 'sablon3'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <div class="tpl-vis"><div style="flex:1;background:#fff;border:1px solid #ddd"></div><div style="flex:1;background:#fff;border:1px solid #ddd"></div><div style="flex:1;background:#fff;border:1px solid #ddd"></div></div>
                            <div class="tpl-title">Şablon 3 (Kart Grid)</div>
                        </div>
                    </label> */ ?>
                    <!-- İKİLİ ŞABLON -->
                    <label>
                        <input type="radio" class="tpl-radio" name="<?php echo esc_attr($radio_name); ?>" value="ikilisablon" <?php checked($block['goruntuleme_sablonu'], 'ikilisablon'); ?>>
                        <div class="tpl-vis-label tema-widget h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Kategori Adı</h2>
                            <div class="tpl-vis row g-1 ana-kat-preview-grid">
                                <div class="col-6">
                                    <div style="height:30px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                </div>
                                <div class="col-6">
                                    <div style="height:30px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                    <div class="mt-1" style="height:10px"></div>
                                </div>
                            </div>
                            <div class="tpl-title">İkili Bölüm</div>
                        </div>
                    </label>
                    <!-- RESMİ İLANLAR -->
                    <label>
                        <input type="radio" class="tpl-radio" name="<?php echo esc_attr($radio_name); ?>" value="resmiilanlar" <?php checked($block['goruntuleme_sablonu'], 'resmiilanlar'); ?>>
                        <div class="tpl-vis-label tema-widget tema-widget-red h-100">
                            <h2 class="mt-0 mb-2 p-0 border-0 alt-kategori-metin">Resmi İlanlar</h2>
                            <div class="tpl-vis row g-1 ana-kat-preview-grid">
                                <div class="col-4"><div class="bg-warning-subtle" style="height:40px"></div></div>
                                <div class="col-4"><div class="bg-warning-subtle" style="height:40px"></div></div>
                                <div class="col-4"><div class="bg-warning-subtle" style="height:40px"></div></div>
                            </div>
                            <div class="tpl-title">Resmi İlanlar</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- DİNAMİK ALANLAR (Şablona Göre Açılır/Kapanır) -->
            <?php 
            $sablon = $block['goruntuleme_sablonu']; 
            // Şablon 1, 2, 3 için
            $is_single = in_array($sablon, array('sablon1', 'sablon2', 'sablon3'));
            // İkili şablon için
            $is_ikili = ($sablon === 'ikilisablon');
            ?>
            <div class="mevzu-block-fields" <?php echo ($is_single || $is_ikili) ? '' : 'style="display:none;"'; ?>>
                
                <!-- TEKLİ BLOK KATEGORİ -->
                <div class="setting-group show-for-sablon1 show-for-sablon2 show-for-sablon3" <?php echo $is_single ? '' : 'style="display:none;"'; ?>>
                    <label>Kategori</label>
                    <select class="block-kategori">
                        <option value="">— Seçiniz —</option>
                        <?php foreach ($categories as $cat_id => $cat_label): ?>
                            <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($block['tekli_blok'], $cat_id); ?>>
                                <?php echo esc_html($cat_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- İKİLİ BLOK KATEGORİLERİ -->
                <div class="setting-group show-for-ikilisablon" <?php echo $is_ikili ? '' : 'style="display:none;"'; ?>>
                    <label>İkili Bölüm — 1. Kategori</label>
                    <select class="block-ikili-1">
                        <option value="">— 1. Kategoriyi Seçin —</option>
                        <?php foreach ($categories as $cat_id => $cat_label): ?>
                            <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($ikili1, $cat_id); ?>>
                                <?php echo esc_html($cat_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="setting-group show-for-ikilisablon" <?php echo $is_ikili ? '' : 'style="display:none;"'; ?>>
                    <label>İkili Bölüm — 2. Kategori</label>
                    <select class="block-ikili-2">
                        <option value="">— 2. Kategoriyi Seçin —</option>
                        <?php foreach ($categories as $cat_id => $cat_label): ?>
                            <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($ikili2, $cat_id); ?>>
                                <?php echo esc_html($cat_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- HABER SAYISI -->
                <div class="setting-group show-for-sablon1" <?php echo $is_single ? '' : 'style="display:none;"'; ?>>
                    <label>Haber Sayısı</label>
                    <input type="number" class="block-sayi" value="<?php echo esc_attr($block['haber_sayisi']); ?>" min="3" max="15" step="3" style="max-width:120px;">
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Kategori listesi
     */
    public function get_categories_array() {
        static $cats = null;
        if ($cats !== null) return $cats;
        
        $cats = array();
        $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $cats[$term->term_id] = $term->name . ' (ID: ' . $term->term_id . ')';
            }
        }
        return $cats;
    }
    
    /**
     * Sayfa listesi
     */
    private function get_pages_array() {
        static $pages = null;
        if ($pages !== null) return $pages;
        
        $pages = array();
        $all_pages = get_pages(array('sort_column' => 'menu_order'));
        foreach ($all_pages as $page) {
            $pages[$page->ID] = $page->post_title . ' (ID: ' . $page->ID . ')';
        }
        return $pages;
    }

    /**
     * Menü listesi
     */
    private function get_menus_array() {
        static $menus = null;
        if ($menus !== null) return $menus;
        
        $menus = array('' => '-- Menü Seçin --');
        $all_menus = wp_get_nav_menus();
        if ($all_menus) {
            foreach ($all_menus as $menu) {
                $menus[$menu->term_id] = $menu->name;
            }
        }
        return $menus;
    }
    
    /**
     * Eklentiler alt sayfası
     */
    public function render_modules_page() {
        $status = Mevzu_License::get_license_status();
        $license_key = Mevzu_License::get_license_key();
        
        $is_locked = false;
        if ($status['status'] === 'banned') {
            $is_locked = true;
        } elseif ($status['status'] === 'inactive' || empty($license_key)) {
            $is_locked = true;
        } elseif ($status['status'] !== 'active' && $status['status'] !== 'unchecked') {
            $is_locked = true;
        }
        ?>
        <div class="wrap mevzu-settings-wrap">
            <h1>Mevzu² Modülleri</h1>
            <p class="description" style="font-size:14px;margin-bottom:20px">Mevzu² temaya ek özellik katan modüllerdir. İhtiyacınıza göre aktif veya deaktif edebilirsiniz.</p>
            
            <?php if ($is_locked): ?>
                <div class="mevzu-notice mevzu-notice-error" style="background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-bottom:20px;border-left:4px solid #f5c6cb;max-width:800px;">
                    <strong>Bilgi:</strong> Lisansınız geçerli olmadığı için eklentiler kullanıma kapatılmıştır. Lütfen aktif bir lisans anahtarı girerek ayarları açın.
                </div>
            <?php else: ?>
            <form id="mevzu-settings-form" method="post">
                <?php wp_nonce_field('mevzu_settings_nonce', 'mevzu_nonce'); ?>
                
                <?php $this->render_modules_grid(); ?>
                
                <div class="mevzu-settings-actions" style="margin-top:20px">
                    <button type="submit" class="button button-primary button-large" id="mevzu-save-settings">
                        Değişiklikleri Kaydet
                    </button>
                    <span class="mevzu-save-status"></span>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Modüller Grid Render
     */
    private function render_modules_grid() {
        $modules = Mevzu_Module_Manager::get_all();
        if (empty($modules)) {
            echo '<p>Kayıtlı modül bulunamadı.</p>';
            return;
        }
        
        $categories = array(
            'icerik'    => array('label' => 'İçerik', 'modules' => array()),
            'otomasyon' => array('label' => 'Otomasyon', 'modules' => array()),
            'sosyal'    => array('label' => 'Sosyal', 'modules' => array()),
            'genel'     => array('label' => 'Genel', 'modules' => array()),
        );
        
        foreach ($modules as $slug => $info) {
            $cat = isset($categories[$info['category']]) ? $info['category'] : 'genel';
            $categories[$cat]['modules'][$slug] = $info;
        }
        ?>
        <div class="mevzu-modules-grid">
            <?php foreach ($categories as $cat_key => $cat): ?>
                <?php if (empty($cat['modules'])) continue; ?>
                <?php foreach ($cat['modules'] as $slug => $mod):
                    $is_active = Mevzu_Module_Manager::is_active($slug);
                ?>
                <div class="mevzu-module-card <?php echo $is_active ? 'active' : ''; ?> <?php echo $mod['is_premium'] ? 'premium' : ''; ?>">
                    <?php if ($mod['is_premium']): ?>
                        <span class="module-premium-badge">PRO</span>
                    <?php endif; ?>
                    <?php
                    $icon_type = isset( $mod['icon_type'] ) ? $mod['icon_type'] : 'dashicons';
                    if ( $icon_type === 'remix' ) :
                        ?>
                    <div class="module-icon module-icon--remix"><i class="<?php echo esc_attr( $mod['icon'] ); ?>" aria-hidden="true"></i></div>
                    <?php else : ?>
                    <div class="module-icon"><span class="dashicons <?php echo esc_attr( $mod['icon'] ); ?>"></span></div>
                    <?php endif; ?>
                    <div class="module-info">
                        <h4><?php echo esc_html($mod['name']); ?></h4>
                        <p><?php echo esc_html($mod['description']); ?></p>
                        <span class="module-meta">v<?php echo esc_html($mod['version']); ?> · <?php echo esc_html($mod['author']); ?></span>
                    </div>
                    <div class="module-toggle">
                        <label class="mevzu-module-switch">
                            <input type="hidden" name="mevzu[module_<?php echo esc_attr($slug); ?>]" value="0">
                            <input type="checkbox" name="mevzu[module_<?php echo esc_attr($slug); ?>]" value="1" <?php checked($is_active); ?> class="module-switch-input">
                            <span class="module-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        
        <style>
        .mevzu-modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
        .mevzu-module-card {
            position: relative; display: flex; align-items: flex-start; gap: 14px;
            background: #f9f9f9; border: 2px solid #e2e4e7; border-radius: 12px;
            padding: 20px; transition: all 0.25s ease;
        }
        .mevzu-module-card:hover { border-color: #c3c4c7; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .mevzu-module-card.active { background: #f0f6fc; border-color: #2271b1; }
        .mevzu-module-card.premium { border-color: #dba617; }
        .mevzu-module-card.premium.active { border-color: #dba617; background: #fef9ed; }
        .module-premium-badge {
            position: absolute; top: -8px; right: 12px;
            background: linear-gradient(135deg, #f0b429, #d69e2e); color: #fff;
            font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 4px;
            letter-spacing: 1px; box-shadow: 0 2px 4px rgba(214,158,46,0.3);
        }
        .module-icon {
            width: 44px; height: 44px; background: #fff; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e4e7; flex-shrink: 0;
        }
        .mevzu-module-card.active .module-icon { background: #2271b1; border-color: #2271b1; }
        .mevzu-module-card.active .module-icon .dashicons { color: #fff; }
        .module-icon--remix { font-size: 22px; line-height: 1; }
        .module-icon--remix i { color: #646970; }
        .mevzu-module-card.active .module-icon--remix i { color: #fff; }
        .mevzu-module-card.premium.active .module-icon--remix i { color: #fff; }
        .mevzu-module-card.premium.active .module-icon { background: #d69e2e; border-color: #d69e2e; }
        .module-info { flex: 1; min-width: 0; }
        .module-info h4 { margin: 0 0 4px; font-size: 14px; color: #1d2327; }
        .module-info p { margin: 0 0 6px; font-size: 12px; color: #646970; line-height: 1.4; }
        .module-meta { font-size: 11px; color: #8c8f94; }
        .module-toggle { flex-shrink: 0; padding-top: 4px; }
        /* Module Switch */
        .mevzu-module-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .mevzu-module-switch input { opacity: 0; width: 0; height: 0; }
        .module-slider {
            position: absolute; cursor: pointer; inset: 0; background: #ccc;
            transition: 0.3s; border-radius: 24px;
        }
        .module-slider:before {
            content: ""; position: absolute; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background: #fff; transition: 0.3s; border-radius: 50%;
        }
        .mevzu-module-switch input:checked + .module-slider { background: #2271b1; }
        .mevzu-module-switch input:checked + .module-slider:before { transform: translateX(20px); }
        .mevzu-module-card.premium .mevzu-module-switch input:checked + .module-slider { background: #d69e2e; }
        @media (max-width:782px) { .mevzu-modules-grid { grid-template-columns: 1fr; } }
        </style>
        
        <script>
        jQuery(function($) {
            $(document).on('change', '.module-switch-input', function() {
                $(this).closest('.mevzu-module-card').toggleClass('active', this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX kaydetme
     */
    public function ajax_save() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }
        
        $data = isset($_POST['mevzu']) ? wp_unslash($_POST['mevzu']) : array();
        if (!is_array($data)) {
            $data = array();
        }

        // Sıcak Gündem haber sayısı (4–16)
        if (isset($data['ust_manset_slider_sayisi'])) {
            $data['ust_manset_slider_sayisi'] = (string) min(16, max(4, intval($data['ust_manset_slider_sayisi'])));
        }
        if ( isset( $data['ust_manset_yeni_slider_sayisi'] ) ) {
            $data['ust_manset_yeni_slider_sayisi'] = (string) min( 20, max( 1, intval( $data['ust_manset_yeni_slider_sayisi'] ) ) );
        }
        if ( isset( $data['anasayfa_son_haberler_sayisi'] ) ) {
            $data['anasayfa_son_haberler_sayisi'] = (string) min( 24, max( 1, intval( $data['anasayfa_son_haberler_sayisi'] ) ) );
        }
        if ( isset( $data['son_dakika_haber_sayisi'] ) ) {
            $data['son_dakika_haber_sayisi'] = (string) min( 30, max( 1, intval( $data['son_dakika_haber_sayisi'] ) ) );
        }

        // Arşiv üst manşet haber sayısı (1–50)
        if (isset($data['archive_manset_slider_sayisi'])) {
            $data['archive_manset_slider_sayisi'] = (string) min(50, max(1, intval($data['archive_manset_slider_sayisi'])));
        }
        
        // Modül durumlarını ayrıca kaydet
        $modules_state = get_option('mevzu_modules', array());
        
        foreach ($data as $key => $value) {
            if (strpos($key, 'module_') === 0) {
                $module_slug = substr($key, 7);
                $modules_state[$module_slug] = intval($value);
            } elseif (is_array($value)) {
                update_option('options_' . $key, $value);
            } else {
                $sanitized_value = (in_array($key, array('header_alan', 'footer_alan', 'footer_text'))) ? wp_unslash($value) : sanitize_text_field($value);
                update_option('options_' . sanitize_text_field($key), $sanitized_value);
            }
        }

        // site_rengi değiştiğinde theme_mod'u da güncelle (header.php öncelikli olarak theme_mod okur)
        if (isset($data['site_rengi']) && !empty($data['site_rengi'])) {
            $color = sanitize_hex_color($data['site_rengi']);
            if ($color) {
                set_theme_mod('mevzu_primary_color', $color);
            }
        }
        
        // Ana formu kaydederken blok verileri de JavaScript tarafından eklenmişse onları da kaydet
        if (isset($_POST['blocks'])) {
            $this->save_blocks_data($_POST['blocks']);
        }

        // 3 Bloklu Bölüm kategorileri
        for ( $i = 1; $i <= 3; $i++ ) {
            $key = 'bolum_uclu_kat_' . $i;
            if ( isset( $_POST[ $key ] ) ) {
                update_option( 'options_bolum_uclu_kat_' . $i, intval( $_POST[ $key ] ) );
            }
        }

        // Ana Kategori blokları
        if (isset($_POST['ana_kat_bloklar'])) {
            $raw     = $_POST['ana_kat_bloklar'];
            $bloklar = json_decode( stripslashes($raw), true );
            if ( is_array($bloklar) ) {
                $count = count($bloklar);
                update_option('options_ana_kat_bloklar', $count);
                foreach ( $bloklar as $i => $blok ) {
                    update_option( 'options_ana_kat_' . $i . '_sablon',          sanitize_text_field($blok['sablon'] ?? 'sablon1') );
                    update_option( 'options_ana_kat_' . $i . '_baslik',          sanitize_text_field($blok['baslik'] ?? '') );
                    update_option( 'options_ana_kat_' . $i . '_haberler_metni',  ($blok['haberler_metni'] ?? '') === '1' ? '1' : '0' );
                    update_option( 'options_ana_kat_' . $i . '_kategori',        intval($blok['kategori'] ?? 0) );
                    update_option( 'options_ana_kat_' . $i . '_haber_sayisi',    intval($blok['haber_sayisi'] ?? 6) );
                }
                for ( $i = $count; $i < $count + 20; $i++ ) {
                    if ( get_option('options_ana_kat_' . $i . '_sablon') === false ) break;
                    delete_option('options_ana_kat_' . $i . '_sablon');
                    delete_option('options_ana_kat_' . $i . '_baslik');
                    delete_option('options_ana_kat_' . $i . '_haberler_metni');
                    delete_option('options_ana_kat_' . $i . '_kategori');
                    delete_option('options_ana_kat_' . $i . '_haber_sayisi');
                }
            }
        }
        
        update_option('mevzu_modules', $modules_state);

        // Manşet sorgu önbelleği — kayıt sonrası eski haber sayısıyla kalmaması için
        delete_transient('anasayfa_manset_sorgusu');
        delete_transient('anasayfa_ust_mansetler_sorgusu');
        delete_transient('anasayfa_alt_manset_sorgusu');
        delete_transient( 'sonhaberler' );
        delete_transient( 'son_dakika_haberleri_v2' );
        
        wp_send_json_success('Ayarlar kaydedildi');
    }

    /**
     * AJAX — Güncelleme Denetle
     */
    public function ajax_r2_test() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetki yok');

        $account_id = sanitize_text_field(get_option('options_r2_account_id',''));
        $access_key = sanitize_text_field(get_option('options_r2_access_key',''));
        $secret_key = get_option('options_r2_secret_key','');
        $bucket     = sanitize_text_field(get_option('options_r2_bucket',''));
        $public_url = get_option('options_r2_public_url','');

        if (!$account_id || !$access_key || !$secret_key || !$bucket || !$public_url) {
            wp_send_json_error('Tüm R2 alanlarını doldurun ve önce kaydedin.');
        }

        // Küçük bir test dosyası yükle
        $test_content = 'mevzu-r2-test-' . time();
        $tmp = wp_tempnam('mevzu_r2_test');
        file_put_contents($tmp, $test_content);

        if (!class_exists('Mevzu_Video_R2')) {
            require_once MEVZU_SETTINGS_PATH . 'class-video-r2.php';
        }
        $result = Mevzu_Video_R2::upload($tmp, 'mevzu-test/connection-test.txt', 'text/plain');
        @unlink($tmp);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success('Bağlantı başarılı! Test dosyası yüklendi: ' . $result);
    }

    public function maybe_schedule_theme_update_cron() {
        $schedules = wp_get_schedules();
        if (!isset($schedules['mevzu_every_fifteen_minutes'])) {
            $ts = wp_next_scheduled('mevzu_theme_update_check_cron');
            if ($ts) {
                wp_unschedule_event($ts, 'mevzu_theme_update_check_cron');
            }
            return;
        }
        if (wp_next_scheduled('mevzu_theme_update_check_cron')) {
            return;
        }
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'mevzu_every_fifteen_minutes', 'mevzu_theme_update_check_cron');
    }

    public function run_theme_update_cron_check() {
        $result = $this->check_remote_theme_version();
        $this->persist_theme_update_check_result($result);
    }

    /**
     * WP-Cron tetiklenmese bile (ör. az trafikli yerel kurulum) yönetici oturumunda en fazla 15 dk’da bir kontrol.
     */
    public function maybe_poll_theme_update_while_in_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $last = (int) get_option('mevzu_theme_update_checked_at', 0);
        if ($last && (time() - $last) < 15 * MINUTE_IN_SECONDS) {
            return;
        }
        $result = $this->check_remote_theme_version();
        $this->persist_theme_update_check_result($result);
    }

    /**
     * Lisans sunucusundan sürüm karşılaştırması (AJAX + cron ortak).
     *
     * @return array|WP_Error latest_version, current_version, update_available
     */
    private function check_remote_theme_version() {
        $license_key     = Mevzu_License::get_license_key();
        $current_version = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';
        $raw_domain      = (string) parse_url(get_site_url(), PHP_URL_HOST);
        $domain          = strtolower(preg_replace('/:\d+$/', '', preg_replace('/^www\./', '', $raw_domain)));
        $site_id         = Mevzu_License::get_site_id();

        $response = wp_remote_post(Mevzu_License::UPDATE_API_URL, array(
            'timeout' => 15,
            'body'    => array(
                'license_key'     => $license_key,
                'current_version' => $current_version,
                'action'          => 'list_versions',
                'domain'          => $domain,
                'site_id'         => $site_id,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['success']) || !$body['success']) {
            return new WP_Error('mevzu_update_api', $body['message'] ?? 'Bilinmeyen hata.');
        }

        $latest_version = $body['latest'] ?? $current_version;
        $update_available = version_compare($latest_version, $current_version, '>');

        return array(
            'latest_version'   => $latest_version,
            'current_version'  => $current_version,
            'update_available' => $update_available,
        );
    }

    /**
     * @param array|WP_Error $result check_remote_theme_version çıktısı
     */
    private function persist_theme_update_check_result($result) {
        update_option('mevzu_theme_update_checked_at', time());
        if (is_wp_error($result)) {
            return;
        }
        update_option('mevzu_theme_update_latest', $result['latest_version']);
        update_option('mevzu_theme_update_available', $result['update_available'] ? '1' : '0');

        if (empty($result['update_available']) || get_option('options_tema_otomatik_guncelle', '0') !== '1') {
            return;
        }

        $latest = $result['latest_version'];
        $defer = (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && $_REQUEST['action'] === 'mevzu_check_update');
        if ($defer) {
            wp_schedule_single_event(time() + 20, 'mevzu_theme_auto_apply_scheduled', array($latest));
            return;
        }

        $this->maybe_attempt_auto_theme_update($latest);
    }

    /**
     * AJAX denetimi sonrası kısa gecikme ile çalışır (yanıt süresini şişirmemek için).
     */
    public function run_scheduled_auto_theme_apply($latest_version) {
        $this->maybe_attempt_auto_theme_update(sanitize_text_field((string) $latest_version));
    }

    /**
     * Ayar açıksa ve uygunsa paketi indirip kurar.
     */
    private function maybe_attempt_auto_theme_update($latest_version) {
        $latest_version = sanitize_text_field((string) $latest_version);
        if (get_option('options_tema_otomatik_guncelle', '0') !== '1') {
            return;
        }
        if (get_option('mevzu_theme_update_available', '0') !== '1') {
            return;
        }

        $target = sanitize_text_field((string) get_option('mevzu_theme_update_latest', ''));
        if ($target === '') {
            $target = $latest_version;
        }
        if ($target === '') {
            return;
        }

        $current_version = defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0';
        if (!version_compare($target, $current_version, '>')) {
            return;
        }

        if (empty(Mevzu_License::get_license_key())) {
            return;
        }

        if (get_transient('mevzu_theme_auto_update_lock')) {
            return;
        }

        set_transient('mevzu_theme_auto_update_lock', 1, 3 * MINUTE_IN_SECONDS);

        $apply = Mevzu_License::apply_theme_version($target, true);

        delete_transient('mevzu_theme_auto_update_lock');

        if (!is_wp_error($apply)) {
            update_option('mevzu_theme_update_available', '0');
            update_option('mevzu_theme_auto_update_last_ok', time());
            update_option('mevzu_theme_auto_update_last_version', $target);
            delete_option('mevzu_theme_auto_update_last_error');
            return;
        }

        update_option('mevzu_theme_auto_update_last_error', $apply->get_error_message());
    }

    public function render_theme_update_admin_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (get_option('mevzu_theme_update_available', '0') !== '1') {
            return;
        }
        $latest = sanitize_text_field((string) get_option('mevzu_theme_update_latest', ''));
        if ($latest === '') {
            return;
        }
        $link = admin_url('admin.php?page=mevzu-ayarlar#lisans');
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>Mevzu²:</strong>
                <?php printf(
                    /* translators: %s remote theme version */
                    esc_html__('Yeni bir tema güncellemesi var (v%s).', 'mevzu2'),
                    esc_html($latest)
                ); ?>
                <?php
                printf(
                    ' <a href="%s">%s</a>',
                    esc_url($link),
                    esc_html__('Lisans ve Güncelleme sayfasında güncelleyebilirsiniz.', 'mevzu2')
                );
                ?>
            </p>
        </div>
        <?php
    }

    public function ajax_check_update() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }

        $result = $this->check_remote_theme_version();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $this->persist_theme_update_check_result($result);

        if ($result['update_available']) {
            wp_send_json_success(array(
                'update_available' => true,
                'version'          => $result['latest_version'],
                'message'          => 'Yeni sürüm mevcut: v' . $result['latest_version'],
            ));
        } else {
            wp_send_json_success(array(
                'update_available' => false,
                'version'          => $result['current_version'],
                'message'          => 'Temanız güncel! (v' . $result['current_version'] . ')',
            ));
        }
    }

    /**
     * AJAX — Anasayfa bloklarını kaydet
     * ACF repeater formatıyla uyumlu: options_bloklar = N, options_bloklar_{i}_{field} = value
     */
    public function ajax_save_ana_kat_blocks() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if ( ! current_user_can('manage_options') ) wp_send_json_error('Yetkiniz yok');

        $raw    = isset($_POST['bloklar']) ? $_POST['bloklar'] : '[]';
        $bloklar = json_decode( stripslashes($raw), true );
        if ( ! is_array($bloklar) ) wp_send_json_error('Geçersiz veri');

        $count = count($bloklar);
        update_option('options_ana_kat_bloklar', $count);

        foreach ( $bloklar as $i => $blok ) {
            update_option( 'options_ana_kat_' . $i . '_sablon',         sanitize_text_field($blok['sablon'] ?? 'sablon1') );
            update_option( 'options_ana_kat_' . $i . '_baslik',         sanitize_text_field($blok['baslik'] ?? '') );
            update_option( 'options_ana_kat_' . $i . '_haberler_metni', $blok['haberler_metni'] === '1' ? '1' : '0' );
            update_option( 'options_ana_kat_' . $i . '_kategori',       intval($blok['kategori'] ?? 0) );
            update_option( 'options_ana_kat_' . $i . '_haber_sayisi',   intval($blok['haber_sayisi'] ?? 6) );
        }

        // Fazladan kalan eski blok verilerini temizle
        for ( $i = $count; $i < $count + 20; $i++ ) {
            if ( get_option('options_ana_kat_' . $i . '_sablon') === false ) break;
            delete_option('options_ana_kat_' . $i . '_sablon');
            delete_option('options_ana_kat_' . $i . '_baslik');
            delete_option('options_ana_kat_' . $i . '_haberler_metni');
            delete_option('options_ana_kat_' . $i . '_kategori');
            delete_option('options_ana_kat_' . $i . '_haber_sayisi');
        }

        if ( isset( $_POST['yazar_kosesi_goster'] ) ) {
            update_option( 'options_yazar_kosesi_goster', $_POST['yazar_kosesi_goster'] === '1' ? '1' : '0' );
        }
        if ( isset( $_POST['video_haberleri_goster'] ) ) {
            update_option( 'options_video_haberleri_goster', $_POST['video_haberleri_goster'] === '1' ? '1' : '0' );
        }

        wp_send_json_success( $count . ' blok kaydedildi' );
    }

    public function ajax_save_blocks() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }
        
        $raw = isset($_POST['blocks']) ? $_POST['blocks'] : '[]';
        $result = $this->save_blocks_data($raw);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // 3 Bloklu Bölüm kategorilerini kaydet
        for ( $i = 1; $i <= 3; $i++ ) {
            $key = 'bolum_uclu_kat_' . $i;
            if ( isset( $_POST[ $key ] ) ) {
                update_option( 'options_bolum_uclu_kat_' . $i, intval( $_POST[ $key ] ) );
            }
        }

        if ( isset( $_POST['yazar_kosesi_goster'] ) ) {
            update_option( 'options_yazar_kosesi_goster', $_POST['yazar_kosesi_goster'] === '1' ? '1' : '0' );
        }
        if ( isset( $_POST['video_haberleri_goster'] ) ) {
            update_option( 'options_video_haberleri_goster', $_POST['video_haberleri_goster'] === '1' ? '1' : '0' );
        }

        wp_send_json_success('Bloklar kaydedildi (' . $result . ' blok)');
    }
    
    /**
     * Blok verilerini kaydetme işlemini yapan iç fonksiyon
     */
    private function save_blocks_data($raw) {
        $blocks = json_decode(stripslashes($raw), true);
        
        if (!is_array($blocks)) {
            return new WP_Error('invalid_data', 'Geçersiz blok verisi');
        }
        
        $new_count = count($blocks);
        $old_count = intval(get_option('options_bloklar', 0));
        
        // Eski blok verilerini temizle
        for ($i = 0; $i < $old_count; $i++) {
            delete_option('options_bloklar_' . $i . '_goruntuleme_sablonu');
            delete_option('options_bloklar_' . $i . '_tekli_blok');
            delete_option('options_bloklar_' . $i . '_haber_sayisi');
            delete_option('options_bloklar_' . $i . '_ikili_blok');
            // ACF field reference keys de temizle
            delete_option('_options_bloklar_' . $i . '_goruntuleme_sablonu');
            delete_option('_options_bloklar_' . $i . '_tekli_blok');
            delete_option('_options_bloklar_' . $i . '_haber_sayisi');
            delete_option('_options_bloklar_' . $i . '_ikili_blok');
        }
        
        // Yeni blok verilerini kaydet
        $valid_templates = array('sablon1', 'sablon2', 'sablon3', 'ikilisablon', 'resmiilanlar');
        foreach ($blocks as $i => $block) {
            $sablon = in_array($block['goruntuleme_sablonu'], $valid_templates) 
                ? $block['goruntuleme_sablonu'] : 'sablon1';
            $kategori = absint($block['tekli_blok']);
            $sayi = max(1, min(20, intval($block['haber_sayisi'])));
            
            // İkili Şablon için dizi formatında kategori kaydet
            $ikili1 = absint(isset($block['ikili_blok_1']) ? $block['ikili_blok_1'] : 0);
            $ikili2 = absint(isset($block['ikili_blok_2']) ? $block['ikili_blok_2'] : 0);
            $ikili_arr = array();
            if ($ikili1) $ikili_arr[] = (string)$ikili1;
            if ($ikili2) $ikili_arr[] = (string)$ikili2;
            
            update_option('options_bloklar_' . $i . '_goruntuleme_sablonu', $sablon);
            update_option('options_bloklar_' . $i . '_tekli_blok', $kategori ? (string)$kategori : '');
            update_option('options_bloklar_' . $i . '_haber_sayisi', (string)$sayi);
            
            if (!empty($ikili_arr)) {
                update_option('options_bloklar_' . $i . '_ikili_blok', $ikili_arr);
            }
        }
        
        // Blok sayısını güncelle
        update_option('options_bloklar', (string)$new_count);
        
        // Front-end transient cache'leri temizle
        for ($i = 0; $i < max($old_count, $new_count); $i++) {
            $cat = isset($blocks[$i]) ? absint($blocks[$i]['tekli_blok']) : 0;
            if ($cat) {
                delete_transient('sablon1_sorgusu_' . $cat);
                delete_transient('sablon2_sorgusu_' . $cat);
                delete_transient('sablon3_sorgusu_' . $cat);
            }
        }
        delete_transient('resmi_ilanlar_sorgusu');
        
        return $new_count;
    }

    public function get_cities_array() {
        return array(
            "Adana" => "Adana",
            "Adıyaman" => "Adıyaman",
            "Afyonkarahisar" => "Afyonkarahisar",
            "Ağrı" => "Ağrı",
            "Aksaray" => "Aksaray",
            "Amasya" => "Amasya",
            "Ankara" => "Ankara",
            "Antalya" => "Antalya",
            "Ardahan" => "Ardahan",
            "Artvin" => "Artvin",
            "Aydın" => "Aydın",
            "Balıkesir" => "Balıkesir",
            "Bartın" => "Bartın",
            "Batman" => "Batman",
            "Bayburt" => "Bayburt",
            "Bilecik" => "Bilecik",
            "Bingöl" => "Bingöl",
            "Bitlis" => "Bitlis",
            "Bolu" => "Bolu",
            "Burdur" => "Burdur",
            "Bursa" => "Bursa",
            "Çanakkale" => "Çanakkale",
            "Çankırı" => "Çankırı",
            "Çorum" => "Çorum",
            "Denizli" => "Denizli",
            "Diyarbakır" => "Diyarbakır",
            "Düzce" => "Düzce",
            "Edirne" => "Edirne",
            "Elazığ" => "Elazığ",
            "Erzincan" => "Erzincan",
            "Erzurum" => "Erzurum",
            "Eskişehir" => "Eskişehir",
            "Gaziantep" => "Gaziantep",
            "Giresun" => "Giresun",
            "Gümüşhane" => "Gümüşhane",
            "Hakkari" => "Hakkari",
            "Hatay" => "Hatay",
            "Iğdır" => "Iğdır",
            "Isparta" => "Isparta",
            "İstanbul" => "İstanbul",
            "İzmir" => "İzmir",
            "Kahramanmaraş" => "Kahramanmaraş",
            "Karabük" => "Karabük",
            "Karaman" => "Karaman",
            "Kars" => "Kars",
            "Kastamonu" => "Kastamonu",
            "Kayseri" => "Kayseri",
            "Kırıkkale" => "Kırıkkale",
            "Kırklareli" => "Kırklareli",
            "Kırşehir" => "Kırşehir",
            "Kilis" => "Kilis",
            "Kocaeli" => "Kocaeli",
            "Konya" => "Konya",
            "Kütahya" => "Kütahya",
            "Malatya" => "Malatya",
            "Manisa" => "Manisa",
            "Mardin" => "Mardin",
            "Mersin" => "Mersin",
            "Muğla" => "Muğla",
            "Muş" => "Muş",
            "Nevşehir" => "Nevşehir",
            "Niğde" => "Niğde",
            "Ordu" => "Ordu",
            "Osmaniye" => "Osmaniye",
            "Rize" => "Rize",
            "Sakarya" => "Sakarya",
            "Samsun" => "Samsun",
            "Siirt" => "Siirt",
            "Sinop" => "Sinop",
            "Sivas" => "Sivas",
            "Şanlıurfa" => "Şanlıurfa",
            "Şırnak" => "Şırnak",
            "Tekirdağ" => "Tekirdağ",
            "Tokat" => "Tokat",
            "Trabzon" => "Trabzon",
            "Tunceli" => "Tunceli",
            "Uşak" => "Uşak",
            "Van" => "Van",
            "Yalova" => "Yalova",
            "Yozgat" => "Yozgat",
            "Zonguldak" => "Zonguldak"
        );
    }
}

new Mevzu_Settings_Page();
