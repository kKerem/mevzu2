/**
 * Mevzu² Görsel Düzenleyici — JS
 */
(function($) {
    'use strict';

    var livePreviewEnabled = true;
    var previewTimer = null;
    var menuNamesCache = {};

    // ═══ Menü adlarını cache'e al ═══
    function cacheMenuNames() {
        $('#mevzu-ve-form select[name^="mevzu[footer_menu_"]').each(function() {
            var $sel = $(this);
            $sel.find('option').each(function() {
                if (this.value) menuNamesCache[this.value] = this.textContent;
            });
        });
    }

    // ═══ Accordion toggle ═══
    $(document).on('click', '.mevzu-ve-section-header', function() {
        var $section = $(this).closest('.mevzu-ve-section');
        var wasOpen = $section.hasClass('open');
        
        // Kapanış ve açılış animasyonu (600ms) - yavaşlatıldı
        $('.mevzu-ve-section').removeClass('open').find('.mevzu-ve-section-body').slideUp(600);
        
        if (!wasOpen) {
            $section.addClass('open');
            $section.find('.mevzu-ve-section-body').slideDown(600);

            // Navigasyon kontrolü
            var dataUrl = $section.attr('data-url');
            if (dataUrl) {
                var $iframe = $('.mevzu-ve-preview iframe');
                if ($iframe.length) {
                    var iframeNode = $iframe[0];
                    try {
                        var currentHref = iframeNode.contentWindow.location.href;
                        var currentUrlBase = currentHref.split('?')[0].replace(/\/$/, '');
                        var targetUrlBase = dataUrl.split('?')[0].replace(/\/$/, '');
                        if (currentUrlBase !== targetUrlBase) {
                            var src = dataUrl;
                            src += (src.indexOf('?') !== -1 ? '&' : '?') + 'mevzu_preview=1&_t=' + Date.now();
                            $iframe.attr('src', src);
                            $('.mevzu-ve-preview-loading').addClass('active');
                        }
                    } catch(e) {}
                }
            }
        }
    });

    // ═══ Preview mode (desktop/tablet/mobile) ═══
    $(document).on('click', '.mevzu-ve-mode', function() {
        var mode = $(this).data('mode');
        $('.mevzu-ve-mode').removeClass('active');
        $(this).addClass('active');
        $('.mevzu-ve-preview').removeClass('mode-desktop mode-tablet mode-mobile').addClass('mode-' + mode);
    });

    // ═══ Canlı Önizleme toggle ═══
    $(document).on('change', '#mevzu-ve-live-preview', function() {
        livePreviewEnabled = $(this).is(':checked');
        if (!livePreviewEnabled) {
            // Transient'i temizle
            $.post(mevzuVE.ajaxUrl, {
                action: 'mevzu_ve_preview_clear',
                nonce: mevzuVE.nonce
            });
        }
    });

    // ═══ Canlı Önizleme: form değişikliklerini dinle ═══
    $(document).on('change input', '#mevzu-ve-form select, #mevzu-ve-form input, #mevzu-ve-form textarea', function() {
        // Footer menü adını güncelle
        var $el = $(this);
        var name = $el.attr('name');
        if (name && name.match(/^mevzu\[footer_menu_\d\]$/)) {
            var menuId = $el.val();
            var menuName = menuNamesCache[menuId] || '';
            $el.closest('.mevzu-ve-footer-col').find('.mevzu-ve-readonly').val(menuName);
        }

        // Alt Manşet ve Sidebar Mantığı
        if (name === 'mevzu[alt_manset_alt_manseti_goster]' || name === 'mevzu[sidebar_goster]') {
            var $altManset = $('input[name="mevzu[alt_manset_alt_manseti_goster]"]');
            var $sidebar = $('input[name="mevzu[sidebar_goster]"]');
            
            if (name === 'mevzu[alt_manset_alt_manseti_goster]' && $altManset.is(':checked')) {
                if (!$sidebar.is(':checked')) {
                    alert('Alt Manşet \'Kenar Çubuğu: Anasayfa\' olmadan kullanılamaz. Kenar Çubuğu otomatik olarak aktif edildi.');
                    $sidebar.prop('checked', true);
                }
            } else if (name === 'mevzu[sidebar_goster]' && !$sidebar.is(':checked')) {
                if ($altManset.is(':checked')) {
                    alert('Alt Manşet kenar çubuğu olmadan kullanılamaz. Alt Manşet kapatıldı.');
                    $altManset.prop('checked', false);
                }
            }
        }

        // Conditional fields
        updateConditionalFields();

        // Canlı önizleme
        if (!livePreviewEnabled) return;

        clearTimeout(previewTimer);
        previewTimer = setTimeout(function() {
            var $form = $('#mevzu-ve-form');
            $.ajax({
                url: mevzuVE.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=mevzu_ve_preview_save&nonce=' + mevzuVE.nonce,
                success: function() {
                    refreshPreview();
                }
            });
        }, 500);
    });

    // ═══ Conditional field visibility ═══
    function updateConditionalFields() {
        $('.mevzu-ve-conditional').each(function() {
            var $block = $(this);
            var depends = $block.data('depends');
            var value = $block.data('value').toString();

            // Toggle (checkbox) veya select
            var $checkbox = $('input[type="checkbox"][name="' + depends + '"]');
            var $field = $('[name="' + depends + '"]').not('[type="hidden"]');

            var current;
            if ($checkbox.length) {
                current = $checkbox.is(':checked') ? '1' : '0';
            } else if ($field.length) {
                current = $field.val();
            } else {
                return;
            }

            if (current === value) {
                $block.slideDown(200);
            } else {
                $block.slideUp(200);
            }
        });
    }

    // ═══ iframe refresh ═══
    function refreshPreview() {
        var $iframe = $('.mevzu-ve-preview iframe');
        if (!$iframe.length) return;

        var iframeNode = $iframe[0];
        try {
            var scrollY = iframeNode.contentWindow.scrollY || iframeNode.contentDocument.documentElement.scrollTop;
            sessionStorage.setItem('mevzu_ve_scroll', scrollY);
        } catch (e) {}

        var $loading = $('.mevzu-ve-preview-loading');
        $loading.addClass('active');
        $iframe.addClass('loading-scroll');
        $iframe.one('load', function() {
            $loading.removeClass('active');
            try {
                var savedScroll = sessionStorage.getItem('mevzu_ve_scroll');
                if (savedScroll) {
                    iframeNode.contentWindow.scrollTo(0, parseInt(savedScroll, 10));
                    sessionStorage.removeItem('mevzu_ve_scroll');
                }
            } catch (e) {}
            setTimeout(function() {
                $iframe.removeClass('loading-scroll');
            }, 50);
        });
        // src swap — daha güvenilir (contentWindow.reload cross-origin sorun çıkarabiliyor)
        var src = $iframe.attr('src');
        // Cache buster ekle
        src = src.replace(/[&?]_t=\d+/, '');
        src += (src.indexOf('?') !== -1 ? '&' : '?') + '_t=' + Date.now();
        $iframe.attr('src', src);
    }

    // ═══ AJAX save (kalıcı) ═══
    $(document).on('click', '.mevzu-ve-save', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('.mevzu-ve-status');
        var $form = $('#mevzu-ve-form');

        $btn.prop('disabled', true).text('Kaydediliyor...');
        $status.removeClass('show').css('color', '');

        $.ajax({
            url: mevzuVE.ajaxUrl,
            type: 'POST',
            data: $form.serialize() + '&action=mevzu_save_settings&nonce=' + mevzuVE.nonce,
            success: function(res) {
                $btn.prop('disabled', false).text('Kaydet');
                if (res.success) {
                    $status.text('✓ Kaydedildi').css('color', '#00a32a').addClass('show');
                    setTimeout(function() { $status.removeClass('show'); }, 3000);
                    // Transient'i temizle, artık kalıcı veri var
                    $.post(mevzuVE.ajaxUrl, {
                        action: 'mevzu_ve_preview_clear',
                        nonce: mevzuVE.nonce
                    });
                    refreshPreview();
                } else {
                    $status.text('✗ ' + (res.data || 'Hata')).css('color', '#d63638').addClass('show');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Kaydet');
                $status.text('✗ Bağlantı hatası').css('color', '#d63638').addClass('show');
            }
        });
    });

    // ═══ Görsel Seç (wp.media) ═══
    $(document).on('click', '.mevzu-ve-image-select', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $field = $btn.closest('.mevzu-ve-field-image');
        var $input = $field.find('.mevzu-ve-image-id');
        var $preview = $field.find('.mevzu-ve-image-preview');
        var $remove = $field.find('.mevzu-ve-image-remove');

        var frame = wp.media({
            title: 'Görsel Seç',
            button: { text: 'Görseli Kullan' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id).trigger('change');
            $preview.html('<img src="' + attachment.url + '" style="max-width:100%;max-height:60px;border-radius:4px">');
            $remove.show();
        });

        frame.open();
    });

    $(document).on('click', '.mevzu-ve-image-remove', function(e) {
        e.preventDefault();
        var $field = $(this).closest('.mevzu-ve-field-image');
        $field.find('.mevzu-ve-image-id').val('').trigger('change');
        $field.find('.mevzu-ve-image-preview').html('');
        $(this).hide();
    });

    // ═══ Color Picker (WP Color Picker + presets) ═══
    var colorTimer = null;
    function initColorPickers() {
        $('.mevzu-ve-color-picker').each(function() {
            var $input = $(this);
            $input.wpColorPicker({
                change: function(event, ui) {
                    var hex = ui.color.toString();
                    $input.val(hex);
                    triggerColorPreview();
                },
                clear: function() {
                    $input.val('');
                    triggerColorPreview();
                }
            });
        });
    }

    function triggerColorPreview() {
        if (!livePreviewEnabled) return;
        clearTimeout(colorTimer);
        colorTimer = setTimeout(function() {
            var $form = $('#mevzu-ve-form');
            $.ajax({
                url: mevzuVE.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=mevzu_ve_preview_save&nonce=' + mevzuVE.nonce,
                success: function() { refreshPreview(); }
            });
        }, 600);
    }

    function triggerLivePreview() {
        if (!livePreviewEnabled) return;
        clearTimeout(previewTimer);
        previewTimer = setTimeout(function() {
            var $form = $('#mevzu-ve-form');
            $.ajax({
                url: mevzuVE.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=mevzu_ve_preview_save&nonce=' + mevzuVE.nonce,
                success: function() { refreshPreview(); }
            });
        }, 500);
    }

    $(document).on('click', '.mevzu-ve-preset-color', function() {
        var color = $(this).data('color');
        var $field = $(this).closest('.mevzu-ve-field-color');
        var $picker = $field.find('.mevzu-ve-color-picker');
        $picker.val(color);
        $picker.wpColorPicker('color', color);
        // Explicitly trigger preview in case change callback is not fired
        triggerColorPreview();
    });

    // ═══ Block item toggle (expand/collapse) ═══
    $(document).on('click', '.mevzu-ve-block-toggle', function(e) {
        e.preventDefault();
        var $body = $(this).closest('.mevzu-ve-block-item').find('.mevzu-ve-block-item-body');
        $body.slideToggle(200);
        $(this).text($body.is(':visible') ? '▴' : '▾');
    });

    function reindexBlocks($list, countFieldClass, namePrefix) {
        var count = 0;
        $list.find('.mevzu-ve-block-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(new RegExp('mevzu\\[' + namePrefix + '_\\d+_'), 'mevzu[' + namePrefix + '_' + index + '_');
                    $(this).attr('name', newName);
                }
            });
            count++;
        });
        $('.' + countFieldClass).val(count).trigger('change');
    }

    // ═══ Block item remove ═══
    $(document).on('click', '.mevzu-ve-block-remove', function(e) {
        e.preventDefault();
        if (!confirm('Bu bloğu kaldırmak istediğinize emin misiniz?')) return;
        var $item = $(this).closest('.mevzu-ve-block-item');
        var $list = $item.closest('.mevzu-ve-block-list');
        var isAnaKat = $list.attr('id') === 'mevzu-ve-ana-kat-list';
        var prefix = isAnaKat ? 'ana_kat' : 'bloklar';
        var countClass = isAnaKat ? 'mevzu-ve-ana-kat-count' : 'mevzu-ve-blocks-count';
        
        $item.slideUp(200, function() {
            $(this).remove();
            reindexBlocks($list, countClass, prefix);
            if ($list.find('.mevzu-ve-block-item').length === 0) {
                $list.find('.mevzu-ve-empty-msg').show();
            }
        });
    });

    // ═══ Block add (Ana Kat) ═══
    $(document).on('click', '.mevzu-ve-add-ana-kat', function(e) {
        e.preventDefault();
        var $list = $('#mevzu-ve-ana-kat-list');
        $list.find('.mevzu-ve-empty-msg').hide();
        var index = $list.find('.mevzu-ve-block-item').length;
        var html = $('#tmpl-mevzu-ve-ana-kat').html().replace(/__INDEX__/g, index);
        var $newItem = $(html).hide();
        $list.append($newItem);
        $newItem.slideDown(200);
        
        if ($.fn.select2) {
            $newItem.find('.mevzu-ve-select2').select2({ width: '100%', minimumResultsForSearch: 6 });
        }
        reindexBlocks($list, 'mevzu-ve-ana-kat-count', 'ana_kat');
    });

    // ═══ Block add (Blocks) ═══
    $(document).on('click', '.mevzu-ve-add-block', function(e) {
        e.preventDefault();
        var $list = $('#mevzu-ve-blocks-list');
        $list.find('.mevzu-ve-empty-msg').hide();
        var index = $list.find('.mevzu-ve-block-item').length;
        var html = $('#tmpl-mevzu-ve-blocks').html().replace(/__INDEX__/g, index);
        var $newItem = $(html).hide();
        $list.append($newItem);
        $newItem.slideDown(200);
        
        if ($.fn.select2) {
            $newItem.find('.mevzu-ve-select2').select2({ width: '100%', minimumResultsForSearch: 6 });
        }
        reindexBlocks($list, 'mevzu-ve-blocks-count', 'bloklar');
    });

    // ═══ Init ═══
    $(document).ready(function() {
        var $iframe = $('.mevzu-ve-preview iframe');
        var $loading = $('.mevzu-ve-preview-loading');
        if ($iframe.length) {
            $loading.addClass('active');
            
            // Check initial iframe context provided by PHP
            try {
                var context = $('.mevzu-ve-wrap').attr('data-initial-context');
                if (context) {
                    var $section = $('.mevzu-ve-section[data-section="' + context + '"]');
                    if ($section.length) {
                        $section.addClass('open').find('.mevzu-ve-section-body').show();
                    }
                }
            } catch(e) {}

            $iframe.on('load', function() {
                $loading.removeClass('active');
                
                // Restore scroll on load if any
                try {
                    var savedScroll = sessionStorage.getItem('mevzu_ve_scroll');
                    if (savedScroll) {
                        this.contentWindow.scrollTo(0, parseInt(savedScroll, 10));
                        sessionStorage.removeItem('mevzu_ve_scroll');
                    }
                } catch (e) {}
                setTimeout(function() {
                    $iframe.removeClass('loading-scroll');
                }, 50);
            });
        }


        // Init color pickers
        initColorPickers();

        // Init Select2 on all selects
        if ($.fn.select2) {
            $('.mevzu-ve-select2').select2({
                width: '100%',
                minimumResultsForSearch: 6
            });
        }

        // Icon radio active state
        $(document).on('change', '.mevzu-ve-icon-radio-item input', function() {
            $(this).closest('.mevzu-ve-icon-radios').find('.mevzu-ve-icon-radio-item').removeClass('active');
            $(this).closest('.mevzu-ve-icon-radio-item').addClass('active');
        });

        // Cache menu names
        cacheMenuNames();

        // Init conditional fields
        updateConditionalFields();

        // Sayfadan ayrılırken transient'i temizle
        $(window).on('beforeunload', function() {
            navigator.sendBeacon(mevzuVE.ajaxUrl + '?action=mevzu_ve_preview_clear&nonce=' + mevzuVE.nonce);
        });
    });

})(jQuery);
