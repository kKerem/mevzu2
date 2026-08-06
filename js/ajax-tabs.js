jQuery(document).ready(function($) {
    var loadingHtml = '<div class="yukleniyor"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity="0.5"/><path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg><p class="mt-3">Yükleniyor...</p></div>';

    $(document).on('click', '.coklu .nav-link-coklu', function(e) {
        e.preventDefault();

        var $tab       = $(this);
        var categoryId = $tab.data('category-id') || 5686;
        var haberSayisi = $tab.data('haber-sayisi') || 6;

        // data-bs-target'tan içerik kabını bul; yoksa #tab-content'e düş
        var targetSel  = $tab.attr('data-bs-target') || '#tab-content';
        var $container = $(targetSel);
        if (!$container.length) $container = $('#tab-content');

        // Aktif tab stilini güncelle
        $tab.closest('ul').find('.nav-link-coklu').removeClass('active').attr('aria-selected', 'false');
        $tab.addClass('active').attr('aria-selected', 'true');

        $container.html(loadingHtml);

        var ajaxUrl = (typeof ajax_tabs !== 'undefined' && ajax_tabs.ajax_url) ? ajax_tabs.ajax_url : (window.location.origin + '/wp-admin/admin-ajax.php');
        $.ajax({
            url:  ajaxUrl,
            type: 'POST',
            data: { action: 'load_tab_posts', category_id: categoryId, haber_sayisi: haberSayisi },
            success: function(html) {
                $container.html(html);
                var viewAll = $tab.closest('.tema-widget').find('#view-all-link');
                if (viewAll.length) viewAll.attr('href', '?cat=' + categoryId);
            }
        });
    });

    // Her widget'ın ilk tabını ayrı ayrı tetikle
    $('.coklu').each(function() {
        $(this).find('.nav-link-coklu').first().trigger('click');
    });
});