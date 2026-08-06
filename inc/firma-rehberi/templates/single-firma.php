<?php
/**
 * Firma Rehberi — Tek Firma Detay Sayfası
 */
get_header();
set_post_view();

if ( ! have_posts() ) { get_footer(); return; }
the_post();

$post_id     = get_the_ID();
$yetkili     = get_post_meta( $post_id, '_firma_yetkili',    true );
$tel         = get_post_meta( $post_id, '_firma_telefon',    true );
$sabit_tel1  = get_post_meta( $post_id, '_firma_sabit_tel1', true );
$sabit_tel2  = get_post_meta( $post_id, '_firma_sabit_tel2', true );
$eposta      = get_post_meta( $post_id, '_firma_eposta',     true );
$website     = get_post_meta( $post_id, '_firma_website', true );
$adres       = get_post_meta( $post_id, '_firma_adres',   true );
$lat         = get_post_meta( $post_id, '_firma_lat',     true );
$lng         = get_post_meta( $post_id, '_firma_lng',     true );
$video       = get_post_meta( $post_id, '_firma_video',   true );
$saatler_raw     = get_post_meta( $post_id, '_firma_saatler', true );
$saatler         = $saatler_raw ? json_decode( $saatler_raw, true ) : [];
$saatler_unknown = get_post_meta( $post_id, '_firma_saatler_unknown', true );
$featured    = firma_is_featured( $post_id );
$kategoriler = get_the_terms( $post_id, 'firma-kategori' );
$sehirler    = get_the_terms( $post_id, 'firma-sehir' );
$view_count  = (int) get_post_meta( $post_id, '_firma_view_count', true );

$gun_adlari = [ 'mon'=>'Pazartesi','tue'=>'Salı','wed'=>'Çarşamba','thu'=>'Perşembe','fri'=>'Cuma','sat'=>'Cumartesi','sun'=>'Pazar' ];
$gun_map    = [ 'Mon'=>'mon','Tue'=>'tue','Wed'=>'wed','Thu'=>'thu','Fri'=>'fri','Sat'=>'sat','Sun'=>'sun' ];
$bugun_key  = $gun_map[ date('D') ] ?? '';

$galeri_raw  = get_post_meta( $post_id, '_firma_galeri', true );
$galeri      = $galeri_raw ? json_decode( $galeri_raw, true ) : [];
$galeri      = is_array( $galeri ) ? array_filter( array_map( 'absint', $galeri ) ) : [];

// Rating verileri
$rating_total  = (int) get_post_meta( $post_id, '_firma_rating_total', true );
$rating_count  = (int) get_post_meta( $post_id, '_firma_rating_count', true );
$rating_avg    = $rating_count > 0 ? round( $rating_total / $rating_count, 1 ) : 0;
$already_voted = false;
if ( is_user_logged_in() ) {
    $voters = json_decode( get_post_meta( $post_id, '_firma_rating_voters', true ) ?: '[]', true );
    $already_voted = in_array( 'u_' . get_current_user_id(), $voters, true );
} else {
    $already_voted = ! empty( $_COOKIE[ 'firma_voter_' . $post_id ] );
}

