<?php
/**
 * XML-RPC Ayarları Sekmesi
 * Güvenlik seçenekleri dsxmlrpc-settings option key'inde saklanır.
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

// $sec_opts değişkeni bir-container.php tarafından tanımlanmış
$so = $sec_opts;
$checked = function( $key ) use ( $so ) {
    return ! empty( $so[ $key ] ) ? 'checked' : '';
};
?>
<p class="description mb-3">
    <?php _e( 'XML-RPC, WordPress\'in uzak bağlantılara izin veren bir protokolüdür. WordPress 3.5\'ten itibaren varsayılan olarak açıktır ve brute force saldırılarına kapı açabilir. <strong>Kapalı tutmanız önerilir.</strong>', 'bulk-image-resizer' ); ?>
</p>

<form id="hg-xmlrpc-form">

    <div class="mevzu-field bir-config-box js-config-box my-3 small">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-auto">
                <div class="switch-content">
                    <label class="switch ms-0">
                        <input type="checkbox" name="dsxmlrpc-switcher" value="1" <?php echo $checked('dsxmlrpc-switcher'); ?> id="hg_xmlrpc_master">
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-lg-auto">
                <label class="mb-0"><strong><?php _e( 'XML-RPC Durumu', 'bulk-image-resizer' ); ?></strong></label>
            </div>
        </div>
        <p class="description m-0 mt-2">
            <span class="text-success fw-semibold hg-on-label<?php echo empty( $so['dsxmlrpc-switcher'] ) ? ' d-none' : ''; ?>"><?php _e( 'Açık — XML-RPC etkin', 'bulk-image-resizer' ); ?></span>
            <span class="text-danger fw-semibold hg-off-label<?php echo ! empty( $so['dsxmlrpc-switcher'] ) ? ' d-none' : ''; ?>"><?php _e( 'Kapalı (önerilen) — XML-RPC engellenmiş', 'bulk-image-resizer' ); ?></span>
        </p>
        <p class="description"><?php _e( 'Kapalıyken <code>xmlrpc.php</code> erişimi tamamen engellenir; açıkken aşağıdaki ek ayarlar uygulanır.', 'bulk-image-resizer' ); ?></p>
    </div>

    <hr>

    <!-- XML-RPC AÇIKKEN GÖRÜNECEK BÖLÜM -->
    <div id="hg_xmlrpc_open_opts" class="<?php echo empty( $so['dsxmlrpc-switcher'] ) ? 'd-none' : ''; ?>">

        <h3><?php _e( 'Slug Değiştirme', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-auto">
                    <label for="hg_xmlrpc_slug" class="mb-0"><strong><?php _e( 'Yeni Slug', 'bulk-image-resizer' ); ?></strong></label>
                </div>
                <div class="col-12 col-lg-auto">
                    <input type="text" name="xmlrpc-slug" id="hg_xmlrpc_slug" class="form-control form-control-sm small py-1 px-2" placeholder="Örnek: mobile-api" value="<?php echo esc_attr( $so['xmlrpc-slug'] ); ?>">
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e( '<code>xmlrpc.php</code> yerine özel URL kullanmak için girin. Boş bırakılırsa varsayılan URL kullanılır.', 'bulk-image-resizer' ); ?></p>
        </div>

        <hr>
        <h3><?php _e( 'Jetpack', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-auto">
                    <div class="switch-content">
                        <label class="switch ms-0">
                            <input type="checkbox" name="jetpack-switcher" value="1" <?php echo $checked('jetpack-switcher'); ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label class="mb-0"><strong><?php _e( 'Jetpack IP Whitelist', 'bulk-image-resizer' ); ?></strong></label>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e( 'Jetpack sunucu IP aralığını otomatik olarak whitelist\'e ekle', 'bulk-image-resizer' ); ?></p>
        </div>

        <hr>
        <h3><?php _e( 'IP Kara Listesi', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 g-lg-4">
                <div class="col-12">
                    <label for="hg_black_ips" class="mb-1"><strong><?php _e( 'Engellenen IP\'ler', 'bulk-image-resizer' ); ?></strong></label>
                    <textarea name="Black-list-IPs" id="hg_black_ips" rows="3" class="form-control form-control-sm small py-1 px-2" placeholder="127.0.0.1, 192.168.1.1"><?php echo esc_textarea( $so['Black-list-IPs'] ); ?></textarea>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e( 'XML-RPC\'ye erişimi engellenecek IP\'leri virgülle ayırarak girin.', 'bulk-image-resizer' ); ?></p>
        </div>

        <hr>
        <h3><?php _e( 'Devre Dışı Bırakılacak Yöntemler', 'bulk-image-resizer' ); ?></h3>
        <p class="description mb-2"><?php _e( 'Seçili XML-RPC yöntemleri çağrıldığında hata döndürülür.', 'bulk-image-resizer' ); ?></p>

        <?php
        $methods = [
            'pingback.ping'                    => 'pingback.ping',
            'mt.getTrackbackPings'             => 'mt.getTrackbackPings',
            'pingback.extensions.getPingbacks' => 'pingback.extensions.getPingbacks',
            'x-pingback'                       => 'x-pingback header',
            'mt.publishPost'                   => 'mt.publishPost',
            'mt.supportedTextFilters'          => 'mt.supportedTextFilters',
            'mt.supportedMethods'              => 'mt.supportedMethods',
        ];
        $disabled = is_array( $so['disabled-methods'] ) ? $so['disabled-methods'] : [];
        foreach ( $methods as $val => $label ) : ?>
            <div class="mevzu-field bir-config-box js-config-box my-2 small">
                <label class="mevzu-field-toggle fw-normal">
                    <input type="checkbox" name="disabled-methods[]" value="<?php echo esc_attr( $val ); ?>" id="dm_<?php echo sanitize_html_class( $val ); ?>"
                        <?php checked( in_array( $val, $disabled, true ) ); ?>>
                    <code><?php echo esc_html( $label ); ?></code>
                </label>
            </div>
        <?php endforeach; ?>

    </div><!-- /#hg_xmlrpc_open_opts -->

    <!-- XML-RPC KAPALI: Whitelist IP -->
    <div id="hg_xmlrpc_closed_opts" class="<?php echo ! empty( $so['dsxmlrpc-switcher'] ) ? 'd-none' : ''; ?>">

        <h3><?php _e( 'İzin Verilen IP\'ler (Whitelist)', 'bulk-image-resizer' ); ?></h3>

        <div class="mevzu-field bir-config-box js-config-box my-3 small">
            <div class="row g-3 g-lg-4">
                <div class="col-12">
                    <label for="hg_white_ips" class="mb-1"><strong><?php _e( 'Whitelist IP\'ler', 'bulk-image-resizer' ); ?></strong></label>
                    <textarea name="White-list-IPs" id="hg_white_ips" rows="3" class="form-control form-control-sm small py-1 px-2" placeholder="127.0.0.1, 192.168.1.1"><?php echo esc_textarea( $so['White-list-IPs'] ); ?></textarea>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e( 'XML-RPC engellenmiş olsa bile bu IP\'lerden erişime izin verilir.', 'bulk-image-resizer' ); ?></p>
        </div>

    </div>

    <div class="mevzu-settings-actions mt-3">
        <button type="button" class="button button-primary" id="hg-xmlrpc-save-btn" onclick="hgSaveSecurity()">
            <?php _e( 'XML-RPC Ayarlarını Kaydet', 'bulk-image-resizer' ); ?>
        </button>
        <span class="hg-save-status ms-2" id="hg-security-status"></span>
    </div>

</form>

<script>
document.getElementById('hg_xmlrpc_master').addEventListener('change', function() {
    var open   = document.getElementById('hg_xmlrpc_open_opts');
    var closed = document.getElementById('hg_xmlrpc_closed_opts');
    var onLbl  = document.querySelector('.hg-on-label');
    var offLbl = document.querySelector('.hg-off-label');
    if (this.checked) {
        open.classList.remove('d-none');
        closed.classList.add('d-none');
        onLbl.classList.remove('d-none');
        offLbl.classList.add('d-none');
    } else {
        open.classList.add('d-none');
        closed.classList.remove('d-none');
        onLbl.classList.add('d-none');
        offLbl.classList.remove('d-none');
    }
});
</script>
