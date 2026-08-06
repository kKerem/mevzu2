<?php
/**
 * Mevzu² Kurulum Sihirbazı
 * 
 * Tema ilk kez yüklendiğinde kullanıcıyı adım adım temel ayarları
 * yapılandırması için yönlendirir.
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Setup_Wizard {
    
    private $step = 1;
    private $total_steps = 6;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'redirect_after_activation'));
        add_action('wp_ajax_mevzu_wizard_save', array($this, 'ajax_save'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Gizli menü sayfası (menüde gözükmez)
     */
    public function add_menu() {
        add_submenu_page(
            'mevzu-ayarlar',
            'Mevzu² Kurulum Sihirbazı',
            'Kurulum Sihirbazı',
            'manage_options',
            'mevzu-setup-wizard',
            array($this, 'render_page')
        );
    }
    
    /**
     * Tema yüklendiğinde sihirbaza yönlendir (sadece ilk seferde)
     */
    public function redirect_after_activation() {
        if (get_option('mevzu_setup_completed')) return;
        if (get_option('mevzu_setup_redirect_done')) return;
        
        // Mevcut veritabanında ayar olup olmadığını kontrol et
        // Eğer daha önce ACF tarafından ayarlar yapılmışsa, sihirbazı atla
        $existing_header = get_option('options_header_sablon');
        if ($existing_header) {
            update_option('mevzu_setup_completed', true);
            return;
        }
        
        update_option('mevzu_setup_redirect_done', true);
        
        if (!is_admin() || defined('DOING_AJAX') || defined('DOING_CRON')) return;
        
        // Sadece ana admin sayfasına girişte yönlendir
        global $pagenow;
        if ($pagenow === 'index.php' || $pagenow === 'themes.php') {
            wp_redirect(admin_url('admin.php?page=mevzu-setup-wizard'));
            exit;
        }
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'mevzu-setup-wizard') === false) return;
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('mevzu-settings', MEVZU_SETTINGS_URL . 'assets/settings.js', array('jquery', 'wp-color-picker'), MEVZU_THEME_VERSION, true);
        wp_localize_script('mevzu-settings', 'mevzuSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mevzu_settings_nonce'),
        ));
    }
    
    public function render_page() {
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        $step = max(1, min($step, $this->total_steps));
        ?>
        <div class="wrap mevzu-settings-wrap mevzu-wizard-wrap mx-auto">
            <div class="mevzu-wizard">
                <div class="mevzu-wizard-header">
                    <h1>Mevzu²'ye Hoş Geldiniz! 🎉</h1>
                    <p>Temanızı birkaç adımda yapılandırın. Buradaki tüm ayarları isterseniz daha sonra tekrar değiştirebilirsiniz.</p>
                    
                    <div class="mevzu-wizard-progress">
                        <?php for ($i = 1; $i <= $this->total_steps; $i++): ?>
                            <div class="step-indicator <?php echo $i <= $step ? 'active' : ''; ?> <?php echo $i < $step ? 'completed' : ''; ?>">
                                <span class="step-number"><?php echo $i; ?></span>
                                <span class="step-label"><?php echo $this->get_step_label($i); ?></span>
                            </div>
                            <?php if ($i < $this->total_steps): ?>
                                <div class="step-connector <?php echo $i < $step ? 'active' : ''; ?>"></div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="mevzu-wizard-body">
                    <form id="mevzu-wizard-form" method="post">
                        <?php wp_nonce_field('mevzu_settings_nonce', 'mevzu_nonce'); ?>
                        <input type="hidden" name="wizard_step" value="<?php echo $step; ?>">
                        
                        <?php 
                        // Kurulum başladığında sayfaların varlığını kontrol et
                        if (function_exists('mevzu_create_default_pages')) {
                            mevzu_create_default_pages();
                        }
                        $this->render_step($step); 
                        ?>
                        
                        <div class="mevzu-wizard-actions">
                            <?php if ($step > 1): ?>
                                <a href="<?php echo admin_url('admin.php?page=mevzu-setup-wizard&step=' . ($step - 1)); ?>" class="button button-secondary button-large">← Geri</a>
                            <?php endif; ?>
                            
                            <?php
                            $status = Mevzu_License::get_license_status();
                            $is_active = ($status['status'] === 'active');
                            ?>
                            
                            <?php if ($step == 1 && !$is_active): ?>
                                <div><button type="button" class="button button-primary button-large" disabled>Lisansı Doğrulayın →</button></div>
                            <?php elseif ($step < $this->total_steps): ?>
                                <button type="submit" class="button button-primary button-large" id="mevzu-wizard-next">
                                    Devam Et →
                                </button>
                            <?php else: ?>
                                <button type="submit" class="button button-primary button-large" id="mevzu-wizard-finish">
                                    ✓ Kurulumu Tamamla
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($step > 1 || $is_active): ?>
                            <a href="<?php echo admin_url(); ?>" class="mevzu-wizard-skip">Sihirbazı Atla</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_step_label($step) {
        $labels = array(
            1 => 'Lisans',
            2 => 'Kimlik',
            3 => 'Header',
            4 => 'İçerik',
            5 => 'Sosyal Medya',
            6 => 'Tamamla',
        );
        return $labels[$step] ?? '';
    }
    
    private function render_step($step) {
        switch ($step) {
            case 1: $this->step_license(); break;
            case 2: $this->step_identity(); break;
            case 3: $this->step_header(); break;
            case 4: $this->step_content(); break;
            case 5: $this->step_social(); break;
            case 6: $this->step_finish(); break;
        }
    }
    
    private function step_license() {
        ?>
        <h2>Lisans İşlemleri</h2>
        <p class="mb-4">Temanızı kullanabilmek ve diğer kurulum adımlarına geçebilmek için lisans anahtarınızı doğrulayın.</p>
        
            <?php Mevzu_License::render_license_section(); ?>
        <?php
    }

    private function step_identity() {
        ?>
        <h2>Site Kimliği</h2>
        <p class="mb-2">Performans için logonun en fazla <span class="text-dark">48px</span> yüksekliğe sahip olması tavsiye edilir.</p>
        
        <div class="row">
            <div class="col">
                <div class="mevzu-field mevzu-field-image">
                    <label><strong>Site Logosu</strong></label>
                    <div class="mevzu-image-preview"></div>
                    <input type="hidden" name="mevzu[logo]" value="" class="mevzu-image-id">
                    <button type="button" class="button mevzu-image-select">Logo Seç</button>
                    <button type="button" class="button mevzu-image-remove" style="display:none">Kaldır</button>
                </div>
            </div>
            <div class="col">
                <div class="mevzu-field mevzu-field-image">
                    <label><strong>Site Logosu (Dark Mode)</strong></label>
                    <div class="mevzu-image-preview"></div>
                    <input type="hidden" name="mevzu[logo_dark]" value="" class="mevzu-image-id">
                    <button type="button" class="button mevzu-image-select">Logo Seç</button>
                    <button type="button" class="button mevzu-image-remove" style="display:none">Kaldır</button>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col">
                <div class="mevzu-field mevzu-field-image">
                    <label><strong>Mobil Logo</strong></label>
                    <div class="mevzu-image-preview"></div>
                    <input type="hidden" name="mevzu[logo_mobil]" value="" class="mevzu-image-id">
                    <button type="button" class="button mevzu-image-select">Mobil Logo Seç</button>
                    <button type="button" class="button mevzu-image-remove" style="display:none">Kaldır</button>
                </div>
            </div>
            <div class="col">
                <div class="mevzu-field mevzu-field-image">
                    <label><strong>Mobil Logo (Dark Mode)</strong></label>
                    <div class="mevzu-image-preview"></div>
                    <input type="hidden" name="mevzu[logo_mobil_dark]" value="" class="mevzu-image-id">
                    <button type="button" class="button mevzu-image-select">Logo Seç</button>
                    <button type="button" class="button mevzu-image-remove" style="display:none">Kaldır</button>
                </div>
            </div>
        </div>
        
        <div class="mevzu-field mevzu-field-image">
            <label><strong>Favicon</strong></label>
            <div class="mevzu-image-preview"></div>
            <input type="hidden" name="mevzu[favicon]" value="" class="mevzu-image-id">
            <button type="button" class="button mevzu-image-select">Favicon Seç</button>
            <button type="button" class="button mevzu-image-remove" style="display:none">Kaldır</button>
        </div>
        
        <div class="mevzu-field">
            <label><strong>Varsayılan Şehir</strong></label>
            <select name="mevzu[varsayilan_sehir]" class="regular-text form-control form-control-sm">
                <option value="">— Şehir Seçiniz —</option>
                <?php foreach ($this->get_cities_array() as $val => $label): ?>
                    <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">Hava durumu ve bölgesel özellikler için.</p>
        </div>
        
        <div class="mevzu-field">
            <label><strong>Site Ana Rengi</strong></label>
            <div class="mevzu-color-picker-wrapper">
                <input type="text" name="mevzu[site_rengi]" value="<?php echo esc_attr(get_option('options_site_rengi', '#e90808')); ?>" class="mevzu-color-picker">
                <div class="mevzu-color-presets">
                    <?php foreach ( mevzu_get_site_primary_color_presets() as $color ) : ?>
                        <span class="mevzu-preset-color" data-color="<?php echo esc_attr( $color ); ?>" style="background-color: <?php echo esc_attr( $color ); ?>" title="<?php echo esc_attr( $color ); ?>"></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="description">Sitenin ana vurgu rengini belirler.</p>
        </div>
        <?php
    }
    
    private function step_header() {
        ?>
        <h2>Header Şablonu</h2>
        <p>Sitenizin üst kısım tasarımını seçin. Daha sonra tekrar değiştirebilirsiniz.</p>
        
        <div class="mevzu-field">
            <label><strong>Header Şablonu</strong></label>
            <div class="mevzu-template-selector">
                <?php 
                $templates = array(
                    'sablon1' => 'Şablon 1 — Klasik Gazete',
                    'sablon2' => 'Şablon 2 — Modern',
                    'sablon3' => 'Şablon 3 — Minimalist',
                    // 'sablon4' => 'Şablon 4 — Kompakt',
                );
                foreach ($templates as $val => $label):
                ?>
                    <label class="mevzu-template-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="radio" name="mevzu[header_sablon]" value="<?php echo esc_attr($val); ?>" <?php echo $val === 'sablon2' ? 'checked' : ''; ?>>
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
        
        <div class="mevzu-field">
            <label><strong>Gösterilecek Kurlar</strong></label>
            <div class="mevzu-checkbox-group">
                <?php 
                $kur_choices = array(
                    'USD' => 'Dolar',
                    'EUR' => 'Euro',
                    'GBP' => 'Sterlin',
                    'GA' => 'Gram Altın',
                    'C' => 'Çeyrek Altın',
                    'ONS' => 'Ons Altın'
                );
                $selected_kurlar = get_option('options_kur_secimi', array('USD', 'EUR', 'GA'));
                foreach ($kur_choices as $val => $label): ?>
                    <label class="mevzu-checkbox-item">
                        <input type="checkbox" name="mevzu[kur_secimi][]" value="<?php echo $val; ?>" <?php echo in_array($val, $selected_kurlar) ? 'checked' : ''; ?>>
                        <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="description">Header üst alanında kayan kurlar.</p>
        </div>

        <div class="mevzu-field">
            <label><strong>Menü Pozisyonu</strong></label>
            <select name="mevzu[header_menu]">
                <option value="me-auto" selected>Sola Yasla</option>
                <option value="mx-auto">Ortala</option>
                <option value="ms-auto">Sağa Yasla</option>
            </select>
        </div>
        <?php
    }
    
    private function step_content() {
        ?>
        <h2>İçerik Ayarları</h2>
        <p>Haberlerin nasıl görüntüleneceğini belirleyin.</p>
        
        <div class="mevzu-field">
            <label><strong>Haberler Şablonu</strong></label>
            <div class="mevzu-template-selector">
                <?php 
                $news_templates = array(
                    '1' => 'Şablon 1 — Klasik Liste',
                    '2' => 'Şablon 2 — Modern Kart',
                    'sade' => 'Sade — Sidebarsız',
                );
                foreach ($news_templates as $val => $label):
                ?>
                    <label class="mevzu-template-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="radio" name="mevzu[sablon]" value="<?php echo esc_attr($val); ?>" <?php echo $val === '2' ? 'checked' : ''; ?>>
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
                            <span class="placeholder col-12"></span>
                            <span class="placeholder placeholder-lg col-5"></span>
                            <span class="placeholder col-12"></span>
                            <span class="placeholder col-12"></span>
                            <span class="placeholder col-11"></span>
                            <span class="placeholder col-12"></span>
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
                            <span class="placeholder col-12"></span>
                            <span class="placeholder placeholder-lg col-5"></span>
                            <span class="placeholder col-12"></span>
                            <span class="placeholder col-12"></span>
                            <span class="placeholder col-11"></span>
                            <span class="placeholder col-12"></span>
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
        
        <div class="mevzu-field mevzu-field-toggle">
            <label>
                <input type="checkbox" name="mevzu[sonsuz_kaydirma]" value="1" checked>
                <strong>Sonsuz Kaydırma</strong>
            </label>
            <p class="description">Haberlerde otomatik yükleme.</p>
        </div>
        
        <div class="mevzu-field mevzu-field-toggle">
            <label>
                <input type="checkbox" name="mevzu[haberlerde_etiket_gosterimi]" value="1" checked>
                <strong>Etiket Gösterimi</strong>
            </label>
        </div>
        
        <div class="mevzu-field">
            <label><strong>Köşe Yazıları Kategorisi</strong></label>
            <select name="mevzu[kose_yazilari_kategorisi]">
                <option value="">— Seçiniz —</option>
                <?php 
                $cats = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
                if (!is_wp_error($cats)):
                    foreach ($cats as $cat): ?>
                        <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach;
                endif; ?>
            </select>
        </div>
        <?php
    }
    
    private function step_social() {
        ?>
        <h2>Sosyal Medya</h2>
        <p>Sosyal medya hesaplarınızı ekleyin.</p>
        
        <?php
        $socials = array(
            'facebook'  => array('label' => 'Facebook', 'type' => 'url', 'placeholder' => 'https://facebook.com/...'),
            'twitter'   => array('label' => 'X (Twitter)', 'type' => 'url', 'placeholder' => 'https://twitter.com/...'),
            'instagram' => array('label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://instagram.com/...'),
            'youtube'   => array('label' => 'Youtube', 'type' => 'url', 'placeholder' => 'https://youtube.com/...'),
            'whatsapp'  => array('label' => 'WhatsApp', 'type' => 'text', 'placeholder' => '5XX XXX XX XX'),
        );
        foreach ($socials as $key => $info):
        ?>
        <div class="mevzu-field">
            <label><strong><?php echo $info['label']; ?></strong></label>
            <input type="<?php echo $info['type']; ?>" name="mevzu[<?php echo $key; ?>]" class="regular-text" placeholder="<?php echo $info['placeholder']; ?>">
        </div>
        <?php endforeach; ?>
        <?php
    }
    
    private function step_finish() {
        ?>
        <div class="mevzu-wizard-finish">
            <div class="text-center py-5 px-3">
                <h2 class="fs-2 mb-3">Her Şey Hazır!</h2>
                <p class="fs-5 text-secondary mx-auto mb-4" style="max-width:500px;">
                    Temanız başarıyla yapılandırıldı. Daha detaylı ayarlar için <strong>Mevzu² Ayarları</strong> menüsünü kullanabilirsiniz.
                </p>
                <div class="mt-4">
                    <a href="<?php echo home_url(); ?>" class="button button-secondary button-large" target="_blank">Sitenizi Görüntüleyin →</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_save() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');
        
        $data = isset($_POST['mevzu']) ? $_POST['mevzu'] : array();
        $step = isset($_POST['wizard_step']) ? intval($_POST['wizard_step']) : 1;
        
        foreach ($data as $key => $value) {
            update_option('options_' . sanitize_text_field($key), is_array($value) ? $value : sanitize_text_field($value));
        }
        
        if ($step >= $this->total_steps) {
            update_option('mevzu_setup_completed', true);
        }
        
        $next = min($step + 1, $this->total_steps);
        wp_send_json_success(array(
            'next_url' => $step >= $this->total_steps
                ? admin_url()
                : admin_url('admin.php?page=mevzu-setup-wizard&step=' . $next),
        ));
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

new Mevzu_Setup_Wizard();
