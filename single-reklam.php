<?php
// Özel URL atanmışsa oraya yönlendir
if (have_posts()) {
    the_post();
    $reklam_ozel_url = get_post_meta(get_the_ID(), 'reklam_ozel_url', true);
    if ($reklam_ozel_url) {
        wp_redirect(esc_url($reklam_ozel_url), 301);
        exit;
    }
    rewind_posts(); // the_post() çağrıldığından döngüyü başa sar
}
?>
<?php get_header(); set_post_view(); ?>

	
	<div id="current-post" data-id="<?php echo get_the_ID(); ?>"></div>
		<div class="container">
			<?php Mevzu_Ads_Manager::render_swiper(); ?>
			<?php get_template_part("sablon/reklamlar"); ?>

            <?php the_post_thumbnail('full', ['title' => get_the_title(), 'loading'=>'lazy', 'class' => 'w-100 rounded-top shadow-sm']); ?>
			
			<div class="row justify-content-between">
				<div class="col-12 col-lg-8">
					<?php while ( have_posts() ) : the_post(); ?>
                        <div class="icerik<?php echo ' haber-'. $post->ID; ?>" property="articleBody">
                                                                
                            <div class="bg-white rounded-bottom shadow-sm<?php echo (!has_post_thumbnail()) ?? ' rounded-top'; ?>">

                                <div class="border-bottom mt-0 m-3 pb-3">
                                    
                                    <div class="row align-items-center single-yazar justify-content-between pt-3">
                                        <div class="col col-md-auto">
                                            <div class="single-breadcrumb">
                                                <?php custom_breadcrumbs(); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto col-md-auto ms-auto ms-lg-0 mt-3 mt-lg-0 d-none d-md-flex">
                                            <button type="button" class="btn btn-paylas" data-bs-toggle="modal" data-bs-target="#paylas">
                                                Paylaş
                                            </button>
                                            <div class="modal fade" id="paylas" tabindex="-1" aria-labelledby="paylasLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content p-lg-3">
                                                        <div class="modal-header border-0">
                                                            <h1 class="modal-title fw-bolder fs-5" id="paylasLabel">Bu Yazıyı Paylaş</h1>
                                                            <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php echo do_shortcode('[social]'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php /*
                                        <div class="col-auto pe-lg-0 mt-3 mt-lg-0<?php if ( !in_category( 'kose-yazilari' ) ) { echo ' '; } elseif ( !in_category( 'kose-yazilari') || get_the_date() !== get_the_modified_date() ) { echo ' d-none'; } ?>">
                                            <a href="https://news.google.com/publications/CAAqBwgKMMLiqAwwuMDtAg?ceid=TR:tr&oc=3" class="googlenews" target="_blank"> </a>
                                        </div> */ ?>
                                    </div>
                                        
                                    <div class="mt-3 mt-md-2 small single-bilgi">
                                        <div class="small text-body fw-normal">
                                            <?php echo get_the_date('d F, Y H:i'); ?> tarihinde yayınlandı
                                            <?php //if (get_the_date() !== get_the_modified_date()) { echo '<span class="px-1 d-none d-md-inline-block">/</span><span class="d-none d-md-inline-block">Güncelleme: ' . get_the_modified_date("d.m.Y H:i") . '</span></span>'; } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="px-2 px-md-3 pb-2 pb-md-3 px-3 px-md-0">
                                    <h1 class="single-title mt-3 mb-2 fz-24"><?php the_title() ?></h1>

                                    <div class="content single-content">
                                        <?php 
                                        $content = get_the_content();
                                        $content = str_replace('<!-- wp:post-featured-image {"className":"cikarilmis-gorsel"} /-->', '', $content);
                                        echo apply_filters('the_content', $content);
                                        ?>
                                    </div>
                                    
                                    <?php 
                                    if ( get_post_type() === 'reklam' ) {
                                        // Reklam post type için yapılacaklar
                                        echo '<p class="text-center mt-3 fw-semibold"><span class="d-inline-block bg-success text-white py-2 px-4 rounded shadow-sm small-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m18.44 13.791l-5.499-9.585a2.1 2.1 0 0 0-1.274-.98a2.14 2.14 0 0 0-1.607.205l-1.196.696a.2.2 0 0 0-.078.06l-.069.087a.6.6 0 0 0-.147.275a25.4 25.4 0 0 1-1.588 3.783a6.1 6.1 0 0 1-1.754 1.892l-1.794 1.588a2.14 2.14 0 0 0-.686 1.244a2.3 2.3 0 0 0 .059.98c-.246.136-.46.324-.627.55a2.1 2.1 0 0 0-.363.744a2.2 2.2 0 0 0 0 .833a2.14 2.14 0 0 0 .806 1.397c.22.168.473.29.743.358q.264.071.539.068q.146.015.294 0c.272-.033.533-.127.764-.274c.216.243.489.428.794.54c.237.093.49.14.745.136q.209.005.412-.049l1.597 2.853a2.17 2.17 0 0 0 1.843 1.058a2.14 2.14 0 0 0 2.039-1.578a2.2 2.2 0 0 0-.206-1.607l-1.196-2.078q.484-.108.98-.118c1.395.059 2.782.236 4.146.53h.343l.157-.07l.147-.088l1.049-.607c.482-.279.835-.737.98-1.274a2.14 2.14 0 0 0-.353-1.569M6.551 16.948a.65.65 0 0 1-.431 0a.6.6 0 0 1-.324-.275l-1.49-2.607l-.097-.167a.66.66 0 0 1 .137-.823l1.107-.98l.412.725l2.127 3.725zm10.674-1.96a.7.7 0 0 1-.304.401l-.549.314l-6.136-10.694l.559-.323a.6.6 0 0 1 .48-.059a.63.63 0 0 1 .392.294l5.499 9.576a.66.66 0 0 1 .059.549zm-.255-5.823a.72.72 0 0 1-.637-.362a.735.735 0 0 1 .265-.98l2.45-1.422a.725.725 0 0 1 .98.265a.735.735 0 0 1-.265.98l-2.45 1.421a.7.7 0 0 1-.343.098m4.518 3.47h-2.832a.735.735 0 1 1 0-1.47h2.832a.735.735 0 0 1 0 1.47m-6.831-6.969a.7.7 0 0 1-.363-.098a.726.726 0 0 1-.274-.98l1.411-2.47a.736.736 0 0 1 1.274.735l-1.411 2.46a.73.73 0 0 1-.637.353"/></svg>
                                        Bu içerik bir reklamdır.</span></p>';
                                    }
                                    ?>
                                    
                                </div>
                            </div>
                                



                        </div>
					<?php endwhile; ?>

					<?php
					if(get_option('options_haberlerde_etiket_gosterimi')==1) {
						if (get_the_tags()) {
							echo '<div class="row my-4 g-2">';
							foreach (get_the_tags() as $tag) {
								echo '<div class="col-auto d-flex align-items-center gap-2 border bg-light rounded-pill py-1"><a class="text-link small d-flex align-items-center pe-2 text-decoration-none fw-normal" href="' . get_tag_link($tag->term_id) . '">
								<svg class="opacity-50 me-1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M10.537 2.164a3 3 0 0 1 2.244.727l.15.14l7.822 7.823a3 3 0 0 1 .135 4.098l-.135.144l-5.657 5.657a3 3 0 0 1-4.098.135l-.144-.135L3.03 12.93a3 3 0 0 1-.878-2.188l.011-.205l.472-5.185a3 3 0 0 1 2.537-2.695l.179-.021zM8.024 8.025a2 2 0 1 0 2.829 2.829a2 2 0 0 0-2.829-2.829"/></g></svg>
								' . $tag->name . '</a>';
                                if ( function_exists('mevzu_render_tag_follow_button') ) {
                                    mevzu_render_tag_follow_button( $tag->term_id, false, false );
                                }
                                echo '</div>';
							}
							echo '</div>';
						}
					}
					?>
					
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
							echo '<div class="tema-widget bg-white shadow-sm rounded-3 mt-3 mt-lg-4 mx-2 mx-md-0">';
								echo '<h2 class="mb-0">'.(in_category('kose-yazilari') ? 'Diğer Yazılar' : 'Benzer Haberler').'</h2>';
								echo '<div class="p-3">';
									echo '<div class="row g-3 g-lg-4">'; // Grid veya flex düzeni için

									while ($related_posts_query->have_posts()) {
										$related_posts_query->the_post();
										?>
										<div class="col-12 col-md-4">
											<?php if (in_category('kose-yazilari')) get_template_part("sablon/card-2"); else get_template_part("sablon/card-1"); ?>
										</div>
										<?php
									}

									echo '</div>';
								echo '</div>';
							echo '</div>';
						} else {
							echo '<p>Benzer '.(in_category('kose-yazilari') ? 'yazı' : 'haber').' bulunamadı.</p>';
						}

						// Sorguyu sıfırla
						wp_reset_postdata();
					}
					?>
		
				</div>
				<div class="col-12 col-lg-4 mt-4">
					<div class="sticky-top">
						<?php dynamic_sidebar('sidebar-single'); ?>
					</div>
				</div>
			</div>


		
		</div>
	</div><!-- #main -->

	


<?php get_footer(); ?>
