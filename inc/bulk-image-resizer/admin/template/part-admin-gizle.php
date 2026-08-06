<?php
/**
 * Admin URL Gizleme Sekmesi
 * wps-hide-login eklentisi ayarlarını yönetir.
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

// $whl_enabled, $whl_page, $whl_redirect — bir-container.php tarafından tanımlandı
$site_url   = trailingslashit( home_url() );
$use_slash  = '/' === substr( get_option( 'permalink_structure', '' ), -1, 1 );
$whl_active = (bool) $whl_enabled;
?>

<p class="text-body">
    Admin paneline giriş sayfası olan <kbd><?php echo esc_html( $site_url ); ?>wp-admin</kbd> sayfasına erişilmesini istemiyorsanız bu özelliği açarak admin panelinin adresini istediğiniz gibi yapabilirsiniz.
</p>
<div class="alert alert-warning inline mb-3 p-2">
    <p class="m-0">
        <span class="dashicons dashicons-warning hg-warning-icon"></span>
        <strong><?php _e( 'Dikkat!', 'bulk-image-resizer' ); ?></strong>
        <?php _e( 'Özel giriş URL\'sini değiştirmeden önce mutlaka not alın. Unutursanız giriş yapamaz hale gelirsiniz!', 'bulk-image-resizer' ); ?>
    </p>
</div>

<form id="hg-hide-login-form">
    <div class="mevzu-field bir-config-box js-config-box my-3 small">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-auto">
                <div class="switch-content">
                    <label class="switch ms-0">
                        <input type="checkbox" name="mevzu_whl_enabled" value="1" id="hg_whl_master" <?php checked( $whl_active ); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-lg-auto">
                <label class="mb-0"><strong><?php _e( 'Admin URL Gizleme', 'bulk-image-resizer' ); ?></strong></label>
            </div>
        </div>
        <p class="description m-0 mt-2">
            <span id="hg_whl_on_lbl"  class="text-success fw-semibold<?php echo $whl_active ? '' : ' d-none'; ?>"><?php _e( 'Aktif — Özel giriş URL\'si kullanılıyor', 'bulk-image-resizer' ); ?></span>
            <span id="hg_whl_off_lbl" class="text-secondary<?php echo $whl_active ? ' d-none' : ''; ?>"><?php _e( 'Pasif — Standart wp-login.php kullanılıyor', 'bulk-image-resizer' ); ?></span>
        </p>
        <p class="description"><?php _e( 'Kapatıldığında devre dışı bırakılır ve /wp-admin ve /wp-login.php sayfaları normal bir şekilde çalışır.', 'bulk-image-resizer' ); ?></p>
    </div>

    <!-- URL Alanları -->
    <div id="hg_whl_fields" class="<?php echo $whl_active ? '' : 'hg-fields-disabled'; ?>">

        <hr>
        <h3><?php _e( 'Özel Giriş URL\'si', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-auto">
                    <label for="hg_whl_page" class="mb-0"><strong><?php _e( 'Login URL Slug\'u', 'bulk-image-resizer' ); ?></strong></label>
                </div>
                <div class="col-12 col-lg-auto">
                    <div class="input-group input-group-sm hg-url-group">
                        <span class="input-group-text"><?php echo esc_html( $site_url ); ?></span>
                        <input type="text" name="whl_page" id="hg_whl_page" class="form-control form-control-sm small py-1 px-2" style="border-top-left-radius:0 !important;border-bottom-left-radius:0 !important;" value="<?php echo esc_attr( $whl_page ); ?>" <?php echo $whl_active ? '' : 'disabled'; ?> placeholder="giris">
                    </div>
                </div>
            </div>
            <p class="description m-0 mt-2">
                <?php printf( __( 'Mevcut giriş URL\'si: <a href="%1$s" target="_blank"><strong>%1$s</strong></a>', 'bulk-image-resizer' ), esc_url( $site_url . $whl_page . ( $use_slash ? '/' : '' ) ) ); ?>
            </p>
        </div>

        <hr>
        <h3><?php _e( 'Yönlendirme URL\'si', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-auto">
                    <label for="hg_whl_redirect" class="mb-0"><strong><?php _e( 'Yönlendirme Slug\'u', 'bulk-image-resizer' ); ?></strong></label>
                </div>
                <div class="col-12 col-lg-auto">
                    <div class="input-group input-group-sm hg-url-group">
                        <span class="input-group-text"><?php echo esc_html( $site_url ); ?></span>
                        <input type="text" name="whl_redirect_admin" id="hg_whl_redirect" class="form-control form-control-sm small py-1 px-2" style="border-top-left-radius:0 !important;border-bottom-left-radius:0 !important;" value="<?php echo esc_attr( $whl_redirect ); ?>" <?php echo $whl_active ? '' : 'disabled'; ?> placeholder="404">
                    </div>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e( 'Giriş yapılmadan wp-admin veya wp-login.php\'ye erişildiğinde yönlendirilecek URL.', 'bulk-image-resizer' ); ?></p>
        </div>

    </div><!-- /#hg_whl_fields -->

    <div class="mevzu-settings-actions">
        <button type="button" class="button button-primary" onclick="hgSaveHideLogin()">
            <?php _e( 'Admin URL Ayarlarını Kaydet', 'bulk-image-resizer' ); ?>
        </button>
        <span class="hg-save-status ms-2" id="hg-hide-login-status"></span>
    </div>

</form>

<script>
(function() {
    var master   = document.getElementById('hg_whl_master');
    var onLbl    = document.getElementById('hg_whl_on_lbl');
    var offLbl   = document.getElementById('hg_whl_off_lbl');
    var fields   = document.getElementById('hg_whl_fields');
    var pageInp  = document.getElementById('hg_whl_page');
    var redirInp = document.getElementById('hg_whl_redirect');

    function toggleFields(enabled) {
        if (enabled) {
            onLbl.classList.remove('d-none');
            offLbl.classList.add('d-none');
            fields.classList.remove('hg-fields-disabled');
            pageInp.removeAttribute('disabled');
            redirInp.removeAttribute('disabled');
        } else {
            onLbl.classList.add('d-none');
            offLbl.classList.remove('d-none');
            fields.classList.add('hg-fields-disabled');
            pageInp.setAttribute('disabled', true);
            redirInp.setAttribute('disabled', true);
        }
    }

    master.addEventListener('change', function() {
        toggleFields(this.checked);
    });
})();
</script>
