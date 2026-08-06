<?php
/**
 * Mevzu² İçe/Dışa Aktar Sistemi
 *
 * Tema ayarlarını JSON formatında dışa/içe aktarma.
 * Kullanıcılar grupları seçerek veya tümünü seçerek export edebilir.
 *
 * Bakım: Ayarlar paneline veya blok AJAX ile kaydedilen **kalıcı site ayarları** (`options_*`, tema içi `mevzu_*` seçenekleri) kullanıcı tarafından yedeklenebildiği için İçe/Dışa Aktara da dahil edilmelidir.
 * Yeni anahtar: `Mevzu_Import_Export::get_export_groups()` içindeki uygun grubun `keys` dizisine ekleyin (`.cursor/rules/mevzu-import-export.mdc`).
 */

if (!defined('ABSPATH')) exit;

class Mevzu_Import_Export {

    /**
     * Export grupları ve ilişkili option key'leri
     */
    private static function get_export_groups() {
        return array(
            'genel' => array(
                'label' => 'Genel Ayarlar',
                'icon'  => '',
                'keys'  => array(
                    'options_logo', 'options_logo_dark', 'options_logo_mobil', 'options_logo_mobil_dark',
                    'options_favicon', 'options_site_rengi', 'options_varsayilan_sehir',
                    'options_kose_yazilari_kategorisi', 'options_video_kategorisi',
                    'options_ilginizi_cekebilecek_haber_sayisi', 'options_google_news',
                    'options_kunye_sayfasi', 'options_iletisim_sayfasi', 'options_gizlilik_politikasi_sayfasi',
                    'options_akis_sayfasi', 'options_finans_sayfasi', 'options_havadurumu_sayfasi',
                    'options_namaz_vakitleri_sayfasi', 'options_sondakika_sayfasi',
                    'options_yazarlar_sayfasi', 'options_yol_durumu_sayfasi', 'options_nobetci_eczaneler_sayfasi',
                ),
            ),
            'header' => array(
                'label' => 'Header Ayarları',
                'icon'  => '',
                'keys'  => array(
                    'options_header_sablon', 'options_header_menu',
                    'options_kur_secimi', 'options_son_dakika_goster', 'options_son_dakika_haber_sayisi', 'options_header_alan',
                ),
            ),
            'manset' => array(
                'label' => 'Manşet Ayarları',
                'icon'  => '',
                'keys'  => array(
                    'options_ust_manset_yeni_goster', 'options_ust_manset_yeni_slider_sayisi',
                    'options_ust_manset_ust_manset_ayarlari', 'options_ust_manset_slider_sayisi',
                    'options_manset_slider_sayisi',
                    'options_manset_slider_modeli', 'options_manset_slider_renk',
                    'options_manset_slider_basliklari', 'options_manset_baslik_boyutu', 'options_manset_baslik_hizasi',
                    'options_alt_manset_alt_manseti_goster',
                    'options_alt_manset_slider_sayisi', 'options_alt_manset_slider_modeli', 'options_alt_manset_slider_renk',
                    'options_alt_manset_slider_basliklari', 'options_alt_manset_baslik_boyutu', 'options_alt_manset_baslik_hizasi',
                    'options_yan_manset_tip',
                    'options_ana_kategori_grup_ana_kategori', 'options_ana_kategori_grup_ana_kategori_title',
                    'options_ana_kategori_grup_ana_kategori_titlecheck',
                ),
            ),
            'arsiv' => array(
                'label' => 'Arşiv Ayarları',
                'icon'  => '',
                'keys'  => array(
                    'options_archive_manset_goster',
                    'options_archive_manset_slider_sayisi',
                    'options_archive_manset_slider_modeli', 'options_archive_manset_slider_renk',
                    'options_archive_manset_slider_basliklari', 'options_archive_manset_baslik_boyutu', 'options_archive_manset_baslik_hizasi',
                ),
            ),
            'yapay_zeka' => array(
                'label' => 'Mevzu² AI',
                'icon'  => '',
                'keys'  => self::get_yapay_zeka_keys(),
            ),
            'icerik' => array(
                'label' => 'İçerik Ayarları',
                'icon'  => '',
                'keys'  => array(
                    'options_sablon', 'options_detaylar', 'options_detaylar_koseyazisi',
                    'options_anasayfa_son_haberler', 'options_anasayfa_son_haberler_sayisi',
                    'options_bolum_uclu_kat_1', 'options_bolum_uclu_kat_2', 'options_bolum_uclu_kat_3',
                    'options_bolum_uclu_goster',
                    'options_sonsuz_kaydirma', 'options_haberlerde_etiket_gosterimi',
                    'options_bizi_takip_edin_bolumu', 'options_gosterilecek_sosyal_medya_hesaplari',
                    'options_ramazan_saatleri',
                    'options_ilangovtr', 'options_ilangovtr_embed',
                    'options_yenileme', 'options_yenileme_suresi',
                ),
            ),
            'footer' => array(
                'label' => 'Footer Ayarları',
                'icon'  => '',
                'keys'  => array(
                    'options_footer_menu_1_title', 'options_footer_menu_1',
                    'options_footer_menu_2_title', 'options_footer_menu_2',
                    'options_footer_menu_3_title', 'options_footer_menu_3',
                    'options_footer_menu_4_title', 'options_footer_menu_4',
                    'options_footer_text', 'options_footer_unvan', 'options_footer_alan',
                ),
            ),
            'sosyal' => array(
                'label' => 'Sosyal Medya',
                'icon'  => '',
                'keys'  => array(
                    'options_facebook', 'options_twitter', 'options_instagram',
                    'options_youtube', 'options_whatsapp',
                ),
            ),
            'bloklar' => array(
                'label' => 'Anasayfa Blokları',
                'icon'  => '',
                'keys'  => array(), // Dinamik — blok sayısına göre
                'dynamic' => true,
            ),
            'eklentiler' => array(
                'label' => 'Modül Durumları',
                'icon'  => '',
                'keys'  => array('mevzu_modules'),
            ),
            'tema_guncelleme' => array(
                'label' => 'Güncelleme Tercihleri',
                'icon'  => '',
                'keys'  => array(
                    'options_tema_otomatik_guncelle',
                ),
            ),
            'hiz_guvenlik' => array(
                'label' => 'Hız & Güvenlik',
                'icon'  => '',
                'keys'  => array(
                    // Görsel Optimizasyonu
                    'bulk_image_resizer',
                    // XML-RPC & Güvenlik (disable-xml-rpc-api uyumlu)
                    'dsxmlrpc-settings',
                    // Admin URL Gizleme (wps-hide-login uyumlu)
                    'whl_page',
                    'whl_redirect_admin',
                    'mevzu_whl_enabled',
                ),
            ),
            'firma_rehberi' => array(
                'label' => 'Firma Rehberi',
                'icon'  => '',
                'keys'  => array(
                    'firma_rehberi_settings',
                ),
            ),
            'reklamlar' => array(
                'label'  => 'Reklam Sistemi',
                'icon'   => '',
                'keys'   => array(), // Dinamik — reklam alanlarına göre
                'dynamic'=> true,
            ),
        );
    }

