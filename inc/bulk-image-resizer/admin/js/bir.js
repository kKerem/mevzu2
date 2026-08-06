var save_config_timeout = null;
var bir_pause = false;

/* ============================================================
 *  Hız & Güvenlik — Sekme Navigasyonu
 * ============================================================ */
jQuery(document).ready(function () {

    var GORSEL_TABS = ['gorsel-config', 'gorsel-islem', 'gorsel-istatistik'];

    document.querySelectorAll('#hg_tabs .tab-link').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            // Aktif tab değiştir
            document.querySelectorAll('#hg_tabs .tab-link').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');

            var tabId = this.dataset.tab;

            // Tab içerikleri göster/gizle
            document.querySelectorAll('#hg_tab_contents .hg-tab-content').forEach(function(c) {
                c.classList.remove('hg-tab-active');
            });
            var target = document.getElementById(tabId);
            if (target) target.classList.add('hg-tab-active');

            // Görsel işlemleri aksiyon butonlarını sadece görsel sekmelerde göster
            var actionsDiv = document.getElementById('hg-gorsel-actions');
            if (actionsDiv) {
                actionsDiv.style.display = GORSEL_TABS.indexOf(tabId) !== -1 ? '' : 'none';
            }

            // Güvenlik formlarının status mesajını temizle
            ['hg-security-status', 'hg-hide-login-status', 'hg-kaynak-status'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.innerHTML = '';
            });

            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabId);
            }

            if (tabId === 'gorsel-istatistik') {
                load_stat();
            }
        });
    });

    // Hash'ten sekme aç
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        var legacyMap = {
            'ayar'             : 'gorsel-config',
            'ayarlar'          : 'gorsel-config',
            'islem'            : 'gorsel-islem',
            'gorsel-islemleri' : 'gorsel-islem',
            'istatistik'       : 'gorsel-istatistik',
            'dokuman'          : 'gorsel-config',
            'dokumantasyon'    : 'gorsel-config',
        };
        var normalizedHash = legacyMap[hash] || hash;
        var hashTab = document.querySelector('#hg_tabs .tab-link[data-tab="' + normalizedHash + '"]');
        if (hashTab) {
            hashTab.click();
            return;
        }
    }

    // Varsayılan: istatistiği başlat (yalnızca görsel sekmelerde)
    load_stat();
});


/**
* ROW CONFIG
*/
jQuery(document).ready(function () {
    jQuery('.js-config-active-row-checkbox').change(function () {
        activ_row_checkbox(this);
    });
    // inizializzo tutti
    jQuery('.js-config-active-row-checkbox').each(function () {
        activ_row_checkbox(this);
    });

});


function activ_row_checkbox(el) {
    $box = jQuery(el).parents('.js-config-box');
    if (jQuery(el).is(':checked')) {
        $box.addClass('bir-active-opt');
    } else {
        $box.removeClass('show-advanced-opt');
        $box.removeClass('bir-active-opt');
       
    }
}

/**
 * END ROW CONFIG
 */


/**
 * Config optimize form
 */
jQuery(document).ready(function () {
    jQuery('#selectPresetDimension').change(function () {
        selectPresetDimension();
        jQuery('#resizeMaxWidth').change();
    });
    selectPresetDimension();
});

function selectPresetDimension() {
    const el = jQuery('#selectPresetDimension');
    if (jQuery(el).val() == '') {
        jQuery('.js-custom-dimension').show();
    } else {
        jQuery('.js-custom-dimension').hide();
        const xy = jQuery(el).val().split('x');
        jQuery('#resizeMaxWidth').val(xy[0]);
        jQuery('#resizeMaxHeight').val(xy[1]);
    }
}

/**
 * END Config optimize form
 */

/**
 * Config Rename form
 */

jQuery(document).ready(function () {
    jQuery('#selectSettingRename').change(function () {
        selectChangeRename();
    });
    setRenameConfig();
});

function selectChangeRename() {
    const el = jQuery('#selectSettingRename');
    if (jQuery(el).val() == '') {
        jQuery('.js-custom-rename').show();
    } else {
        jQuery('.js-custom-rename').hide();
        jQuery('#birRealRename').val(jQuery(el).val());
    }
}

