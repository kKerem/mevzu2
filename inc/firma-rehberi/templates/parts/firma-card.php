<?php
/**
 * Firma kartı — kart/liste görünümü Bootstrap card yapısıyla.
 */
$post_id     = get_the_ID();
$tel         = get_post_meta( $post_id, '_firma_telefon', true );
$website     = get_post_meta( $post_id, '_firma_website', true );
$adres       = get_post_meta( $post_id, '_firma_adres',   true );
$featured    = firma_is_featured( $post_id );
$kategoriler = get_the_terms( $post_id, 'firma-kategori' );
$sehirler    = get_the_terms( $post_id, 'firma-sehir'    );

// Bugün açık mı?
$bugun_acik  = null;
$saatler_raw = get_post_meta( $post_id, '_firma_saatler', true );
if ( $saatler_raw ) {
    $saatler   = json_decode( $saatler_raw, true );
    $gun_map   = [ 'Mon'=>'mon','Tue'=>'tue','Wed'=>'wed','Thu'=>'thu','Fri'=>'fri','Sat'=>'sat','Sun'=>'sun' ];
    $bugun_key = $gun_map[ date('D') ] ?? '';
    if ( $bugun_key && isset( $saatler[ $bugun_key ] ) ) {
        $h = $saatler[ $bugun_key ];
        if ( ! empty( $h['closed'] ) ) {
            $bugun_acik = false;
        } else {
            $now   = strtotime( 'today ' . date('H:i') );
            $open  = strtotime( 'today ' . ($h['open']  ?? '00:00') );
            $close = strtotime( 'today ' . ($h['close'] ?? '23:59') );
            $bugun_acik = ( $now >= $open && $now <= $close );
        }
    }
}
?>
<div class="bg-white shadow-sm rounded-3 h-100 d-flex flex-column<?php echo $featured ? ' border-warning' : ''; ?>">

    <a href="<?php the_permalink(); ?>" class="text-decoration-none text-link d-flex flex-column flex-grow-1">

        <!-- Logo / Görsel -->
        <div class="firma-card-logo position-relative p-1">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
            <?php else : ?>
                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                    <i class="ri-store-2-line" style="font-size:2.5rem"></i>
                </div>
            <?php endif; ?>
            <?php if ( $featured ) : ?>
                <span class="firma-featured-badge m-3 bg-warning text-body p-1 rounded text-uppercase fw-bolder">Öne Çıkarılan</span>
            <?php endif; ?>
        </div>

        <!-- Kart Gövdesi -->
        <div class="card-body p-3 pt-2 d-flex flex-column gap-1">

            <?php if ( $kategoriler && ! is_wp_error( $kategoriler ) ) : ?>
                <div class="d-flex flex-wrap gap-1 mb-1">
                    <?php foreach ( $kategoriler as $kat ) : ?>
                        <span class="badge text-bg-light border fw-normal"><?php echo esc_html( $kat->name ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 class="fw-bold fs-6 mb-1 satir-2"><?php the_title(); ?></h3>

            <?php if ( $sehirler && ! is_wp_error( $sehirler ) ) : ?>
                <div class="text-muted small d-flex align-items-center gap-1">
                    <i class="ri-map-pin-line"></i><?php echo esc_html( $sehirler[0]->name ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $tel ) : ?>
                <div class="text-muted small d-flex align-items-center gap-1">
                    <i class="ri-phone-line"></i><?php echo esc_html( $tel ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $bugun_acik !== null ) : ?>
                <div class="d-flex align-items-center gap-2 opacity-75 small-2 fw-semibold mt-auto <?php echo $bugun_acik ? 'text-success' : 'text-danger'; ?>">
                    <i class="ri-circle-fill small-2"></i> <?php echo $bugun_acik ? 'Şuan Açık' : 'Şuan Kapalı'; ?>
                </div>
            <?php endif; ?>
        </div>
    </a>

</div>
