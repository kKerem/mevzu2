<?php
/**
 * Il popup di benvenuto quando installi il plugin
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/admin
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;
?>
<div class="wrap">
    <div class="notice notice-info is-dismissible">
        <div class="op-row-notice">
            <div class="op-row-notice-half">
                <h4 class="op-notice-half"><?php _e('Görsel Optimizasyonu eklentisini indirdiğiniz için teşekkürler', 'bulk-image-resizer'); ?></h4>
            </div>
            <div class="op-info-box">
                <p><?php _e("Bu eklenti açık kaynak kodludur ve ek ücretli paket içermez.", 'bulk-image-resizer'); ?>
                </p>
                <p>Başlamak için Mevzu² Ayarları > <a href="<?php echo admin_url('admin.php?page=hiz-guvenlik'); ?>">Hız &amp; Güvenlik</a> menüsüne gidin.</p>
            </div>
        </div>
    </div>
</div>