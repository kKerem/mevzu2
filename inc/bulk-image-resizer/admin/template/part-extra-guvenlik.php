<?php
/**
 * Extra Güvenlik Sekmesi
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

$so      = $sec_opts;

$fields = [
    [
        'key'   => 'security-headers',
        'title' => __( 'Güvenlik HTTP Başlıkları', 'bulk-image-resizer' ),
        'desc'  => __( 'Her yanıta güvenlik başlıkları ekler: <code>X-Content-Type-Options</code>, <code>X-Frame-Options: SAMEORIGIN</code>, <code>X-XSS-Protection</code>, <code>Referrer-Policy</code>, <code>Permissions-Policy</code>. Clickjacking ve MIME sniffing saldırılarını önler.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'login-rate-limit',
        'title' => __( 'Giriş Denemesi Sınırlama', 'bulk-image-resizer' ),
        'desc'  => __( '15 dakika içinde aynı IP\'den 5 başarısız giriş denemesi yapılırsa o IP geçici olarak engellenir. Brute-force saldırılarına karşı koruma sağlar.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'json-rest-api',
        'title' => __( 'JSON REST API\'yi Devre Dışı Bırak', 'bulk-image-resizer' ),
        'desc'  => __( 'Oturum açmamış kullanıcılar için REST API isteklerini engeller. Oturum açmış kullanıcılar normal şekilde erişebilir.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'htaccess-protection',
        'name'  => 'htaccess protection',
        'title' => __( '.htaccess Yazma Koruması', 'bulk-image-resizer' ),
        'desc'  => __( '.htaccess dosyasını salt-okunur (0444) yaparak yetkisiz değişikliklere karşı korur.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'remove-wp-ver',
        'title' => __( 'WordPress Sürümünü Gizle', 'bulk-image-resizer' ),
        'desc'  => __( 'WordPress sürüm bilgisini &lt;head&gt;\'den ve script/style URL\'lerinden kaldırır. Saldırganların hedeflemesini zorlaştırır. Ancak yeni bir güncelleme yayınlandığında tarayıcınız temanın stil dosyalarını önbellekten okumaya devam ettiği için ', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'disable-code-editor',
        'title' => __( 'Dahili Kod Editörünü Devre Dışı Bırak', 'bulk-image-resizer' ),
        'desc'  => __( 'WordPress\'in yerleşik tema/eklenti editörünü devre dışı bırakır. Aktifken tema ve eklenti kodlarını düzenlemek için FTP/SSH kullanmanız gerekir.', 'bulk-image-resizer' ),
        'warn'  => __( 'Bu seçenek aktifken WordPress panelinden kod düzenleyemezsiniz!', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'disable-wlw',
        'title' => __( 'WLW Manifest\'i Devre Dışı Bırak', 'bulk-image-resizer' ),
        'desc'  => __( 'Artık kullanılmayan Windows Live Writer manifest bağlantısını &lt;head&gt;\'den kaldırır.', 'bulk-image-resizer' ),
    ],
];

$extra_defaults = [
    'security-headers'    => true,
    'login-rate-limit'    => true,
    'json-rest-api'       => false,
    'htaccess protection' => false,
    'remove-wp-ver'       => false,
    'disable-code-editor' => false,
    'disable-wlw'         => true,
];
?>
<p class="description mb-3">
    <?php _e( 'Aşağıdaki seçenekler sitenizin güvenlik açıklarını kapatmanıza yardımcı olur.', 'bulk-image-resizer' ); ?>
</p>

<form id="hg-extra-guvenlik-form">

    <?php foreach ( $fields as $i => $f ) :
        $name = $f['name'] ?? $f['key'];
        $val  = isset( $so[ $name ] ) ? $so[ $name ] : ( $extra_defaults[ $name ] ?? false );
    ?>
    <?php if ( $i > 0 ) : ?><hr><?php endif; ?>
    <div class="mevzu-field bir-config-box js-config-box my-3 small">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-auto">
                <div class="switch-content">
                    <label class="switch ms-0">
                        <input type="checkbox" name="<?php echo esc_attr( $f['key'] ); ?>" value="1" <?php checked( ! empty( $val ) ); ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-lg-auto">
                <label class="mb-0"><strong><?php echo $f['title']; ?></strong></label>
            </div>
        </div>
        <p class="description m-0 mt-2"><?php echo $f['desc']; ?></p>
        <?php if ( ! empty( $f['warn'] ) ) : ?>
            <p class="description text-warning">
                <span class="dashicons dashicons-warning hg-warning-icon"></span>
                <?php echo $f['warn']; ?>
            </p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="mevzu-settings-actions">
        <button type="button" class="button button-primary" onclick="hgSaveSecurity()">
            <?php _e( 'Güvenlik Ayarlarını Kaydet', 'bulk-image-resizer' ); ?>
        </button>
        <span class="hg-save-status ms-2" id="hg-security-status"></span>
    </div>

</form>
