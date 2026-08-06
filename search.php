<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package mevzu2
 */

get_header();
?>

	<div class="container">
		<?php get_template_part("sablon/reklamlar"); ?>

		<?php ramazan(); ?>
				
		<div class="single-breadcrumb">
			<?php custom_breadcrumbs(); ?>
		</div>

		<?php if ( have_posts() ) : ?>
			<?php 
			// Global $wp_query'yi pagination için koru (döngüden önce)
			global $wp_query;
			$search_max_pages = $wp_query->max_num_pages;
			$search_found_posts = isset($wp_query->found_posts) ? $wp_query->found_posts : 0;
			?>
			<div class="row justify-content-between my-3">
				<div class="col-12 col-lg-8">
					<div class="tema-widget bg-white rounded shadow-sm">
						<h1 class="text-capitalize mb-0"><?php printf( esc_html__( '%s', 'mevzu2' ), '<span>' . get_search_query() . '</span>' ); ?></h1>

						<?php
						/* Start the Loop */
						while ( have_posts() ) : the_post();?>
							<a href="<?php the_permalink() ?>" class="manset-haber ripple border-bottom p-3" data-bs-ripple-color="light" title="<?php the_title(); ?>">
								<div class="row align-items-center">
									<div class="col-12 col-md-2 col-lg">
										<div class="img-hover w-100">
											<?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
										</div>
									</div>
									<div class="col-12 col-md-10 col-lg-7 mt-2 mt-md-0">
										<?php
										// Kategoriyi al
										$categories = get_the_category();
										if ( ! empty( $categories ) ) {
											$first_category = $categories[0];
											echo '<div class="me-2 me-md-0 badge badge-primary bg-'. esc_attr( $first_category->slug ).'">' . esc_html( $first_category->name ) . '</div>';
										}
										?>
										<span class="text-body fw-normal ms-md-2 fz-12"><?php echo time_ago(get_the_date('Y-m-d H:i:s'));?></span>
										<h3 class="card-title my-2 satir-3 fs-5"><?php the_title(); ?></h3>
										<div class="text-body small satir-3">
											<?php echo mb_substr(strip_tags(get_the_content()), 0, 200) . '...'; ?>
										</div>
									</div>
								</div>
							</a>
						<?php endwhile; ?> 
					</div>
					<div class="mt-3">
						<?php 
						// Global $wp_query'yi pagination için geri yükle
						global $wp_query;
						if ( isset($search_max_pages) && $search_max_pages > 0 ) {
							$wp_query->max_num_pages = $search_max_pages;
						}
						if ( isset($search_found_posts) && $search_found_posts > 0 && !isset($wp_query->found_posts) ) {
							$wp_query->found_posts = $search_found_posts;
						}
						echo bootstrap_pagination(); 
						?>
					</div>
				</div>
				<div class="col-12 col-lg-4">
					<div class="sticky-top">
						<?php dynamic_sidebar('sidebar-single'); ?>
					</div>
				</div>
			</div>
		<?php else :
			get_template_part( 'template-parts/content', 'none' );
		endif;
		?>
	</div>

<?php
get_footer();
