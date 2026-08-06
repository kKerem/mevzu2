<?php
/**
 * Elementi grafici per il config
 */
namespace bulk_image_resizer;

class Bir_config_fn {
    /**
     * Genera il select per le dimensioni preset
     * @param string $val 1280x720|1920x1080|2560x1440|2100x2100| custom values widthxheight
     * @return string Html
     */
    static function html_select_dimensions($val) {
        $dim = array('1280x720'=>'HD', '1920x1080'=>'Full HD', '2560x1440'=>'Quad HD', '2100x2100'=>'Baskı 13x18cm', ''=>'Özel Boyut');
        ?>
        <select id="selectPresetDimension" name="op-preset-dim" class="js-running-input-disable form-control form-control-sm small py-1 px-2">
        <?php
            $find_selected = false;
            foreach ($dim as $key=>$label) {
                $label =  $label. (($key != "") ? " (".$key."px)" : ""); 
                if (($key == $val) || (!$find_selected && $key == "")) {
                    $find_selected = true;
                    $result_sel = $label;
                    $selected =  ' selected="selected"'; 
                } else {
                    $selected = "";
                }
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected
                ; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <?php
    }

    /**
     * Genera il select per la qualità delle immagini
     * @param string $val 
     * @return string Html
     */
    static function html_select_quality($val = BIR_QUALITY_MEDIUM) {
        $dim = array(BIR_QUALITY_HIGHT=>'Düşük Kalite', BIR_QUALITY_MEDIUM=>'Orta Kalite', BIR_QUALITY_LOW=>'Yüksek Kalite', BIR_QUALITY_LOSSLESS=>'Kayıpsız');
        ?>
        <select name="quality" id="settingQuality" class="js-running-input-disable">
        <?php
            foreach ($dim as $key=>$label) {
                $selected = ($key == $val) ? ' selected="selected"' : ""; 
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <p class="small mt-2 mb-0 text-muted">Görsel kalitesi düştükçe görselin dosya boyutu küçülür.<br><span class="text-primary"><span class="fw-semibold">1MB .JPEG</span> için tahmini sonuç:</span></p>
        <ul class="small text-body">
            <li class="m-0"><span class="fw-semibold">Düşük Kalite:</span> ~800–950KB~350–600KB <span class="text-success">(-40% ila arası -65% düşüş)</span></li>
            <li class="m-0"><span class="fw-semibold">Orta Kalite:</span> ~550–750KB <span class="text-success">(-25% ila arası -45% düşüş)</span></li>
            <li class="m-0"><span class="fw-semibold">Yüksek Kalite:</span> ~800–950KB <span class="text-success">(-5% ila arası -20% düşüş)</span></li>
            <li class="m-0"><span class="fw-semibold">Kayıpsız:</span> ~1MB <span class="text-muted">(Sıkıştırma yok)</span></li>
        </ul>
        <?php
    }
    /**
     * Genera il select per rinominare le immagini
     */
    static function html_select_rename($rename = '[image_name]') {
        $dim = array('[image_name]'=>'Orijinal adı temizle', '[uniqid]'=>'Benzersiz kimlik', ''=>'Özel');
      
        ?>
        <select name="rename_type" id="selectSettingRename" class="js-running-input-disable">
        <?php
            foreach ($dim as $key=>$label) {
                $selected = ($key == $rename || ($rename == '' && $key == '[image_name]')) ? ' selected="selected"' : ""; 
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <?php
    }
    
}