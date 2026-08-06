<?php namespace bulk_image_resizer; ?>
<h3 class="h5 mb-3">Toplu işlem</h3>
<?php 
$info = Bir_list_functions::status();

if ($info['status'] == 'RUNNING' && $info['action'] == 'resize') {
    ?>
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 bir-config-warning" id="opConfigWarning" >
        <span><?php _e('Tamamlanmamış bir toplu optimizasyon bulundu. Devam etmek ister misiniz?', 'bulk-image-resizer'); ?></span>
        <button type="button" id="opRunBulk" class="button bg-primary text-white js-running-btn-disable" onClick="next_bulk()"><?php _e('Devam et', 'bulk-image-resizer'); ?></button>
    </div>
    <?php
}
if ($info['status'] == 'RUNNING' && $info['action'] != 'resize') {
    $info['percent'] = 0;
}
?>

<div class="alert alert-primary bir-config-info">
<?php _e('Görsel optimizasyonunu buradan başlatın. Ayarları değiştirmek için "Yapılandırma" sekmesini kullanın.', 'bulk-image-resizer'); ?>
</div>

<div id="bulkSuccessAlert" class="alert alert-success bir-config-success d-none">
<span class="dashicons dashicons-yes-alt"></span> <span id="bulkSuccessAlertMsg"><?php _e('Optimizasyon işlemi tamamlandı', 'bulk-image-resizer'); ?></span>
</div>

    
<div class="op-form-field mb-3">
    <button type="button" id="opRunBulk" class="button bg-primary text-white js-running-btn-disable" onClick="startBulk()"><?php _e('Görselleri işle', 'bulk-image-resizer'); ?></button>
</div>

<div class="bir-progress-box">
    <div class="bir-progress">
        <div class="bir-progress-bar bir-progress-disabled" id="progress_bar" role="progressbar" style="width: <?php echo $info['percent']; ?>%;" aria-valuenow="<?php echo $info['percent']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $info['percent']; ?>%</div>
    </div>
</div>

<div id="birBulkInfo" class="mb-2 fw-semibold"></div>
<div id="birBulkLog" class="bir-box-log border rounded p-3 bg-light-subtle"></div>
<br>
<div class="alert alert-secondary bir-config-info">
<?php _e('Varsayılan olarak yüklenen orijinal görsel saklanır. "Geri yükle" ile orijinal görselleri ve adlarını geri getirebilirsiniz. Disk alanı sorununuz varsa orijinal görselleri silebilirsiniz. Bu işlem geri alınamaz!', 'bulk-image-resizer'); ?>
</div>
<div class="op-form-field d-flex flex-wrap gap-2">
    <button type="button" id="opRunBulk" class="btn btn-outline-danger js-running-btn-disable" onClick="startRestore()"><?php _e('Geri yükle', 'bulk-image-resizer'); ?></button>
    <button type="button" id="opRunBulk" class="btn btn-danger js-running-btn-disable" onClick="startRemoveOriginal()"><?php _e('Orijinal görselleri sil', 'bulk-image-resizer'); ?></button>
</div>
