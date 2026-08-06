<?php
/**
 * WordPress Hızlandırma Sekmesi
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) die;

$so = $sec_opts;

$fields = [
    [
        'key'   => 'slow-heartbeat',
        'title' => __( 'Heartbeat Yavaşlatma', 'bulk-image-resizer' ),
        'desc'  => __( 'WordPress Heartbeat API\'sini 60 saniyeye yavaşlatır. Sunucu kaynaklarını korur ve admin panelini hızlandırır.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'remove-emojis',
        'title' => __( 'Yerleşik Emojileri Kaldır', 'bulk-image-resizer' ),
        'desc'  => __( 'WordPress\'in kendi emoji JavaScript/CSS dosyalarını kaldırır. Tarayıcı emojileri çalışmaya devam eder.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'hotlink-fix',
        'title' => __( 'Hotlink Koruması', 'bulk-image-resizer' ),
        'desc'  => __( 'Diğer sitelerin görselerinizi ve dosyalarınızı doğrudan bağlamasını engeller. <code>.htaccess</code> dosyasına kural eklenerek çalışır.', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'remove-rss',
        'title' => __( 'RSS Feed\'ini Devre Dışı Bırak', 'bulk-image-resizer' ),
        'desc'  => __( 'RSS ve RSD feed\'lerini devre dışı bırakır. Haber/dergi sitesi değilseniz önerilir.', 'bulk-image-resizer' ),
        'warn'  => __( 'RSS ile abonelik sistemi kullanıyorsanız bu seçeneği aktif etmeyin!', 'bulk-image-resizer' ),
    ],
    [
        'key'   => 'disable-oembed',
        'title' => __( 'oEmbed\'i Devre Dışı Bırak', 'bulk-image-resizer' ),
        'desc'  => __( 'YouTube, Twitter gibi sitelerin içerik önizlemelerini devre dışı bırakır. Ön yüzde <code>wp-embed.js</code> yüklenmez.', 'bulk-image-resizer' ),
    ],
];
?>
<p class="description mb-3">
    <?php _e( 'Gereksiz WordPress işlevlerini devre dışı bırakarak sayfa yükleme hızınızı artırın.', 'bulk-image-resizer' ); ?>
</p>

<form id="hg-hizlandirma-form">

    <?php foreach ( $fields as $i => $f ) :
        $val = isset( $so[ $f['key'] ] ) ? $so[ $f['key'] ] : false;
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
            <?php _e( 'Hızlandırma Ayarlarını Kaydet', 'bulk-image-resizer' ); ?>
        </button>
        <span class="hg-save-status ms-2" id="hg-security-status"></span>
    </div>

</form>
