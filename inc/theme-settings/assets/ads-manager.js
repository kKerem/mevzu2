/**
 * Mevzu² Reklam Yöneticisi — Admin JS
 */
(function ($) {
    'use strict';

    // ── Tür seçimi ──────────────────────────────────────────────
    $(document).on('click', '.mevzu-type-btn', function () {
        var $card = $(this).closest('.mevzu-ad-card');
        var type  = $(this).data('type');

        $card.find('.mevzu-type-btn').removeClass('active');
        $(this).addClass('active');

        $card.find('.mevzu-ad-type').val(type);
        $card.find('.mevzu-ad-panel').removeClass('active');
        $card.find('.mevzu-panel-' + type).addClass('active');
    });

    // ── Toggle (aktif/pasif) card görünümü ──────────────────────
    $(document).on('change', '.mevzu-ad-active', function () {
        $(this).closest('.mevzu-ad-card').toggleClass('is-active', this.checked);
    });

    // ── Swiper toggle card görünümü ──────────────────────────────
    $(document).on('change', '#mevzu-swiper-aktif', function () {
        $(this).closest('.mevzu-ad-card').toggleClass('is-active', this.checked);
    });
    $(document).on('change', '#mevzu-side-aktif', function () {
        $(this).closest('.mevzu-ad-card').toggleClass('is-active', this.checked);
    });

    // ── Swiper toggle kaydet ─────────────────────────────────────
    $(document).on('click', '#mevzu-save-swiper', function () {
        var $btn    = $(this);
        var $status = $('.mevzu-swiper-status');
        $btn.prop('disabled', true).text('Kaydediliyor...');
        $.ajax({
            url:  mevzuAds.ajaxUrl,
            type: 'POST',
            data: {
                action:      'mevzu_save_swiper',
                nonce:       mevzuAds.nonce,
                aktif:       $('#mevzu-swiper-aktif').is(':checked') ? 1 : 0,
                goruntuleme: $('input[name="mevzu_swiper_goruntuleme"]:checked').val() || 'anasayfa',
                tip:         $('input[name="mevzu_swiper_tip"]:checked').val() || 'swiper',
            },
            success: function (res) {
                if (res.success) {
                    $status.text('✓ Kaydedildi');
                    setTimeout(function () { $status.text(''); }, 2500);
                } else {
                    $status.addClass('error').text('✗ ' + (res.data || 'Hata!'));
                }
            },
            error: function () {
                $status.addClass('error').text('✗ Bağlantı hatası');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Kaydet');
            }
        });
    });

    // ── Yan reklam ayarlarını kaydet ─────────────────────────────
    $(document).on('click', '#mevzu-save-side-ads', function () {
        var $btn    = $(this);
        var $status = $('.mevzu-side-status');
        $btn.prop('disabled', true).text('Kaydediliyor...');
        $.ajax({
            url:  mevzuAds.ajaxUrl,
            type: 'POST',
            data: {
                action:      'mevzu_save_side_ads',
                nonce:       mevzuAds.nonce,
                aktif:       $('#mevzu-side-aktif').is(':checked') ? 1 : 0,
                goruntuleme: $('input[name="mevzu_side_goruntuleme"]:checked').val() || 'tumu',
                fixed_sol:   $('#mevzu-side-fixed-sol').is(':checked') ? 1 : 0,
                fixed_sag:   $('#mevzu-side-fixed-sag').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                if (res.success) {
                    $status.text('✓ Kaydedildi');
                    setTimeout(function () { $status.text(''); }, 2500);
                } else {
                    $status.addClass('error').text('✗ ' + (res.data || 'Hata!'));
                }
            },
            error: function () {
                $status.addClass('error').text('✗ Bağlantı hatası');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Kaydet');
            }
        });
    });

    // ── Görsel seç ──────────────────────────────────────────────
    $(document).on('click', '.mevzu-select-image', function () {
        var $card = $(this).closest('.mevzu-ad-card');

        var frame = wp.media({
            title:    'Görsel Seç',
            button:   { text: 'Seç' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $card.find('.mevzu-ad-image-id').val(attachment.id);
            $card.find('.mevzu-image-preview').html('<img src="' + attachment.url + '" alt="">');
            $card.find('.mevzu-select-image').text('Görseli Değiştir');
            $card.find('.mevzu-remove-image').show();
        });

        frame.open();
    });

    // ── Görseli kaldır ──────────────────────────────────────────
    $(document).on('click', '.mevzu-remove-image', function () {
        var $card = $(this).closest('.mevzu-ad-card');
        $card.find('.mevzu-ad-image-id').val('');
        $card.find('.mevzu-image-preview').html('');
        $card.find('.mevzu-select-image').text('Görsel Seç');
        $(this).hide();
    });

    // ── Kaydet ──────────────────────────────────────────────────
    $(document).on('click', '.mevzu-save-ad', function () {
        var $card   = $(this).closest('.mevzu-ad-card');
        var $btn    = $(this);
        var $status = $card.find('.mevzu-save-status');
        var id      = $card.data('id');

        $btn.prop('disabled', true).text('Kaydediliyor...');
        $status.removeClass('error').text('');

        $.ajax({
            url:  mevzuAds.ajaxUrl,
            type: 'POST',
            data: {
                action:      'mevzu_save_ad',
                nonce:       mevzuAds.nonce,
                zone_id:     id,
                active:      $card.find('.mevzu-ad-active').is(':checked') ? 1 : 0,
                type:        $card.find('.mevzu-ad-type').val(),
                html_code:   $card.find('.mevzu-ad-html-code').val(),
                image_id:    $card.find('.mevzu-ad-image-id').val(),
                link_url:    $card.find('.mevzu-ad-link-url').val(),
                link_title:  $card.find('.mevzu-ad-link-title').val(),
                placeholder: $card.find('.mevzu-ad-placeholder').is(':checked') ? 1 : 0,
                start_date:  $card.find('.mevzu-ad-start-date').val(),
                end_date:    $card.find('.mevzu-ad-end-date').val(),
            },
            success: function (res) {
                if (res.success) {
                    $status.text('✓ Kaydedildi');
                    setTimeout(function () { $status.text(''); }, 2500);
                } else {
                    $status.addClass('error').text('✗ ' + (res.data || 'Hata!'));
                }
            },
            error: function () {
                $status.addClass('error').text('✗ Bağlantı hatası');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Kaydet');
            }
        });
    });

    // ── Gelen Hash'e Göre Hedefe Git (Blink Efekti) ────────────────
    $(window).on('load', function() {
        if (window.location.hash) {
            var targetId = window.location.hash; // e.g. #govde_ust
            var $target = $(targetId);
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 50
                }, 500);

                var blinkCount = 0;
                var maxBlinks = 6; // 3 kez yanıp sönecek (6 geçiş)
                var originalBg = $target.css('background-color') || '#ffffff';

                var blinkInterval = setInterval(function() {
                    if (blinkCount % 2 === 0) {
                        $target.css('background-color', '#fff3cd'); // Açık sarı
                        $target.css('transition', 'background-color 0.3s');
                    } else {
                        $target.css('background-color', originalBg);
                    }
                    
                    blinkCount++;
                    if (blinkCount >= maxBlinks) {
                        clearInterval(blinkInterval);
                        $target.css('background-color', ''); // Stili temizle
                    }
                }, 400);
            }
        }
    });

}(jQuery));
