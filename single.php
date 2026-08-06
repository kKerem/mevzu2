<?php get_header(); set_post_view(); ?>

	
	<div id="current-post" data-id="<?php echo get_the_ID(); ?>"></div>
		<div class="container">
			<?php Mevzu_Ads_Manager::render_swiper(); ?>
			<?php get_template_part("sablon/reklamlar"); ?>

			<?php ramazan(); ?>

			<?php echo anasayfa_reklam('govde_ust_reklam'); ?>

			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$haber_sablon = mevzu_get_single_haber_sablon( get_the_ID() );
				$has_sidebar  = mevzu_single_has_sidebar( $haber_sablon );
				$main_col = $has_sidebar ? 'col-12 col-lg-8' : 'col-12';
				?>

			<div class="row mt-lg-3 justify-content-center">
				<div class="<?php echo esc_attr( $main_col ); ?>">
					<?php mevzu_load_single_haber_template( $haber_sablon ); ?>
					<?php
					if ( get_option( 'options_haberlerde_etiket_gosterimi' ) == 1 && ! has_category( get_option( 'options_kose_yazilari_kategorisi' ), $post->ID ) ) {
						if ( get_the_tags() ) {
							echo '<div class="px-2 px-md-0"><div class="row align-items-center mb-3 g-3 gap-2">';
							foreach ( get_the_tags() as $tag ) {
								echo '<div class="col-auto d-flex align-items-center gap-2 border bg-light rounded-pill py-1"><a class="text-link small d-flex align-items-center pe-2 text-decoration-none fw-normal" href="' . get_tag_link( $tag->term_id ) . '">
								<svg class="opacity-50 me-1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M10.537 2.164a3 3 0 0 1 2.244.727l.15.14l7.822 7.823a3 3 0 0 1 .135 4.098l-.135.144l-5.657 5.657a3 3 0 0 1-4.098.135l-.144-.135L3.03 12.93a3 3 0 0 1-.878-2.188l.011-.205l.472-5.185a3 3 0 0 1 2.537-2.695l.179-.021zM8.024 8.025a2 2 0 1 0 2.829 2.829a2 2 0 0 0-2.829-2.829"/></g></svg>
								' . $tag->name . '</a>';
								if ( function_exists( 'mevzu_render_tag_follow_button' ) ) {
									mevzu_render_tag_follow_button( $tag->term_id, false, false );
								}
								echo '</div>';
							}
							echo '</div></div>';
						}
					}
					?>

					<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>

					<?php
					$current_post_id    = get_the_ID();
					$current_categories = wp_get_post_categories( $current_post_id );

					if ( ! empty( $current_categories ) ) {
						$related_posts_query = new WP_Query(
							array(
								'post_type'      => 'post',
								'posts_per_page' => 6,
								'post__not_in'   => array( $current_post_id ),
								'orderby'        => 'date',
								'order'          => 'DESC',
								'offset'         => 1,
								'category__in'   => $current_categories,
							)
						);

						if ( $related_posts_query->have_posts() ) : ?>
							<div class="tema-widget bg-white shadow-sm rounded-3 mt-3 mt-lg-4 mx-2 mx-md-0">
								<h2 class="mb-0"><?php echo in_category( 'kose-yazilari' ) ? 'Diğer Yazılar' : 'Benzer Haberler'; ?></h2>
								<div class="p-3">
									<div class="swiper swiper-yatay" id="swiper-slider-3">
										<div class="swiper-wrapper">
											<?php
											while ( $related_posts_query->have_posts() ) :
												$related_posts_query->the_post();
												?>
												<div class="swiper-slide">
													<?php
													if ( in_category( 'kose-yazilari' ) ) {
														get_template_part( 'sablon/card-2' );
													} else {
														get_template_part( 'sablon/sablon-1-nobox' );
													}
													?>
												</div>
												<?php
											endwhile;
											?>
										</div>
									</div>
								</div>
							</div>
						<?php else : ?>
							<p>Benzer <?php echo in_category( 'kose-yazilari' ) ? 'yazı' : 'haber'; ?> bulunamadı.</p>
						<?php
						endif;
						wp_reset_postdata();
					}
					?>
				</div>

				<?php if ( $has_sidebar ) : ?>
                    <div class="col-12 col-lg-4">
                        <div class="sticky-top">
                            <?php dynamic_sidebar( 'sidebar-single' ); ?>
                        </div>
                    </div>
				<?php endif; ?>
			</div>

			<?php endwhile; ?>

			<?php if ( ! in_category( 'kose-yazilari' ) && get_option( 'options_sonsuz_kaydirma' ) == 1 ) {
				echo do_shortcode( '[post_listings]' );
			} ?>

		</div>
	</div><!-- #main -->

<?php
/**
 * AlSat Haber Puan Entegrasyonu — single.php snippet
 *
 * Aşağıdaki kodu, kullandığınız temanın single.php dosyasındaki
 * get_header(); satırından hemen sonra veya get_footer();'dan hemen önceye
 * yapıştırın. Kod sadece URL'de ?puankazan=TOKEN parametresi varsa çalışır.
 */

if (!defined('ABSPATH')) {
    exit;
}

// AlSat API adresi: önce functions.php snippet'indeki ayarı dene, yoksa sabit değer kullan.
$alsat_api = function_exists('get_option') ? get_option('alsat_api_url', '') : '';
if (!$alsat_api) {
    $alsat_api = 'http://localhost:3000'; // CANLIYA ALMADAN ÖNCE DEĞİŞTİRİN
}
$alsat_api = rtrim($alsat_api, '/');

