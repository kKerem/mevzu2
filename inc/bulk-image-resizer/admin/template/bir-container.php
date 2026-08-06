<?php
/**
 * Hız & Güvenlik — Ana Container Template
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

$info = Bir_list_functions::status();

// Güvenlik ayarlarını yükle
$sec_opts    = get_option( 'dsxmlrpc-settings', [] );
if ( ! is_array( $sec_opts ) ) $sec_opts = [];
$sec_default = [
    'dsxmlrpc-switcher'   => false,
    'xmlrpc-slug'         => '',
    'jetpack-switcher'    => false,
    'disabled-methods'    => ['pingback.ping', 'x-pingback', 'mt.getTrackbackPings', 'pingback.extensions.getPingbacks'],
    'White-list-IPs'      => '',
    'Black-list-IPs'      => '',
    'security-headers'    => true,
    'login-rate-limit'    => true,
    'json-rest-api'       => false,
    'htaccess protection' => false,
    'remove-wp-ver'       => true,
    'disable-code-editor' => false,
    'disable-wlw'         => true,
    'slow-heartbeat'      => true,
    'hotlink-fix'         => false,
    'remove-emojis'       => true,
    'remove-rss'          => false,
    'disable-oembed'      => false,
    'swiper_source'       => 'local',
    'select2_source'      => 'local',
    'jquery_source'       => 'wordpress',
];
foreach ( $sec_default as $k => $v ) {
    if ( ! isset( $sec_opts[ $k ] ) ) $sec_opts[ $k ] = $v;
}

// Hide-login ayarları
$whl_enabled  = (int) get_option( 'mevzu_whl_enabled', 0 );
$whl_page     = get_option( 'whl_page', 'giris' );
$whl_redirect = get_option( 'whl_redirect_admin', '404' );
if ( empty( $whl_page ) ) $whl_page = 'giris';
if ( empty( $whl_redirect ) ) $whl_redirect = '404';
?>
<div class="wrap mevzu-settings-wrap" id="statusof_bulk_image_container">

    <h1 class="mb-3">
        <?php _e( 'Hız & Güvenlik', 'bulk-image-resizer' ); ?>
    </h1>

    <?php if ( $check_fn_editor !== '' ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $check_fn_editor ); ?></p>
        </div>
    <?php endif; ?>

    <div class="mevzu-settings-container">

        <!-- ===== SOL: Sekme Navigasyonu ===== -->
        <div class="mevzu-settings-tabs" id="hg_tabs">

            <div class="hg-nav-group">
                <div class="hg-nav-group-title">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php _e( 'Görsel Optimizasyonu', 'bulk-image-resizer' ); ?>
                </div>
                <a href="#" class="tab-link active" data-tab="gorsel-config">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e( 'Yapılandırma', 'bulk-image-resizer' ); ?>
                </a>
                <a href="#" class="tab-link" data-tab="gorsel-islem">
                    <span class="dashicons dashicons-controls-repeat"></span>
                    <?php _e( 'Optimizasyon Alanı', 'bulk-image-resizer' ); ?>
                </a>
                <a href="#" class="tab-link" data-tab="gorsel-istatistik">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e( 'İstatistik', 'bulk-image-resizer' ); ?>
                </a>
            </div>

            <div class="hg-nav-group">
                <div class="hg-nav-group-title">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php _e( 'Güvenlik Önlemleri', 'bulk-image-resizer' ); ?>
                </div>
                <a href="#" class="tab-link" data-tab="xmlrpc">
                    <span class="dashicons dashicons-lock"></span>
                    <?php _e( 'XML-RPC Ayarları', 'bulk-image-resizer' ); ?>
                </a>
                <a href="#" class="tab-link" data-tab="extra-guvenlik">
                    <span class="dashicons dashicons-privacy"></span>
                    <?php _e( 'Extra Güvenlik', 'bulk-image-resizer' ); ?>
                </a>
                <a href="#" class="tab-link" data-tab="wp-hiz">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e( 'WordPress Hızlandırma', 'bulk-image-resizer' ); ?>
                </a>
                <a href="#" class="tab-link" data-tab="admin-gizle">
                    <span class="dashicons dashicons-hidden"></span>
                    <?php _e( 'Admin URL Gizleme', 'bulk-image-resizer' ); ?>
                </a>
            </div>

            <div class="hg-nav-group">
                <div class="hg-nav-group-title">
                    <span class="dashicons dashicons-performance"></span>
                    <?php _e( 'Frontend Kaynakları', 'bulk-image-resizer' ); ?>
                </div>
                <a href="#" class="tab-link" data-tab="kaynak-yukleme">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e( 'Kaynak Yükleme', 'bulk-image-resizer' ); ?>
                </a>
            </div>

        </div><!-- /.mevzu-settings-tabs -->

        <!-- ===== SAĞ: İçerik ===== -->
        <div class="mevzu-settings-content">

            <!-- Görsel İşlemleri Aksiyon Butonu -->
            <div id="hg-gorsel-actions" class="mevzu-settings-actions mb-3">
                <button type="button" class="button bg-primary text-white js-running-btn-disable" onclick="birExecuteBtn()">
                    <span class="dashicons dashicons-controls-play hg-btn-icon"></span>
                    <?php _e( 'Görselleri İşle', 'bulk-image-resizer' ); ?>
                </button>
                <button type="button" id="btnPause"  class="button" onclick="birPauseBtn()"  style="display:none"><?php _e( 'Duraklat', 'bulk-image-resizer' ); ?></button>
                <button type="button" id="btnResume" class="button" onclick="birResumetBtn()" style="<?php echo ( $info['status'] === 'RUNNING' && $info['action'] === 'resize' ) ? 'display:inline-block' : 'display:none'; ?>"><?php _e( 'Devam et', 'bulk-image-resizer' ); ?></button>
                <button type="button" id="btnStop"   class="button dbp-submit" onclick="birStopBtn()" style="display:none"><?php _e( 'Durdur', 'bulk-image-resizer' ); ?></button>
            </div>

            <div id="hg_tab_contents">

                <!-- TAB: Yapılandırma Ayarları -->
                <div id="gorsel-config" class="hg-tab-content hg-tab-active">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e( 'Yapılandırma', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-config.php'; ?>
                </div>

                <!-- TAB: Optimizasyon Alanı -->
                <div id="gorsel-islem" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-controls-repeat"></span>
                        <?php _e( 'Optimizasyon Alanı', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-bulk.php'; ?>
                </div>

                <!-- TAB: İstatistik -->
                <div id="gorsel-istatistik" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e( 'İstatistik', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-stat.php'; ?>
                </div>

                <!-- TAB: XML-RPC Ayarları -->
                <div id="xmlrpc" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-lock"></span>
                        <?php _e( 'XML-RPC Ayarları', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-xmlrpc.php'; ?>
                </div>

                <!-- TAB: Extra Güvenlik -->
                <div id="extra-guvenlik" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-privacy"></span>
                        <?php _e( 'Extra Güvenlik', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-extra-guvenlik.php'; ?>
                </div>

                <!-- TAB: WordPress Hızlandırma -->
                <div id="wp-hiz" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e( 'WordPress Hızlandırma', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-hizlandirma.php'; ?>
                </div>

                <!-- TAB: Admin URL Gizleme -->
                <div id="admin-gizle" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-hidden"></span>
                        <?php _e( 'Admin URL Gizleme', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-admin-gizle.php'; ?>
                </div>

                <!-- TAB: Kaynak Yükleme -->
                <div id="kaynak-yukleme" class="hg-tab-content">
                    <h2 class="hg-tab-heading">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e( 'Kaynak Yükleme', 'bulk-image-resizer' ); ?>
                    </h2>
                    <?php require_once __DIR__ . '/part-kaynak-yukleme.php'; ?>
                </div>

            </div><!-- /#hg_tab_contents -->
        </div><!-- /.mevzu-settings-content -->
    </div><!-- /.mevzu-settings-container -->
</div><!-- /#statusof_bulk_image_container -->
