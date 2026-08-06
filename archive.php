<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package mevzu2
 */

if ( ! function_exists( 'mevzu2_archive_manset_base_query_args' ) ) {
	/**
	 * Arşiv manşet / liste sorgusu için taban WP_Query argümanları (kategori, etiket, vergi, yazar, tarih, yazı tipi arşivi).
	 *
	 * @return array<string,mixed>|null Desteklenmeyen arşivde null.
	 */
	function mevzu2_archive_manset_base_query_args() {
		$base = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);
		if ( is_category() ) {
			$base['cat'] = (int) get_queried_object_id();
			return $base;
		}
		if ( is_tag() ) {
			$base['tag_id'] = (int) get_queried_object_id();
			return $base;
		}
		if ( is_tax() ) {
			$term = get_queried_object();
			if ( ! $term || is_wp_error( $term ) || empty( $term->taxonomy ) || empty( $term->term_id ) ) {
				return null;
			}
			$base['tax_query'] = array(
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => (int) $term->term_id,
				),
			);
			return $base;
		}
		if ( is_author() ) {
			$base['author'] = (int) get_queried_object_id();
			return $base;
		}
		if ( is_date() ) {
			$piece = array();
			if ( get_query_var( 'year' ) ) {
				$piece['year'] = (int) get_query_var( 'year' );
			}
			if ( get_query_var( 'monthnum' ) ) {
				$piece['month'] = (int) get_query_var( 'monthnum' );
			}
			if ( get_query_var( 'day' ) ) {
				$piece['day'] = (int) get_query_var( 'day' );
			}
			if ( empty( $piece ) ) {
				return null;
			}
			$base['date_query'] = array( $piece );
			return $base;
		}
		if ( is_post_type_archive() ) {
			$pt = get_query_var( 'post_type' );
			if ( empty( $pt ) ) {
				return null;
			}
			if ( is_array( $pt ) ) {
				$pt = reset( $pt );
			}
			$base['post_type'] = $pt;
			return $base;
		}
		return null;
	}
}

get_header();
$current_category_id = get_queried_object_id();

