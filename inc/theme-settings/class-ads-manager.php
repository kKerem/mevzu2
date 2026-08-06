<?php
/**
 * Mevzu² Reklam Yöneticisi
 *
 * Sade, güçlü reklam sistemi:
 *  - Her alanda HTML kodu (AdSense dahil) veya Resim+Link
 *  - Tek toggle ile aç/kapa
 *  - Opsiyonel "boş alan" placeholder'ı
 *  - mevzu_reklam($alan_id) ile şablonlarda çağrılır
 *  - [mevzu_reklam slot="alan_id"] shortcode
 */

if (!defined('ABSPATH')) exit;

class Mevzu_Ads_Manager {

    /** Temada tanımlı sabit reklam alanları */
    public static function zones(): array {
        return [
            // ── Genel ──────────────────────────────────────────
            'govde_ust'       => ['label' => 'Gövde Üst',          'group' => 'Genel'],
            'icerik_oncesi'   => ['label' => 'İçerik Öncesi',      'group' => 'Genel'],
            'icerik_sonrasi'  => ['label' => 'İçerik Sonrası',     'group' => 'Genel'],
            // ── Yan Reklamlar ───────────────────────────────────
            'yan_sol'         => ['label' => 'Yan Sol Reklam',      'group' => 'Yan Reklamlar'],
            'yan_sag'         => ['label' => 'Yan Sağ Reklam',      'group' => 'Yan Reklamlar'],
            // ── Anasayfa ───────────────────────────────────────
            'anasayfa_ust'    => ['label' => 'Anasayfa Üst',       'group' => 'Anasayfa'],
            'anasayfa_alt'    => ['label' => 'Anasayfa Alt',       'group' => 'Anasayfa'],
            'anasayfa_1'      => ['label' => 'Anasayfa Blok 1',    'group' => 'Anasayfa'],
            'anasayfa_2'      => ['label' => 'Anasayfa Blok 2',    'group' => 'Anasayfa'],
            'anasayfa_3'      => ['label' => 'Anasayfa Blok 3',    'group' => 'Anasayfa'],
            'anasayfa_4'      => ['label' => 'Anasayfa Blok 4',    'group' => 'Anasayfa'],
            'anasayfa_5'      => ['label' => 'Anasayfa Blok 5',    'group' => 'Anasayfa'],
            'anasayfa_6'      => ['label' => 'Anasayfa Blok 6',    'group' => 'Anasayfa'],
            'anasayfa_7'      => ['label' => 'Anasayfa Blok 7',    'group' => 'Anasayfa'],
            'anasayfa_8'      => ['label' => 'Anasayfa Blok 8',    'group' => 'Anasayfa'],
        ];
    }

    /** Bir alanın verisini oku */
    public static function get(string $id): array {
        $default = [
            'active'      => false,
            'type'        => 'image',   // 'image' | 'html'
            'html_code'   => '',
            'image_id'    => '',
            'link_url'    => '',
            'link_title'  => '',
            'placeholder' => false,
            'start_date'  => '',
            'end_date'    => '',
        ];
        $saved = get_option('mevzu_ad_' . $id, []);
        $saved = is_array($saved) ? $saved : [];

        // Geriye dönük uyumluluk:
        // "Mevzu2 - Reklam" gibi eski group/repeater alanlarında tutulan verileri
        // yeni reklam yöneticisi yapısına fallback olarak taşır.
        if (empty($saved)) {
            $legacy = self::get_legacy_group($id);
            if (!empty($legacy)) {
                $saved = [
                    'active'      => !empty($legacy['reklam_aktif']),
                    'type'        => !empty($legacy['type']) ? sanitize_key($legacy['type']) : 'image',
                    'html_code'   => isset($legacy['html_code']) ? wp_unslash((string) $legacy['html_code']) : '',
                    'image_id'    => absint($legacy['image'] ?? 0),
                    'link_url'    => esc_url_raw($legacy['reklam_url'] ?? ''),
                    'link_title'  => sanitize_text_field($legacy['reklam_basligi'] ?? ''),
                    'placeholder' => !empty($legacy['placeholder']),
                    'start_date'  => sanitize_text_field($legacy['start_date'] ?? ''),
                    'end_date'    => sanitize_text_field($legacy['end_date'] ?? ''),
                ];
            }
        }

        return wp_parse_args($saved, $default);
    }

    private static function get_legacy_group(string $id): array {
        $legacy_map = [
            'govde_ust'      => 'govde_ust_reklam',
            'icerik_oncesi'  => 'icerik_oncesi',
            'icerik_sonrasi' => 'icerik_sonrasi',
            'anasayfa_ust'   => 'ust_reklam',
            'anasayfa_alt'   => 'alt_reklam',
            'anasayfa_1'     => 'anasayfa_reklam_1',
            'anasayfa_2'     => 'anasayfa_reklam_2',
            'anasayfa_3'     => 'anasayfa_reklam_3',
            'anasayfa_4'     => 'anasayfa_reklam_4',
            'anasayfa_5'     => 'anasayfa_reklam_5',
            'anasayfa_6'     => 'anasayfa_reklam_6',
            'anasayfa_7'     => 'anasayfa_reklam_7',
            'anasayfa_8'     => 'anasayfa_reklam_8',
        ];

        if (!isset($legacy_map[$id])) {
            return [];
        }

        $group = get_option('options_' . $legacy_map[$id], []);
        return is_array($group) ? $group : [];
    }

