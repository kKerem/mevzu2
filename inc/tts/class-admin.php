<?php
/**
 * Admin panel sınıfı — Mevzu² AI
 * API Key ve Project ID admin panelinde gösterilmez.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KKEREM_TTS_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 5);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action( 'wp_ajax_mevzu_yz_test_synthesis', array( $this, 'ajax_test_synthesis' ) );
        add_action( 'wp_ajax_kkerem_tts_test_synthesis', array( $this, 'ajax_test_synthesis' ) );
    }

    /**
     * WP admin sol menü robot ikonu (SVG).
     */
    private function admin_menu_icon_data_uri() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 2C13.5 2.44425 13.3069 2.84339 13 3.11805V5H18C19.6569 5 21 6.34315 21 8V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V8C3 6.34315 4.34315 5 6 5H11V3.11805C10.6931 2.84339 10.5 2.44425 10.5 2C10.5 1.17157 11.1716 0.5 12 0.5C12.8284 0.5 13.5 1.17157 13.5 2ZM6 7C5.44772 7 5 7.44772 5 8V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V8C19 7.44772 18.5523 7 18 7H13H11H6ZM2 10H0V16H2V10ZM22 10H24V16H22V10ZM9 14.5C9.82843 14.5 10.5 13.8284 10.5 13C10.5 12.1716 9.82843 11.5 9 11.5C8.17157 11.5 7.5 12.1716 7.5 13C7.5 13.8284 8.17157 14.5 9 14.5ZM15 14.5C15.8284 14.5 16.5 13.8284 16.5 13C16.5 12.1716 15.8284 11.5 15 11.5C14.1716 11.5 13.5 12.1716 13.5 13C13.5 13.8284 14.1716 14.5 15 14.5Z"></path></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }
    
    public function add_admin_menu() {
        $label = MEVZU_YZ_MODULE_LABEL;
        $page  = MEVZU_YZ_ADMIN_PAGE;

        add_menu_page(
            $label . ' Ayarları',
            $label,
            'publish_posts',
            $page,
            array( $this, 'admin_page' ),
            $this->admin_menu_icon_data_uri(),
            56
        );

        add_submenu_page(
            $page,
            $label . ' Ayarları',
            'Ayarlar',
            'publish_posts',
            $page,
            array( $this, 'admin_page' )
        );
    }
    
    public function register_settings() {
        register_setting('kkerem_tts_settings', 'kkerem_tts_category_id');
        register_setting('kkerem_tts_settings', 'kkerem_tts_update_mode');
        register_setting('kkerem_tts_settings', 'kkerem_tts_voice_name');
        register_setting('kkerem_tts_settings', 'kkerem_tts_language_code');
        register_setting('kkerem_tts_settings', 'kkerem_tts_speaking_rate');
        register_setting('kkerem_tts_settings', 'kkerem_tts_pitch');
        register_setting('kkerem_tts_settings', 'kkerem_tts_debug_enabled');
        register_setting(
            'kkerem_tts_settings',
            'kkerem_tts_category_audio_retention_days',
            array(
                'type'              => 'integer',
                'sanitize_callback' => array( 'Mevzu_TTS_Audio_Retention', 'sanitize_retention_days' ),
                'default'           => 0,
            )
        );
        register_setting(
            'kkerem_tts_settings',
            'kkerem_tts_yzm_audio_retention_days',
            array(
                'type'              => 'integer',
                'sanitize_callback' => array( 'Mevzu_TTS_Audio_Retention', 'sanitize_retention_days' ),
                'default'           => 0,
            )
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ( $hook !== 'toplevel_page_' . MEVZU_YZ_ADMIN_PAGE ) {
            return;
        }

        wp_enqueue_script( 'mevzu-yapay-zeka-admin', MEVZU_TTS_URL . 'assets/js/admin.js', array( 'jquery' ), MEVZU_TTS_VERSION, true );
        wp_enqueue_style( 'mevzu-yapay-zeka-admin', MEVZU_TTS_URL . 'assets/css/admin.css', array(), MEVZU_TTS_VERSION );

        wp_localize_script(
            'mevzu-yapay-zeka-admin',
            'mevzuYZ',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'mevzu_yz_nonce' ),
            )
        );
    }
    
    /**
     * Modül Yöneticisi’ndeki sürüm (yoksa TTS paket sürümü).
     */
    private function get_module_version() {
        $slug = defined( 'MEVZU_YZ_MODULE_SLUG' ) ? MEVZU_YZ_MODULE_SLUG : 'yapay-zeka';
        if ( class_exists( 'Mevzu_Module_Manager' ) ) {
            $modules = Mevzu_Module_Manager::get_all();
            if ( ! empty( $modules[ $slug ]['version'] ) ) {
                return (string) $modules[ $slug ]['version'];
            }
        }
        return defined( 'MEVZU_TTS_VERSION' ) ? (string) MEVZU_TTS_VERSION : '';
    }

    public function admin_page() {
        $daily_usage     = Mevzu_TTS_Daily_Limit::get_usage();
        $daily_remaining = Mevzu_TTS_Daily_Limit::remaining();
        $module_version  = $this->get_module_version();
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html( ( defined( 'MEVZU_YZ_MODULE_LABEL' ) ? MEVZU_YZ_MODULE_LABEL : 'Mevzu² AI' ) . ' Ayarları' ); ?>
                <?php if ( $module_version !== '' ) : ?>
                    <span class="subtitle">v<?php echo esc_html( $module_version ); ?></span>
                <?php endif; ?>
            </h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>Günlük Kullanım:</strong> 
                    <?php echo $daily_usage; ?> / <?php echo Mevzu_TTS_Daily_Limit::get_limit(); ?> 
                    (Kalan: <?php echo $daily_remaining; ?>)
                </p>
            </div>
            
            <form method="post" action="options.php" id="mevzu-yapay-zeka-form" class="mevzu-admin-form">
                <?php
                settings_fields('kkerem_tts_settings');
                do_settings_sections('kkerem_tts_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Hedef Kategori</th>
                        <td>
                            <?php
                            wp_dropdown_categories(array(
                                'name'             => 'kkerem_tts_category_id',
                                'id'               => 'kkerem_tts_category_id',
                                'selected'         => get_option('kkerem_tts_category_id'),
                                'show_option_none' => '— Kategori Seçin —',
                                'option_none_value'=> '',
                                'hide_empty'       => false,
                                'hierarchical'     => true,
                                'orderby'          => 'name',
                                'class'            => 'regular-text',
                            ));
                            ?>
                            <p class="description">Bu kategorideki yazılar otomatik seslendirilir. Hedef dışı yazılar için düzenleme ekranında <strong>Sayfa Ayarları → Yapay Zeka manşetinde göster</strong> kutusunu işaretleyin.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Hedef kategori — ses saklama</th>
                        <td>
                            <?php
                            $cat_retention = (int) get_option( 'kkerem_tts_category_audio_retention_days', 0 );
                            ?>
                            <input type="number" name="kkerem_tts_category_audio_retention_days" id="kkerem_tts_category_audio_retention_days"
                                min="0" max="3650" step="1" value="<?php echo esc_attr( $cat_retention ); ?>" class="small-text" />
                            <label for="kkerem_tts_category_audio_retention_days">gün sonra MP3 dosyasını otomatik sil</label>
                            <p class="description">
                                <strong>0</strong> = otomatik silme kapalı. Hedef kategorideki yazıların <code>post-{id}.mp3</code> dosyaları,
                                dosyanın oluşturulma tarihinden itibaren belirtilen gün sayısı dolunca silinir (günlük görev).
                                Yazı yeniden seslendirilirse süre dosyanın yeni oluşturulma tarihinden yeniden başlar.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Yapay Zeka manşeti — günün haberleri</th>
                        <td>
                            <?php
                            $yzm_retention = (int) get_option( 'kkerem_tts_yzm_audio_retention_days', 0 );
                            ?>
                            <input type="number" name="kkerem_tts_yzm_audio_retention_days" id="kkerem_tts_yzm_audio_retention_days"
                                min="0" max="3650" step="1" value="<?php echo esc_attr( $yzm_retention ); ?>" class="small-text" />
                            <label for="kkerem_tts_yzm_audio_retention_days">takvim günü sakla (sonra MP3 silinsin)</label>
                            <p class="description">
                                Anasayfadaki <strong>Yapay Zeka manşeti</strong> alanında sunulan haberlerin ses dosyaları için.
                                <strong>Sayfa Ayarları → Yapay Zeka manşetinde göster</strong> işaretli yazılara uygulanır.
                                Silme <strong>takvim gününe</strong> göredir (site saati); dosya yaşına (24 saat) göre değildir.
                                <strong>1</strong> = yalnızca bugünün sesleri kalır; günlük görev çalışınca dün ve önceki günlerin MP3’leri silinir (gün sonu temizliği).
                                <strong>0</strong> = otomatik silme kapalı. Aynı yazı hem YZ manşeti hem hedef kategorideyse bu kural geçerlidir.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Güncelleme Modu</th>
                        <td>
                            <select name="kkerem_tts_update_mode">
                                <option value="publish_only" <?php selected(get_option('kkerem_tts_update_mode'), 'publish_only'); ?>>Sadece yayınlanırken</option>
                                <option value="always" <?php selected(get_option('kkerem_tts_update_mode'), 'always'); ?>>Her güncellemede</option>
                            </select>
                            <p class="description">«Sadece yayınlanırken»: yazı ilk kez yayına alındığında otomatik ses üretilir; sonraki düzenlemelerde yeniden üretilmez (editörden manuel yenileme kullanılabilir). «Her güncellemede»: her kayıtta hedef kategori veya YZ manşeti koşulu sağlanıyorsa kuyruğa alınır.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Ses Adı</th>
                        <td>
                            <select name="kkerem_tts_voice_name">
                                <optgroup label="Türkçe Chirp3 HD Sesler (En Yeni Teknoloji)">
                                    <option value="tr-TR-Chirp3-HD-Achernar" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Achernar'); ?>>Türkçe Chirp3 HD Kadın - Achernar</option>
                                    <option value="tr-TR-Chirp3-HD-Achird" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Achird'); ?>>Türkçe Chirp3 HD Erkek - Achird</option>
                                    <option value="tr-TR-Chirp3-HD-Algenib" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Algenib'); ?>>Türkçe Chirp3 HD Erkek - Algenib</option>
                                    <option value="tr-TR-Chirp3-HD-Algieba" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Algieba'); ?>>Türkçe Chirp3 HD Erkek - Algieba</option>
                                    <option value="tr-TR-Chirp3-HD-Alnilam" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Alnilam'); ?>>Türkçe Chirp3 HD Erkek - Alnilam</option>
                                    <option value="tr-TR-Chirp3-HD-Aoede" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Aoede'); ?>>Türkçe Chirp3 HD Kadın - Aoede</option>
                                    <option value="tr-TR-Chirp3-HD-Autonoe" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Autonoe'); ?>>Türkçe Chirp3 HD Kadın - Autonoe</option>
                                    <option value="tr-TR-Chirp3-HD-Callirrhoe" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Callirrhoe'); ?>>Türkçe Chirp3 HD Kadın - Callirrhoe</option>
                                    <option value="tr-TR-Chirp3-HD-Charon" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Charon'); ?>>Türkçe Chirp3 HD Erkek - Charon</option>
                                    <option value="tr-TR-Chirp3-HD-Despina" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Despina'); ?>>Türkçe Chirp3 HD Kadın - Despina</option>
                                    <option value="tr-TR-Chirp3-HD-Enceladus" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Enceladus'); ?>>Türkçe Chirp3 HD Erkek - Enceladus</option>
                                    <option value="tr-TR-Chirp3-HD-Erinome" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Erinome'); ?>>Türkçe Chirp3 HD Kadın - Erinome</option>
                                    <option value="tr-TR-Chirp3-HD-Fenrir" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Fenrir'); ?>>Türkçe Chirp3 HD Erkek - Fenrir</option>
                                    <option value="tr-TR-Chirp3-HD-Gacrux" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Gacrux'); ?>>Türkçe Chirp3 HD Kadın - Gacrux</option>
                                    <option value="tr-TR-Chirp3-HD-Iapetus" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Iapetus'); ?>>Türkçe Chirp3 HD Erkek - Iapetus</option>
                                    <option value="tr-TR-Chirp3-HD-Kore" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Kore'); ?>>Türkçe Chirp3 HD Kadın - Kore</option>
                                    <option value="tr-TR-Chirp3-HD-Laomedeia" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Laomedeia'); ?>>Türkçe Chirp3 HD Kadın - Laomedeia</option>
                                    <option value="tr-TR-Chirp3-HD-Leda" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Leda'); ?>>Türkçe Chirp3 HD Kadın - Leda</option>
                                    <option value="tr-TR-Chirp3-HD-Orus" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Orus'); ?>>Türkçe Chirp3 HD Erkek - Orus</option>
                                    <option value="tr-TR-Chirp3-HD-Puck" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Puck'); ?>>Türkçe Chirp3 HD Erkek - Puck</option>
                                    <option value="tr-TR-Chirp3-HD-Pulcherrima" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Pulcherrima'); ?>>Türkçe Chirp3 HD Kadın - Pulcherrima</option>
                                    <option value="tr-TR-Chirp3-HD-Rasalgethi" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Rasalgethi'); ?>>Türkçe Chirp3 HD Erkek - Rasalgethi</option>
                                    <option value="tr-TR-Chirp3-HD-Sadachbia" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Sadachbia'); ?>>Türkçe Chirp3 HD Erkek - Sadachbia</option>
                                    <option value="tr-TR-Chirp3-HD-Sadaltager" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Sadaltager'); ?>>Türkçe Chirp3 HD Erkek - Sadaltager</option>
                                    <option value="tr-TR-Chirp3-HD-Schedar" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Schedar'); ?>>Türkçe Chirp3 HD Erkek - Schedar</option>
                                    <option value="tr-TR-Chirp3-HD-Sulafat" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Sulafat'); ?>>Türkçe Chirp3 HD Kadın - Sulafat</option>
                                    <option value="tr-TR-Chirp3-HD-Umbriel" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Umbriel'); ?>>Türkçe Chirp3 HD Erkek - Umbriel</option>
                                    <option value="tr-TR-Chirp3-HD-Vindemiatrix" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Vindemiatrix'); ?>>Türkçe Chirp3 HD Kadın - Vindemiatrix</option>
                                    <option value="tr-TR-Chirp3-HD-Zephyr" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Zephyr'); ?>>Türkçe Chirp3 HD Kadın - Zephyr</option>
                                    <option value="tr-TR-Chirp3-HD-Zubenelgenubi" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Chirp3-HD-Zubenelgenubi'); ?>>Türkçe Chirp3 HD Erkek - Zubenelgenubi</option>
                                </optgroup>
                                <optgroup label="Türkçe Standard Sesler (Ekonomik)">
                                    <option value="tr-TR-Standard-A" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Standard-A'); ?>>Türkçe Standard Kadın (tr-TR-Standard-A)</option>
                                    <option value="tr-TR-Standard-B" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Standard-B'); ?>>Türkçe Standard Erkek (tr-TR-Standard-B)</option>
                                    <option value="tr-TR-Standard-C" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Standard-C'); ?>>Türkçe Standard Kadın 2 (tr-TR-Standard-C)</option>
                                    <option value="tr-TR-Standard-D" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Standard-D'); ?>>Türkçe Standard Kadın 3 (tr-TR-Standard-D)</option>
                                    <option value="tr-TR-Standard-E" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Standard-E'); ?>>Türkçe Standard Erkek 2 (tr-TR-Standard-E)</option>
                                </optgroup>
                                <optgroup label="Türkçe WaveNet Sesler (Premium)">
                                    <option value="tr-TR-Wavenet-A" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Wavenet-A'); ?>>Türkçe WaveNet Kadın (tr-TR-Wavenet-A)</option>
                                    <option value="tr-TR-Wavenet-B" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Wavenet-B'); ?>>Türkçe WaveNet Erkek (tr-TR-Wavenet-B)</option>
                                    <option value="tr-TR-Wavenet-C" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Wavenet-C'); ?>>Türkçe WaveNet Kadın 2 (tr-TR-Wavenet-C)</option>
                                    <option value="tr-TR-Wavenet-D" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Wavenet-D'); ?>>Türkçe WaveNet Kadın 3 (tr-TR-Wavenet-D)</option>
                                    <option value="tr-TR-Wavenet-E" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Wavenet-E'); ?>>Türkçe WaveNet Erkek 2 (tr-TR-Wavenet-E)</option>
                                </optgroup>
                                <optgroup label="Neural2 Sesler (Premium)">
                                    <option value="tr-TR-Neural2-A" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Neural2-A'); ?>>Türkçe Neural2 Kadın (tr-TR-Neural2-A)</option>
                                    <option value="tr-TR-Neural2-B" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Neural2-B'); ?>>Türkçe Neural2 Erkek (tr-TR-Neural2-B)</option>
                                    <option value="tr-TR-Neural2-C" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Neural2-C'); ?>>Türkçe Neural2 Kadın 2 (tr-TR-Neural2-C)</option>
                                    <option value="tr-TR-Neural2-D" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Neural2-D'); ?>>Türkçe Neural2 Erkek 2 (tr-TR-Neural2-D)</option>
                                    <option value="tr-TR-Neural2-E" <?php selected(get_option('kkerem_tts_voice_name'), 'tr-TR-Neural2-E'); ?>>Türkçe Neural2 Kadın 3 (tr-TR-Neural2-E)</option>
                                </optgroup>
                            </select>
                            <p class="description">
                                <strong>Mevzu² AI ses kütüphanesinde kullanılabilen modeller:</strong><br>
                                • <strong>Chirp3 HD:</strong> En yeni teknoloji, konuşma deneyimi için optimize edilmiş<br>
                                • <strong>Standard:</strong> Temel sesler, daha ucuz<br>
                                • <strong>WaveNet:</strong> En kaliteli sesler, daha pahalı<br>
                                • <strong>Neural2:</strong> Custom Voice teknolojisi, en doğal sesler
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Dil Kodu</th>
                        <td>
                            <input type="text" name="kkerem_tts_language_code" value="<?php echo esc_attr(get_option('kkerem_tts_language_code', 'tr-TR')); ?>" class="small-text" readonly />
                            <p class="description">Bu alan ses seçimine göre otomatik olarak güncellenir.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Konuşma Hızı</th>
                        <td>
                            <input type="range" name="kkerem_tts_speaking_rate" min="0.25" max="4.0" step="0.25" value="<?php echo esc_attr(get_option('kkerem_tts_speaking_rate', '1.0')); ?>" />
                            <span id="speaking-rate-value"><?php echo esc_attr(get_option('kkerem_tts_speaking_rate', '1.0')); ?></span>
                            <p class="description">0.25 (çok yavaş) - 4.0 (çok hızlı)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Ses Tonu</th>
                        <td>
                            <input type="range" name="kkerem_tts_pitch" min="-20.0" max="20.0" step="1.0" value="<?php echo esc_attr(get_option('kkerem_tts_pitch', '0.0')); ?>" />
                            <span id="pitch-value"><?php echo esc_attr(get_option('kkerem_tts_pitch', '0.0')); ?></span>
                            <p class="description">-20.0 (çok düşük) - 20.0 (çok yüksek)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Debug Logları</th>
                        <td>
                            <label>
                                <input type="checkbox" name="kkerem_tts_debug_enabled" value="1" <?php checked(get_option('kkerem_tts_debug_enabled', '0'), '1'); ?> />
                                Debug loglarını aktif et
                            </label>
                            <p class="description">Sorun giderme için detaylı loglar oluşturur.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="kkerem-tts-test-section">
                <h3>Test</h3>
                <p>Ses ayarlarınızı test etmek için aşağıdaki metni kullanabilirsiniz:</p>
                <textarea id="test-text" rows="3" cols="50" placeholder="Test metni girin...">Merhaba, bu Mevzu² AI test sesidir.</textarea>
                <br><br>
                <button id="test-tts" class="button button-secondary">Ses Oluştur ve Dinle</button>
                <div id="test-audio-container" style="margin-top: 10px;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Debug loglarının aktif olup olmadığını kontrol et
     */
    public static function is_debug_enabled() {
        return get_option('kkerem_tts_debug_enabled', '0') === '1';
    }
    
    /**
     * Debug log yaz (sadece debug aktifse)
     */
    public static function debug_log($message) {
        if (self::is_debug_enabled()) {
            error_log('Mevzu TTS: ' . $message);
        }
    }
    
    /**
     * AJAX: Test ses sentezi
     */
    public function ajax_test_synthesis() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_nonce' );
        
        if (!current_user_can('publish_posts')) {
            wp_die('Yetkiniz yok');
        }
        
        // Günlük limit kontrolü
        if (!Mevzu_TTS_Daily_Limit::can_use()) {
            wp_send_json_error('Mevzu² AI günlük kotanız doldu. Kullanım: ' . Mevzu_TTS_Daily_Limit::get_usage() . ' / ' . Mevzu_TTS_Daily_Limit::get_limit());
        }
        
        $text = sanitize_textarea_field($_POST['text']);
        
        self::debug_log('Test - Gelen metin: ' . $text);
        
        if (empty($text)) {
            wp_send_json_error('Test metni boş olamaz');
        }
        
        $tts_service = new KKEREM_TTS_Service();
        $result = $tts_service->test_synthesis($text);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            if (!Mevzu_AI_Client::is_ready()) {
                wp_send_json_error(Mevzu_AI_Client::get_unavailable_message());
            } else {
                $voice_name = get_option('kkerem_tts_voice_name');
                wp_send_json_error('Mevzu² AI ses oluşturulamadı. Ses: ' . $voice_name . '. Ayrıntılar için hata günlüğüne bakın.');
            }
        }
    }
}
