/**
 * Mevzu² Tema Ayarları — Admin JS
 */
(function($) {
    'use strict';

    var MEVZU_SCHEMA_TABS = ['header', 'bloklar', 'footer'];

    /**
     * Anasayfa şema önizlemesi yalnızca header / manşet / bloklar / footer sekmelerinde;
     * diğerlerinde ana sütun tam genişlik (col-md-12).
     */
    function syncMevzuHomeSchemaSidebar(tab) {
        var $wrap = $('.mevzu-settings-wrap');
        var $main = $wrap.find('.mevzu-settings-main-col');
        var $schema = $wrap.find('.mevzu-settings-schema-col');
        if (!$main.length || !$schema.length) {
            return;
        }
        var show = MEVZU_SCHEMA_TABS.indexOf(tab) !== -1;
        if (show) {
            $main.removeClass('col-md-12').addClass('col-md-9');
            $schema.attr('class', 'col-12 col-md-3 d-none d-md-block mevzu-settings-schema-col');
        } else {
            $main.removeClass('col-md-9').addClass('col-md-12');
            $schema.attr('class', 'mevzu-settings-schema-col d-none');
        }
    }

    // ============================================================
    //  Tab Navigasyonu
    // ============================================================
    $(document).on('click', '.tab-link', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // Tab link aktifle
        $(this).closest('.mevzu-settings-tabs').find('.tab-link').removeClass('active');
        $(this).addClass('active');
        
        // Tab content göster
        $(this).closest('.mevzu-settings-container, .mevzu-settings-wrap').find('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
        
        syncMevzuHomeSchemaSidebar(tab);

        // Kaydet butonunu gizle/göster
        var noSaveTabs = [
            'import-export',
            'lisans',
            'hakkinda'
        ];
        var $actions = $(this).closest('.mevzu-settings-wrap').find('.mevzu-settings-actions').first();
        if (noSaveTabs.indexOf(tab) !== -1) {
            $actions.addClass('d-none');
        } else {
            $actions.removeClass('d-none');
        }
        
        // URL Hash güncelle
        if (history.replaceState) {
            history.replaceState(null, null, '#' + tab);
        }
    });

    // Hash'ten tab aç
    $(document).ready(function() {
        // #manset hash'ini #bloklar'a yönlendir (manşet alanı artık Anasayfa Ayarları'nda)
        if (window.location.hash === '#manset') {
            if (history.replaceState) {
                history.replaceState(null, null, '#bloklar');
            }
            window.location.hash = '#bloklar';
        }
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            var tabLink = $('[data-tab="' + hash + '"]');
            if (tabLink.length) {
                tabLink.trigger('click');
            }
        }
        var activeTab = $('.mevzu-settings-wrap .mevzu-settings-tabs .tab-link.active').data('tab');
        if (activeTab) {
            syncMevzuHomeSchemaSidebar(activeTab);
        }
    });

    $(window).on('hashchange', function() {
        var hash = window.location.hash.replace(/^#/, '');
        if (!hash) {
            return;
        }
        var $link = $('.mevzu-settings-wrap .mevzu-settings-tabs .tab-link[data-tab="' + hash + '"]');
        if ($link.length) {
            $link.trigger('click');
        }
    });

    // ============================================================
    //  Switch — tek ayar otomatik kayıt (Lisans sekmesi vb.)
    // ============================================================
    $(document).on('change', '.mevzu-switch-autosave', function() {
        var $input = $(this);
        var key = $input.data('option-key');
        if (!key) {
            return;
        }
        var val = $input.is(':checked') ? '1' : '0';
        var $status = $('#mevzu-switch-status-' + key);
        var postData = {
            action: 'mevzu_save_settings',
            nonce: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : '',
        };
        postData['mevzu[' + key + ']'] = val;

        $input.prop('disabled', true);
        $status.text('Kaydediliyor...').css('color', '#646970');

        $.post((typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl, postData)
            .done(function(response) {
                $input.prop('disabled', false);
                if (response.success) {
                    $status.text('✓ Kaydedildi').css('color', '#00a32a');
                    setTimeout(function() { $status.text(''); }, 2500);
                } else {
                    $status.text('✗ ' + (response.data || 'Hata')).css('color', '#d63638');
                    $input.prop('checked', val !== '1');
                }
            })
            .fail(function() {
                $input.prop('disabled', false);
                $status.text('✗ Bağlantı hatası').css('color', '#d63638');
                $input.prop('checked', val !== '1');
            });
    });

    // ============================================================
    //  Form Kaydetme (AJAX)
    // ============================================================
    $(document).on('submit', '#mevzu-settings-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var submitter = e.originalEvent && e.originalEvent.submitter;
        var $btn = submitter ? $(submitter) : $();
        if (!$btn.length || !$btn.is('button[type="submit"]')) {
            $btn = $form.find('#mevzu-save-settings');
        }
        var defaultLabel = $btn.data('label-default');
        if (!defaultLabel) {
            defaultLabel = $btn.text();
            $btn.data('label-default', defaultLabel);
        }
        var $status = $btn.closest('p, .mevzu-settings-actions').find('.mevzu-save-status').first();
        if (!$status.length) {
            $status = $form.find('.mevzu-save-status').first();
        }
        var action = $form.data('action') || 'mevzu_save_settings';
        
        $btn.prop('disabled', true).text('Kaydediliyor...');
        $status.removeClass('show');
        
        var formData = $form.serialize();
        
        // Anasayfa blokları sayfasında yapılan değişiklikleri de ana kaydet butonuna basınca yakala
        if ($('#mevzu-blocks-list').length) {
            var blocks = [];
            $('#mevzu-blocks-list .mevzu-block-row').each(function() {
                blocks.push({
                    goruntuleme_sablonu: $(this).find('.tpl-radio:checked').val(),
                    tekli_blok:         $(this).find('.block-kategori').val(),
                    ikili_blok_1:       $(this).find('.block-ikili-1').val(),
                    ikili_blok_2:       $(this).find('.block-ikili-2').val(),
                    haber_sayisi:       $(this).find('.block-sayi').val() || '3'
                });
            });
            formData += '&blocks=' + encodeURIComponent(JSON.stringify(blocks));
        }

        // Ana Kategori blokları da ana kaydet butonuna basınca yakala
        if ($('#mevzu-ana-kat-list').length) {
            var anaKatBloklar = [];
            $('#mevzu-ana-kat-list .mevzu-ana-kat-row').each(function() {
                anaKatBloklar.push({
                    sablon:         $(this).find('.ana-kat-sablon-radio:checked').val(),
                    baslik:         $(this).find('.ana-kat-baslik').val(),
                    haberler_metni: $(this).find('.ana-kat-haberler-metni').is(':checked') ? '1' : '0',
                    kategori:       $(this).find('.ana-kat-kategori').val(),
                    haber_sayisi:   $(this).find('.ana-kat-haber-sayisi').val() || '6'
                });
            });
            formData += '&ana_kat_bloklar=' + encodeURIComponent(JSON.stringify(anaKatBloklar));
        }
        
        $.ajax({
            url: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl,
            type: 'POST',
            data: formData + '&action=' + action + '&nonce=' + 
                  ((typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : ''),
            success: function(response) {
                $btn.prop('disabled', false).text($btn.data('label-default'));
                if (response.success) {
                    $status.text('✓ ' + (response.data || 'Kaydedildi!')).addClass('show');
                    setTimeout(function() { $status.removeClass('show'); }, 3000);
                } else {
                    $status.text('✗ ' + (response.data || 'Hata!')).css('color', '#d63638').addClass('show');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text($btn.data('label-default'));
                $status.text('✗ Bağlantı hatası').css('color', '#d63638').addClass('show');
            }
        });
    });

    // ============================================================
    //  Wizard Form
    // ============================================================
    $(document).on('submit', '#mevzu-wizard-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('#mevzu-wizard-next, #mevzu-wizard-finish');
        
        $btn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=mevzu_wizard_save&nonce=' + 
                  ((typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : ''),
            success: function(response) {
                if (response.success && response.data.next_url) {
                    window.location.href = response.data.next_url;
                } else {
                    $btn.prop('disabled', false).text('Devam Et →');
                    alert(response.data || 'Bir hata oluştu');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Devam Et →');
                alert('Bağlantı hatası');
            }
        });
    });

    // ============================================================
    //  Medya Yükleyici (Görsel Seçme)
    // ============================================================
    $(document).on('click', '.mevzu-image-select', function(e) {
        e.preventDefault();
        
        var $container = $(this).closest('.mevzu-field-image, td');
        var $input = $container.find('.mevzu-image-id');
        var $preview = $container.find('.mevzu-image-preview');
        var $removeBtn = $container.find('.mevzu-image-remove');
        
        var frame = wp.media({
            title: 'Görsel Seç',
            button: { text: 'Bu Görseli Kullan' },
            multiple: false,
            library: { type: 'image' }
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var imgUrl = attachment.sizes && attachment.sizes.thumbnail 
                ? attachment.sizes.thumbnail.url 
                : attachment.url;
            
            $input.val(attachment.id);
            $preview.html('<img src="' + imgUrl + '" style="max-width:200px;max-height:100px;border-radius:8px;border:2px solid #dcdcde">');
            $removeBtn.show();
        });
        
        frame.open();
    });

    $(document).on('click', '.mevzu-image-remove', function(e) {
        e.preventDefault();
        
        var $container = $(this).closest('.mevzu-field-image, td');
        $container.find('.mevzu-image-id').val('');
        $container.find('.mevzu-image-preview').html('');
        $(this).hide();
    });

    // ============================================================
    //  Koşullu Görünürlük (Show/Hide)
    // ============================================================
    function initConditionalVisibility() {
        const toggleMap = {
            'mevzu[ust_manset_yeni_goster]': '#ustMansetYeniDetail',
            'mevzu[ust_manset_ust_manset_ayarlari]': '#ustManset',
            'mevzu[manset_slider_basliklari]': '#mansetText',
            'mevzu[archive_manset_goster]': '#archiveMansetDetail',
            'mevzu[yapay_zeka_manseti_goster]': '#yzmMansetDetail',
            'mevzu[alt_manset_alt_manseti_goster]': '#altManset',
            'mevzu[alt_manset_slider_basliklari]': '#altMansetText',
            'mevzu[bolum_uclu_goster]': '#bolumUcluIcerik',
            'mevzu[anasayfa_son_haberler]': '#sonHaberlerDetail',
            'mevzu[ilangovtr]': '#ilanGovtrDetail',
            'mevzu[son_dakika_goster]': '#sonDakikaDetail'
        };

        $.each(toggleMap, function(name, target) {
            const $checkbox = $('input[name="' + name + '"][type="checkbox"]');
            const $target = $(target);

            if ($checkbox.length && $target.length) {
                // İlk yükleme
                if (!$checkbox.is(':checked')) {
                    $target.hide();
                }

                // Değişim anı
                $checkbox.on('change', function() {
                    if ($(this).is(':checked')) {
                        $target.slideDown(200);
                    } else {
                        $target.slideUp(200);
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        initConditionalVisibility();
        
        // WP Color Picker
        $('.mevzu-color-picker').wpColorPicker();

        // Select2 başlat
        if ($.fn.select2) {
            $('.mevzu-select2').select2({
                width: '100%',
                placeholder: '— Seçiniz —',
                allowClear: true
            });
        }

        // Preset Renk Seçimi
        $(document).on('click', '.mevzu-preset-color', function() {
            var color = $(this).data('color');
            var $picker = $(this).closest('.mevzu-field').find('.mevzu-color-picker');
            
            // wpColorPicker'ın API'sini kullan
            $picker.wpColorPicker('color', color);
        });
    });

    // ============================================================
    //  Güncelleme Kontrolü
    // ============================================================
    $(document).on('click', '#mevzu-check-update', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#update-check-status');
        
        $btn.prop('disabled', true).find('.dashicons').addClass('ri-spin');
        $status.text('Kontrol ediliyor...').css('color', '#646970');
        
        $.ajax({
            url: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_check_update',
                nonce: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : ''
            },
            success: function(response) {
                $btn.prop('disabled', false).find('.dashicons').removeClass('ri-spin');
                if (response.success) {
                    $status.text(response.data.message);
                    if (response.data.update_available) {
                        var newVersion = response.data.version;
                        var $updateBtn = $('<button type="button" id="mevzu-do-update" class="button ms-2">Şimdi Güncelle</button>');
                        $status.css('color', '#d63638').after($updateBtn);
                        $updateBtn.on('click', function() {
                            $updateBtn.prop('disabled', true).text('Güncelleniyor...');
                            $.ajax({
                                url: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl,
                                type: 'POST',
                                timeout: 60000,
                                data: {
                                    action: 'mevzu_apply_version',
                                    nonce: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : '',
                                    version: newVersion
                                },
                                success: function(res) {
                                    if (res.success) {
                                        $updateBtn.remove();
                                        $status.css('color', '#2271b1').text('✓ v' + newVersion + ' kuruldu. Sayfa yenileniyor...');
                                        setTimeout(function() { location.reload(); }, 2000);
                                    } else {
                                        $updateBtn.prop('disabled', false).text('Şimdi Güncelle');
                                        $status.css('color', '#d63638').text('Hata: ' + (res.data || 'Güncelleme başarısız'));
                                    }
                                },
                                error: function() {
                                    $updateBtn.prop('disabled', false).text('Şimdi Güncelle');
                                    $status.css('color', '#d63638').text('Bağlantı hatası');
                                }
                            });
                        });
                    } else {
                        $status.css('color', '#2271b1');
                    }
                } else {
                    $status.text('Hata: ' + response.data).css('color', '#d63638');
                }
            },
            error: function() {
                $btn.prop('disabled', false).find('.dashicons').removeClass('ri-spin');
                $status.text('Bağlantı hatası').css('color', '#d63638');
            }
        });
    });

})(jQuery);

// ============================================================
//  Video Depolama — R2 alanları toggle
// ============================================================
(function($) {
    function toggleR2Fields() {
        var val = $('#mevzu_video_depolama').val();
        $('#mevzu-r2-fields').toggle(val === 'r2');
    }
    $(document).on('change', '#mevzu_video_depolama', toggleR2Fields);

    // Sayfa yüklendiğinde ve "video-depolama" tabına geçildiğinde durumu uygula
    $(document).ready(function() { toggleR2Fields(); });
    $(document).on('click', '.tab-link[data-tab="video-depolama"]', function() {
        setTimeout(toggleR2Fields, 50);
    });

    // R2 bağlantı testi
    $(document).on('click', '#mevzu-r2-test', function(e) {
        e.preventDefault();
        var $btn    = $(this);
        var $result = $('#mevzu-r2-test-result');
        $btn.prop('disabled', true).text('Test ediliyor...');
        $result.text('').css('color', '#646970');

        $.ajax({
            url: (typeof mevzuSettings !== 'undefined') ? mevzuSettings.ajaxUrl : ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_r2_test',
                nonce:  (typeof mevzuSettings !== 'undefined') ? mevzuSettings.nonce : ''
            },
            success: function(res) {
                $btn.prop('disabled', false).text('R2 Bağlantısını Test Et');
                if (res.success) {
                    $result.text('✓ ' + res.data).css('color', '#00a32a');
                } else {
                    $result.text('✗ ' + res.data).css('color', '#d63638');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('R2 Bağlantısını Test Et');
                $result.text('Bağlantı hatası').css('color', '#d63638');
            }
        });
    });
})(jQuery);