function setRenameConfig() {
    const select = jQuery('#selectSettingRename');
    const real = jQuery('#birRealRename');
    // se real è un valore di select
    if (real.val() != '' && jQuery(select).find('option[value="'+jQuery(real).val()+'"]').length > 0) {
        jQuery(select).val(jQuery(real).val());
        jQuery('.js-custom-rename').hide();
    } else {
        jQuery(select).val('');
        jQuery('.js-custom-rename').show();
    }
}



/**
 * END Config Rename form
 */

/**
 * Salvataggio dei dati della configurazione
 */


jQuery(document).ready(function () {
    // Manuel kayda geçildi: form submit Enter ile tetiklenirse ajax kaydet
    jQuery('#opBulkImageResizerSetup').on('submit', function (e) {
        e.preventDefault();
        save_config();
    });
});

info_min_500 = false;
function save_config() {
    var $saveStatus = jQuery('#bir-config-save-status');
    if (get_plugin_status() != '') {
        clearTimeout(save_config_timeout);
        save_config_timeout = setTimeout( () => { save_config(); }, 2000);
        set_plugin_status('');
        return false;
    }
   
    let max_width = parseInt(jQuery('#resizeMaxWidth').val());
    let max_height = parseInt(jQuery('#resizeMaxHeight').val());
    conf_dim_yes = true;
    // find checkbox name =resize_active
   
    if ((max_height < 500 || max_width < 500) &&  jQuery('#opBulkImageResizerSetup input[name="resize_active"]').is(':checked') && !info_min_500) {
        alert('500px altındaki görselleri yeniden boyutlandırmak istediğinize emin misiniz?');
        info_min_500 = true;
    }
    
    var form = document.getElementById('opBulkImageResizerSetup');
    var formDataArray = jQuery(form).serializeArray();
    var data = {};
    formDataArray.forEach(function (item) {
        data[item.name] = item.value;
    });

    // Her durumda ajax action gönderilsin
    data.action = 'bir_save_configuration';
    data.version = data.version || '2.0.0';

    // Checkbox alanlarını explicit '1' / '0' olarak gönder (boş string PHP isset() için TRUE döndüğü için kullanılmaz)
    ['resize_active', 'optimize_active', 'webp_active', 'rename_active', 'rename_change_title'].forEach(function (key) {
        var el = form.querySelector('input[name="' + key + '"]');
        data[key] = (el && el.checked) ? '1' : '0';
    });
    // after serialize
    set_plugin_status('setting-submit');
    $saveStatus.removeClass('text-success text-danger').text('Kaydediliyor...');
    
    // Salvo i dati
    jQuery.ajax({
        method: "POST",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        set_plugin_status('');
        if (ris && ris.success) {
            showSuccessAlert('Ayarlar kaydedildi');
            $saveStatus.addClass('text-success').text('Ayarlar kaydedildi.');
            jQuery(document).trigger('bir_config_saved');
        } else {
            $saveStatus.removeClass('text-success').addClass('text-danger').text('Kaydetme başarısız oldu. Lütfen tekrar deneyin.');
        }
    }).fail(function () {
        set_plugin_status('');
        $saveStatus.removeClass('text-success').addClass('text-danger').text('Kaydetme başarısız oldu. Lütfen tekrar deneyin.');
    });
}

/**
 * END Salvataggio dei dati della configurazione
 */

/**
 * BULK
 */

/**
 * Eseguo il bulk dal bottone Execute Bulk
 */
function hgSwitchTab(tabId) {
    var tabLink = document.querySelector('#hg_tabs .tab-link[data-tab="' + tabId + '"]');
    if (tabLink) tabLink.click();
}

function get_tab_active() {
    var active = document.querySelector('#hg_tabs .tab-link.active');
    return active ? active.dataset.tab : 'gorsel-config';
}

function birExecuteBtn() {
    if (get_plugin_status() != '') return false;
    if (get_tab_active() === 'gorsel-config') {
        jQuery(document).on('bir_config_saved', function (e) {
            hgSwitchTab('gorsel-islem');
            jQuery(document).off('bir_config_saved');
            startBulk();
        });
        save_config();
    } else {
        hgSwitchTab('gorsel-islem');
        startBulk();
    }
}

function birPauseBtn() {
    bir_pause = true;
    set_plugin_status('bulk-pause');
}

function birResumetBtn() {
    bir_pause = false;
    set_plugin_status('bulk-running');
    if (get_tab_active() === 'gorsel-config') {
        jQuery(document).on('bir_config_saved', function (e) {
            hgSwitchTab('gorsel-islem');
            jQuery(document).off('bir_config_saved');
            next_bulk();
        });
        save_config();
    } else {
        hgSwitchTab('gorsel-islem');
        next_bulk();
    }
}