$basvuru_page_id = Firma_Admin::get('basvuru_sayfasi');
$basvuru_url     = $basvuru_page_id ? get_permalink( $basvuru_page_id ) : '';
$login_required  = Firma_Admin::get('login_required', false);
$show_btn        = $basvuru_url && ( ! $login_required || is_user_logged_in() );
?>
<div class="container">
    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>

    <?php echo anasayfa_reklam('govde_ust_reklam'); ?>

    <div class="row justify-content-between mt-lg-3">
        <div class="col-12 col-lg-8">




            <div class="bg-white shadow-sm rounded-3" id="firma-print-area">

                <div class="p-3 pb-2">
                    <!-- Başlık & Logo -->
                    <div class="row g-3 g-lg-4">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="col-12 col-md-4">
                                <div class="position-relative">
                                    <?php if ( $featured ) : ?>
                                        <span class="position-absolute top-0 start-0 m-2 firma-featured-badge bg-primary text-white p-1 rounded text-uppercase fw-bolder">Öne Çıkarılan</span>
                                    <?php endif; ?>
                                    <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col">
                            <h1 class="fz-18 fw-bolder mt-2 mb-1"><?php the_title(); ?></h1>

                            <!-- Yıldız Değerlendirme -->
                            <div class="firma-rating-widget row align-items-stretch gx-2 mt-2 small"
                                data-post-id="<?php echo $post_id; ?>"
                                data-nonce="<?php echo wp_create_nonce('firma_submit_nonce'); ?>"
                                data-voted="<?php echo $already_voted ? '1' : '0'; ?>"
                                data-avg="<?php echo $rating_avg; ?>">

                                <div class="col-auto">
                                    <div class="firma-rating-score h5 fw-bolder bg-warning rounded d-flex align-items-center justify-content-center py-1 px-2 h-100 w-100"><?php echo $rating_avg > 0 ? number_format( $rating_avg, 1 ) : '—'; ?></div>
                                </div>

                                <div class="col">
                                    <div class="firma-rating-msg text-muted small-2">
                                        <?php echo $rating_count; ?> değerlendirme<?php if ( $already_voted ) : ?> <span class="text-dark">(daha önce değerlendirmişsiniz)</span><?php endif; ?>
                                    </div>
                                    <div class="firma-stars-display d-flex gap-1 align-items-center">
                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                            <i class="ri-star-<?php echo $i <= round( $rating_avg ) ? 'fill' : 'line'; ?> text-warning h4 m-0<?php echo ! $already_voted ? ' firma-star-btn' : ''; ?>"
                                            <?php if ( ! $already_voted ) : ?>
                                            data-star="<?php echo $i; ?>" role="button" style="cursor:pointer;"
                                            <?php endif; ?>></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>

                <!-- İletişim -->
                <div class="tema-widget">
                    <div class="table-responsive firma-iletisim p-2 pt-0">
                        <table class="table table-sm align-top mb-0 border rounded overflow-hidden">
                            <tbody class="small">
                                <?php if ( $yetkili ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2" style="width:25%"><i class="ri-user-fill text-dark me-2 h6"></i>Yetkili Kişi</th>
                                        <td class="p-2 fw-semibold"><?php echo esc_html( $yetkili ); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $tel ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2" style="width:25%"><i class="ri-smartphone-fill text-dark me-2 h6"></i>Cep Telefonu</th>
                                        <td class="p-2">
                                            <div class="row g-2">
                                                <div class="col-auto"><a href="tel:<?php echo esc_attr($tel); ?>" class="btn btn-secondary fw-semibold d-flex align-items-center border-0"><i class="ri-phone-line mb-0 me-2 h6"></i>Ara</a></div>
                                                <div class="col col-md-auto"><a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/','',$tel)); ?>" class="btn btn-success fw-semibold d-flex align-items-center border-0"><i class="ri-whatsapp-line mb-0 me-2 h6"></i>WhatsApp</a></div>
                                                <div class="col-12"><span class="text-muted small"><?php echo esc_html($tel); ?></span></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $sabit_tel1 ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2"><i class="ri-phone-fill text-dark me-2 h6"></i>Telefon 1</th>
                                        <td class="p-2">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto"><a href="tel:<?php echo esc_attr($sabit_tel1); ?>" class="btn btn-secondary fw-semibold d-flex align-items-center border-0"><i class="ri-phone-line mb-0 me-2 h6"></i>Ara</a></div>
                                                <div class="col-auto"><span class="text-muted small"><?php echo esc_html($sabit_tel1); ?></span></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $sabit_tel2 ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2"><i class="ri-phone-fill text-dark me-2 h6"></i>Telefon 2</th>
                                        <td class="p-2">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto"><a href="tel:<?php echo esc_attr($sabit_tel2); ?>" class="btn btn-secondary fw-semibold d-flex align-items-center border-0"><i class="ri-phone-line mb-0 me-2 h6"></i>Ara</a></div>
                                                <div class="col-auto"><span class="text-muted small"><?php echo esc_html($sabit_tel2); ?></span></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $eposta ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2"><i class="ri-mail-fill text-dark me-2 h6"></i>E-Posta</th>
                                        <td class="p-2">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto"><a href="mailto:<?php echo esc_attr($eposta); ?>" class="btn btn-secondary fw-semibold d-flex align-items-center border-0"><i class="ri-mail-send-line mb-0 me-2 h6"></i>E-Posta Gönder</a></div>
                                                <div class="col-12"><span class="text-muted small"><?php echo esc_html($eposta); ?></span></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $website ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2"><i class="ri-global-fill text-dark me-2 h6"></i>İnternet Sitemiz</th>
                                        <td class="p-2"><a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="text-link fw-semibold d-inline-flex align-items-center"><?php echo esc_html(preg_replace('#^https?://#', '', rtrim($website, '/'))); ?><i class="ri-external-link-line mb-0 ms-1 h6"></i></a></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $kategoriler && ! is_wp_error( $kategoriler ) ) : ?>
                                    <tr class="align-middle">
                                        <th class="fw-normal p-2"><i class="ri-price-tag-3-fill text-dark me-2 h6"></i>Faaliyet Alanlarımız</th>
                                        <td class="p-2">
                                            <?php foreach ( $kategoriler as $i => $kat ) : ?>
                                                <a href="<?php echo get_term_link( $kat ); ?>" class="text-link"><?php echo esc_html( $kat->name ); ?></a><?php echo ( $i < count( $kategoriler ) - 1 ) ? '<span class="text-muted">,</span>' : ''; ?>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ( $sehirler && ! is_wp_error($sehirler) ) : ?>
                                    <tr>
                                        <th class="fw-normal p-2"><i class="ri-building-fill text-dark me-2 h6"></i>Çalışma Bölgelerimiz</th>
                                        <td class="p-2">
                                            <?php foreach ($sehirler as $i => $s) : ?>
                                                <a href="<?php echo get_term_link($s); ?>" class="text-link text-decoration-none"><?php echo esc_html($s->name); ?></a><?php echo ( $i < count($sehirler)-1 ) ? '<span class="text-muted">,</span> ' : ''; ?>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Galeri -->
            <?php
            $galeri_items = array_values( array_filter( $galeri, function( $id ) {
                return wp_get_attachment_url( $id );
            } ) );
            if ( ! empty( $galeri_items ) ) :
                $gcount = count( $galeri_items );
            ?>
            <div class="widget bg-white shadow-sm rounded-3 mt-3" id="firma-print-area">
                <h2 class="mb-0">Galeri</h2>
                <div class="p-3">
                    <!-- Ana Swiper -->
                    <div class="swiper firma-galeri-swiper mt-3 rounded overflow-hidden" id="firma-galeri-main" style="height:380px;">
                        <div class="swiper-wrapper">
                            <?php foreach ( $galeri_items as $idx => $img_id ) :
                                $full  = wp_get_attachment_url( $img_id );
                                $large = wp_get_attachment_image_url( $img_id, 'large' );
                            ?>
                                <div class="swiper-slide">
                                    <div class="firma-galeri-lightbox-trigger h-100"
                                        style="cursor:zoom-in;"
                                        data-full="<?php echo esc_url( $full ); ?>"
                                        data-bs-toggle="modal" data-bs-target="#firmaLightboxModal">
                                        <img src="<?php echo esc_url( $large ); ?>" class="w-100 h-100"
                                            style="object-fit:cover;" alt="">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( $gcount > 1 ) : ?>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Küçük resim (thumb) Swiper -->
                    <?php if ( $gcount > 1 ) : ?>
                    <div class="swiper firma-galeri-thumbs pt-2" id="firma-galeri-thumbs">
                        <div class="swiper-wrapper">
                            <?php foreach ( $galeri_items as $img_id ) :
                                $thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                            ?>
                                <div class="swiper-slide" style="width:64px;height:64px;cursor:pointer;opacity:.6;transition:opacity .2s;">
                                    <img src="<?php echo esc_url( $thumb ); ?>" alt=""
                                        class="w-100 h-100 object-fit-cover rounded" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Galeri Lightbox Modal -->
                    <div class="modal fade" id="firmaLightboxModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content bg-black border-0">
                                <div class="modal-header border-0 p-2 justify-content-end">
                                    <span class="text-white me-auto ms-2 small" id="firmaLightboxCounter"></span>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0 position-relative">
                                    <div class="swiper" id="firma-lightbox-swiper">
                                        <div class="swiper-wrapper">
                                            <?php foreach ( $galeri_items as $img_id ) :
                                                $full = wp_get_attachment_url( $img_id );
                                            ?>
                                                <div class="swiper-slide d-flex align-items-center justify-content-center" style="background:#000;">
                                                    <img src="<?php echo esc_url($full); ?>" alt=""
                                                        style="max-height:85vh;max-width:100%;object-fit:contain;">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="swiper-button-prev" style="color:#fff;"></div>
                                        <div class="swiper-button-next" style="color:#fff;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <!-- Tanıtım Videosu -->
            <div class="widget bg-white shadow-sm rounded-3 mt-3" id="firma-print-area">
                <?php if ( $video ) :
                    // YouTube URL'sini embed URL'ye çevir
                    $video_id = '';
                    if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video, $m ) ) {
                        $video_id = $m[1];
                    }
                    if ( $video_id ) :
                ?>
                    <h2 class="mb-0">Tanıtım Videosu</h2>
                    <div class="p-3">
                        <div class="ratio ratio-16x9 rounded overflow-hidden">
                            <iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>?rel=0"
                                    title="Firma Tanıtım Videosu"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen loading="lazy"></iframe>
                        </div>
                    </div>
                <?php endif; endif; ?>
            </div>

            <div class="bg-white shadow-sm rounded-3 mt-3" id="firma-print-area">

                <?php //echo anasayfa_reklam('icerik_oncesi'); ?>
                
                <!-- Açıklama -->
                <?php if ( get_the_content() ) : ?>
                    <h2 class="tema-baslik pt-3 mb-0">Firma Hakkında</h2>
                    <div class="content single-content fz-14 p-3">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <?php //echo anasayfa_reklam('icerik_sonrasi'); ?>
            </div>

            <div class="bg-white shadow-sm rounded mt-3 p-2">
                <!-- Görüntülenme + Paylaş -->
                <div class="row align-items-center justify-content-between gap-2 w-100 small g-0"
                        id="firma-etkilesim-bar"
                        data-post-id="<?php echo $post_id; ?>"
                        data-nonce="<?php echo wp_create_nonce( 'firma_submit_nonce' ); ?>">
                    <div class="col text-muted small">
                        <?php
                        $share_url   = rawurlencode( get_permalink() );
                        $share_title = rawurlencode( get_the_title() );
                        $share_links = [
                            [
                                'label' => 'Paylaş',
                                'class' => 'facebook',
                                'icon'  => 'ri-facebook-fill',
                                'color' => '#1877f2',
                                'href'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $share_url,
                            ],
                            [
                                'label' => 'Tweetle',
                                'class' => 'twitter',
                                'icon'  => 'ri-twitter-x-fill',
                                'color' => '#000',
                                'href'  => 'https://twitter.com/intent/tweet?text=' . $share_title . '&url=' . $share_url,
                            ],
                            [
                                'label' => 'Gönder',
                                'class' => 'whatsapp',
                                'icon'  => 'ri-whatsapp-fill',
                                'color' => '#25d366',
                                'href'  => 'https://wa.me/?text=' . $share_title . '%20' . $share_url,
                            ],
                            [
                                'label' => 'Linki İlet',
                                'class' => 'telegram',
                                'icon'  => 'ri-telegram-fill',
                                'color' => '#229ed9',
                                'href'  => 'https://t.me/share/url?url=' . $share_url . '&text=' . $share_title,
                            ],
                        ];
                        ?>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <?php foreach ( $share_links as $s ) : ?>
                                <a href="<?php echo esc_url( $s['href'] ); ?>"
                                   target="_blank" rel="nofollow noopener"
                                   class="btn btn-sm d-flex align-items-center text-white fw-semibold p-0 rounded overflow-hidden <?php echo $s['class']; ?>">
                                    <i class="<?php echo $s['icon']; ?> h5 m-0 bg-dark bg-opacity-25 py-1 px-2"></i>
                                    <span class="text-white py-1 px-2"><?php echo $s['label']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn btn-secondary btn-sm fw-semibold rounded py-1 px-3 text-capitalize d-flex align-items-center gap-1 border-0"
                            onclick="firmaPrintSection('firma-print-area');">
                            <i class="ri-printer-line fz-16"></i>Çıktı Al
                        </button>
                    </div>
                </div>

                <!-- Paylaş Modal -->
                <div class="modal fade" id="firma-paylas-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content p-lg-3">
                            <div class="modal-header border-0">
                                <h2 class="modal-title fw-bolder fs-5">Bu Firmayı Paylaş</h2>
                                <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php echo do_shortcode('[social]'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col-12 col-lg-4">
            <div class="sticky-top d-flex flex-column gap-4" style="top:100px">

                
                <!-- Harita -->
                <?php if ( $adres ) : ?>
                    <section class="widget">
                        <h2<?php echo ( $lat && $lng ? '' : ' class="mb-0"' ); ?>>Adresimiz</h2>
                        <?php if ( $lat && $lng ) : ?>
                            <div class="firma-harita-embed">
                                <iframe
                                    src="https://maps.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>&z=15&output=embed"
                                    width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"
                                    title="<?php echo esc_attr( get_the_title() ); ?>"></iframe>
                            </div>
                        <?php endif; ?>
                        <div class="p-3 rounded-3 overflow-hidden small">
                            <?php if ( $adres ) : ?>
                                <?php
                                $maps_url = ( $lat && $lng )
                                    ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $lat . ',' . $lng )
                                    : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $adres );
                                $wa_lines = [
                                    'Şirket: ' . get_the_title(),
                                    'Adres: ' . $adres,
                                    'Yol Tarifi: ' . $maps_url,
                                ];
                                $wa_url = 'https://wa.me/?text=' . implode(
                                    '%0A',
                                    array_map( 'rawurlencode', $wa_lines )
                                );
                                ?>
                                <div><?php echo esc_html( $adres ); ?></div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener"
                                        class="btn btn-dark fw-semibold d-flex align-items-center">
                                        <i class="ri-road-map-line me-1"></i>Yol Tarifi Al
                                    </a>
                                    <a href="<?php echo esc_attr( $wa_url ); ?>" target="_blank" rel="noopener"
                                        class="btn btn-secondary fw-semibold d-flex align-items-center border-0">
                                        <i class="ri-whatsapp-line me-1"></i>Konumu Paylaş
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Çalışma Saatleri -->
                <?php if ( ! $saatler_unknown && ! empty( $saatler ) ) : ?>
                    <section class="widget">
                        <h2 class="mb-1">Çalışma Saatlerimiz</h2>
                        <table class="table table-sm table-borderless fw-normal mb-2">
                            <tbody>
                            <?php foreach ( $gun_adlari as $key => $gun_adi ) :
                                $h      = $saatler[ $key ] ?? null;
                                $kapali = $h && ! empty( $h['closed'] );
                                $bugun  = ( $key === $bugun_key );
                            ?>
                                <tr <?php echo $bugun ? 'class="small fw-semibold"' : ' class="small"'; ?>>
                                    <td class="ps-3"><?php echo esc_html( $gun_adi ); ?></td>
                                    <td class="text-end pe-3">
                                        <?php if ( $kapali ) : ?>
                                            <span class="text-danger fw-semibold small">Kapalı</span>
                                        <?php elseif ( $h ) : ?>
                                            <span><?php echo esc_html( $h['open'] . ' – ' . $h['close'] ); ?></span>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                <?php endif; ?>

                <hr class="m-0">

                <?php if ( is_active_sidebar( 'firma-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'firma-sidebar' ); ?>
                <?php endif; ?>

                <?php if ( $show_btn ) : ?>
                    <div class="tema-widget bg-white shadow-sm rounded-3 p-3">
                        <h3 class="fs-6 fw-semibold mb-2">Firmanız burada yer alsın!</h3>
                        <p class="text-muted small mb-3">Firmanızı ücretsiz ekleyin, müşterileriniz sizi kolayca bulsun.</p>
                        <a href="<?php echo esc_url( $basvuru_url ); ?>" class="btn btn-primary w-100">
                            <i class="ri-add-line me-2 h6"></i>Firmamı Ekle
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
            
</div>

<script>
function firmaPrintSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var printWindow = window.open('', '_blank', 'width=1000,height=800');
    if (!printWindow) return;

    var cssLinks = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"]'))
        .map(function(link) { return '<link rel="stylesheet" href="' + link.href + '">'; })
        .join('');

    printWindow.document.write(
        '<!doctype html><html><head><meta charset="utf-8"><title><?php echo esc_js( get_the_title() ); ?></title>' +
        cssLinks +
        '<style>body{padding:20px;} .btn,.modal,.carousel-control-prev,.carousel-control-next{display:none !important;}</style>' +
        '</head><body>' + section.innerHTML + '</body></html>'
    );
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function () {
        printWindow.print();
        printWindow.close();
    }, 300);
}

