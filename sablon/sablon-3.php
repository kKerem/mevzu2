<a href="<?php the_permalink() ?>" class="text-link ripple d-block pb-3" data-bs-ripple-color="light" title="<?php the_title(); ?>">
    <div class="row w-100 mx-0 align-items-center">
        <?php if( ! get_query_var('is_user_feed') ) : ?>
            <div class="col-4 col-md-2 col-lg">
                <?php the_post_thumbnail('gorsel-thumbnail-widget', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
            </div>
            <div class="col-8 col-md-10 col-lg-7 ps-0">
            <?php
            $first_category = get_filtered_first_category();
            if ( $first_category ) {
                $cat_color = get_term_meta($first_category->term_id, 'cat_renk', true);
                echo '<div class="d-none d-md-inline-block badge badge-primary bg-' . esc_attr($cat_color) . ' bg-' . esc_attr($first_category->slug) . '">' . 
                    esc_html($first_category->name) . 
                '</div>';
            }
            ?>
        <?php else: ?>
            <div class="col-12">
        <?php endif; ?>
            <h3 class="title satir-2 mt-1 mb-0"><?php the_title(); ?></h3>
        </div>
    </div>
</a>