    /**
     * Yapay Zeka manşeti + ses sentezi (TTS) ayar anahtarları.
     */
    private static function get_yapay_zeka_keys() {
        return array(
            'options_yapay_zeka_manseti_goster',
            'options_yapay_zeka_manseti_baslik',
            'options_yapay_zeka_manseti_baslangic_cumlesi',
            'options_yapay_zeka_manseti_bitis_cumlesi',
            'kkerem_tts_category_id',
            'kkerem_tts_update_mode',
            'kkerem_tts_voice_name',
            'kkerem_tts_language_code',
            'kkerem_tts_speaking_rate',
            'kkerem_tts_pitch',
            'kkerem_tts_debug_enabled',
            'kkerem_tts_category_audio_retention_days',
            'kkerem_tts_yzm_audio_retention_days',
        );
    }

    /**
     * Anasayfa blokları anahtarlarını dinamik olarak topla
     */
    private static function get_block_keys() {
        $keys = array('options_bloklar');
        $count = intval(get_option('options_bloklar', 0));
        
        for ($i = 0; $i < $count; $i++) {
            $keys[] = 'options_bloklar_' . $i . '_goruntuleme_sablonu';
            $keys[] = 'options_bloklar_' . $i . '_tekli_blok';
            $keys[] = 'options_bloklar_' . $i . '_haber_sayisi';
            $keys[] = 'options_bloklar_' . $i . '_ikili_blok';
        }
        
        return $keys;
    }