// ---- Galeri Swiper (load sonrası — Swiper CDN'den yüklendikten sonra çalışır) ----
window.addEventListener('load', function () {
    var mainEl = document.getElementById('firma-galeri-main');
    if (!mainEl || typeof Swiper === 'undefined') return;

    var mainSwiper = new Swiper(mainEl, {
        loop: <?php echo $gcount > 1 ? 'true' : 'false'; ?>,
        spaceBetween: 0,
        navigation: {
            nextEl: '#firma-galeri-main .swiper-button-next',
            prevEl: '#firma-galeri-main .swiper-button-prev',
        },
        on: {
            slideChange: function () {
                updateThumbs(mainSwiper.realIndex);
            }
        }
    });

    var thumbsEl = document.getElementById('firma-galeri-thumbs');
    function updateThumbs(activeIdx) {
        if (!thumbsEl) return;
        thumbsEl.querySelectorAll('.swiper-slide').forEach(function (s, i) {
            s.style.opacity  = i === activeIdx ? '1'    : '0.55';
            s.style.outline  = i === activeIdx ? '2px solid var(--mevzu-primary)' : 'none';
        });
    }
    updateThumbs(0);

    if (thumbsEl) {
        thumbsEl.querySelectorAll('.swiper-slide').forEach(function (s, i) {
            s.addEventListener('click', function () {
                mainSwiper.slideToLoop(i);
                updateThumbs(i);
            });
        });
    }
});