if (is_category()) { $ad = single_cat_title('', false) . ' Haberleri';}
elseif (is_tag()) { $ad = single_tag_title('', false) . ' Etiketleri';}
elseif (is_author()) { $ad = get_the_author() . ' Yazıları';}
elseif (is_date()) { $ad = 'Tarih: ' . get_the_date();}
else { $ad = get_the_archive_title();}
?>

	<div class="container">
		<?php Mevzu_Ads_Manager::render_swiper(); ?>
		<?php get_template_part("sablon/reklamlar"); ?>

		<?php ramazan(); ?>

    	<?php if($current_category_id != get_option('options_video_kategorisi')) : ?>
			<?php
			if (is_category()) {
				$args = array(
					'post_type'      => 'post',
					'posts_per_page' => 1,
					'orderby'        => 'meta_value_num',
					'meta_key'       => 'views_count',
					'order'          => 'DESC',
					'cat'            => $current_category_id,
					'date_query'     => array(
						array(
							'after'     => '1111 week ago',
							'inclusive' => true,
						),
					),
				);
				$popular_posts_query = new WP_Query($args);
				if ($popular_posts_query->have_posts()) : ?>
					<div class="tema-widget bg-white rounded shadow-sm mt-3 mt-lg-4">
						<?php $count=0; while ($popular_posts_query->have_posts()) :
							$popular_posts_query->the_post(); $count++;
							if($count == 1 ) :
							?>
							<h2>Haftanın Okunanı</h2>
							
							<div class="px-3">
								<div class="row">
									<?php get_template_part('sablon/card-4-ilk'); ?>
								</div>
							</div>
							<?php endif; ?>
						<?php endwhile; wp_reset_postdata(); ?>
					</div>
				<?php
				endif;
			}
			?>

			<div class="row justify-content-between mt-4 mb-3 mb-lg-4 tema-widget">
				<div class="col-12 col-lg-8" data-aos="fade-in">
					<?php if ( have_posts() ) : ?>
						<?php 
						// Global $wp_query'yi pagination için koru (döngüden önce)
						global $wp_query;
						$archive_max_pages = $wp_query->max_num_pages;
						$archive_found_posts = isset($wp_query->found_posts) ? $wp_query->found_posts : 0;
						?>
						<!-- Doviz çevirici -->
						<?php if (is_category('ekonomi')) :
						$args = array(
							'before_widget' => '<section class="widget mt-3 mt-lg-4">',
							'after_widget'  => '</section>',
							'before_title'  => '<h2>',
							'after_title'   => '</h2>',
						);
						$instance = array(
							'offset'      => 0,
						);
						$widget = new DovizCeviriciWidget();
						echo $widget->widget($args, $instance);
						endif; ?>
						<!-- Doviz çevirici -->

						<div class="<?=mevzu_class()?> d-flex justify-content-between align-items-center mb-3">
							<h2 class="d-inline-flex">
								<span class="me-2"><?php echo $ad; ?></span>
								<?php 
								if ( is_category() && function_exists('mevzu_render_category_follow_button') ) {
									echo mevzu_render_category_follow_button($current_category_id, $show_text = "true");
								} elseif ( is_tag() && function_exists('mevzu_render_tag_follow_button') ) {
									echo mevzu_render_tag_follow_button($current_category_id, $show_text = "true");
								}
								?>
							</h2>
						</div>

						<div class="row g-3 tema-widget tema-kategori">
							<?php
							$mevzu_manset_base  = mevzu2_archive_manset_base_query_args();
							$mevzu_manset_paged = max( 1, (int) get_query_var( 'paged' ) );
							$mevzu_manset_ppp   = (int) get_option( 'posts_per_page' );
							$mevzu_grid_q       = null;
							$mevzu_arch_cfg     = function_exists( 'mevzu2_archive_manset_slider_config' ) ? mevzu2_archive_manset_slider_config() : array( 'show' => true, 'count' => 15 );

							if ( $mevzu_manset_base ) {
								$mevzu_swiper_ids = array();
								if ( ! empty( $mevzu_arch_cfg['show'] ) ) {
									$mevzu_q_swiper = new WP_Query( array_merge( $mevzu_manset_base, array( 'posts_per_page' => (int) $mevzu_arch_cfg['count'] ) ) );
									$mevzu_swiper_ids = wp_list_pluck( $mevzu_q_swiper->posts, 'ID' );

									if ( 1 === $mevzu_manset_paged && $mevzu_q_swiper->have_posts() ) {
										$GLOBALS['mevzu_archive_manset_query'] = $mevzu_q_swiper;
										get_template_part( 'sablon/archive-kategori-manset-swiper' );
									}
								}

								$mevzu_grid_args = array_merge(
									$mevzu_manset_base,
									array(
										'posts_per_page' => $mevzu_manset_ppp,
										'paged'          => $mevzu_manset_paged,
									)
								);
								if ( ! empty( $mevzu_arch_cfg['show'] ) && ! empty( $mevzu_swiper_ids ) ) {
									$mevzu_grid_args['post__not_in'] = $mevzu_swiper_ids;
								}
								$mevzu_grid_q = new WP_Query( $mevzu_grid_args );

								while ( $mevzu_grid_q->have_posts() ) :
									$mevzu_grid_q->the_post();
									?>
								<div class="col-12 col-md-4">
									<div class="bg-white shadow-sm rounded-3 h-100">
										<a href="<?php the_permalink(); ?>" class="ripple" data-bs-ripple-color="light">
											<?php if ( get_post_thumbnail_id() ) : ?>
												<?php the_post_thumbnail( 'gorsel-thumbnail-col-3', array( 'title' => get_the_title(), 'loading' => 'lazy' ) ); ?>
											<?php else : ?>
												<img src="<?php echo esc_url( get_template_directory_uri() . '/img/404.webp' ); ?>" alt="<?php esc_attr_e( 'Görsel yok', 'mevzu2' ); ?>">
											<?php endif; ?>
											<div class="card-body p-2">
												<h3 class="card-title satir-2"><?php the_title(); ?></h3>
											</div>
										</a>
									</div>
								</div>
									<?php
								endwhile;
								wp_reset_postdata();
							} else {
								while ( have_posts() ) :
									the_post();
									?>
								<div class="col-12 col-md-4 mt-4">
									<div class="bg-white shadow-sm rounded-3 h-100">
										<a href="<?php the_permalink(); ?>" class="ripple" data-bs-ripple-color="light">
											<?php if ( get_post_thumbnail_id() ) : ?>
												<?php the_post_thumbnail( 'gorsel-thumbnail-col-3', array( 'title' => get_the_title(), 'loading' => 'lazy' ) ); ?>
											<?php else : ?>
												<img src="<?php bloginfo( 'template_url' ); ?>/img/404.webp" alt="Görsel yok">
											<?php endif; ?>
											<div class="card-body p-2">
												<h3 class="card-title satir-2"><?php the_title(); ?></h3>
											</div>
										</a>
									</div>
								</div>
									<?php
								endwhile;
							}
							?>
						</div>

						<?php
						if ( $mevzu_manset_base && $mevzu_grid_q instanceof WP_Query ) {
							echo bootstrap_pagination( $mevzu_grid_q );
						} else {
							global $wp_query;
							if ( isset( $archive_max_pages ) && $archive_max_pages > 0 ) {
								$wp_query->max_num_pages = $archive_max_pages;
							}
							if ( isset( $archive_found_posts ) && $archive_found_posts > 0 && ! isset( $wp_query->found_posts ) ) {
								$wp_query->found_posts = $archive_found_posts;
							}
							echo bootstrap_pagination();
						}
						?>

						<?php 
					else :
						get_template_part( 'template-parts/content', 'none' );
					endif;
					?>
				</div>
				<div class="col-12 col-lg-4">
					<div class="sticky-top h-100">
						<?php
						if (is_category('spor')) {
							$args = array(
								'before_widget' => '<section class="widget mt-3 mt-lg-4">',
								'after_widget'  => '</section>',
								'before_title'  => '<h2>',
								'after_title'   => '</h2>',
							);
							$instance = array(
								'title'       => 'Süper Lig', // Başlık
							);
							$widget = new widget_spor();
							echo $widget->widget($args, $instance);
						}
						if (is_category('ekonomi')) {
							$args = array(
								'before_widget' => '<section class="widget mt-3 mt-lg-4">',
								'after_widget'  => '</section>',
								'before_title'  => '<h2>',
								'after_title'   => '</h2>',
							);
							$instance = array(
								'adet'  => 0,
								'gizle' => 1
							);
							$widget = new widget_anlikkur();
							echo $widget->widget($args, $instance);
						}
						?>
						<?php if ( is_active_sidebar( 'sidebar-archive' ) ) : dynamic_sidebar('sidebar-archive'); else : echo icerik_yok(tur: 'sidebar', sidebar: 'sidebar-archive', title: 'Kenar Çubuğu: Arşiv', desc: 'Bu alanda henüz hiçbir bileşen(widget) yok.', icon: '<i class="ri-layout-right-line"></i>'); endif; ?>
					</div>
				</div>
			</div>
		<?php else : ?>
			
		<?php if ( have_posts() ) : ?>
			<?php 
			// Global $wp_query'yi pagination için koru (döngüden önce)
			global $wp_query;
			$archive_max_pages = $wp_query->max_num_pages;
			$archive_found_posts = isset($wp_query->found_posts) ? $wp_query->found_posts : 0;
			?>
			<div class="tema-widget bg-white shadow-sm rounded-3 mb-3 d-flex justify-content-between align-items-center p-3">
				<h2 class="mb-0 m-0"><?=$ad?></h2>
				<?php 
				if ( is_category() && function_exists('mevzu_render_category_follow_button') ) {
					mevzu_render_category_follow_button($current_category_id);
				} elseif ( is_tag() && function_exists('mevzu_render_tag_follow_button') ) {
					mevzu_render_tag_follow_button($current_category_id);
				}
				?>
			</div>
			<div class="row tema-widget tema-kategori g-4">
				<?php
				$mevzu_manset_base  = mevzu2_archive_manset_base_query_args();
				$mevzu_manset_paged = max( 1, (int) get_query_var( 'paged' ) );
				$mevzu_manset_ppp   = (int) get_option( 'posts_per_page' );
				$mevzu_grid_q       = null;
				$mevzu_arch_cfg     = function_exists( 'mevzu2_archive_manset_slider_config' ) ? mevzu2_archive_manset_slider_config() : array( 'show' => true, 'count' => 15 );

				if ( $mevzu_manset_base ) {
					$mevzu_swiper_ids = array();
					if ( ! empty( $mevzu_arch_cfg['show'] ) ) {
						$mevzu_q_swiper = new WP_Query( array_merge( $mevzu_manset_base, array( 'posts_per_page' => (int) $mevzu_arch_cfg['count'] ) ) );
						$mevzu_swiper_ids = wp_list_pluck( $mevzu_q_swiper->posts, 'ID' );

						if ( 1 === $mevzu_manset_paged && $mevzu_q_swiper->have_posts() ) {
							$GLOBALS['mevzu_archive_manset_query'] = $mevzu_q_swiper;
							get_template_part( 'sablon/archive-kategori-manset-swiper' );
						}
					}

					$mevzu_grid_args = array_merge(
						$mevzu_manset_base,
						array(
							'posts_per_page' => $mevzu_manset_ppp,
							'paged'          => $mevzu_manset_paged,
						)
					);
					if ( ! empty( $mevzu_arch_cfg['show'] ) && ! empty( $mevzu_swiper_ids ) ) {
						$mevzu_grid_args['post__not_in'] = $mevzu_swiper_ids;
					}
					$mevzu_grid_q = new WP_Query( $mevzu_grid_args );

					while ( $mevzu_grid_q->have_posts() ) :
						$mevzu_grid_q->the_post();
						?>
					<div class="col-12 col-md-3">
						<a href="<?php the_permalink(); ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
							<?php if ( get_post_thumbnail_id() ) : ?>
								<div class="img-hover position-relative">
									<?php the_post_thumbnail( 'gorsel-thumbnail-col-3', array( 'title' => get_the_title(), 'loading' => 'lazy' ) ); ?>
									<svg class="position-absolute top-50 start-50 translate-middle bg-primary text-white rounded-circle p-1 opacity-75" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 15 15"><path fill="currentColor" d="M4.79 2.093A.5.5 0 0 0 4 2.5v10a.5.5 0 0 0 .79.407l7-5a.5.5 0 0 0 0-.814l-7-5Z"></path></svg>
								</div>
							<?php else : ?>
								<?php echo m_default( null ); ?>
							<?php endif; ?>
							<h3 class="mt-2 satir-1"><?php the_title(); ?></h3>
							<?php if ( get_option( 'options_detaylar' ) ) { ?>
								<div class="row align-items-center small text-body text-dark">
									<?php
									foreach ( get_option( 'options_detaylar' ) as $detay ) {
										if ( $detay === 'tiklama' ) {
											?>
											<div class="col-auto">
												<?php echo get_post_view(); ?> görüntülenme
											</div>
											<?php
										}
										if ( $detay === 'yorum' ) {
											?>
											<div class="col text-end">
												<?php echo get_comments_number(); ?> yorum
											</div>
											<?php
										}
									}
									?>
								</div>
							<?php } ?>
						</a>
					</div>
						<?php
					endwhile;
					wp_reset_postdata();
				} else {
					while ( have_posts() ) :
						the_post();
						?>
					<div class="col-12 col-md-3">
						<a href="<?php the_permalink(); ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
							<?php if ( get_post_thumbnail_id() ) : ?>
								<div class="img-hover position-relative">
									<?php the_post_thumbnail( 'gorsel-thumbnail-col-3', array( 'title' => get_the_title(), 'loading' => 'lazy' ) ); ?>
									<svg class="position-absolute top-50 start-50 translate-middle bg-primary text-white rounded-circle p-1 opacity-75" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 15 15"><path fill="currentColor" d="M4.79 2.093A.5.5 0 0 0 4 2.5v10a.5.5 0 0 0 .79.407l7-5a.5.5 0 0 0 0-.814l-7-5Z"></path></svg>
								</div>
							<?php else : ?>
								<?php echo m_default( null ); ?>
							<?php endif; ?>
							<h3 class="mt-2 satir-1"><?php the_title(); ?></h3>
							<?php if ( get_option( 'options_detaylar' ) ) { ?>
								<div class="row align-items-center small text-body text-dark">
									<?php
									foreach ( get_option( 'options_detaylar' ) as $detay ) {
										if ( $detay === 'tiklama' ) {
											?>
											<div class="col-auto">
												<?php echo get_post_view(); ?> görüntülenme
											</div>
											<?php
										}
										if ( $detay === 'yorum' ) {
											?>
											<div class="col text-end">
												<?php echo get_comments_number(); ?> yorum
											</div>
											<?php
										}
									}
									?>
								</div>
							<?php } ?>
						</a>
					</div>
						<?php
					endwhile;
				}
				?>
			</div>
			<?php
			if ( ! empty( $mevzu_manset_base ) && $mevzu_grid_q instanceof WP_Query ) {
				echo bootstrap_pagination( $mevzu_grid_q );
			} else {
				global $wp_query;
				if ( isset( $archive_max_pages ) && $archive_max_pages > 0 ) {
					$wp_query->max_num_pages = $archive_max_pages;
				}
				if ( isset( $archive_found_posts ) && $archive_found_posts > 0 && ! isset( $wp_query->found_posts ) ) {
					$wp_query->found_posts = $archive_found_posts;
				}
				echo bootstrap_pagination();
			}
			?>
			<?php else :
				get_template_part( 'template-parts/content', 'none' );
			endif;
		endif; ?>

	</div>

<?php
get_footer();