    /**
     * Ana Kategori blok anahtarlarını dinamik olarak topla
     */
    private static function get_ana_kat_keys() {
        $keys  = array('options_ana_kat_bloklar');
        $count = intval( get_option('options_ana_kat_bloklar', 0) );
        for ( $i = 0; $i < $count; $i++ ) {
            $keys[] = 'options_ana_kat_' . $i . '_sablon';
            $keys[] = 'options_ana_kat_' . $i . '_baslik';
            $keys[] = 'options_ana_kat_' . $i . '_haberler_metni';
            $keys[] = 'options_ana_kat_' . $i . '_kategori';
            $keys[] = 'options_ana_kat_' . $i . '_haber_sayisi';
        }
        return $keys;
    }

    /**
     * Bloklar sekmesindeki ek anahtarlar (İçe/Dışa Aktar — Anasayfa Blokları grubu ile birlikte).
     */
    private static function get_bloklar_tab_extra_keys() {
        return array(
            'options_yazar_kosesi_goster',
            'options_video_haberleri_goster',
            'options_sidebar_goster',
        );
    }

    /**
     * Reklam Sistemi alan anahtarlarını dinamik olarak topla
     */
    private static function get_ad_keys() {
        $keys = array(
            'options_ust_reklam_alani',
            'options_ust_reklam_goruntuleme',
            'options_ust_reklam_tip',
            'options_yan_reklam_alani',
            'options_yan_reklam_goruntuleme',
            'options_yan_reklam_fixed_sol',
            'options_yan_reklam_fixed_sag',
        );
        if (class_exists('Mevzu_Ads_Manager')) {
            $zones = Mevzu_Ads_Manager::zones();
            foreach ($zones as $id => $meta) {
                $keys[] = 'mevzu_ad_' . $id;
            }
        }
        return $keys;
    }

    public function __construct() {
        add_action('wp_ajax_mevzu_export_settings', array($this, 'ajax_export'));
        add_action('wp_ajax_mevzu_import_settings', array($this, 'ajax_import'));
    }

    /**
     * AJAX — Seçili grupları JSON olarak dışa aktar
     */
    public function ajax_export() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');

        $selected_groups = isset($_POST['groups']) ? (array) $_POST['groups'] : array();
        
        if (empty($selected_groups)) {
            wp_send_json_error('En az bir grup seçilmelidir.');
        }

        $groups = self::get_export_groups();
        $export_data = array(
            '_export_info' => array(
                'theme'      => 'Mevzu²',
                'version'    => defined('MEVZU_THEME_VERSION') ? MEVZU_THEME_VERSION : '1.0.0',
                'site_url'   => get_site_url(),
                'export_date' => current_time('Y-m-d H:i:s'),
                'groups'     => $selected_groups,
            ),
        );

        foreach ($selected_groups as $group_key) {
            if (!isset($groups[$group_key])) continue;
            
            $group = $groups[$group_key];
            $keys = $group['keys'];
            
            // Dinamik gruplar
            if (!empty($group['dynamic'])) {
                if ($group_key === 'bloklar') {
                    $keys = array_merge(
                        self::get_block_keys(),
                        self::get_ana_kat_keys(),
                        self::get_bloklar_tab_extra_keys()
                    );
                } elseif ($group_key === 'reklamlar') {
                    $keys = self::get_ad_keys();
                }
            }
            
            $group_data = array();
            foreach ($keys as $key) {
                $value = get_option($key);
                if ($value !== false) {
                    $group_data[$key] = $value;
                }
            }
            
            $export_data[$group_key] = $group_data;
        }