function birStopBtn() {
    bir_pause = false;
    set_plugin_status('bulk-stop');
    if (get_tab_active() === 'gorsel-config') {
        jQuery(document).on('bir_config_saved', function (e) {
            hgSwitchTab('gorsel-islem');
            jQuery(document).off('bir_config_saved');
        });
        save_config();
    } else {
        hgSwitchTab('gorsel-islem');
    }
    jQuery('#opConfigWarning').hide();
    data = {action:"bir_stop"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        set_plugin_status('');
        showSuccessAlert('İşlem durduruldu');
    })
}


function startBulk() {
    if (get_plugin_status() != '') {
        alert('Lütfen bekleyin, eklenti şu an meşgul');
        return false;
    }
    set_plugin_status('bulk-running');
    clear_info();
    jQuery('#opConfigWarning').hide();
    data = {action:"bir_start_bulk"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_bulk();
        print_info(ris);
    })
}
function next_bulk() {
    data = {action:"bir_next_bulk"};
    set_plugin_status('bulk-running');
    jQuery('#opConfigWarning').hide();
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        print_info(ris);
        if (ris['done'] < ris['total']) {
            if (!bir_pause) {
                next_bulk();
            }
        } else {
            load_last_bulk_stat();
        }
        
    }).error(function (ris) {
        alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin." );
        if (!bir_pause) {
            next_bulk();
        }
    });

}



function load_last_bulk_stat() {
    data = {action:"bir_get_stat"};
    // quando carico le statistiche fa anche l'update.
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
        set_plugin_status('');
        showSuccessAlert('Optimizasyon işlemi tamamlandı');
        jQuery('#configMsgStat').html('').removeClass('bir-config-warning');
        // verifico se ris è un oggetto
        if (typeof jsonData == 'object') {
            //console.log (jsonData);
            var risparmio_perc = (jsonData.file_size['total_file_size_original'] - jsonData.file_size['total_file_size']) / jsonData.file_size['total_file_size_original'] * 100;
            showSuccessAlert('Optimizasyon işlemi tamamlandı. <b>'+ risparmio_perc.toFixed(2)+ ' % tasarruf sağlandı</b>');
        }
    });
}


var bir_status = '';
/**
 * 
 * @param {*} status bulk-running | setting-submit | bulk-restore
 */
function set_plugin_status(status) {
    if (bir_status == status) return;
    bir_status = status;
    jQuery('#bulkSuccessAlert').addClass('d-none');
    if (status == 'bulk-running') {
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#statusof_bulk_image_container').addClass('js-state-running-bulk');
        jQuery('#btnPause').css('display', 'inline-block');
        jQuery('#btnPause').removeClass('bir-progress-disabled');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-running-bulk');
        jQuery('#btnPause').css('display', 'none');
    }
    if (status == 'bulk-pause') {
        jQuery('#progress_bar').addClass('bir-progress-disabled');
        jQuery('#btnPause').css('display', 'inline-block');
        jQuery('#btnPause').addClass('bir-btn-disabled');
        jQuery('#btnResume').css('display', 'inline-block');
        jQuery('#btnStop').css('display', 'inline-block');
        
    } else {
        jQuery('#btnPause').removeClass('bir-btn-disabled');
        jQuery('#btnResume').css('display', 'none');
        jQuery('#btnStop').css('display', 'none');
    }

    if (status == 'bulk-restore') {
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#statusof_bulk_image_container').addClass('js-state-restore-bulk');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-restore-bulk');
    }
    if (status == 'bulk-delete-original') {
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#statusof_bulk_image_container').addClass('js-state-delete-original-bulk');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-delete-original-bulk');
    }

    if (status == 'setting-submit') {
        jQuery('#statusof_bulk_image_container').addClass('js-state-submit');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-submit');
    }

    if (status != '') {
        jQuery('.js-running-input-disable').prop('disabled', true);
        jQuery('.js-running-btn-disable').addClass('bir-btn-disabled');
     } else {
        jQuery('.js-running-input-disable').prop('disabled', false);
        jQuery('.js-running-btn-disable').removeClass('bir-btn-disabled');
    }
}

function get_plugin_status() {
    return bir_status;
}


function clear_info() {
    jQuery('#birBulkInfo').html('Başlatılıyor...');
    jQuery('#birBulkLog').html('');
}

