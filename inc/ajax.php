<?php 
/* Infinite scroll - ajax */
namespace MyApp;
function asset_loader() {
   // Registers scripts.
   wp_register_script( 'app', get_template_directory_uri().'/js/script_ajax.js', [ 'jquery' ], _S_VERSION, true );



   wp_enqueue_script( 'app' );

   wp_localize_script( 'app', 'siteConfig', [
      'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
      'ajax_nonce' => wp_create_nonce( 'loadmore_post_nonce' ),
   ] );
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\asset_loader' );

/**
 * Loadmore functions
 *
 */
namespace MyApp;
use \WP_Query;

/**
 * Load more script call back
 *
 * @param bool $initial_request Initial Request( non-ajax request to load initial post ).
 *
 */
function ajax_script_post_load_more( bool $initial_request = false ) {

   if ( !$initial_request && ! check_ajax_referer( 'loadmore_post_nonce', 'ajax_nonce', false ) ) {
      wp_send_json_error( __( 'Invalid security token sent.', 'text-domain' ) );
      wp_die( '0', 400 );
   }

   $is_ajax_request = ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
                      strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) === 'xmlhttprequest';

   $page_no = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
   $page_no = ! empty( $_POST['page'] ) ? filter_var( $_POST['page'], FILTER_VALIDATE_INT ) + 1 : $page_no;

   $last_post_id = isset($_POST['last_post_id']) ? intval($_POST['last_post_id']) : 0;
   $current_post_id = isset($_POST['current_post_id']) ? intval($_POST['current_post_id']) : ( $initial_request ? get_the_ID() : 0 );

   $exclude_posts = array_filter([$last_post_id, $current_post_id]);

   // Default Argument.
   $args = [
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'posts_per_page' => 1,
      'paged'          => $page_no,
      'post__not_in'   => $exclude_posts, // Bu post ID'lerini dışla
   ];

   $query = new WP_Query( $args );

   if ( $query->have_posts() ):
      ?>
       
         <div class="row justify-content-between mt-lg-5">
            <div class="col-12">
               <div class="divider mb-lg-5"></div>
            </div>
			<?php
			$query->the_post();
			$haber_sablon = mevzu_get_single_haber_sablon( get_the_ID() );
			$has_sidebar  = mevzu_single_has_sidebar( $haber_sablon );
			$main_col     = $has_sidebar ? 'col-12 col-lg-8' : 'col-12';
			rewind_posts();
			?>
			<div class="row justify-content-center w-100">
			<div class="<?php echo esc_attr( $main_col ); ?>">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php mevzu_load_single_haber_template( mevzu_get_single_haber_sablon( get_the_ID() ) ); ?>
					<?php endwhile; ?>
				<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>

				<?php
				// Mevcut yazının kategorilerini al
				$current_post_id = get_the_ID();
				$current_categories = wp_get_post_categories($current_post_id);

				if (!empty($current_categories)) {
					// WP_Query için argümanlar
					$args = array(
						'post_type'      => 'post', // Yazı türü
						'posts_per_page' => 6, // Kaç yazı gösterilecek
						'post__not_in'   => array($current_post_id), // Şu anki yazıyı hariç tut
						'orderby'        => 'date', // Tarihe göre sırala
						'order'          => 'DESC', // En son yazılanlar önce gelsin
						'category__in'   => $current_categories, // Aynı kategoriden yazıları getir
					);

					$related_posts_query = new WP_Query($args);

					// Eğer ilişkili yazılar varsa göster
					if ($related_posts_query->have_posts()) {
						echo '<div class="tema-widget bg-white mt-3 mt-lg-4 p-3 pb-0 bg-sekil bg-sekil-red overflow-hidden rounded shadow-sm mx-2 mx-md-0">';
							echo '<h2 class="mb-0">Benzer Haberler</h2>';
							echo '<div class="row mt-3">'; // Grid veya flex düzeni için

							while ($related_posts_query->have_posts()) {
								$related_posts_query->the_post();
								?>
								<div class="col-12 col-md-4 mb-2 mb-lg-4">
									<?php get_template_part("sablon/card-1"); ?>
								</div>
								<?php
							}

							echo '</div>';
						echo '</div>';
					} else {
						echo '<p>Benzer haberler bulunamadı.</p>';
					}

					// Sorguyu sıfırla
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
		</div>

            <?php
               if ( ! $is_ajax_request ) :
                  $total_pages = $query->max_num_pages;
                  get_template_part( 'template-parts/common/pagination', null, [
                     'total_pages'  => $total_pages,
                     'current_page' => $page_no,
                  ] );
               endif;
               ?>
   <?php

   else:
      wp_die( '0' );
   endif;

   wp_reset_postdata();

   if ( $is_ajax_request && ! $initial_request ) {
      wp_die();
   }

}

/*
 * Load more script ajax hooks
 */
add_action( 'wp_ajax_nopriv_load_more', __NAMESPACE__ . '\\ajax_script_post_load_more' );
add_action( 'wp_ajax_load_more', __NAMESPACE__ . '\\ajax_script_post_load_more' );

/*
* Initial posts display.
*/
function post_script_load_more() {

    
   ?>

   <div id="load-more-content">
      <?php
      ajax_script_post_load_more( true );
      ?>
   </div>

   <button id="load-more" data-page="1" class="load-more-btn d-block mx-auto border-0 bg-transparent py-3 py-lg-5 text-primary">
      <div class="spinner-border" role="status">
         <span class="visually-hidden">Yükleniyor...</span>
      </div>
   </button>
   


   <?php
}

/**
 * Create a short code.
 *
 * Usage echo do_shortcode('[post_listings]');
 */
add_shortcode( 'post_listings', __NAMESPACE__ . '\\post_script_load_more' );
/* Infinite scroll - ajax */