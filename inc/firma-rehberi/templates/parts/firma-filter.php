<?php
/**
 * Firma filtre çubuğu
 */
$kategoriler = get_terms( [ 'taxonomy' => 'firma-kategori', 'hide_empty' => true, 'pad_counts' => true, 'parent' => 0, 'orderby' => 'name', 'order' => 'ASC' ] );
$sehirler    = get_terms( [ 'taxonomy' => 'firma-sehir',    'hide_empty' => true,  'orderby' => 'name', 'order' => 'ASC' ] );

$cur_kat   = sanitize_text_field( is_tax('firma-kategori') ? get_queried_object()->slug : ( $_GET['firma_kat']   ?? '' ) );
$cur_sehir = sanitize_text_field( is_tax('firma-sehir')    ? get_queried_object()->slug : ( $_GET['firma_sehir'] ?? '' ) );
$cur_arama = sanitize_text_field( $_GET['firma_arama'] ?? '' );

// Temel archive URL (taxonomy sayfasındaysa /firmalar'a yönlendir)
$base_url = get_post_type_archive_link('firma') ?: home_url('/firmalar/');
?>
<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 bg-white rounded shadow-sm mb-3">
    <div class="d-flex gap-2 flex-wrap flex-grow-1">

        <input type="text" id="firma-arama" placeholder="Firma ara..."
               class="form-control form-control-sm" style="max-width:200px" autocomplete="off"
               value="<?php echo esc_attr( $cur_arama ); ?>">

        <?php if ( ! empty( $kategoriler ) && ! is_wp_error( $kategoriler ) ) : ?>
            <select id="firma-filtre-kat" class="form-select form-select-sm" style="max-width:180px">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ( $kategoriler as $kat ) : ?>
                    <option value="<?php echo esc_attr( $kat->slug ); ?>" <?php selected( $cur_kat, $kat->slug ); ?>>
                        <?php echo esc_html( $kat->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ( ! empty( $sehirler ) && ! is_wp_error( $sehirler ) ) : ?>
            <select id="firma-filtre-sehir" class="form-select form-select-sm" style="max-width:160px">
                <option value="">Tüm Şehirler</option>
                <?php foreach ( $sehirler as $sehir ) : ?>
                    <option value="<?php echo esc_attr( $sehir->slug ); ?>" <?php selected( $cur_sehir, $sehir->slug ); ?>>
                        <?php echo esc_html( $sehir->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

    <button type="button" id="firma-filtre-ara" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <i class="ri-search-line"></i>Ara
        </button>
        <?php if ( $cur_kat || $cur_sehir || $cur_arama ) : ?>
            <a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="ri-close-line"></i>Temizle
            </a>
        <?php endif; ?>

    </div>
</div>
<script>
(function () {
    var baseUrl = '<?php echo esc_js( $base_url ); ?>';

    function buildUrl() {
        var url   = new URL(baseUrl);
        var kat   = document.getElementById('firma-filtre-kat');
        var sehir = document.getElementById('firma-filtre-sehir');
        var arama = document.getElementById('firma-arama');
        if (kat   && kat.value)        url.searchParams.set('firma_kat',   kat.value);
        if (sehir && sehir.value)      url.searchParams.set('firma_sehir', sehir.value);
        if (arama && arama.value.trim()) url.searchParams.set('firma_arama', arama.value.trim());
        return url.toString();
    }

    function goFilter() { window.location.href = buildUrl(); }

    var araBtn = document.getElementById('firma-filtre-ara');
    if (araBtn) araBtn.addEventListener('click', goFilter);

    var aramaInp = document.getElementById('firma-arama');
    if (aramaInp) {
        aramaInp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); goFilter(); }
        });
    }
})();
</script>