        wp_send_json_success($export_data);
    }

    /**
     * AJAX — JSON'dan ayarları içe aktar
     */
    public function ajax_import() {
        check_ajax_referer('mevzu_settings_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Yetkiniz yok');

        // Dosya kontrolü
        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Geçerli bir JSON dosyası yükleyin.');
        }

        $file = $_FILES['import_file'];
        
        // Dosya türü kontrolü
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            wp_send_json_error('Sadece .json dosyaları kabul edilir.');
        }

        // Dosya boyutu kontrolü (5MB max)
        if ($file['size'] > 5242880) {
            wp_send_json_error('Dosya boyutu 5MB\'ı aşamaz.');
        }

        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Geçersiz JSON dosyası.');
        }

        // Mevzu² export dosyası mı kontrol et
        if (!isset($data['_export_info']) || !isset($data['_export_info']['theme']) || $data['_export_info']['theme'] !== 'Mevzu²') {
            wp_send_json_error('Bu dosya geçerli bir Mevzu² export dosyası değil.');
        }

        $groups = self::get_export_groups();
        $imported_count  = 0;
        $imported_groups = array();
        $hiz_guvenlik_imported    = false;
        $firma_rehberi_imported   = false;

        foreach ($data as $group_key => $group_data) {
            if ($group_key === '_export_info') continue;
            if (!is_array($group_data)) continue;

            // Lisans anahtarı import edilmez
            if ($group_key === 'lisans') continue;

            foreach ($group_data as $option_key => $option_value) {
                // Lisans ile ilgili key'leri atla
                if (strpos($option_key, 'license') !== false || strpos($option_key, 'lisans') !== false) continue;

                update_option($option_key, $option_value);
                $imported_count++;
            }

            if ($group_key === 'hiz_guvenlik') {
                $hiz_guvenlik_imported = true;
            }
            if ($group_key === 'firma_rehberi') {
                $firma_rehberi_imported = true;
            }

            $imported_groups[] = $group_key;
        }

        // Hız & Güvenlik grubunu içe aktardıysak .htaccess + wps-hide-login durumunu güncelle
        if ($hiz_guvenlik_imported) {
            $sec_opts = get_option('dsxmlrpc-settings', array());
            if (is_array($sec_opts) && class_exists('bulk_image_resizer\Bulk_image_resizer_admin')) {
                bulk_image_resizer\Bulk_image_resizer_admin::apply_security_options($sec_opts);
            }
        }

        // Firma Rehberi içe aktarıldıysa rewrite flush yap
        if ($firma_rehberi_imported) {
            flush_rewrite_rules();
        }

        if ( function_exists( 'mevzu_yz_import_normalize_options' ) ) {
            mevzu_yz_import_normalize_options();
        }

        // Cache temizle
        delete_transient('kkerem_theme_gist_content');
        for ($i = 0; $i < 20; $i++) {
            delete_transient('sablon1_sorgusu_' . $i);
            delete_transient('sablon2_sorgusu_' . $i);
            delete_transient('sablon3_sorgusu_' . $i);
        }
        delete_transient('resmi_ilanlar_sorgusu');

        $export_info = $data['_export_info'];
        wp_send_json_success(array(
            'message' => sprintf(
                '%d ayar başarıyla içe aktarıldı (%s grubundan).',
                $imported_count,
                implode(', ', $imported_groups)
            ),
            'source_site' => $export_info['site_url'] ?? '',
            'source_version' => $export_info['version'] ?? '',
            'source_date' => $export_info['export_date'] ?? '',
        ));
    }

    // ============================================================
    //  İÇE/DIŞA AKTAR TAB İÇERİĞİ (RENDER)
    // ============================================================

    /**
     * Tab içeriğini render et
     */
    public static function render_import_export_section() {
        $groups = self::get_export_groups();
        ?>
        <h2 class="mb-3 pb-3">İçe / Dışa Aktar</h2>
        <p class="description">Bu sayfadan tema ayarlarınızı yedekleyebilir ve geri yükleyebilirsiniz.</p>


        <div class="row mt-3">
            <div class="col-12 col-md">
                <!-- DIŞA AKTARMA -->
                <h3 class="mt-0">Dışa Aktar (Export)</h3>
                
                <label class="d-inline-flex align-items-center gap-2 p-2 px-3 border rounded-3 cursor-pointer mevzu-export-group-label mb-3 fw-semibold">
                    <input type="checkbox" id="mevzu-export-all" class="form-check-input">
                    Tümünü Seç
                </label>
                
                <div class="row g-2 mb-3" id="mevzu-export-groups">
                    <?php foreach ($groups as $key => $group): ?>
                        <div class="col-12 col-md-3">
                            <label class="d-flex align-items-center gap-2 p-2 px-3 border rounded-3 cursor-pointer mevzu-export-group-label">
                                <input type="checkbox" name="export_group[]" value="<?php echo esc_attr($key); ?>" class="mevzu-export-group-cb form-check-input">
                                <?php echo esc_html($group['label']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="button button-primary" id="mevzu-do-export">
                    <span class="dashicons dashicons-download align-middle me-1"></span>
                    Seçilenleri Dışa Aktar
                </button>
                <span id="export-status" class="ms-3 small"></span>
            </div>
            <div class="col-12 col-md">
                <!-- İÇE AKTARMA -->
                <h3 class="mt-0">İçe Aktar (Import)</h3>
                
                <div class="my-3">
                    <div id="mevzu-import-dropzone" class="border border-2 border-dashed rounded-3 p-5 text-center cursor-pointer bg-white" style="border-style:dashed">
                        <span class="dashicons dashicons-upload text-muted" style="font-size:36px;width:36px;height:36px"></span>
                        <p class="mt-2 mb-1 fw-semibold text-secondary">JSON dosyasını sürükleyip bırakın</p>
                        <p class="mb-0 small text-muted">veya dosya seçmek için tıklayın</p>
                        <input type="file" id="mevzu-import-file" accept=".json" class="d-none">
                    </div>
                    
                    <div id="mevzu-import-preview" class="d-none mt-3 bg-white border rounded-3 p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <strong id="import-file-name"></strong>
                                <span id="import-file-size" class="text-muted ms-2 small"></span>
                            </div>
                            <button type="button" class="button button-link-delete" id="mevzu-import-cancel"><i class="ri-delete-bin-2-line me-2"></i>İptal</button>
                        </div>
                        <div id="import-file-info" class="small text-muted"></div>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="button button-primary" id="mevzu-do-import" disabled>
                        <span class="dashicons dashicons-upload align-middle me-1"></span>
                        İçe Aktar
                    </button>
                    <span id="import-status" class="small"></span>
                </div>
                
                <div class="alert alert-warning mt-3 small p-2 d-flex align-items-center">
                    <i class="ri-error-warning-line fs-6 me-2"></i>
                    <span>
                        <strong>Dikkat:</strong> İçe aktarma işlemi mevcut ayarlarınızın üzerine yazacaktır. Önce mevcut ayarlarınızı dışa aktarmanız önerilir.
                    </span>
                </div>
            </div>
        </div>

        <style>
        .mevzu-export-group-label:hover { border-color: #2271b1 !important; background: #f0f6fc !important; }
        .mevzu-export-group-label:has(input:checked) { border-color: #2271b1 !important; background: #f0f6fc !important; }
        #mevzu-import-dropzone:hover,
        #mevzu-import-dropzone.dragover { border-color: #2271b1 !important; background: #f0f6fc !important; }
        .cursor-pointer { cursor: pointer; }
        </style>

        <script>
        jQuery(function($) {
            
            // ===================== TÜMÜNÜ SEÇ =====================
            $('#mevzu-export-all').on('change', function() {
                var checked = $(this).is(':checked');
                $('.mevzu-export-group-cb').prop('checked', checked);
            });
            
            // Alt checkbox'lardan biri değişirse master'ı güncelle
            $(document).on('change', '.mevzu-export-group-cb', function() {
                var total = $('.mevzu-export-group-cb').length;
                var checked = $('.mevzu-export-group-cb:checked').length;
                $('#mevzu-export-all').prop('checked', total === checked);
            });

            // ===================== DIŞA AKTARMA =====================
            $('#mevzu-do-export').on('click', function() {
                var $btn = $(this);
                var $status = $('#export-status');
                var selectedGroups = [];
                
                $('.mevzu-export-group-cb:checked').each(function() {
                    selectedGroups.push($(this).val());
                });
                
                if (!selectedGroups.length) {
                    $status.text('En az bir grup seçin.').css('color', '#dba617');
                    return;
                }
                
                $btn.prop('disabled', true);
                $status.text('Hazırlanıyor...').css('color', '#646970');
                
                $.post(mevzuSettings.ajaxUrl, {
                    action: 'mevzu_export_settings',
                    nonce: mevzuSettings.nonce,
                    groups: selectedGroups
                }, function(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        // JSON dosyasını indir
                        var jsonStr = JSON.stringify(res.data, null, 2);
                        var blob = new Blob([jsonStr], { type: 'application/json' });
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement('a');
                        var siteName = window.location.hostname.replace(/\./g, '-');
                        var date = new Date().toISOString().slice(0,10);
                        link.href = url;
                        link.download = 'mevzu2-ayarlar-' + date + '.json';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                        
                        $status.text('Dışa aktarma başarılı!').css('color', '#00a32a');
                        setTimeout(function(){ $status.text(''); }, 3000);
                    } else {
                        $status.text('Hata: ' + res.data).css('color', '#d63638');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $status.text('Bağlantı hatası').css('color', '#d63638');
                });
            });

            // ===================== İÇE AKTARMA =====================
            var importFile = null;
            
            // Drag & Drop
            var $dropzone = $('#mevzu-import-dropzone');
            
            $dropzone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            }).on('dragleave drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            }).on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length) handleImportFile(files[0]);
            }).on('click', function(e) {
                if (e.target.id !== 'mevzu-import-file') {
                    $('#mevzu-import-file').trigger('click');
                }
            });
            
            $('#mevzu-import-file').on('click', function(e) {
                e.stopPropagation();
            }).on('change', function() {
                if (this.files.length) handleImportFile(this.files[0]);
            });
            
            function handleImportFile(file) {
                if (!file.name.endsWith('.json')) {
                    $('#import-status').text('Sadece .json dosyaları kabul edilir.').css('color', '#d63638');
                    return;
                }
                
                importFile = file;
                
                // Dosya ön izlemesi
                $('#import-file-name').text(file.name);
                $('#import-file-size').text(formatBytes(file.size));
                
                // Dosya içeriğini oku ve bilgi göster
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var data = JSON.parse(e.target.result);
                        if (data._export_info) {
                            var info = data._export_info;
                            var groups = info.groups || [];
                            var groupLabels = <?php echo json_encode(array_map(function($g) { return $g['icon'] . ' ' . $g['label']; }, $groups)); ?>;
                            
                            var html = '<span style="color:#2271b1">Kaynak:</span> ' + (info.site_url || '-');
                            html += ' &nbsp;|&nbsp; <span style="color:#2271b1">Versiyon:</span> v' + (info.version || '-');
                            html += ' &nbsp;|&nbsp; <span style="color:#2271b1">Tarih:</span> ' + (info.export_date || '-');
                            html += '<br><span style="color:#2271b1">Gruplar:</span> ';
                            
                            var exportedGroups = [];
                            groups.forEach(function(g) {
                                if (groupLabels[g]) exportedGroups.push(groupLabels[g]);
                            });
                            html += exportedGroups.join(', ') || 'Bilinmiyor';
                            
                            $('#import-file-info').html(html);
                        } else {
                            $('#import-file-info').text('Mevzu² export dosyası olduğu doğrulanamadı.');
                        }
                    } catch(err) {
                        $('#import-file-info').html('<span style="color:#d63638">Geçersiz JSON dosyası</span>');
                    }
                };
                reader.readAsText(file);
                
                $dropzone.addClass('d-none');
                $('#mevzu-import-preview').removeClass('d-none');
                $('#mevzu-do-import').prop('disabled', false);
            }
            
            function formatBytes(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            }
            
            // İptal
            $('#mevzu-import-cancel').on('click', function() {
                importFile = null;
                $dropzone.removeClass('d-none');
                $('#mevzu-import-preview').addClass('d-none');
                $('#mevzu-do-import').prop('disabled', true);
                $('#mevzu-import-file').val('');
                $('#import-status').text('');
            });
            
            // İçe aktarma uygula
            $('#mevzu-do-import').on('click', function() {
                if (!importFile) return;
                
                if (!confirm('Mevcut ayarlarınızın üzerine yazılacaktır. Devam etmek istediğinize emin misiniz?')) return;
                
                var $btn = $(this);
                var $status = $('#import-status');
                
                $btn.prop('disabled', true);
                $status.text('İçe aktarılıyor...').css('color', '#646970');
                
                var formData = new FormData();
                formData.append('action', 'mevzu_import_settings');
                formData.append('nonce', mevzuSettings.nonce);
                formData.append('import_file', importFile);
                
                $.ajax({
                    url: mevzuSettings.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $btn.prop('disabled', false);
                        if (res.success) {
                            $status.html(res.data.message).css('color', '#00a32a');
                            setTimeout(function(){ window.location.reload(); }, 2000);
                        } else {
                            $status.text('Hata: ' + res.data).css('color', '#d63638');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $status.text('Bağlantı hatası').css('color', '#d63638');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

new Mevzu_Import_Export();
