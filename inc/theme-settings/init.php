<?php
/**
 * Mevzu² Tema Ayarları — Ana Giriş Dosyası
 * 
 * ACF eklentisinin yerini alan native ayar paneli altyapısı.
 * Bu dosya functions.php'den erken aşamada yüklenir.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Tema versiyonu
if (!defined('MEVZU_THEME_VERSION')) {
    $theme = wp_get_theme();
    define('MEVZU_THEME_VERSION', $theme->get('Version') ?: '?');
}

define('MEVZU_SETTINGS_PATH', __DIR__ . '/');
define('MEVZU_SETTINGS_URL', get_stylesheet_directory_uri() . '/inc/theme-settings/');

// 1. ACF Uyumluluk Katmanı (ilk yüklenmeli)
require_once MEVZU_SETTINGS_PATH . 'compat.php';

// Haber gömülü medya URL → HTML (tekil yazı şablonu + admin)
require_once MEVZU_SETTINGS_PATH . 'embed-media-helper.php';

// Cloudflare R2 video depolama
require_once MEVZU_SETTINGS_PATH . 'class-video-r2.php';

// 2. Modül Yöneticisi
require_once MEVZU_SETTINGS_PATH . 'class-module-manager.php';

// 3. Modülleri Kaydet
mevzu_register_modules();

function mevzu_register_modules() {
    $theme_dir = get_template_directory();
    $bulk_image_resizer_file = $theme_dir . '/inc/bulk-image-resizer/bulk-image-resizer.php';

    if ( file_exists( $theme_dir . '/inc/tts/constants.php' ) ) {
        require_once $theme_dir . '/inc/tts/constants.php';
    }
    
    // — Üyelik Sistemi —
    Mevzu_Module_Manager::register('membership', array(
        'name'        => 'Üyelik Sistemi',
        'description' => 'Kullanıcı kayıt, giriş, bildirim ve abonelik sistemi.',
        'icon'        => 'dashicons-groups',
        'version'     => '1.5',
        'is_premium'  => false,
        'init_file'   => $theme_dir . '/inc/membership/init.php',
        'default'     => true,
        'category'    => 'genel',
    ));
    
    // — Mevzu² AI (TTS) —
    Mevzu_Module_Manager::register( defined( 'MEVZU_YZ_MODULE_SLUG' ) ? MEVZU_YZ_MODULE_SLUG : 'yapay-zeka', array(
        'name'        => 'Mevzu² AI',
        'description' => 'Tema için yapay zeka destek modu.',
        'icon'        => 'ri-robot-2-line',
        'icon_type'   => 'remix',
        'version'     => '1.4',
        'is_premium'  => true,
        'init_file'   => $theme_dir . '/inc/tts/init.php',
        'default'     => true,
        'category'    => 'icerik',
    ));
    
    // — Otomasyon (Popup & Ramazan) —
    Mevzu_Module_Manager::register('automation', array(
        'name'        => 'Otomasyon',
        'description' => 'Bayram/tatil popup otomasyonu ve Ramazan modu yönetimi.',
        'icon'        => 'dashicons-calendar-alt',
        'version'     => '2.3',
        'is_premium'  => false,
        'init_file'   => '', // class-popup-page.php zaten init edilir
        'default'     => true,
        'category'    => 'otomasyon',
    ));
    
    // — Resmi İlan Sistemi —
    Mevzu_Module_Manager::register('resmi-ilanlar', array(
        'name'        => 'Resmi İlan Sistemi',
        'description' => 'Resmi ilanların yönetimi ve listelenmesi.',
        'icon'        => 'dashicons-feedback',
        'version'     => '1.8',
        'is_premium'  => false,
        'init_file'   => $theme_dir . '/inc/resmi-ilanlar/init.php',
        'default'     => true,
        'category'    => 'icerik',
    ));

    // — Firma Rehberi —
    Mevzu_Module_Manager::register('firma-rehberi', array(
        'name'        => 'Firma Rehberi',
        'description' => 'Yerel firma rehberi: başvuru formu, Google Maps harita entegrasyonu ve moderasyon sistemi.',
        'icon'        => 'dashicons-store',
        'version'     => '1.3',
        'is_premium'  => true,
        'init_file'   => $theme_dir . '/inc/firma-rehberi/init.php',
        'default'     => true,
        'category'    => 'icerik',
    ));

    // — Toplu Görsel Optimizasyonu —
    Mevzu_Module_Manager::register('bulk-image-resizer', array(
        'name'        => 'Toplu Görsel Optimizasyonu',
        'description' => 'Görselleri toplu optimize eder, boyutlandırır ve medya kütüphanesi performansını artırır.',
        'icon'        => 'dashicons-format-image',
        'version'     => '1.9',
        'is_premium'  => false,
        'init_file'   => file_exists($bulk_image_resizer_file) ? $bulk_image_resizer_file : '',
        'default'     => true,
        'category'    => 'icerik',
    ));

    // — Sosyal Otomasyon —
    Mevzu_Module_Manager::register('social-automation', array(
        'name'        => 'Sosyal Otomasyon',
        'description' => 'Yazılar yayınlandığında otomatik olarak sosyal medya platformlarına ve webhook\'a paylaşır.',
        'icon'        => 'ri-share-forward-line',
        'icon_type'   => 'remix',
        'version'     => '1.0',
        'is_premium'  => false,
        'init_file'   => $theme_dir . '/inc/social-automation/init.php',
        'default'     => false,
        'category'    => 'sosyal',
    ));
}

// 4. Lisans Sistemi (Admin + Frontend — site ID & banned kontrolü için)
require_once MEVZU_SETTINGS_PATH . 'class-license.php';

// Reklam Yöneticisi (Admin + Frontend fonksiyonları)
require_once MEVZU_SETTINGS_PATH . 'class-ads-manager.php';

// Popup/Otomasyon sınıfı — hem admin paneli hem de frontend (get_active_popup) için gerekli
if (Mevzu_Module_Manager::is_active('automation')) {
    require_once MEVZU_SETTINGS_PATH . 'class-popup-page.php';
}

/**
 * Özel cron aralığı — wp-cron.php ve ön yüzde is_admin() false olduğu için burada kayıtlı olmalı.
 * Aksi halde mevzu_theme_update_check_cron yeniden planlanırken invalid_schedule oluşur.
 */