    /** Bir alanın verisini yaz */
    public static function save(string $id, array $data): void {
        update_option('mevzu_ad_' . $id, $data, false);
    }

    /** ─────────────────────────────────────────────────────────
     *  Admin Sayfası
     * ───────────────────────────────────────────────────────── */
    public function __construct() {
        add_action('admin_menu',                    [$this, 'add_menu']);
        add_action('wp_ajax_mevzu_save_ad',         [$this, 'ajax_save']);
        add_action('wp_ajax_mevzu_save_swiper',     [$this, 'ajax_save_swiper']);
        add_action('wp_ajax_mevzu_save_side_ads',   [$this, 'ajax_save_side_ads']);
        add_action('admin_enqueue_scripts',         [$this, 'enqueue']);
        add_shortcode('mevzu_reklam',               [$this, 'shortcode']);
        // Reklam post type meta kutusu
        add_action('add_meta_boxes_reklam',         [$this, 'add_reklam_metabox']);
        add_action('save_post_reklam',              [$this, 'save_reklam_metabox']);
        // Transient geçersizleştirme
        add_action('save_post_reklam',              [$this, 'invalidate_swiper_transient']);
        add_action('trash_post',                    [$this, 'maybe_invalidate_swiper_transient']);
        add_action('untrash_post',                  [$this, 'maybe_invalidate_swiper_transient']);
        add_action('before_delete_post',            [$this, 'maybe_invalidate_swiper_transient']);
    }

    public function add_menu(): void {
        add_menu_page(
            'Reklam Yönetimi',
            'Reklam Yönetimi',
            'manage_options',
            'mevzu-reklamlar',
            [$this, 'render_page'],
            'dashicons-megaphone',
            10
        );
    }

