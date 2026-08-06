<div class="card card-kucuk sablon-1 tema-widget shadow-none">
    <a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light">
        <div class="img-hover">
            <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
        </div>
        <div class="card-body p-0">
            <h3 class="card-title satir-2 fw-semibold my-2"><?php the_title(); ?></h3>
            <?php
            $first_category = get_filtered_first_category();
            if ( $first_category ) {
                $cat_color = get_term_meta($first_category->term_id, 'cat_renk', true);
                echo '<div class="badge badge-primary bg-' . esc_attr($cat_color) . ' bg-' . esc_attr($first_category->slug) . '">' . 
                    esc_html($first_category->name) . 
                '</div>';
            }
            ?>
        </div>
    </a>
</div>