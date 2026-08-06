<?php
/**
 * La form di configurazione 
 * @var Bir_options_var $options i parametri stanno dentro 
 */
namespace bulk_image_resizer;

if (!isset($options) || !is_object($options)) {
    $options = new Bir_options_var();
}

$option_value = static function ($key, $default = '') use ($options) {
    $val = $options->$key; // private property'ler için __get tetiklenir
    return ($val !== null && $val !== '') ? $val : $default;
};
?>
<script>
    var admin_ajax = '<?php echo admin_url('admin-ajax.php'); ?>';
    //var total_images = <?php //echo $total_images; ?>;
</script>
<form id="opBulkImageResizerSetup">
    <input type="hidden" name="action" value="bir_save_configuration">
    <input type="hidden" name="version" value="2.0.0">
    <?php 
    /**
     * RESIZE CONFIGURATION
     */
    ?>
    <div class="bir-config-box js-config-box my-3">
        <p>
            Bu modül, sitedeki mevcut görselleri ve bundan sonra yüklenecek görselleri optimize etmenizi sağlar. Optimizasyon sırasında orijinal görsel saklanır; böylece sorun durumunda geri yükleme yapabilirsiniz. Disk alanı sorununuz varsa daha sonra orijinal görselleri silebilirsiniz. Görsel işlemleri sekmesinden ilgili butonu kullanın. Görsel optimizasyonu yapılırken küçük boyutlu görseller (thumbnail) de yeniden üretilir. Görsel adı veya uzantısı değiştiğinde modül, yazılar ve postmeta içindeki bağlantıları da güncelleyerek kırık görsel oluşmasını engeller.
        </p>
        <div class="bir-config-row mevzu-field mb-0">
            <div class="row g-3 align-items-center w-100">
                <div class="col-12 col-lg-auto">
                    <div class="op-form-field-small">
                        <div class="switch-content">
                            <label class="switch js-running-switch-disable">
                                <input type="checkbox" value="1" name="resize_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($option_value('resize_active', 0)) == 1) ? 'checked="checked"' : ""; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label for="resize_active" class="mb-0"><strong><?php _e('Boyutlandırma', 'bulk-image-resizer'); ?></strong></label>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e('Seçilen değerlerden büyük yüklenen görselleri kırpar. Belirlenen boyutları aşan görseller yeniden boyutlandırılır. Etkinse hem yükleme anında hem de toplu işlem sırasında uygulanır.', 'bulk-image-resizer'); ?></p>
            <div class="row align-items-center mt-2 bir-config-advanced-row">
                <div class="col-12 col-lg-auto">
                    <fieldset>
                        <legend class="small-2 mb-1 text-muted"><?php _e('Boyutlar', 'bulk-image-resizer'); ?></legend>
                        <?php Bir_config_fn::html_select_dimensions($option_value('max_width', 0) . "x" . $option_value('max_height', 0)); ?>
                    </fieldset>
                </div>
                <div class="col-12 col-lg-auto">
                    <div class=" js-config-advanced">
                        <fieldset>
                            <legend class="small-2 mb-1 text-muted"><?php _e('Boyut Tercihi', 'bulk-image-resizer'); ?></legend>
                            <div class="d-flex align-items-center js-custom-dimension text-body">
                                <div class="op-form-field-small me-2">
                                    <input class="js-running-input-disable form-control form-control-sm small py-1 px-2" style="width:65px;height:34.5px" name="max_width" type="number" id="resizeMaxWidth" value="<?php echo esc_attr($option_value('max_width', 1920)); ?>">
                                </div>
                                x
                                <div class="op-form-field-small ms-2">
                                    <input class="js-running-input-disable form-control form-control-sm small py-1 px-2" style="width:65px;height:34.5px" name="max_height" type="number" id="resizeMaxHeight" value="<?php echo esc_attr($option_value('max_height', 1080)); ?>">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
    </div>
            
    <hr>


    <?php 
    /**
     * WEBP IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box my-3">
        <div class="bir-config-row mevzu-field mb-0">
            <div class="row g-3 align-items-center w-100">
                <div class="col-12 col-lg-auto">
                    <div class="op-form-field-small">
                        <div class="switch-content">
                            <label class="switch js-running-switch-disable">
                                <input type="checkbox" value="1" name="webp_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($option_value('webp_active', 0)) == 1) ? 'checked="checked"' : ""; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label for="webp_active" class="mb-0"><strong><?php _e('WEBP’ye dönüştür', 'bulk-image-resizer'); ?></strong></label>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e('Tüm görselleri WEBP formatına çevirmek site performansını artırmanın etkili bir yoludur. Aynı kalitede daha iyi sıkıştırma sağlar ve PNG görsellerin de optimize edilmesine yardımcı olur.', 'bulk-image-resizer'); ?></p>
        </div>
    </div>
            
    <hr>

    <?php 
    /**
     * OPTIMIZE IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box my-3">
        <div class="bir-config-row mevzu-field mb-0">
            <div class="row g-3 align-items-center w-100">
                <div class="col-12 col-lg-auto">
                    <div class="op-form-field-small">
                        <div class="switch-content">
                            <label class="switch js-running-switch-disable">
                                <input type="checkbox" value="1" name="optimize_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($option_value('optimize_active', 0)) == 1) ? 'checked="checked"' : ""; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label for="optimize_active" class="mb-0"><strong><?php _e('Optimizasyon', 'bulk-image-resizer'); ?></strong></label>
                </div>
            </div>
            <p class="description m-0 mt-2"><?php _e('Boyutlandırma veya WEBP dönüşümü yapılırken görseller otomatik optimize edilir. Çoğu site için orta kalite ideal seçenektir.', 'bulk-image-resizer'); ?></p>
            <div class="row g-3 mt-2 align-items-center bir-default-configuration w-100">
                <div class="col-12 col-lg-auto">
                    <fieldset>
                        <legend class="small-2 mb-1 text-muted"><?php _e('Görsel Kalitesi', 'bulk-image-resizer'); ?></legend>
                        <div class="op-form-field">
                            <?php Bir_config_fn::html_select_quality($option_value('quality', 75)); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
        
    <hr>
    
    <?php 
    /**
     * RENAME IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box my-3">
        <div class="bir-config-row mevzu-field mb-0">
            <div class="row g-3 align-items-center w-100">
                <div class="col-12 col-lg-auto">
                    <div class="op-form-field-small">
                        <div class="switch-content">
                            <label class="switch js-running-switch-disable">
                                <input type="checkbox" value="1" name="rename_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($option_value('rename_active', 0)) == 1) ? 'checked="checked"' : ""; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <label for="rename_active" class="mb-0"><strong><?php _e('Dosya adlandırma', 'bulk-image-resizer'); ?></strong></label>
                </div>
            </div>
            <p class="description m-0 mt-2">
                <?php _e('Görsel dosya adları çoğu zaman düzensiz olur; bu nedenle yeniden adlandırma SEO ve düzen açısından faydalıdır.', 'bulk-image-resizer'); ?>
                <?php _e('Etkin olduğunda görsel adları yükleme anında ve toplu işlemlerde değiştirilir. <br>Ad bir kez değiştirildiğinde sonraki toplu işlemlerde korunur.', 'bulk-image-resizer'); ?>
                <?php _e('Dosya adıyla birlikte WordPress tarafında üretilen tüm thumbnail adları da güncellenir. <br>Görsel kullanan içeriklerde bağlantılar yeni adla güncellenir. Yine de işlem sonrasında kontrol yapmanız önerilir. <br>WordPress dışı özel tablolar kullanan eklentiler otomatik güncellenmeyebilir. Bu nedenle işlemden önce yedek almanız tavsiye edilir.', 'bulk-image-resizer'); ?>  
            </p>
            <div class="row g-3 mt-2 align-items-center bir-default-configuration w-100">
                <div class="col-12 col-lg-auto">
                    <fieldset>
                        <legend class="small-2 mb-1 text-muted"><?php _e('Yeni ad', 'bulk-image-resizer'); ?></legend>
                        <div class="op-form-field">
                            <?php Bir_config_fn::html_select_rename($option_value('rename', '[image_name]-[uniqid]')); ?>
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="row g-3 mt-0 mb-3 w-100">
                <div class="col-12 col-lg-auto">
                    <label class="adfo-checkbox bir-checkbox-new-imgs rounded-circle m-0">
                        <input type="checkbox" name="rename_change_title" value="1" <?php echo (absint($option_value('rename_change_title', 0)) == 1) ? 'checked="checked"' : ""; ?>>
                        <div class="adfo-checbox-box-bg"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 2.5 3.4L17.9 8z"></path></svg>
                        <span><?php _e('Düzenli başlık', 'bulk-image-resizer'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="bir-config-advanced-row js-config-advanced">
            <div class="row g-3 g-lg-4">
                <div class="col-12">
                    <div class="row g-2 align-items-center js-custom-rename">
                        <div class="col-12 col-md-auto">
                            <div class="op-form-label-big"><?php _e('Özel', 'bulk-image-resizer'); ?></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="op-form-field">
                                <input id="birRealRename" class="js-running-input-disable bir-input-long" name="rename" type="text" value="<?php echo esc_attr($option_value('rename', '[image_name]-[uniqid]')); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="op-form-row js-custom-rename">
                        <b>[uniqid]</b> <?php _e('Benzersiz bir kimlik üretir', 'bulk-image-resizer'); ?><br>
                        <b>[md5]</b> <?php _e('Orijinal adın md5 değeriyle değiştirilir', 'bulk-image-resizer'); ?><br>
                        <b>[id]</b> <?php _e('Artan sıralı numara ile değiştirilir', 'bulk-image-resizer'); ?> <br>
                        <b>[image_name]</b> <?php _e('Temizlenmiş orijinal görsel adı ile değiştirilir', 'bulk-image-resizer'); ?><br>
                        <b>[rand]</b> <?php _e('a-z0-9 aralığında rastgele karakter üretir', 'bulk-image-resizer'); ?><br>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="op-form-row js-custom-rename">
                        <b>[date]</b> <?php _e('Görselin yüklenme tarihiyle değiştirilir', 'bulk-image-resizer'); ?><br>
                        <b>[time]</b> <?php _e('Görselin yüklenme saatiyle değiştirilir', 'bulk-image-resizer'); ?><br>
                        <b>[timestamp]</b> <?php _e('Görselin yükleme zaman damgasıyla değiştirilir', 'bulk-image-resizer'); ?><br>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="alert alert-warning inline mt-3">
        <p class="m-0">
            <span class="dashicons dashicons-warning hg-warning-icon"></span>
            <strong>Dikkat!</strong>
            <?php _e('Kullanmadan önce mutlaka yedek alın!', 'bulk-image-resizer'); ?>
        </p>
    </div>

    <div class="mevzu-settings-actions mt-3">
        <button type="button" class="button button-primary js-running-btn-disable" id="bir-save-config-btn" onclick="save_config()">
            <?php _e('Değişiklikleri Kaydet', 'bulk-image-resizer'); ?>
        </button>
        <span id="bir-config-save-status" class="ms-2 small"></span>
    </div>

    <?php 
    /**
     * Puoi aggiungere html nel form setting
     *
     * @since 1.2.0
     */
        do_action( 'bulk-image-resizer-after-setup-form');
    ?>

</form>