function print_info(info) {
    if (info['status'] == 'NOT_STARTED') {
        jQuery('#birBulkInfo').html('');
        jQuery('#birBulkInfo').html(info['done']+"/"+info['total']+" "+info['percent']+"%");
    } else {
        jQuery('#birBulkInfo').html(info['done']+"/"+info['total']+" "+info['percent']+"% " + info['status']);
    }
    jQuery('#progress_bar').css('width', info['percent'] + '%');
    jQuery('#progress_bar').attr("aria-valuenow", info['percent'] + '%');
    jQuery('#progress_bar').text(info['percent'] + '%');
    if (info['logs'] != undefined && info['logs'].length > 0) {
        for (let i = 0; i < info['logs'].length; i++) {
            jQuery('#birBulkLog').append(info['logs'][i]+"<br>");
        }
    }
}


function showSuccessAlert(msg) {
    jQuery('#bulkSuccessAlert').removeClass('d-none');
    jQuery('#bulkSuccessAlertMsg').empty().html(msg);
    jQuery('#opRunBulk').hide();
}

/**
 * END BULK
 */

/**
 * RESTORE ALL IMAGES
 */
function startRestore() {
    if (get_plugin_status() != '') {
        alert('Lütfen bekleyin, eklenti şu an meşgul');
        return false;
    }
    if (confirm('Tüm optimizasyonlar geri alınacak. Tüm görselleri orijinal haline döndürmek istediğinize emin misiniz? İşlem biraz sürebilir.') == false) return false;
   
    set_plugin_status('bulk-restore');
    clear_info();
    data = {action:"bir_start_restore"};
    jQuery('#opConfigWarning').hide();
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_restore();
        print_info(ris);
    }).error(function (ris) {
        alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin." );
        next_restore();
    });
}

function next_restore() {
    data = {action:"bir_next_restore"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        // verifico se ris è un oggetto
        if (typeof ris != 'object') {
            alert ("Beklenmeyen bir hata oluştu.");
            next_restore();
        } else {
            print_info(ris);
            if (ris['done'] < ris['total']) {
                next_restore();
            } else {
                set_plugin_status('');
                showSuccessAlert('Geri yükleme işlemi tamamlandı');
            }
        }
    }).error(function (ris) {
        alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin." );
        next_restore();
    });
}

/**
 * END RESTORE ALL IMAGES
 */


/**
 * REMOVE ORIGINAL IMAGES
 */
function startRemoveOriginal() {
    if (get_plugin_status() != '') {
        alert('Lütfen bekleyin, eklenti şu an meşgul');
        return false;
    }
    if (confirm('Orijinal görseller silinirse sunucuda alan kazanılır ancak optimize edilen görselleri geri yükleyemezsiniz.') == false) return false;
   
    set_plugin_status('bulk-delete-original');
    clear_info();
    data = {action:"bir_start_delete_orginal"};
    jQuery('#opConfigWarning').hide();
    
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_removeOriginal();
        print_info(ris);
    }).error(function (ris) {
        alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin." );
        next_removeOriginal();
    });
}


function next_removeOriginal() {
    data = {action:"bir_next_delete_orginal"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        // verifico se ris è un oggetto
        if (typeof ris != 'object') {
            alert ("Beklenmeyen bir hata oluştu.");
            next_removeOriginal();
        } else {
            print_info(ris);
            if (ris['done'] < ris['total']) {
                next_removeOriginal();
            } else {
                set_plugin_status('');
                showSuccessAlert('Tüm orijinal görseller silindi');
            }
        }
    }).error(function (ris) {
        alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin." );
        next_removeOriginal();
    });
}

/**
 * END REMOVE ORIGINAL IMAGES
 */


/**
 * STAT
 */

function update_stat() {
    data = {action:"bir_get_stat"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
    });
}


