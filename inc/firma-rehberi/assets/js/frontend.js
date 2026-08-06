/**
 * Firma Rehberi — Frontend JS
 */
(function ($) {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Görünüm Toggle (kart ↔ liste)                                        */
    /* ------------------------------------------------------------------ */
    $(document).on('click', '.firma-gorunum-btn', function () {
        var gorunum = $(this).data('gorunum');
        $('#firma-listesi')
            .removeClass('firma-gorunum-kart firma-gorunum-liste')
            .addClass('firma-gorunum-' + gorunum);
        $('.firma-gorunum-btn').removeClass('active');
        $(this).addClass('active');
    });

    /* Kategori / Şehir / Arama → sadece #firma-filtre-ara butonuyla tetiklenir */
    /* change listener kasıtlı olarak yok — otomatik yönlendirme istenmiyor  */

    /* ------------------------------------------------------------------ */
    /* Daha Fazla Yükle (AJAX)                                              */
    /* ------------------------------------------------------------------ */
    $(document).on('click', '#firma-load-more', function () {
        var $btn  = $(this);
        var page  = parseInt($btn.data('page'), 10);
        var max   = parseInt($btn.data('max'), 10);

        $btn.prop('disabled', true).text('Yükleniyor...');

        $.post(firmaData.ajaxUrl, {
            action:   'firma_load_more',
            page:     page,
            kategori: $('#firma-filtre-kat').val()   || '',
            sehir:    $('#firma-filtre-sehir').val() || '',
        }, function (res) {
            if (res.success) {
                $('#firma-listesi').append(res.data.html);
                if (!res.data.has_more) {
                    $btn.closest('.text-center').remove();
                } else {
                    $btn.data('page', page + 1).prop('disabled', false).text('Daha Fazla Göster');
                }
            }
        });
    });

    /* ------------------------------------------------------------------ */
    /* Başvuru Formu — AJAX Submit                                          */
    /* ------------------------------------------------------------------ */
    $(document).on('submit', '#firma-basvuru-form', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $form.find('[type="submit"]');
        var $error   = $('#firma-basvuru-error');

        // Logo kontrolü
        var logoInput = document.getElementById('firma_logo');
        if (logoInput && logoInput.files[0]) {
            var file = logoInput.files[0];
            if (['image/jpeg','image/png','image/webp','image/gif'].indexOf(file.type) === -1) {
                showError($error, firmaData.strings.file_type); return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showError($error, firmaData.strings.file_size); return;
            }
        }

        var formData = new FormData($form[0]);
        formData.append('action', 'firma_submit');
        formData.append('nonce', firmaData.nonce);

        $btn.find('.firma-btn-text').addClass('d-none');
        $btn.find('.firma-btn-loading').removeClass('d-none');
        $btn.prop('disabled', true);
        $error.addClass('d-none').text('');

        $.ajax({
            url: firmaData.ajaxUrl, type: 'POST',
            data: formData, processData: false, contentType: false,
            success: function (res) {
                resetBtn($btn);
                if (res.success) {
                    $form.closest('.bg-white').hide();
                    $('#firma-basvuru-success').removeClass('d-none');
                    $('html,body').animate({ scrollTop: $('#firma-basvuru-success').offset().top - 80 }, 400);
                } else {
                    showError($error, res.data || firmaData.strings.error);
                }
            },
            error: function () {
                resetBtn($btn);
                showError($error, firmaData.strings.error);
            }
        });
    });

    function showError($el, msg) { $el.text(msg).removeClass('d-none'); }
    function resetBtn($btn) {
        $btn.find('.firma-btn-text').removeClass('d-none');
        $btn.find('.firma-btn-loading').addClass('d-none');
        $btn.prop('disabled', false);
    }

    /* Logo Önizleme */
    $(document).on('change', '#firma_logo', function () {
        var file = this.files[0];
        var $preview = $('#firma-logo-preview');
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (ev) {
                $preview.find('img').attr('src', ev.target.result);
                $preview.removeClass('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            $preview.addClass('d-none');
        }
    });

    /* Galeri Önizleme */
    $(document).on('change', '#firma_galeri', function () {
        var $wrap = $('#firma-galeri-preview');
        $wrap.empty();
        var files = this.files;
        var max   = Math.min(files.length, 5);
        for (var i = 0; i < max; i++) {
            (function (file) {
                if (!file.type.startsWith('image/')) return;
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var $thumb = $('<div class="position-relative" style="width:72px;height:72px;">' +
                        '<img src="' + ev.target.result + '" class="rounded border w-100 h-100 object-fit-cover">' +
                        '</div>');
                    $wrap.append($thumb);
                };
                reader.readAsDataURL(file);
            })(files[i]);
        }
    });

    /* ------------------------------------------------------------------ */
    /* Yıldız Değerlendirme                                                */
    /* ------------------------------------------------------------------ */
    var $ratingWidget = $('.firma-rating-widget');
    if ( $ratingWidget.length ) {
        var $stars  = $ratingWidget.find('.firma-star-btn');
        var $msg    = $ratingWidget.find('.firma-rating-msg');
        var rPostId = $ratingWidget.data('post-id');
        var rNonce  = $ratingWidget.data('nonce');
        var rAvg    = parseFloat( $ratingWidget.data('avg') ) || 0;

        // Hover: seçili yıldıza kadar doldur, çıkınca mevcut avg'e dön
        $stars.on('mouseenter', function () {
            var hov = parseInt( $(this).data('star'), 10 );
            $stars.each(function (i) {
                $(this)
                    .toggleClass('ri-star-fill', i < hov)
                    .toggleClass('ri-star-line', i >= hov);
            });
        }).on('mouseleave', function () {
            // Orijinal avg durumuna dön
            var rounded = Math.round(rAvg);
            $stars.each(function (i) {
                $(this)
                    .toggleClass('ri-star-fill', i < rounded)
                    .toggleClass('ri-star-line', i >= rounded);
            });
        });

        // Tıklama — oy ver
        $stars.on('click', function () {
            var chosen = parseInt( $(this).data('star'), 10 );

            // Etkileşimi kapat
            $stars.off('mouseenter mouseleave click').css('cursor', 'default');

            $.post(firmaData.ajaxUrl, {
                action:  'firma_rate',
                post_id: rPostId,
                stars:   chosen,
                nonce:   rNonce
            }, function (res) {
                if ( res.success ) {
                    var d = res.data;
                    rAvg = d.avg;
                    // Yıldızları yeni ortalamaya göre güncelle
                    var rounded = Math.round(d.avg);
                    $stars.each(function (i) {
                        $(this)
                            .removeClass('firma-star-btn')
                            .toggleClass('ri-star-fill', i < rounded)
                            .toggleClass('ri-star-line', i >= rounded);
                    });
                    // Skoru güncelle
                    $ratingWidget.find('.firma-rating-score').text(parseFloat(d.avg).toFixed(1));
                    // Mesajı güncelle
                    $msg.html(d.count + ' değerlendirme <i class="ri-check-line text-success ms-1"></i><span class="text-success">değerlendirmeniz kaydedildi!</span>');
                } else if ( res.data && res.data.voted ) {
                    $msg.html(res.data.count + ' değerlendirme <i class="ri-check-line text-success ms-1"></i><span class="text-success">değerlendirmeniz kaydedildi!</span>');
                }
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Galeri Lightbox                                                      */
    /* ------------------------------------------------------------------ */
    $(document).on('click', '.firma-galeri-lightbox-trigger', function () {
        var full = $(this).data('full');
        $('#firmaLightboxImg').attr('src', full);
    });

    /* ------------------------------------------------------------------ */
    /* Galeri Carousel — Aktif Thumbnail Vurgusu                           */
    /* ------------------------------------------------------------------ */
    var $carousel = $('#firmaGaleriCarousel');
    if ($carousel.length) {
        $carousel.on('slid.bs.carousel', function (e) {
            var idx = e.to;
            $('.firma-galeri-thumb').removeClass('opacity-100 border border-primary border-2').addClass('opacity-75');
            $('.firma-galeri-thumb').eq(idx).removeClass('opacity-75').addClass('opacity-100 border border-primary border-2');
        });
    }

    /* ------------------------------------------------------------------ */
    /* Görüntülenme Sayacı (Single Firma)                                   */
    /* ------------------------------------------------------------------ */
    var $etkilesim = $('#firma-etkilesim-bar');
    if ($etkilesim.length) {
        var postId = $etkilesim.data('post-id');
        var nonce  = $etkilesim.data('nonce');

        $.post(firmaData.ajaxUrl, { action: 'firma_view', post_id: postId, nonce: nonce },
            function (res) {
                if (res.success) $('#firma-view-count').text(res.data.count);
            }
        );
    }


})(jQuery);