add_filter(
    'cron_schedules',
    static function ( $schedules ) {
        if ( ! isset( $schedules['mevzu_every_fifteen_minutes'] ) ) {
            $schedules['mevzu_every_fifteen_minutes'] = array(
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => 'Mevzu² tema güncelleme kontrolü (15 dk)',
            );
        }
        return $schedules;
    },
    1
);

// Tema güncelleme cron kancası (wp-cron her istekte; yönetici paneli şart değil)
require_once MEVZU_SETTINGS_PATH . 'class-admin-fields.php';
require_once MEVZU_SETTINGS_PATH . 'class-settings-page.php';
require_once MEVZU_SETTINGS_PATH . 'class-user-fields.php';

// 5. Admin Ayar Paneli
if (is_admin()) {
    require_once MEVZU_SETTINGS_PATH . 'class-import-export.php';
    require_once MEVZU_SETTINGS_PATH . 'class-post-metabox.php';
    require_once MEVZU_SETTINGS_PATH . 'class-category-fields.php';
    require_once MEVZU_SETTINGS_PATH . 'class-menu-fields.php';
    require_once MEVZU_SETTINGS_PATH . 'class-setup-wizard.php';
    require_once MEVZU_SETTINGS_PATH . 'class-changelog.php';
    require_once MEVZU_SETTINGS_PATH . 'class-visual-editor.php';
    new Mevzu_Visual_Editor();
}

// Görsel Düzenleyici — frontend preview desteği (admin bar gizleme + transient override)
if (!is_admin() && isset($_GET['mevzu_preview']) && $_GET['mevzu_preview'] === '1') {
    require_once MEVZU_SETTINGS_PATH . 'class-visual-editor.php';
    new Mevzu_Visual_Editor();
}

// 5. Aktif modülleri yükle (membership ve tts init dosyaları)
Mevzu_Module_Manager::load_modules();

/**
 * Bulk Image Resizer aktifken adminde optimize edilmemiş görsel uyarısını göster.
 */
function mevzu_bulk_image_resizer_unoptimized_count() {
    global $wpdb;

    $cache_key = 'mevzu_bir_unoptimized_count_v2';
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return absint($cached);
    }

    $posts_table    = $wpdb->posts;
    $postmeta_table = $wpdb->postmeta;

    // BIR'in işlediği görseller _bir_attachment_originalfilesize meta key'i alır.
    // Henüz bu key'i almamış görseller = optimize edilmemiş.
    $count = (int) $wpdb->get_var(
        "SELECT COUNT(p.ID)
         FROM {$posts_table} p
         LEFT JOIN {$postmeta_table} pm
            ON p.ID = pm.post_id
           AND pm.meta_key = '_bir_attachment_originalfilesize'
         WHERE p.post_type = 'attachment'
           AND p.post_mime_type LIKE 'image/%'
           AND pm.meta_id IS NULL"
    );

    set_transient($cache_key, $count, 5 * MINUTE_IN_SECONDS);
    return $count;
}

function mevzu_bulk_image_resizer_notice() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!Mevzu_Module_Manager::is_active('bulk-image-resizer')) {
        return;
    }

    // WebP dönüştürme kapalıysa notice gösterme
    $bir_settings = get_option('bulk_image_resizer', []);
    $webp_active  = isset($bir_settings['webp_active']) ? (int) $bir_settings['webp_active'] : 0;
    if ( $webp_active < 1 ) {
        return;
    }

    $count = mevzu_bulk_image_resizer_unoptimized_count();
    if ($count < 1) {
        return;
    }

    $optimize_url = admin_url('admin.php?page=hiz-guvenlik');
    ?>
    <div class="notice notice-warning">
        <p>
            <?php
            printf(
                'Optimize edilmemiş %d görsel var. <a href="%s">Optimize etmek için tıklayın</a>.',
                absint($count),
                esc_url($optimize_url)
            );
            ?>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'mevzu_bulk_image_resizer_notice');

/**
 * mevzu_whl_enabled = 0 ise wps-hide-login eklentisini pasif konuma getir.
 * Bu hook plugins_loaded'dan ÖNCE çalışmaz; dolayısıyla eklenti zaten devre dışı
 * bırakıldığında bu hook gereksizdir. Yine de savunmacı kontrol olarak bırakıyoruz.
 */
function mevzu_maybe_disable_whl() {
    if ( (int) get_option( 'mevzu_whl_enabled', 0 ) === 0 ) {
        // wps-hide-login aktifse devre dışı bırak (tek seferlik)
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
            deactivate_plugins( 'wps-hide-login/wps-hide-login.php' );
        }
    }
}
// Bu fonksiyonu burada çağırmıyoruz; devre dışı bırakma AJAX handler'dan yapılır.