function load_stat() {
    
    jQuery('#birStatInfo').html('İstatistikler yükleniyor...');
    data = {action:"bir_get_stat"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
        // verifico se ris è un oggetto
        if (typeof jsonData != 'object') {
            alert ("Beklenmeyen bir hata oluştu.");
        } else {
            if (jsonData.file_size['msg'] != '') {
                jQuery('#stat_box_filesize_info').html(jsonData.file_size['msg']).css('display', 'block');
            }
            delete jsonData.file_size['msg'];
            drawFileSizeTable(jsonData) ;
            chart_history_filesize(jsonData.history.labels, jsonData.history.dataset_1, jsonData.history.dataset_2);
            jQuery('#configMsgStat').html('').removeClass('bir-config-warning');
            if (jsonData.file_numbers.total_files  > jsonData.file_numbers.total_files_original *1.2 ) {
                var img_to_optimize = jsonData.file_numbers.total_files - jsonData.file_numbers.total_files_original;
                jQuery('#configMsgStat').html('Optimize edilmesi gereken <b>' + img_to_optimize + '</b> görsel var. <a href="#gorsel-islem" onclick="hgSwitchTab(\'gorsel-islem\');return false;">Hemen işle &rarr;</a>').css('display', 'block');
                jQuery('#configMsgStat').addClass('bir-config-warning');
            }
        }
    }).error(function (jsonData) {
        //alert ("Geçerli JSON yanıtı alınamadı. Başka bir eklenti çakışıyor olabilir. Site debug modunu kapatıp tekrar deneyin.");
    });
}

function drawFileSizeTable(jsonData) {
    // Ottieni il riferimento al div in cui disegnare la tabella
    var tableDiv = jQuery("#stat_box_filesize");
  
    // Crea la tabella
    var table = jQuery('<table class="bir-table">');

    // Aggiungi una riga per ogni titolo e valore
    for (var key in jsonData.file_size) {
      var row = jQuery("<tr>");
      var titleCell = jQuery("<td>").text(getTitle(key));
      var valueCell = jQuery("<td>").text(formatSize(jsonData.file_size[key]));
      row.append(titleCell, valueCell);
      table.append(row);
    }

    var row = jQuery("<tr>");
    var titleCell = jQuery("<td>").text("Tasarruf");
    var risparmio_perc = (jsonData.file_size['total_file_size_original'] - jsonData.file_size['total_file_size']) / jsonData.file_size['total_file_size_original'] * 100;
   
    // formatto il numero
    var risparmio_perc = risparmio_perc.toFixed(2);
    var valueCell = jQuery("<td>").text( risparmio_perc+"% alan tasarrufu");
    row.append(titleCell, valueCell);
    table.append(row);
    // Aggiungi la tabella al div
    tableDiv.html(table);
  }
  
  // Funzione per ottenere il titolo in italiano
  function getTitle(key) {
    switch (key) {
      case "total_file_size":
        return "Optimizasyon sonrası toplam dosya boyutu";
      case "total_file_size_original":
        return "Optimizasyon öncesi orijinal dosyaların toplam boyutu";
      default:
        return key;
    }
  }
  
  // Funzione per formattare la dimensione in KB, MB, GB, ecc.
  function formatSize(size) {
    var units = ["B", "KB", "MB", "GB", "TB"];
    var unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024;
      unitIndex++;
    }
    return size.toFixed(2) + " " + units[unitIndex];
  }

  /**
   * END STAT
   */


  /**
   * Chart1
   */

/* ============================================================
 *  Hız & Güvenlik — Güvenlik Ayarları Kaydetme
 * ============================================================ */

/**
 * XML-RPC / Extra Güvenlik / WP Hızlandırma formlarından veri toplar ve kaydeder.
 * Bu üç sekme aynı AJAX action'ı kullanır.
 */
function hgSaveSecurity() {
    var data = {
        action : 'hiz_guvenlik_save_security',
        nonce  : hizGuvenlikData.nonce
    };

    // Tüm açık güvenlik formlarındaki alanları topla
    var forms = ['#hg-xmlrpc-form', '#hg-extra-guvenlik-form', '#hg-hizlandirma-form'];
    forms.forEach(function(sel) {
        var form = document.querySelector(sel);
        if (!form) return;
        var serialized = jQuery(form).serializeArray();
        serialized.forEach(function(field) {
            // Checkbox dizisi desteği (disabled-methods[])
            if (field.name.endsWith('[]')) {
                var key = field.name.slice(0, -2);
                if (!data[key]) data[key] = [];
                data[key].push(field.value);
            } else {
                data[field.name] = field.value;
            }
        });
        // Unchecked checkbox'ları da gönder (false olarak)
        form.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
            if (!cb.name.endsWith('[]') && !cb.checked) {
                data[cb.name] = '';
            }
        });
    });

    var $statusEls = jQuery('.hg-save-status');
    $statusEls.html('<span style="color:#888">Kaydediliyor...</span>');

    jQuery.post(hizGuvenlikData.ajaxUrl, data, function(res) {
        if (res.success) {
            $statusEls.html('<span style="color:#4CAF50"><span class="dashicons dashicons-yes" style="vertical-align:middle;"></span> ' + hizGuvenlikData.strings.saved + '</span>');
        } else {
            $statusEls.html('<span style="color:#e74c3c"><span class="dashicons dashicons-no" style="vertical-align:middle;"></span> ' + hizGuvenlikData.strings.error + '</span>');
        }
        setTimeout(function() { $statusEls.html(''); }, 3500);
    }).fail(function() {
        $statusEls.html('<span style="color:#e74c3c">' + hizGuvenlikData.strings.error + '</span>');
    });
}

