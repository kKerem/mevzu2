<?php get_header(); ?>
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init();
</script>
<script src="https://use.typekit.net/bkt6ydm.js"></script>
<script>try{Typekit.load({ async: true });}catch(e){}</script>

	<div class="container">
    
		<?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 1 ? reklam('govde_ust_reklam') : NULL); ?>

    <div id="post-<?php the_ID(); ?>" <?php post_class('tema-widget bg-white shadow-sm rounded'); ?>>
      <h1><?php the_title(); ?></h1>
    </div>

      <?php 
        $args = array(
        'post_type'      => 'post',         // Post türünde sorgu yapacak
        'posts_per_page' => -1,             // Tüm postları alacak
        'post_status'    => 'publish',      // Yayınlanmış postları alacak
        'date_query'     => array(
          array(
            'after'     => '00:01:00',     // Başlangıç zamanı
            'before'    => '23:59:59',       // Bitiş zamanı
            'inclusive' => true,            // İki zaman aralığı da dahil edilecek
          ),
        ),
      );

      $query = new WP_Query($args);

      if ($query->have_posts()) : $count=0; ?>
      <div class="row justify-content-center my-3 mt-lg-4 mb-lg-5">
        <div class="col-12 col-lg-8">
          <ul class="timeline timeline-centered">
            <?php while ($query->have_posts()) : $query->the_post(); $count++; ?>
              <li class="timeline-item">
                  <div class="timeline-marker"><span></span></div>
                  <div class="timeline-info">
                      <span><?php echo get_the_date("H:i"); ?></span>
                  </div>
                  <?php if($count % 2 == 0) : ?>
                  <div class="timeline-content tema-widget" data-aos="fade-left" data-aos-duration="500">
                  <?php else: ?>
                  <div class="timeline-content sol-taraf tema-widget" data-aos="fade-right" data-aos-duration="500">
                  <?php endif; ?>
                    <div class="card card-kucuk">
                        <a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light">
                            <div class="img-hover position-relative">
                                <?php echo the_post_thumbnail('gorsel-366-238', ['class'=>'card-img-top', 'title' => get_the_title()]); ?>
                                <div class="position-absolute bottom-0">
                                    <div class="kategori" style="position: unset; padding-bottom: 0;">
                                        <span class="kategori-1 kategori-<?php echo get_the_category()[0]->term_id ?> <?php echo 'bg-' . get_term_meta(get_the_category()[0]->term_id, 'cat_renk', true); ?>"><?php echo get_the_category()[0]->name ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body bg-white">
                                <h3 class="card-title satir-2"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </div>
                  </div>
              </li>
            <?php endwhile;
          endif;

          // WordPress query sıfırlama
          wp_reset_postdata(); ?>
          </ul>
        </div>
      </div>

	</div>

<?php get_footer(); ?>