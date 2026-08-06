<div class="index-videolar my-4 p-3 tema-widget shadow-sm rounded">
    <div class="container px-0">
        <div class="row align-items-center mb-3">
            <div class="col">
                <h2 class="text-white m-0"><?php echo get_term((get_option('options_video_kategorisi') ? get_option('options_video_kategorisi') : 1), 'category')->name; ?></h2>
            </div>
            <div class="col-auto ms-md-auto">
                <a href="<?php echo esc_url(get_category_link((get_option('options_video_kategorisi') ? get_option('options_video_kategorisi') : 1))); ?>" class="bg-hepsinigoster">
                    Tümü<span class="d-none d-md-inline-block">nü Göster</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m13 8l4 4l-4 4M7 8l4 4l-4 4"/></svg>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-8">
                <?php
                $transient_key_first = 'first_video_post';
                $first_post_query = get_transient($transient_key_first);
                if (false === $first_post_query) {
                    $args = array(
                        'post_type' => 'post',
                        'posts_per_page' => 1,
                        'cat'  => get_option('options_video_kategorisi'),
                        'orderby' => 'date',
                        'order' => 'DESC'
                    );
                    $first_post_query = new WP_Query($args);
                    set_transient($transient_key_first, $first_post_query, 7 * HOUR_IN_SECONDS);
                }
                if ($first_post_query->have_posts()) {
                    while ($first_post_query->have_posts()) { 
                        $first_post_query->the_post(); ?>
                        <a href="<?php the_permalink() ?>" class="ripple d-block text-link">
                            <div class="position-relative">
                                <?php the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'class' => 'rounded', 'loading'=>'lazy']); ?>
                                <span class="play"><svg xmlns="http://www.w3.org/2000/svg" width="21" height="24" viewBox="0 0 448 512"><path fill="currentColor" d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6"/></svg></span>
                            </div>
                            <div class="text-secondary small fw-semibold mt-3"><?php echo get_the_date(); ?></div>
                            <h3 class="text-white satir-2 fz-16 mt-1"><?php the_title(); ?></h3>
                        </a>
                    <?php }
                    wp_reset_postdata();
                }
                ?>
            </div>
            <div class="col-12 col-md sag mt-3 mt-lg-0">
                <?php
                $transient_key_others = 'other_video_posts';
                $other_posts_query = get_transient($transient_key_others);
                if (false === $other_posts_query) {
                    $args = array(
                        'post_type' => 'post',
                        'posts_per_page' => 6,
                        'cat'  => get_option('options_video_kategorisi'),
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'offset' => 1
                    );
                    $other_posts_query = new WP_Query($args);
                    set_transient($transient_key_others, $other_posts_query, 15 * MINUTE_IN_SECONDS);
                }
                if ($other_posts_query->have_posts()) {
                    while ($other_posts_query->have_posts()) { 
                        $other_posts_query->the_post(); ?>
                        <a href="<?php the_permalink() ?>">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="position-relative">
                                        <?php the_post_thumbnail('gorsel-thumbnail-widget', ['title' => get_the_title(), 'class' => 'rounded', 'loading'=>'lazy']); ?>
                                        <span class="play"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="15" viewBox="0 0 448 512"><path fill="currentColor" d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6"/></svg></span>
                                    </div>
                                </div>
                                <div class="col"><h3 class="text-white satir-3"><?php the_title(); ?></h3></div>
                            </div>
                        </a>
                    <?php } wp_reset_postdata();
                } ?>
            </div>
        </div>
    </div>
</div>