/**
 * Admin URL Gizleme formu kaydetme
 */
function hgSaveHideLogin() {
    var form      = document.getElementById('hg-hide-login-form');
    var $statusEl = jQuery('#hg-hide-login-status');
    var enabled   = document.getElementById('hg_whl_master').checked;

    if (!enabled && !confirm(hizGuvenlikData.strings.confirm_disable_hide)) {
        return;
    }

    var data = { action: 'hiz_guvenlik_save_hide_login', nonce: hizGuvenlikData.nonce };
    var serialized = jQuery(form).serializeArray();
    serialized.forEach(function(f) { data[f.name] = f.value; });

    // Unchecked switch
    if (!enabled) data['mevzu_whl_enabled'] = '';

    $statusEl.html('<span style="color:#888">Kaydediliyor...</span>');

    jQuery.post(hizGuvenlikData.ajaxUrl, data, function(res) {
        if (res.success) {
            $statusEl.html('<span style="color:#4CAF50"><span class="dashicons dashicons-yes" style="vertical-align:middle;"></span> ' + hizGuvenlikData.strings.saved + '</span>');
            // Kayıt sonrası sayfayı yenile (eklenti aktif/pasif durumu değişti)
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            $statusEl.html('<span style="color:#e74c3c">' + hizGuvenlikData.strings.error + '</span>');
        }
    }).fail(function() {
        $statusEl.html('<span style="color:#e74c3c">' + hizGuvenlikData.strings.error + '</span>');
    });
}

/* ============================================================
 *  Hız & Güvenlik — Kaynak Yükleme Kaydetme
 * ============================================================ */
jQuery(document).ready(function () {
    var form = document.getElementById('hg-kaynak-yukleme-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hgSaveKaynak();
        });
    }
});

function hgSaveKaynak() {
    var form      = document.getElementById('hg-kaynak-yukleme-form');
    var $statusEl = jQuery('#hg-kaynak-status');

    var data = { action: 'hiz_guvenlik_save_kaynak', nonce: hizGuvenlikData.nonce };
    var serialized = jQuery(form).serializeArray();
    serialized.forEach(function(f) { data[f.name] = f.value; });

    $statusEl.html('<span style="color:#888">Kaydediliyor...</span>');

    jQuery.post(hizGuvenlikData.ajaxUrl, data, function(res) {
        if (res.success) {
            $statusEl.html('<span style="color:#4CAF50"><span class="dashicons dashicons-yes" style="vertical-align:middle;"></span> ' + hizGuvenlikData.strings.saved + '</span>');
        } else {
            $statusEl.html('<span style="color:#e74c3c">' + hizGuvenlikData.strings.error + '</span>');
        }
    }).fail(function() {
        $statusEl.html('<span style="color:#e74c3c">' + hizGuvenlikData.strings.error + '</span>');
    });
}

/* ============================================================
 *  END Hız & Güvenlik — Güvenlik Kaydetme
 * ============================================================ */

var  filesize_history_info_chart =  null;  
function chart_history_filesize(labels, dataset_1, dataset_2) {
  const ctx = document.getElementById('filesize_history_info_chart');

  const data = {
    labels: labels,
    datasets: [
        {
            label: 'Optimize edilmiş görseller',
            data: dataset_2,
            fill: true,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        },
        {
        label: 'Optimize edilmemiş görseller',
        data: dataset_1,
        fill: true,
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)'
        }

    ]
  };
  if (filesize_history_info_chart != null) filesize_history_info_chart.destroy();
  filesize_history_info_chart = new Chart(ctx, {
    type: 'line',
    data: data,
    options: {
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
                callback: function(value, index, values) {
                  return value + ' MB'; // Aggiunge il suffisso "MB" ai valori dell'asse y
                }
              }
          }
        }
      }
  });
}