    public function enqueue($hook): void {
        if (strpos($hook, 'mevzu-reklamlar') === false) return;
        wp_enqueue_media();
        $settings_url = get_template_directory_uri() . '/inc/theme-settings/assets/';
        $settings_dir = get_template_directory() . '/inc/theme-settings/assets/';
        $css_ver = file_exists($settings_dir . 'ads-manager.css') ? (string) filemtime($settings_dir . 'ads-manager.css') : MEVZU_THEME_VERSION;
        $js_ver  = file_exists($settings_dir . 'ads-manager.js')  ? (string) filemtime($settings_dir . 'ads-manager.js')  : MEVZU_THEME_VERSION;
        wp_enqueue_style('mevzu-ads-css',  $settings_url . 'ads-manager.css', [], $css_ver);
        wp_enqueue_script('mevzu-ads-js',  $settings_url . 'ads-manager.js',  ['jquery'], $js_ver, true);
        wp_localize_script('mevzu-ads-js', 'mevzuAds', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mevzu_save_ad'),
        ]);
    }

    public function render_page(): void {
        $zones  = self::zones();
        $groups = [];
        foreach ($zones as $id => $meta) {
            $groups[$meta['group']][$id] = $meta['label'];
        }
        $swiper_aktif     = (int) get_option('options_ust_reklam_alani', 0);
        $swiper_goruntule = get_option('options_ust_reklam_goruntuleme', 'anasayfa');
        $swiper_tip       = get_option('options_ust_reklam_tip', 'swiper');
        $side_aktif       = (int) get_option('options_yan_reklam_alani', 0);
        $side_goruntule   = get_option('options_yan_reklam_goruntuleme', 'tumu');
        $side_fixed_sol   = (int) get_option('options_yan_reklam_fixed_sol', 0);
        $side_fixed_sag   = (int) get_option('options_yan_reklam_fixed_sag', 0);
        ?>
        <div class="wrap mevzu-ads-wrap">
            <h1 class="mevzu-ads-title">
                <span class="dashicons dashicons-megaphone"></span>
                Reklam Yönetimi
            </h1>
            <p class="mevzu-ads-subtitle">
                Her alana <strong>HTML kodu</strong> (Google AdSense, iframe vb.) veya <strong>resim+link</strong> ekleyebilirsiniz.
                Şablonlarda <kbd>mevzu_reklam('alan_id')</kbd>, yazılarda <kbd>[mevzu_reklam slot="alan_id"]</kbd> kısayoluyla kullanın.
            </p>

            <div class="d-flex gap-3">
                <!-- Swiper Üst Reklam Alanı -->
                <div id="swiper_ust_reklam_alani" class="mevzu-ad-card <?php echo $swiper_aktif ? 'is-active' : ''; ?>"
                    style="margin-bottom:24px;max-width:640px">
                    <div class="mevzu-ad-card-header">
                        <div class="mevzu-ad-card-info d-flex gap-2 align-items-center">
                            <strong>Swiper Üst Reklam Alanı</strong>
                            <code class="mevzu-ad-id">post_type: reklam</code>
                        </div>
                        <label class="mevzu-toggle" title="Aktif / Pasif">
                            <input type="checkbox" id="mevzu-swiper-aktif" <?php checked($swiper_aktif, 1); ?>>
                            <span class="mevzu-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="mevzu-ad-card-body" style="gap:16px">
                        <p style="margin:0;font-size:13px;color:#555">
                            Reklam içeriklerini "Reklam İlanları" bölümünden yönetin. Her ilana özel URL ve yayın tarihi atayabilirsiniz.
                        </p>
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=reklam')); ?>"
                        class="button" style="align-self:flex-start">
                            <span class="dashicons dashicons-edit" style="margin-top:3px"></span>
                            Reklam İlanlarını Yönet
                        </a>

                        <!-- Görüntüleme Yeri -->
                        <div class="mevzu-field">
                            <label class="fw-semibold">
                                Nerede Gösterilsin?
                            </label>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ([
                                    'anasayfa' => 'Sadece Anasayfada Göster',
                                    'arsiv'    => 'Sadece Arşiv / Kategori Sayfalarında Göster',
                                    'icerik'   => 'Sadece İçeriklerde (Yazılarda) Göster',
                                    'tumu'     => 'Tüm Sayfalarda Göster',
                                ] as $val => $lbl): ?>
                                <label class="d-flex align-items-center gap-1 cursor-pointer fw-normal">
                                    <input type="radio" name="mevzu_swiper_goruntuleme"
                                        value="<?php echo esc_attr($val); ?>"
                                        class="mevzu-swiper-goruntuleme"
                                        <?php checked($swiper_goruntule, $val); ?>>
                                    <?php echo esc_html($lbl); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Görüntüleme Tipi -->
                        <div class="mevzu-field">
                            <label class="fw-semibold">
                                Görüntüleme Şekli
                            </label>
                            <div class="d-flex gap-3">
                                <label class="d-flex align-items-center gap-1 cursor-pointer fw-normal">
                                    <input type="radio" name="mevzu_swiper_tip"
                                        value="swiper" class="mevzu-swiper-tip"
                                        <?php checked($swiper_tip, 'swiper'); ?>>
                                    Swiper (kaydırılır)
                                </label>
                                <label class="d-flex align-items-center gap-1 cursor-pointer fw-normal">
                                    <input type="radio" name="mevzu_swiper_tip"
                                        value="liste" class="mevzu-swiper-tip"
                                        <?php checked($swiper_tip, 'liste'); ?>>
                                    Liste (alt alta)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mevzu-ad-card-footer">
                        <span class="mevzu-swiper-status mevzu-save-status"></span>
                        <button type="button" class="button button-primary" id="mevzu-save-swiper">Kaydet</button>
                    </div>
                </div>

                <!-- Yan Reklam Alanları -->
                <div id="yan_reklam_alani" class="mevzu-ad-card <?php echo $side_aktif ? 'is-active' : ''; ?>"
                    style="margin-bottom:24px;max-width:760px">
                    <div class="mevzu-ad-card-header">
                        <div class="mevzu-ad-card-info d-flex gap-2 align-items-center">
                            <strong>Yan Sol / Sağ Reklam Alanları</strong>
                            <code class="mevzu-ad-id">slot: yan_sol + yan_sag</code>
                        </div>
                        <label class="mevzu-toggle" title="Aktif / Pasif">
                            <input type="checkbox" id="mevzu-side-aktif" <?php checked($side_aktif, 1); ?>>
                            <span class="mevzu-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="mevzu-ad-card-body" style="gap:16px">
                        <p style="margin:0;font-size:13px;color:#555">
                            Reklam içeriklerini aşağıdaki "Yan Sol Reklam" ve "Yan Sağ Reklam" kartlarından yönetin.
                        </p>

                        <div class="mevzu-field">
                            <label class="fw-semibold">Nerede Gösterilsin?</label>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ([
                                    'anasayfa' => 'Sadece Anasayfada Göster',
                                    'arsiv'    => 'Sadece Arşiv / Kategori Sayfalarında Göster',
                                    'icerik'   => 'Sadece İçeriklerde (Yazılarda) Göster',
                                    'tumu'     => 'Tüm Sayfalarda Göster',
                                ] as $val => $lbl): ?>
                                <label class="d-flex align-items-center gap-1 cursor-pointer fw-normal">
                                    <input type="radio" name="mevzu_side_goruntuleme"
                                        value="<?php echo esc_attr($val); ?>"
                                        class="mevzu-side-goruntuleme"
                                        <?php checked($side_goruntule, $val); ?>>
                                    <?php echo esc_html($lbl); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mevzu-field">
                            <label class="fw-semibold">Sabit Konum Seçenekleri</label>
                            <div class="d-flex flex-column gap-2">
                                <label class="d-flex align-items-center gap-2 cursor-pointer fw-normal">
                                    <input type="checkbox" id="mevzu-side-fixed-sol" <?php checked($side_fixed_sol, 1); ?>>
                                    Sol reklamı <code>fixed-top</code> gibi sabitle
                                </label>
                                <label class="d-flex align-items-center gap-2 cursor-pointer fw-normal">
                                    <input type="checkbox" id="mevzu-side-fixed-sag" <?php checked($side_fixed_sag, 1); ?>>
                                    Sağ reklamı <code>fixed-top</code> gibi sabitle
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mevzu-ad-card-footer">
                        <span class="mevzu-side-status mevzu-save-status"></span>
                        <button type="button" class="button button-primary" id="mevzu-save-side-ads">Kaydet</button>
                    </div>
                </div>
            </div>

            <div class="mevzu-ads-grid">
            <?php foreach ($groups as $group_name => $group_zones): ?>

                <div class="mevzu-ads-section">
                    <h2 class="mevzu-ads-group"><?php echo esc_html($group_name); ?></h2>
                    <?php foreach ($group_zones as $id => $label): $data = self::get($id); ?>
                    <div id="<?php echo esc_attr($id); ?>" class="mevzu-ad-card <?php echo $data['active'] ? 'is-active' : ''; ?>" data-id="<?php echo esc_attr($id); ?>">
                        <div class="mevzu-ad-card-header">
                            <div class="mevzu-ad-card-info d-flex gap-2 align-items-center">
                                <strong><?php echo esc_html($label); ?></strong>
                                <code class="mevzu-ad-id"><?php echo esc_html($id); ?></code>
                            </div>
                            <label class="mevzu-toggle" title="Aktif / Pasif">
                                <input type="checkbox" class="mevzu-ad-active" <?php checked($data['active']); ?>>
                                <span class="mevzu-toggle-slider"></span>
                            </label>
                        </div>

                        <div class="mevzu-ad-card-body">
                            <!-- Tür Seçimi -->
                            <div class="mevzu-ad-type-tabs">
                                <button class="mevzu-type-btn <?php echo $data['type'] === 'image' ? 'active' : ''; ?>" data-type="image">
                                    <span class="dashicons dashicons-format-image"></span> Resim + Link
                                </button>
                                <button class="mevzu-type-btn <?php echo $data['type'] === 'html' ? 'active' : ''; ?>" data-type="html">
                                    <span class="dashicons dashicons-editor-code"></span> HTML Kodu
                                </button>
                            </div>
                            <input type="hidden" class="mevzu-ad-type" value="<?php echo esc_attr($data['type']); ?>">

                            <!-- Resim Paneli -->
                            <div class="mevzu-ad-panel mevzu-panel-image <?php echo $data['type'] === 'image' ? 'active' : ''; ?>">
                                <div class="mevzu-image-preview">
                                    <?php
                                    $img_url = '';
                                    if ($data['image_id'] && is_numeric($data['image_id'])) {
                                        $img_url = wp_get_attachment_url($data['image_id']);
                                    }
                                    if ($img_url): ?>
                                        <img src="<?php echo esc_url($img_url); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <div class="mevzu-image-actions">
                                    <button type="button" class="button mevzu-select-image">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php echo $img_url ? 'Görseli Değiştir' : 'Görsel Seç'; ?>
                                    </button>
                                    <button type="button" class="button mevzu-remove-image" <?php echo !$img_url ? 'style="display:none"' : ''; ?>>
                                        Kaldır
                                    </button>
                                </div>
                                <input type="hidden" class="mevzu-ad-image-id" value="<?php echo esc_attr($data['image_id']); ?>">
                                <div class="mevzu-field">
                                    <label>Tıklama Linki</label>
                                    <input type="url" class="mevzu-ad-link-url regular-text" value="<?php echo esc_url($data['link_url']); ?>" placeholder="https://...">
                                </div>
                                <div class="mevzu-field">
                                    <label>Başlık / Alt Text</label>
                                    <input type="text" class="mevzu-ad-link-title regular-text" value="<?php echo esc_attr($data['link_title']); ?>" placeholder="Reklam">
                                </div>
                            </div>

                            <!-- HTML Paneli -->
                            <div class="mevzu-ad-panel mevzu-panel-html <?php echo $data['type'] === 'html' ? 'active' : ''; ?>">
                                <label>HTML / Reklam Kodu</label>
                                <textarea class="mevzu-ad-html-code" rows="6" placeholder="Google AdSense kodu, iframe veya herhangi bir HTML yapıştırın..."><?php echo esc_textarea($data['html_code']); ?></textarea>
                                <p class="description">Script etiketleri de desteklenir (AdSense, vb.)</p>
                            </div>

                            <!-- Opsiyonlar -->
                            <div class="mevzu-ad-options">
                                <label class="mevzu-checkbox-label">
                                    <input type="checkbox" class="mevzu-ad-placeholder" <?php checked($data['placeholder']); ?>>
                                    Reklam boşsa text-hover "Bu alana reklam ver" yazısını göster
                                </label>
                            </div>

                            <!-- Yayın Tarihleri -->
                            <div class="mevzu-ad-dates">
                                <div class="mevzu-ad-dates-row">
                                    <div class="mevzu-field">
                                        <label class="fw-normal">Yayın Başlangıcı</label>
                                        <input type="date" class="mevzu-ad-start-date" value="<?php echo esc_attr($data['start_date']); ?>">
                                    </div>
                                    <div class="mevzu-field">
                                        <label class="fw-normal">Yayın Bitişi</label>
                                        <input type="date" class="mevzu-ad-end-date" value="<?php echo esc_attr($data['end_date']); ?>">
                                    </div>
                                </div>
                                <p class="description m-0 small">Boş bırakılırsa tarih sınırı uygulanmaz.</p>
                            </div>
                        </div>

                        <div class="mevzu-ad-card-footer">
                            <span class="mevzu-save-status"></span>
                            <button type="button" class="button button-primary mevzu-save-ad">Kaydet</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function ajax_save(): void {
        check_ajax_referer('mevzu_save_ad', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok.');
        }

        $id = sanitize_key($_POST['zone_id'] ?? '');
        if (!array_key_exists($id, self::zones())) {
            wp_send_json_error('Geçersiz reklam alanı.');
        }

        $data = [
            'active'      => intval($_POST['active'] ?? 0) === 1,
            'type'        => in_array($_POST['type'] ?? '', ['image', 'html']) ? $_POST['type'] : 'image',
            // manage_options yetkisi olan kullanıcılar script dahil tüm HTML'i girebilir (AdSense için gerekli)
            'html_code'   => current_user_can('manage_options')
                ? wp_unslash($_POST['html_code'] ?? '')
                : wp_kses_post(wp_unslash($_POST['html_code'] ?? '')),
            'image_id'    => absint($_POST['image_id'] ?? 0),
            'link_url'    => esc_url_raw($_POST['link_url'] ?? ''),
            'link_title'  => sanitize_text_field($_POST['link_title'] ?? ''),
            'placeholder' => intval($_POST['placeholder'] ?? 0) === 1,
            'start_date'  => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date'    => sanitize_text_field($_POST['end_date']   ?? ''),
        ];

        self::save($id, $data);
        wp_send_json_success('Kaydedildi.');
    }

    /** Swiper toggle'larını kaydet */
    public function ajax_save_swiper(): void {
        check_ajax_referer('mevzu_save_ad', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok.');
        }
        $aktif = intval($_POST['aktif'] ?? 0) === 1 ? 1 : 0;
        update_option('options_ust_reklam_alani', $aktif);

        $goruntuleme = sanitize_key($_POST['goruntuleme'] ?? 'anasayfa');
        if (!in_array($goruntuleme, ['anasayfa', 'arsiv', 'icerik', 'tumu'])) $goruntuleme = 'anasayfa';
        update_option('options_ust_reklam_goruntuleme', $goruntuleme);

        $tip = sanitize_key($_POST['tip'] ?? 'swiper');
        if (!in_array($tip, ['swiper', 'liste'])) $tip = 'swiper';
        update_option('options_ust_reklam_tip', $tip);

        wp_send_json_success('Kaydedildi.');
    }

    /** Yan reklam gösterim ayarlarını kaydet */
    public function ajax_save_side_ads(): void {
        check_ajax_referer('mevzu_save_ad', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok.');
        }

        $aktif = intval($_POST['aktif'] ?? 0) === 1 ? 1 : 0;
        update_option('options_yan_reklam_alani', $aktif);

        $goruntuleme = sanitize_key($_POST['goruntuleme'] ?? 'tumu');
        if (!in_array($goruntuleme, ['anasayfa', 'arsiv', 'icerik', 'tumu'], true)) {
            $goruntuleme = 'tumu';
        }
        update_option('options_yan_reklam_goruntuleme', $goruntuleme);

        $fixed_sol = intval($_POST['fixed_sol'] ?? 0) === 1 ? 1 : 0;
        $fixed_sag = intval($_POST['fixed_sag'] ?? 0) === 1 ? 1 : 0;
        update_option('options_yan_reklam_fixed_sol', $fixed_sol);
        update_option('options_yan_reklam_fixed_sag', $fixed_sag);

        wp_send_json_success('Kaydedildi.');
    }

    /** Gösterim alanına göre sayfanın uygunluğunu kontrol et */
    public static function is_display_context(string $display): bool {
        return match ($display) {
            'anasayfa' => is_home() || is_front_page(),
            'arsiv'    => is_archive() || is_category() || is_tag() || is_author() || is_search(),
            'icerik'   => is_single() || is_page(),
            'tumu'     => true,
            default    => is_home() || is_front_page(),
        };
    }

    /** Reklam post type için meta kutu ekle */
    public function add_reklam_metabox(): void {
        add_meta_box(
            'mevzu-reklam-settings',
            'Reklam Ayarları',
            [$this, 'render_reklam_metabox'],
            'reklam',
            'normal',
            'high'
        );
    }

    /** Reklam meta kutusu HTML */
    public function render_reklam_metabox(\WP_Post $post): void {
        $url        = get_post_meta($post->ID, 'reklam_ozel_url', true);
        $start_date = get_post_meta($post->ID, 'reklam_baslangic_tarihi', true);
        $end_date   = get_post_meta($post->ID, 'reklam_bitis_tarihi', true);
        wp_nonce_field('mevzu_reklam_meta', 'mevzu_reklam_meta_nonce');
        ?>
        <table class="form-table mt-0">
            <tr>
                <th style="width:160px">
                    <label for="reklam_ozel_url">Özel URL (opsiyonel)</label>
                </th>
                <td>
                    <input type="url" id="reklam_ozel_url" name="reklam_ozel_url"
                           value="<?php echo esc_attr($url); ?>"
                           class="regular-text" placeholder="https://...">
                    <p class="description">
                        Dolu ise reklama tıklandığında bu URL'ı açar (yeni sekmede).
                        Boş bırakılırsa reklamın kendi sayfası gösterilir.
                    </p>
                </td>
            </tr>
            <tr>
                <th><label class="fw-normal" for="reklam_baslangic_tarihi">Yayın Başlangıcı</label></th>
                <td>
                    <input type="date" id="reklam_baslangic_tarihi" name="reklam_baslangic_tarihi"
                           value="<?php echo esc_attr($start_date); ?>">
                    <p class="description">Boş bırakılırsa tarih sınırı uygulanmaz.</p>
                </td>
            </tr>
            <tr>
                <th><label class="fw-normal" for="reklam_bitis_tarihi">Yayın Bitişi</label></th>
                <td>
                    <input type="date" id="reklam_bitis_tarihi" name="reklam_bitis_tarihi"
                           value="<?php echo esc_attr($end_date); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    /** Reklam meta verilerini kaydet */
    public function save_reklam_metabox(int $post_id): void {
        if (!isset($_POST['mevzu_reklam_meta_nonce'])) return;
        if (!wp_verify_nonce($_POST['mevzu_reklam_meta_nonce'], 'mevzu_reklam_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta($post_id, 'reklam_ozel_url',         esc_url_raw($_POST['reklam_ozel_url'] ?? ''));
        update_post_meta($post_id, 'reklam_baslangic_tarihi', sanitize_text_field($_POST['reklam_baslangic_tarihi'] ?? ''));
        update_post_meta($post_id, 'reklam_bitis_tarihi',     sanitize_text_field($_POST['reklam_bitis_tarihi'] ?? ''));
    }

    /** Swiper transient'ını silmek için */
    public function invalidate_swiper_transient(): void {
        delete_transient('anasayfa_ust_reklam_sorgusu');
    }

    public function maybe_invalidate_swiper_transient(int $post_id): void {
        if (get_post_type($post_id) === 'reklam') {
            delete_transient('anasayfa_ust_reklam_sorgusu');
        }
    }

    /** Shortcode: [mevzu_reklam slot="govde_ust"] */
    public function shortcode($atts): string {
        $atts = shortcode_atts(['slot' => ''], $atts, 'mevzu_reklam');
        ob_start();
        mevzu_reklam(sanitize_key($atts['slot']));
        return ob_get_clean();
    }

    /** Swiper Reklam Alanını her yerde çalışacak şekilde Render Eder (Çağrı şablonlarda yapılır) */
    public static function render_swiper(): void {
        if (!get_option('options_ust_reklam_alani')) return;

        $swiper_goruntule = get_option('options_ust_reklam_goruntuleme', 'anasayfa');
        $sayfa_uygun = self::is_display_context($swiper_goruntule);

        // Reklam post type single sayfasındaysak sonsuz döngü/mantık hatası olmasın
        if (is_singular('reklam')) $sayfa_uygun = false;

        if (!$sayfa_uygun) return;

        $today = current_time('Y-m-d');
        $reklam_query = new \WP_Query([
            'post_type'      => 'reklam',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $gosterilecek = [];
        if ($reklam_query->have_posts()) {
            while ($reklam_query->have_posts()) {
                $reklam_query->the_post();
                $rid       = get_the_ID();
                $baslangic = get_post_meta($rid, 'reklam_baslangic_tarihi', true);
                $bitis     = get_post_meta($rid, 'reklam_bitis_tarihi', true);
                if ($baslangic && $today < $baslangic) continue;
                if ($bitis     && $today > $bitis)     continue;
                $gosterilecek[] = $rid;
            }
            wp_reset_postdata();
        }

        if (empty($gosterilecek)) return;

        $swiper_tip = get_option('options_ust_reklam_tip', 'swiper');
        $can_edit   = current_user_can('edit_posts');
        $edit_class = 'position-absolute top-0 start-0 btn btn-secondary py-1 px-2 d-flex align-items-center rounded small-2 rounded-pill fw-semibold m-2 border-0 shadow-none';

        if ($swiper_tip === 'swiper') {
            echo '<div class="mb-3">';
            echo '<div class="swiper rounded shadow-sm my-3 position-relative" id="swiper-ustreklam">';
            echo '<div class="swiper-wrapper">';
            foreach ($gosterilecek as $rid) {
                $ozel_url = get_post_meta($rid, 'reklam_ozel_url', true);
                $href     = $ozel_url ?: get_permalink($rid);
                $target   = $ozel_url ? ' target="_blank" rel="noopener nofollow"' : '';
                
                echo '<div class="swiper-slide position-relative">';
                if ($can_edit) {
                    echo '<a href="' . get_edit_post_link($rid) . '" class="' . $edit_class . '"><i class="ri-pencil-line fs-6 me-1"></i> Reklamı Düzenle</a>';
                }
                echo '<a href="' . esc_url($href) . '"' . $target . ' class="d-block" aria-label="' . esc_attr(get_the_title($rid)) . '">';
                echo get_the_post_thumbnail($rid, 'full', ['title' => get_the_title($rid), 'loading' => 'lazy', 'class' => 'rounded-0 h-auto w-100']);
                echo '</a></div>';
            }
            echo '</div><div class="swiper-pagination"></div></div></div>';
        } else {
            echo '<div class="mevzu-reklam-liste my-3">';
            foreach ($gosterilecek as $rid) {
                $ozel_url = get_post_meta($rid, 'reklam_ozel_url', true);
                $href     = $ozel_url ?: get_permalink($rid);
                $target   = $ozel_url ? ' target="_blank" rel="noopener nofollow"' : '';
                
                echo '<div class="my-2 rounded overflow-hidden shadow-sm position-relative">';
                if ($can_edit) {
                    echo '<a href="' . get_edit_post_link($rid) . '" class="' . $edit_class . '"><i class="ri-pencil-line fs-6 me-1"></i> Reklamı Düzenle</a>';
                }
                echo '<a href="' . esc_url($href) . '"' . $target . ' class="d-block" aria-label="' . esc_attr(get_the_title($rid)) . '">';
                echo get_the_post_thumbnail($rid, 'full', ['title' => get_the_title($rid), 'loading' => 'lazy', 'class' => 'w-100 h-auto']);
                echo '</a></div>';
            }
            echo '</div>';
        }
    }
}

new Mevzu_Ads_Manager();

// ──────────────────────────────────────────────────────────────
//  Genel çağrı fonksiyonu — şablonlarda kullanın
//  mevzu_reklam('govde_ust');
// ──────────────────────────────────────────────────────────────
function mevzu_reklam(string $id): void {
    // `reklamlari_gizle` meta alanı aktifse reklamı bastır
    if (is_singular() && get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 1) {
        return;
    }

    $data = Mevzu_Ads_Manager::get($id);

    // Yayın tarihi kontrolü
    $today = current_time('Y-m-d');
    if (!empty($data['start_date']) && $today < $data['start_date']) {
        if ($data['placeholder']) echo mevzu_reklam_placeholder($id);
        return;
    }
    if (!empty($data['end_date']) && $today > $data['end_date']) {
        if ($data['placeholder']) echo mevzu_reklam_placeholder($id);
        return;
    }

    if (!$data['active']) {
        // Alan kapalı ise hiçbir şey gösterme (placeholder dahil).
        return;
    }

    $can_manage_ads = current_user_can('manage_options');
    $edit_btn_html  = '';
    if ($can_manage_ads) {
        $edit_btn_html = '<a href="' . esc_url(admin_url('admin.php?page=mevzu-reklamlar#' . $id)) . '" class="position-absolute top-0 start-0 btn btn-secondary py-1 px-2 d-flex align-items-center rounded small-2 rounded-pill fw-semibold m-2 border-0 shadow-none" target="_blank" title="Reklam Alanını Yönet"><i class="ri-pencil-line fs-6 me-1"></i> Reklamı Düzenle</a>';
    }

    if ($data['type'] === 'html' && !empty($data['html_code'])) {
        echo '<div class="mevzu-reklam-alani mevzu-reklam-html ' . esc_attr($id) . ' my-3 position-relative" style="min-height:20px;">';
        echo $edit_btn_html;
        echo $data['html_code']; // wp_kses_post uygulandı
        echo '</div>';
        return;
    }

    if ($data['type'] === 'image' && !empty($data['image_id'])) {
        $img = wp_get_attachment_image_src($data['image_id'], 'full');
        if (!$img) {
            if ($data['placeholder']) echo mevzu_reklam_placeholder($id);
            return;
        }
        $img_url = $img[0];
        $width   = (int) $img[1];
        $height  = (int) $img[2];
        $title   = esc_attr($data['link_title'] ?: 'Reklam');
        $link    = $data['link_url'];
        $dims    = ($width && $height) ? ' width="' . $width . '" height="' . $height . '"' : '';
        echo '<div class="mevzu-reklam-alani mevzu-reklam-image ' . esc_attr($id) . ' my-3 rounded overflow-hidden shadow-sm position-relative">';
        echo $edit_btn_html;
        if ($link) {
            echo '<a class="d-block" href="' . esc_url($link) . '" title="' . $title . '" target="_blank" rel="noopener nofollow">';
        }
        echo '<img src="' . esc_url($img_url) . '" alt="' . $title . '" title="' . $title . '"' . $dims . ' loading="lazy" class="w-100">';
        if ($link) echo '</a>';
        echo '</div>';
        return;
    }

    // Hiçbir içerik yok — placeholder göster
    if ($data['placeholder']) {
        echo mevzu_reklam_placeholder($id);
    }
}

/** Placeholder HTML */
function mevzu_reklam_placeholder(string $id): string {
    $zones = Mevzu_Ads_Manager::zones();
    $label = isset($zones[$id]) ? $zones[$id]['label'] : str_replace('_', ' ', $id);

    $edit_btn_html = '';
    if (current_user_can('manage_options')) {
        $edit_btn_html = '<a href="' . esc_url(admin_url('admin.php?page=mevzu-reklamlar#' . $id)) . '" class="position-absolute top-0 start-0 btn btn-secondary py-1 px-2 d-flex align-items-center rounded small-2 rounded-pill fw-semibold m-2 border-0 shadow-none" target="_blank" title="Reklam Alanını Yönet"><i class="ri-pencil-line fs-6 me-1"></i> Reklamı Düzenle</a>';
    }

    return '<div class="mevzu-reklam-placeholder text-center p-4 shadow-sm rounded-3 small my-3 my-lg-4 bg-white position-relative ' . esc_attr($id) . '">' . 
        $edit_btn_html . '
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24">
            <path class="primary" fill="currentColor" d="M11.71 17.99A5.993 5.993 0 0 1 6 12c0-3.31 2.69-6 6-6c3.22 0 5.84 2.53 5.99 5.71l-2.1-.63a3.999 3.999 0 1 0-4.81 4.81zM22 12c0 .3-.01.6-.04.9l-1.97-.59c.01-.1.01-.21.01-.31c0-4.42-3.58-8-8-8s-8 3.58-8 8s3.58 8 8 8c.1 0 .21 0 .31-.01l.59 1.97c-.3.03-.6.04-.9.04c-5.52 0-10-4.48-10-10S6.48 2 12 2s10 4.48 10 10m-3.77 4.26L22 15l-10-3l3 10l1.26-3.77l4.27 4.27l1.98-1.98z"/>
        </svg>
        <span class="d-block opacity-75 fw-semibold mt-1">Reklam Alanı — ' . esc_html($label) . '</span>
        <a href="' . esc_url(get_bloginfo('url')) . '/iletisim" class="d-inline-block small text-dark mt-1 text-hover">Bu alana reklam ver</a>
    </div>';
}

// ──────────────────────────────────────────────────────────────
//  Geriye dönük uyumluluk — eski çağrıları yeni sisteme yönlendir
// ──────────────────────────────────────────────────────────────
function reklam(string $id): void {
    // Eski ID'leri yeni ID'lere dönüştür
    $map = [
        'govde_ust_reklam' => 'govde_ust',
        'ust_reklam'       => 'anasayfa_ust',
        'alt_reklam'       => 'anasayfa_alt',
    ];
    mevzu_reklam($map[$id] ?? $id);
}

function anasayfa_reklam(string $id): void {
    // Eski repeater ID'lerini yeni ID'lere dönüştür
    $map = [
        'govde_ust_reklam'  => 'govde_ust',
        'icerik_oncesi'     => 'icerik_oncesi',
        'icerik_sonrasi'    => 'icerik_sonrasi',
        'ust_reklam'        => 'anasayfa_ust',
        'alt_reklam'        => 'anasayfa_alt',
        'anasayfa_reklam_1' => 'anasayfa_1',
        'anasayfa_reklam_2' => 'anasayfa_2',
        'anasayfa_reklam_3' => 'anasayfa_3',
        'anasayfa_reklam_4' => 'anasayfa_4',
        'anasayfa_reklam_5' => 'anasayfa_5',
        'anasayfa_reklam_6' => 'anasayfa_6',
        'anasayfa_reklam_7' => 'anasayfa_7',
        'anasayfa_reklam_8' => 'anasayfa_8',
    ];
    mevzu_reklam($map[$id] ?? $id);
}
