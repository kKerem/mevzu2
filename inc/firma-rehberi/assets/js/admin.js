/**
 * Firma Rehberi — Admin JS (Harita Picker + Çalışma Saatleri)
 */
(function ($) {
    'use strict';


    /* ------------------------------------------------------------------ */
    /* Galeri — WP Media Library Picker                                     */
    /* ------------------------------------------------------------------ */
    var galeriFrame;

    /* Galeri Sortable */
    if ($('#firma-galeri-container').length) {
        $('#firma-galeri-container').sortable({
            items:  '.firma-galeri-item',
            cursor: 'grabbing',
            opacity: 0.7,
            update: function () {
                var ids = [];
                $('#firma-galeri-container .firma-galeri-item').each(function () {
                    ids.push(parseInt($(this).data('id'), 10));
                });
                $('#firma-galeri-ids').val(JSON.stringify(ids));
            },
        });
    }

    function galeriItemHtml(id, url) {
        return '<div class="firma-galeri-item" style="position:relative;width:80px;height:80px;cursor:grab;" data-id="' + id + '">' +
            '<img src="' + url + '" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">' +
            '<button type="button" class="firma-galeri-remove" style="position:absolute;top:-6px;right:-6px;background:#d63638;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:14px;line-height:1;cursor:pointer;padding:0;">&times;</button>' +
            '</div>';
    }

    $(document).on('click', '#firma-galeri-ekle', function (e) {
        e.preventDefault();

        if ( galeriFrame ) {
            galeriFrame.open();
            return;
        }

        galeriFrame = wp.media({
            title:    'Galeri Görsellerini Seç',
            button:   { text: 'Seçilenleri Ekle' },
            multiple: true,
        });

        galeriFrame.on('select', function () {
            var selection = galeriFrame.state().get('selection');
            var ids = [];
            var existing = $('#firma-galeri-ids').val();
            try { ids = JSON.parse(existing) || []; } catch(e) { ids = []; }

            selection.each(function (attachment) {
                var id  = attachment.get('id');
                var url = attachment.get('sizes') && attachment.get('sizes').thumbnail
                            ? attachment.get('sizes').thumbnail.url
                            : attachment.get('url');

                if (ids.indexOf(id) === -1) {
                    ids.push(id);
                    $('#firma-galeri-container').append(galeriItemHtml(id, url));
                }
            });

            $('#firma-galeri-ids').val(JSON.stringify(ids));
            // Yeni eklenen item'ları sortable'a dahil et
            $('#firma-galeri-container').sortable('refresh');
        });

        galeriFrame.open();
    });

    $(document).on('click', '.firma-galeri-remove', function () {
        var $item = $(this).closest('.firma-galeri-item');
        var id    = parseInt($item.data('id'), 10);
        $item.remove();

        var ids = [];
        try { ids = JSON.parse($('#firma-galeri-ids').val()) || []; } catch(e) { ids = []; }
        ids = ids.filter(function (i) { return i !== id; });
        $('#firma-galeri-ids').val(JSON.stringify(ids));
    });

    /* ------------------------------------------------------------------ */
    /* Çalışma Saatleri — "Kapalı" checkbox → input'ları devre dışı bırak  */
    /* ------------------------------------------------------------------ */
    $(document).on('change', '.firma-closed-cb', function () {
        var $row    = $(this).closest('tr');
        var disabled = $(this).prop('checked');
        $row.find('.firma-time-input').prop('disabled', disabled);
        $row.toggleClass('firma-day-kapali', disabled);
    });

})(jQuery);