// ---- Lightbox Swiper ----
var lightboxSwiper = null;
var lightboxModalEl = document.getElementById('firmaLightboxModal');

if (lightboxModalEl) {
    lightboxModalEl.addEventListener('shown.bs.modal', function () {
        if (!lightboxSwiper) {
            lightboxSwiper = new Swiper('#firma-lightbox-swiper', {
                loop: <?php echo $gcount > 1 ? 'true' : 'false'; ?>,
                navigation: {
                    nextEl: '#firma-lightbox-swiper .swiper-button-next',
                    prevEl: '#firma-lightbox-swiper .swiper-button-prev',
                },
                keyboard: { enabled: true },
                on: {
                    slideChange: function () {
                        var counter = document.getElementById('firmaLightboxCounter');
                        if (counter) counter.textContent = (lightboxSwiper.realIndex + 1) + ' / <?php echo $gcount; ?>';
                    }
                }
            });
        }
    });
}

// Galeri slide'a tıklanınca modal'ı o slayta aç
document.querySelectorAll('.firma-galeri-lightbox-trigger').forEach(function (el, idx) {
    el.addEventListener('click', function () {
        if (lightboxSwiper) {
            lightboxSwiper.slideToLoop(idx, 0);
        }
        var counter = document.getElementById('firmaLightboxCounter');
        if (counter) counter.textContent = (idx + 1) + ' / <?php echo $gcount; ?>';
    });
});

</script>

<?php get_footer(); ?>
