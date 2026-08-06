<?php
/**
 * Kategori / arşiv: tema ayarlarındaki manşet haber sayısı — ana manşet ile aynı Swiper yapısı.
 *
 * @package mevzu2
 */

$archive_manset_query = ( isset( $archive_manset_query ) && $archive_manset_query instanceof WP_Query )
	? $archive_manset_query
	: ( isset( $GLOBALS['mevzu_archive_manset_query'] ) && $GLOBALS['mevzu_archive_manset_query'] instanceof WP_Query ? $GLOBALS['mevzu_archive_manset_query'] : null );

if ( ! $archive_manset_query || ! $archive_manset_query->have_posts() ) {
	return;
}

$slider_modeli = function_exists( 'get_opt_g' ) ? get_opt_g( 'options_archive_manset', 'slider_modeli', 'default' ) : 'default';
?>
<div class="col-12">
	<div id="swiper-archive-kategori-manset" class="swiper swiper-yatay slider-archive-kategori rounded-3 widget">
		<div class="swiper-wrapper">
			<?php
			while ( $archive_manset_query->have_posts() ) :
				$archive_manset_query->the_post();
				?>
			<div class="swiper-slide">
				<a href="<?php the_permalink(); ?>" class="position-relative">
					<?php if ( get_post_thumbnail_id() ) : ?>
						<?php
						the_post_thumbnail(
							'gorsel-thumbnail-col-8',
							array(
								'title'   => get_the_title(),
								'loading' => 'lazy',
								'class'   => 'rounded-0 h-auto',
							)
						);
						?>
					<?php else : ?>
						<img src="<?php echo esc_url( get_template_directory_uri() . '/img/404.webp' ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="rounded-0 w-100 h-auto" loading="lazy" width="800" height="450">
					<?php endif; ?>
					<?php
					$yazi_ayarlari = get_post_meta( get_the_ID(), 'yazi_ayarlari', true );
					if ( function_exists( 'get_opt_g' ) && (int) get_opt_g( 'options_archive_manset', 'slider_basliklari', 0 ) === 1 && ( ! is_array( $yazi_ayarlari ) || ! in_array( 'manset_var', $yazi_ayarlari, true ) ) ) :
						?>
					<h3 class="swiper-title <?php echo esc_attr( get_opt_g( 'options_archive_manset', 'baslik_boyutu', 'fz-16' ) . ' ' . get_opt_g( 'options_archive_manset', 'baslik_hizasi', 'text-center' ) ); ?>">
						<?php the_title(); ?>
					</h3>
					<?php endif; ?>
				</a>
			</div>
			<?php endwhile; ?>
		</div>
		<div class="swiper-pagination rounded-md-bottom swiper-pagination-swiper-manset swiper-pagination-swiper-<?php echo esc_attr( $slider_modeli ); ?>"></div>
		<div class="swiper-button-prev start-0 rounded-end">
			<svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122l-5.66-5.658Z"/></g></svg>
		</div>
		<div class="swiper-button-next end-0 rounded-start">
			<svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z"/></g></svg>
		</div>
	</div>
</div>
<?php
unset( $GLOBALS['mevzu_archive_manset_query'] );
wp_reset_postdata();