$alsat_token = isset($_GET['puankazan']) ? sanitize_text_field($_GET['puankazan']) : '';

if (is_singular('post') && $alsat_token && $alsat_api) {

// Haber içeriğindeki kelime sayısına göre okuma süresi hesapla (Türkçe karakter uyumlu)
$alsat_content       = wp_strip_all_tags(get_the_content());
$alsat_word_count    = count(array_filter(preg_split('/\s+/u', $alsat_content)));
$alsat_words_per_min = 200;
$alsat_read_seconds  = max(10, (int) round(($alsat_word_count / max(1, $alsat_words_per_min)) * 60));
$alsat_countdown     = max(5, (int) round($alsat_read_seconds / 2));
?>
<!-- AlSat Claim Banner -->
<div id="alsat-puan-box" class="position-fixed bottom-0 start-50 translate-middle-x mb-3 fz-14"
     style="z-index:99999;width:min(92vw,420px);">
    <div class="bg-white shadow-sm p-3 rounded-4 overflow-hidden">
        <div class="d-flex align-items-center gap-3">
            <!-- İkon -->
            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0 w-48 h-48 bg-success bg-opacity-10">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 12 20 22 4 22 4 12"></polyline>
                    <rect x="2" y="7" width="20" height="5"></rect>
                    <line x1="12" y1="22" x2="12" y2="7"></line>
                    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                </svg>
            </div>
            <!-- Metin -->
            <div class="flex-grow-1 min-w-0">
                <div id="alsat-title" class="fw-bold mb-1">Okumaya devam edin, puan kazanın</div>
                <div id="alsat-sub" class="text-secondary small">
                    <span id="alsat-left"><?php echo (int) $alsat_countdown; ?></span> sn sonra puanınız hesabınıza yatacak.
                </div>
            </div>
            <!-- Kapatma butonu -->
            <button type="button" class="btn-close flex-shrink-0" aria-label="Kapat"
                    onclick="document.getElementById('alsat-puan-box').remove()"></button>
        </div>
        <!-- Progress bar -->
        <div class="progress mt-3 rounded-pill" style="height:8px;">
            <div id="alsat-bar" class="progress-bar bg-success" role="progressbar"
                 style="width:0%;transition:width .05s linear;"></div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var TOKEN   = <?php echo json_encode($alsat_token); ?>;
    var BASE    = <?php echo json_encode($alsat_api); ?>;
    var SECONDS = <?php echo (int) $alsat_countdown; ?>;

    var box   = document.getElementById('alsat-puan-box');
    var bar   = document.getElementById('alsat-bar');
    var lblL  = document.getElementById('alsat-left');
    var title = document.getElementById('alsat-title');
    var sub   = document.getElementById('alsat-sub');

    if (!box || !TOKEN || !BASE) return;

    var totalSeconds = SECONDS;
    var startTime    = Date.now();
    var hiddenAt     = null;
    var hiddenTotal  = 0;
    var claimed      = false;

    function setMessage(mainTitle, detail, color) {
        if (mainTitle) title.textContent = mainTitle;
        if (detail)    sub.textContent   = detail;
        if (color) {
            bar.style.background = color;
            bar.style.width = '100%';
        }
    }

    function closeBox() {
        box.style.opacity = '0';
        box.style.transition = 'opacity .4s ease';
        setTimeout(function () { box.remove(); }, 400);
    }

    function claim() {
        if (claimed) return;
        claimed = true;
        setMessage('Puan veriliyor…', 'Lütfen bekleyin.', null);

        var url = BASE + (BASE.match(/\/api\/news\/claim$/) ? '?puankazan=' : '/api/news/claim?puankazan=') + encodeURIComponent(TOKEN);

        fetch(url, { method: 'GET', mode: 'cors', credentials: 'omit' })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (d) {
                if (d && d.ok) {
                    if (d.already) {
                        setMessage('Puan zaten verildi', d.message || 'Bu haber için daha önce puan alınmış.', '#16a34a');
                    } else {
                        setMessage('Tebrikler! 🎉', d.message || (d.reward ? '+' + d.reward + ' puan hesabınıza eklendi.' : 'Puanınız eklendi.'), '#16a34a');
                    }
                } else {
                    setMessage('Puan verilemedi', (d && d.error) ? d.error : 'Lütfen AlSat üzerinden tekrar deneyin.', '#dc2626');
                }
                setTimeout(closeBox, 4000);
            })
            .catch(function () {
                setMessage('Bağlantı hatası', 'AlSat sunucusuna ulaşılamadı.', '#dc2626');
                setTimeout(closeBox, 4000);
            });
    }

    function tick() {
        if (claimed) return;

        if (document.hidden) {
            if (!hiddenAt) hiddenAt = Date.now();
            requestAnimationFrame(tick);
            return;
        }

        if (hiddenAt) {
            hiddenTotal += Date.now() - hiddenAt;
            hiddenAt = null;
        }

        var elapsed = (Date.now() - startTime - hiddenTotal) / 1000;
        var left    = Math.max(0, totalSeconds - elapsed);
        var pct     = Math.min(100, (elapsed / totalSeconds) * 100);

        bar.style.width = pct + '%';
        if (lblL) lblL.textContent = Math.ceil(left);

        if (left <= 0) {
            claim();
            return;
        }

        requestAnimationFrame(tick);
    }

    if (lblL) lblL.textContent = totalSeconds;
    requestAnimationFrame(tick);
})();
</script>
<?php } ?>



<?php get_footer(); ?>
