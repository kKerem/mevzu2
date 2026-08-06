<?php
/**
 * Otomasyon Menüsü — Popup & Ramazan
 * 
 * Üst menü: Otomasyon
 * Alt sayfalar:
 *   - Popup (Önemli gün popup'ları + genel duyuru)
 *   - Ramazan (Ramazan modu ayarları)
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Popup_Page {
    
    /**
     * Sabit tarihli resmi günler
     */
    private $sabit_gunler = array(
        'yilbasi'     => array('ad' => 'Yeni Yıl Kutlaması',                            'ay' => 1,  'gun' => 1,  'sure' => 1),
        'kadinlar'    => array('ad' => 'Dünya Kadınlar Günü',                            'ay' => 3,  'gun' => 8,  'sure' => 1),
        'canakkale'   => array('ad' => 'Çanakkale Zaferi ve Şehitleri Anma Günü',        'ay' => 3,  'gun' => 18, 'sure' => 1),
        'nevruz'      => array('ad' => 'Nevruz',                                         'ay' => 3,  'gun' => 21, 'sure' => 1),
        'cocuk'       => array('ad' => 'Ulusal Egemenlik ve Çocuk Bayramı',              'ay' => 4,  'gun' => 23, 'sure' => 1),
        'isci'        => array('ad' => 'Emek ve Dayanışma Günü',                         'ay' => 5,  'gun' => 1,  'sure' => 1),
        'genclik'     => array('ad' => "Atatürk'ü Anma, Gençlik ve Spor Bayramı",       'ay' => 5,  'gun' => 19, 'sure' => 1),
        'demokrasi'   => array('ad' => 'Demokrasi ve Milli Birlik Günü',                 'ay' => 7,  'gun' => 15, 'sure' => 1),
        'zafer'       => array('ad' => 'Zafer Bayramı',                                  'ay' => 8,  'gun' => 30, 'sure' => 1),
        'cumhuriyet'  => array('ad' => 'Cumhuriyet Bayramı',                             'ay' => 10, 'gun' => 29, 'sure' => 1),
        'ataturk'     => array('ad' => "Atatürk'ü Anma Günü",                           'ay' => 11, 'gun' => 10, 'sure' => 1),
    );
    
    /**
     * Dini bayramlar — Hicri takvime bağlı (2025-2030)
     */
    private function get_dini_bayramlar($yil = null) {
        if (!$yil) $yil = (int)date('Y');
        
        $veriler = array(
            2025 => array(
                'ramazan_baslangic' => '2025-03-01', 'ramazan_bitis' => '2025-03-29',
                'ramazan_bayrami'   => array('2025-03-30', '2025-04-01'),
                'kurban_bayrami'    => array('2025-06-06', '2025-06-09'),
            ),
            2026 => array(
                'ramazan_baslangic' => '2026-02-18', 'ramazan_bitis' => '2026-03-19',
                'ramazan_bayrami'   => array('2026-03-20', '2026-03-22'),
                'kurban_bayrami'    => array('2026-05-27', '2026-05-30'),
            ),
            2027 => array(
                'ramazan_baslangic' => '2027-02-08', 'ramazan_bitis' => '2027-03-08',
                'ramazan_bayrami'   => array('2027-03-09', '2027-03-11'),
                'kurban_bayrami'    => array('2027-05-16', '2027-05-19'),
            ),
            2028 => array(
                'ramazan_baslangic' => '2028-01-28', 'ramazan_bitis' => '2028-02-25',
                'ramazan_bayrami'   => array('2028-02-26', '2028-02-28'),
                'kurban_bayrami'    => array('2028-05-05', '2028-05-08'),
            ),
            2029 => array(
                'ramazan_baslangic' => '2029-01-16', 'ramazan_bitis' => '2029-02-13',
                'ramazan_bayrami'   => array('2029-02-14', '2029-02-16'),
                'kurban_bayrami'    => array('2029-04-24', '2029-04-27'),
            ),
            2030 => array(
                'ramazan_baslangic' => '2030-01-06', 'ramazan_bitis' => '2030-02-03',
                'ramazan_bayrami'   => array('2030-02-04', '2030-02-06'),
                'kurban_bayrami'    => array('2030-04-13', '2030-04-16'),
            ),
        );
        
        return isset($veriler[$yil]) ? $veriler[$yil] : $veriler[2026];
    }
    
    private function get_varsayilan_tarihler($yil = null) {
        if (!$yil) $yil = (int)date('Y');
        $dini = $this->get_dini_bayramlar($yil);
        $tarihler = array();
        
        foreach ($this->sabit_gunler as $key => $info) {
            $baslangic = sprintf('%04d-%02d-%02d', $yil, $info['ay'], $info['gun']);
            $bitis = date('Y-m-d', strtotime($baslangic . ' + ' . ($info['sure'] - 1) . ' days'));
            $tarihler[$key] = array('baslangic' => $baslangic, 'bitis' => $bitis);
        }
        
        $tarihler['ramazan_bayrami'] = array('baslangic' => $dini['ramazan_bayrami'][0], 'bitis' => $dini['ramazan_bayrami'][1]);
        $tarihler['kurban_bayrami']  = array('baslangic' => $dini['kurban_bayrami'][0],  'bitis' => $dini['kurban_bayrami'][1]);
        
        return $tarihler;
    }

    private function tum_gunler() {
        $gunler = $this->sabit_gunler;
        $gunler['ramazan_bayrami'] = array('ad' => 'Ramazan Bayramı', 'ay' => 0, 'gun' => 0, 'sure' => 3);
        $gunler['kurban_bayrami']  = array('ad' => 'Kurban Bayramı',  'ay' => 0, 'gun' => 0, 'sure' => 4);
        return $gunler;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('wp_ajax_mevzu_save_popup', array($this, 'ajax_save'));
        add_action('wp_ajax_mevzu_save_ramazan', array($this, 'ajax_save_ramazan'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_menu() {
        // Üst menü: Otomasyon
        add_menu_page(
            'Otomasyon',
            'Otomasyon',
            'publish_posts',
            'mevzu-otomasyon',
            array($this, 'render_popup_page'),
            'dashicons-calendar-alt',
            10
        );
        
        // Alt sayfa: Popup
        add_submenu_page(
            'mevzu-otomasyon',
            'Popup & Önemli Günler',
            'Popup',
            'publish_posts',
            'mevzu-otomasyon',
            array($this, 'render_popup_page')
        );
        
        // Alt sayfa: Ramazan
        add_submenu_page(
            'mevzu-otomasyon',
            'Ramazan Ayı',
            'Ramazan Ayı',
            'publish_posts',
            'mevzu-ramazan',
            array($this, 'render_ramazan_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'mevzu-otomasyon') === false && strpos($hook, 'mevzu-ramazan') === false) return;
        wp_enqueue_media();
        wp_enqueue_script('mevzu-settings', MEVZU_SETTINGS_URL . 'assets/settings.js', array('jquery'), MEVZU_THEME_VERSION, true);
        wp_localize_script('mevzu-settings', 'mevzuSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mevzu_popup_nonce'),
        ));
    }
    
    private function opt($key, $default = '') { return get_option('options_' . $key, $default); }
    
    // =========================================================================
    //  POPUP SAYFASI
    // =========================================================================
    public function render_popup_page() {
        $yil = (int)date('Y');
        $varsayilan = $this->get_varsayilan_tarihler($yil);
        $tum = $this->tum_gunler();
        ?>
        <div class="wrap mevzu-settings-wrap">
            <h1>Popup & Önemli Gün Otomasyonu</h1>
            <p class="description" style="font-size:14px;margin-bottom:20px">
                <?php echo $yil; ?> yılı için resmi tatiller, bayramlar ve genel duyuru popup'larını yönetin.
            </p>
            
            <form id="mevzu-settings-form" method="post" data-action="mevzu_save_popup">
                <?php wp_nonce_field('mevzu_popup_nonce', 'mevzu_nonce'); ?>
                
                <!-- GENEL POPUP -->
                <div class="mevzu-popup-section">
                    <h2>Genel Duyuru Popup</h2>
                    <p class="description">Özel günlerden bağımsız, istediğiniz zaman gösterilecek popup.</p>
                    <?php $this->render_popup_card('genel'); ?>
                </div>
                
                <!-- ÖNEMLI GÜN POPUP'LARI -->
                <div class="mevzu-popup-section">
                    <h2>Önemli Gün & Bayram Popup'ları — <?php echo $yil; ?></h2>
                    <p class="description">
                        Her bir gün için toggle'ı açın, popup görseli seçin ve gösterim ayarlayın.
                        Dini bayram tarihleri her yıl otomatik güncellenir; isterseniz manuel düzenleyebilirsiniz.
                    </p>
                    
                    <div class="mevzu-popup-grid">
                        <?php foreach ($tum as $key => $info): ?>
                            <?php 
                            $def_baslangic = isset($varsayilan[$key]) ? $varsayilan[$key]['baslangic'] : '';
                            $def_bitis = isset($varsayilan[$key]) ? $varsayilan[$key]['bitis'] : '';
                            $this->render_holiday_card($key, $info['ad'], $def_baslangic, $def_bitis);
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mevzu-settings-actions" style="margin-top:20px">
                    <button type="submit" class="button button-primary button-large" id="mevzu-save-settings">Tüm Popup Ayarlarını Kaydet</button>
                    <span class="mevzu-save-status"></span>
                </div>
            </form>
        </div>
        <?php $this->render_styles_scripts(); ?>
        <?php
    }
    
    // =========================================================================
    //  RAMAZAN SAYFASI
    // =========================================================================
    public function render_ramazan_page() {
        $yil = (int)date('Y');
        $dini = $this->get_dini_bayramlar($yil);
        ?>
        <div class="wrap mevzu-settings-wrap">
            <h1>Ramazan Ayı</h1>
            <p class="description" style="font-size:14px;margin-bottom:20px">
                Ramazan ayında imsak/iftar geri sayımını ve ilgili görünüm değişikliklerini buradan yönetibilirsiniz.
            </p>
            
            <form id="mevzu-settings-form" method="post" data-action="mevzu_save_ramazan">
                <?php wp_nonce_field('mevzu_popup_nonce', 'mevzu_nonce'); ?>
                
                <div class="mevzu-popup-section mevzu-ramazan-section">
                    <h2>Ramazan Ayı Ayarları</h2>
                    <p class="description">
                        <?php echo $yil; ?> Ramazan Ayı: 
                        <strong><?php echo date_i18n('d F', strtotime($dini['ramazan_baslangic'])); ?> – <?php echo date_i18n('d F Y', strtotime($dini['ramazan_bitis'])); ?></strong>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Ramazan Modu</th>
                            <td>
                                <label class="mevzu-switch">
                                    <input type="hidden" name="mevzu[ramazan_saatleri]" value="0">
                                    <input type="checkbox" name="mevzu[ramazan_saatleri]" value="1" <?php checked($this->opt('ramazan_saatleri'), '1'); ?>>
                                    <span class="slider round small"></span>
                                </label>
                                <span class="switch-label">Aktif</span>
                                <p class="description small">
                                    Aktif olduğunda ramazan boyunca tüm sayfalarda içeriğin üstünde yer alır.<br> 
                                </p>
                                <div class="tpl-vis-label cursor-default col-6 small mt-2">
                                    <div class="tpl-title mb-2 fw-normal text-uppercase text-muted fw-semibold small">Örnek Gösterim</div>
                                    <div class="tpl-vis h-45">
                                        <div class="d-flex justify-content-center align-items-center text-muted" style="flex:2">Header</div>
                                    </div>
                                    <div class="border border-primary bg-primary-subtle p-1 rounded mb-2">
                                        <div class="tpl-vis h-30 m-0 small-2">
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">Samsun</div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">İmsak<span class="ps-1 fw-semibold">07:02</span></div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">Güneş<span class="ps-1 fw-semibold">07:02</span></div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">İkindi<span class="ps-1 fw-semibold">07:02</span></div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">Akşam<span class="ps-1 fw-semibold">07:02</span></div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:1">Yatsı<span class="ps-1 fw-semibold">07:02</span></div>
                                            <div class="d-flex justify-content-center align-items-center" style="flex:2">İftara son 35dk 55sn</div>
                                        </div>
                                    </div>
                                    <div class="tpl-vis h-120 mb-0 small-2">
                                        <div class="d-flex justify-content-center align-items-center" style="flex:2;"></div>
                                        <div class="d-flex justify-content-center align-items-center" style="flex:1;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Ramazan Başlangıç</th>
                            <td>
                                <input type="date" name="mevzu[ramazan_baslangic]" 
                                       value="<?php echo esc_attr($this->opt('ramazan_baslangic', $dini['ramazan_baslangic'])); ?>" class="regular-text">
                                <p class="description">Varsayılan: <?php echo $dini['ramazan_baslangic']; ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Ramazan Bitiş</th>
                            <td>
                                <input type="date" name="mevzu[ramazan_bitis]" 
                                       value="<?php echo esc_attr($this->opt('ramazan_bitis', $dini['ramazan_bitis'])); ?>" class="regular-text">
                                <p class="description">Varsayılan: <?php echo $dini['ramazan_bitis']; ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="mevzu-popup-section d-inline-block">
                    <h2>Ramazan Takvimi</h2>
                    <p class="description mb-2">Dini bayram tarihleri Hicri takvime göre her yıl ~10-11 gün geriye kayar.</p>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Yıl</th>
                                <th>Ramazan Ayı</th>
                                <th>Ramazan Bayramı</th>
                                <th>Kurban Bayramı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($y = 2025; $y <= 2030; $y++):
                                $d = $this->get_dini_bayramlar($y);
                                $isCurrent = ($y === $yil);
                            ?>
                            <tr<?php echo $isCurrent ? ' style="font-weight:700;background:#f0f6fc"' : ''; ?>>
                                <td><?php echo $y; ?><?php echo $isCurrent ? ' ←' : ''; ?></td>
                                <td><?php echo date_i18n('d M', strtotime($d['ramazan_baslangic'])) . ' – ' . date_i18n('d M', strtotime($d['ramazan_bitis'])); ?></td>
                                <td><?php echo date_i18n('d M', strtotime($d['ramazan_bayrami'][0])) . ' – ' . date_i18n('d M', strtotime($d['ramazan_bayrami'][1])); ?></td>
                                <td><?php echo date_i18n('d M', strtotime($d['kurban_bayrami'][0])) . ' – ' . date_i18n('d M', strtotime($d['kurban_bayrami'][1])); ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mevzu-settings-actions" style="margin-top:20px">
                    <button type="submit" class="button button-primary button-large" id="mevzu-save-settings">Ramazan Ayarlarını Kaydet</button>
                    <span class="mevzu-save-status"></span>
                </div>
            </form>
        </div>
        <?php $this->render_styles_scripts(); ?>
        <?php
    }
    
    // =========================================================================
    //  RENDER HELPERS
    // =========================================================================
    
    private function render_popup_card($key) {
        $prefix = 'popup_' . $key . '_';
        $gorsel = $this->opt($prefix . 'gorsel');
        $gorsel_url = $gorsel && is_numeric($gorsel) ? wp_get_attachment_url($gorsel) : '';
        ?>
        <div class="mevzu-popup-card-general">
            <table class="form-table">
                <tr>
                    <th scope="row">Durum</th>
                    <td>
                        <label class="mevzu-switch">
                            <input type="hidden" name="mevzu[<?php echo $prefix; ?>aktif]" value="0">
                            <input type="checkbox" name="mevzu[<?php echo $prefix; ?>aktif]" value="1" <?php checked($this->opt($prefix . 'aktif'), '1'); ?>>
                            <span class="slider round"></span>
                        </label>
                        <span class="switch-label">Aktif</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Tarih Aralığı</th>
                    <td>
                        <div style="display:flex;gap:16px;align-items:center">
                            <div>
                                <label style="font-size:12px;color:#646970">Başlangıç</label><br>
                                <input type="date" name="mevzu[<?php echo $prefix; ?>baslangic]" 
                                       value="<?php echo esc_attr($this->opt($prefix . 'baslangic')); ?>">
                            </div>
                            <span style="margin-top:16px">→</span>
                            <div>
                                <label style="font-size:12px;color:#646970">Bitiş</label><br>
                                <input type="date" name="mevzu[<?php echo $prefix; ?>bitis]" 
                                       value="<?php echo esc_attr($this->opt($prefix . 'bitis')); ?>">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Popup Görseli</th>
                    <td class="mevzu-field-image">
                        <div class="mevzu-image-preview">
                            <?php if ($gorsel_url): ?><img src="<?php echo esc_url($gorsel_url); ?>" style="max-width:300px;border-radius:8px"><?php endif; ?>
                        </div>
                        <input type="hidden" name="mevzu[<?php echo $prefix; ?>gorsel]" value="<?php echo esc_attr($gorsel); ?>" class="mevzu-image-id">
                        <button type="button" class="button mevzu-image-select">Görsel Seç</button>
                        <button type="button" class="button mevzu-image-remove" <?php echo !$gorsel ? 'style="display:none"' : ''; ?>>Kaldır</button>
                        <button type="button" class="button mevzu-popup-preview-btn" <?php echo !$gorsel ? 'style="display:none"' : ''; ?>>Önizle</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Gösterim</th>
                    <td>
                        <select name="mevzu[<?php echo $prefix; ?>gosterim]">
                            <option value="once" <?php selected($this->opt($prefix . 'gosterim', 'once'), 'once'); ?>>Günde 1 kez göster</option>
                            <option value="always" <?php selected($this->opt($prefix . 'gosterim'), 'always'); ?>>Sürekli göster</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    private function render_holiday_card($key, $title, $def_baslangic, $def_bitis) {
        $prefix = 'popup_' . $key . '_';
        $aktif = $this->opt($prefix . 'aktif');
        $baslangic = $this->opt($prefix . 'baslangic', $def_baslangic);
        $bitis = $this->opt($prefix . 'bitis', $def_bitis);
        $gorsel = $this->opt($prefix . 'gorsel');
        $gorsel_url = $gorsel && is_numeric($gorsel) ? wp_get_attachment_url($gorsel) : '';
        $gosterim = $this->opt($prefix . 'gosterim', 'once');
        
        $tarih_str = '';
        if ($baslangic) {
            $tarih_str = date_i18n('d M', strtotime($baslangic));
            if ($bitis && $bitis !== $baslangic) {
                $tarih_str .= ' – ' . date_i18n('d M', strtotime($bitis));
            }
        }
        ?>
        <div class="mevzu-holiday-card <?php echo $aktif ? 'active' : ''; ?>">
            <div class="card-header">
                <h4 class="card-title"><?php echo esc_html($title); ?></h4>
                <label class="mevzu-switch">
                    <input type="hidden" name="mevzu[<?php echo $prefix; ?>aktif]" value="0">
                    <input type="checkbox" class="holiday-toggle" name="mevzu[<?php echo $prefix; ?>aktif]" value="1" <?php checked($aktif, '1'); ?>>
                    <span class="slider round"></span>
                </label>
            </div>
            <?php if ($tarih_str): ?>
            <div style="margin:-8px 0 12px;font-size:13px;color:#646970"><span class="dashicons dashicons-calendar"></span> <?php echo $tarih_str; ?></div>
            <?php endif; ?>
            <div class="card-body">
                <div class="card-dates">
                    <div class="card-field">
                        <label>Başlangıç</label>
                        <input type="date" name="mevzu[<?php echo $prefix; ?>baslangic]" value="<?php echo esc_attr($baslangic); ?>">
                    </div>
                    <div class="card-field">
                        <label>Bitiş</label>
                        <input type="date" name="mevzu[<?php echo $prefix; ?>bitis]" value="<?php echo esc_attr($bitis); ?>">
                    </div>
                </div>
                <div class="card-field mevzu-field-image">
                    <label>Popup Görseli</label>
                    <div class="card-image-preview mevzu-image-preview">
                        <?php if ($gorsel_url): ?><img src="<?php echo esc_url($gorsel_url); ?>"><?php endif; ?>
                    </div>
                    <input type="hidden" name="mevzu[<?php echo $prefix; ?>gorsel]" value="<?php echo esc_attr($gorsel); ?>" class="mevzu-image-id">
                    <button type="button" class="button button-small mevzu-image-select">Görsel Seç</button>
                    <button type="button" class="button button-small mevzu-image-remove" <?php echo !$gorsel ? 'style="display:none"' : ''; ?>>Kaldır</button>
                    <button type="button" class="button button-small mevzu-popup-preview-btn" <?php echo !$gorsel ? 'style="display:none"' : ''; ?>>Önizle</button>
                </div>
                <div class="card-field">
                    <label>Gösterim</label>
                    <select name="mevzu[<?php echo $prefix; ?>gosterim]">
                        <option value="once" <?php selected($gosterim, 'once'); ?>>Günde 1 kez göster</option>
                        <option value="always" <?php selected($gosterim, 'always'); ?>>Sürekli göster</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }
    
    // =========================================================================
    //  CSS & JS (iki sayfa da kullanır)
    // =========================================================================
    private function render_styles_scripts() {
        ?>
        <style>
        .mevzu-popup-section {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e4e7;
            padding: 1rem;
            margin-bottom: 24px;
        }
        .mevzu-popup-section h2 {
            margin: 0 0 8px;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 12px;
        }
        .mevzu-popup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .mevzu-holiday-card {
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 10px;
            padding: 20px;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .mevzu-holiday-card.active {
            border-color: #2271b1;
            background: #f0f6fc;
            box-shadow: 0 2px 8px rgba(34,113,177,0.1);
        }
        .mevzu-holiday-card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }
        .mevzu-holiday-card .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #1d2327;
            margin: 0;
        }
        .mevzu-holiday-card .card-body { display: none; }
        .mevzu-holiday-card.active .card-body { display: block; }
        .mevzu-holiday-card .card-field { margin-bottom: 12px; }
        .mevzu-holiday-card .card-field label {
            display: block; font-weight: 600; font-size: 12px;
            color: #646970; margin-bottom: 4px; text-transform: uppercase;
        }
        .mevzu-holiday-card .card-field input[type="date"],
        .mevzu-holiday-card .card-field select {
            width: 100%; padding: 6px 10px; border: 1px solid #dcdcde;
            border-radius: 6px; font-size: 13px;
        }
        .mevzu-holiday-card .card-dates { display: flex; gap: 12px; }
        .mevzu-holiday-card .card-dates .card-field { flex: 1; }
        .mevzu-holiday-card .card-image-preview img {
            max-width: 100%; max-height: 120px; border-radius: 6px;
            border: 1px solid #dcdcde; margin-bottom: 8px;
        }
        .mevzu-popup-card-general .card-field { margin-bottom: 14px; }
        @media (max-width:782px) {
            .mevzu-popup-grid { grid-template-columns: 1fr; }
            .mevzu-holiday-card .card-dates { flex-direction: column; gap: 8px; }
        }
        /* Popup Preview Modal */
        .mevzu-popup-preview-btn { margin-left: 4px !important; }
        .mevzu-popup-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 999999; align-items: center; justify-content: center;
            padding: 20px; animation: mevzuFadeIn 0.2s ease;
        }
        .mevzu-popup-overlay.show { display: flex; }
        @keyframes mevzuFadeIn { from { opacity: 0; } to { opacity: 1; } }
        .mevzu-popup-modal {
            position: relative; max-width: 800px; width: 100%; text-align: center;
            animation: mevzuSlideUp 0.3s ease;
        }
        @keyframes mevzuSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .mevzu-popup-modal img {
            max-width: 100%; max-height: 80vh; border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        .mevzu-popup-modal .close-btn {
            position: absolute; top: -12px; right: -12px; width: 32px; height: 32px;
            background: #fff; border: 2px solid #6c757d; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .mevzu-popup-modal .close-btn:hover { transform: scale(1.1); }
        .mevzu-popup-modal .preview-label {
            display: inline-block; margin-top: 12px; background: rgba(255,255,255,0.9);
            color: #333; padding: 4px 16px; border-radius: 20px; font-size: 12px;
            font-weight: 600;
        }
        </style>

        <!-- Popup Önizleme Modal -->
        <div class="mevzu-popup-overlay" id="mevzuPopupPreview">
            <div class="mevzu-popup-modal">
                <button type="button" class="close-btn" id="mevzuPreviewClose">✕</button>
                <img src="" alt="Popup Önizleme" id="mevzuPreviewImage">
                <div class="preview-label">📱 Popup Önizleme — Ziyaretçiler görseli bu şekilde görecek</div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Holiday toggle
            $(document).on('change', '.holiday-toggle', function() {
                var $card = $(this).closest('.mevzu-holiday-card');
                $card.toggleClass('active', this.checked);
            });
            $('.holiday-toggle:checked').each(function() {
                $(this).closest('.mevzu-holiday-card').addClass('active');
            });

            // Popup Önizleme — footer.php'deki gerçek modal yapısıyla aynı görünüm
            $(document).on('click', '.mevzu-popup-preview-btn', function(e) {
                e.preventDefault();
                var $container = $(this).closest('.mevzu-field-image, td');
                var $img = $container.find('.mevzu-image-preview img');
                if ($img.length && $img.attr('src')) {
                    $('#mevzuPreviewImage').attr('src', $img.attr('src'));
                    $('#mevzuPopupPreview').addClass('show');
                } else {
                    alert('Önce bir görsel seçin.');
                }
            });

            // Kapat
            $('#mevzuPreviewClose').on('click', function() {
                $('#mevzuPopupPreview').removeClass('show');
            });
            $('#mevzuPopupPreview').on('click', function(e) {
                if (e.target === this) $(this).removeClass('show');
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') $('#mevzuPopupPreview').removeClass('show');
            });

            // Görsel seçildikten sonra Önizle butonunu göster/gizle
            var origSelect = $(document).data('events') || {};
            $(document).on('click', '.mevzu-image-select', function() {
                var $container = $(this).closest('.mevzu-field-image, td');
                // Kısa gecikmeyle kontrol et (WP media seçimi sonrası)
                setTimeout(function() {
                    var hasImage = $container.find('.mevzu-image-id').val();
                    $container.find('.mevzu-popup-preview-btn').toggle(!!hasImage);
                }, 500);
            });
            $(document).on('click', '.mevzu-image-remove', function() {
                $(this).siblings('.mevzu-popup-preview-btn').hide();
            });
        });
        </script>
        <?php
    }
    
    // =========================================================================
    //  STATIC: Template'lerden çağrılır
    // =========================================================================
    public static function get_active_popup() {
        $today = date('Y-m-d');
        $tum_gunler = array(
            'yilbasi', 'kadinlar', 'canakkale', 'nevruz', 'cocuk', 'isci',
            'genclik', 'demokrasi', 'zafer', 'cumhuriyet', 'ataturk',
            'ramazan_bayrami', 'kurban_bayrami', 'genel'
        );
        
        foreach ($tum_gunler as $key) {
            $prefix = 'popup_' . $key . '_';
            if (get_option('options_' . $prefix . 'aktif', '0') !== '1') continue;
            $baslangic = get_option('options_' . $prefix . 'baslangic', '');
            $bitis = get_option('options_' . $prefix . 'bitis', '');
            if (!$baslangic || !$bitis) continue;
            if ($today < $baslangic || $today > $bitis) continue;
            $gorsel = get_option('options_' . $prefix . 'gorsel', '');
            if (!$gorsel) continue;
            
            return array(
                'key'        => $key,
                'gorsel'     => $gorsel,
                'gorsel_url' => wp_get_attachment_url($gorsel),
                'gosterim'   => get_option('options_' . $prefix . 'gosterim', 'once'),
            );
        }
        
        // Eski format uyumluluğu
        if (get_option('options_popup_aktifmi', '0') === '1') {
            $gorsel = get_option('options_popup_gorsel', '');
            if ($gorsel) {
                return array(
                    'key'        => 'legacy',
                    'gorsel'     => $gorsel,
                    'gorsel_url' => wp_get_attachment_url($gorsel),
                    'gosterim'   => get_option('options_popup_secim', '1') === '1' ? 'once' : 'always',
                );
            }
        }
        
        return null;
    }
    
    // =========================================================================
    //  AJAX KAYDETME
    // =========================================================================
    public function ajax_save() {
        check_ajax_referer('mevzu_popup_nonce', 'nonce');
        if (!current_user_can('publish_posts')) wp_send_json_error('Yetkiniz yok');
        $data = isset($_POST['mevzu']) ? $_POST['mevzu'] : array();
        foreach ($data as $key => $value) {
            $sanitized_key = sanitize_text_field($key);
            $value = (strpos($sanitized_key, 'gorsel') !== false) ? absint($value) : sanitize_text_field($value);
            update_option('options_' . $sanitized_key, $value);
        }
        wp_send_json_success('Popup ayarları kaydedildi');
    }
    
    public function ajax_save_ramazan() {
        check_ajax_referer('mevzu_popup_nonce', 'nonce');
        if (!current_user_can('publish_posts')) wp_send_json_error('Yetkiniz yok');
        $data = isset($_POST['mevzu']) ? $_POST['mevzu'] : array();
        foreach ($data as $key => $value) {
            update_option('options_' . sanitize_text_field($key), sanitize_text_field($value));
        }
        wp_send_json_success('Ramazan ayarları kaydedildi');
    }
}

new Mevzu_Popup_Page();
