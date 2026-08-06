<a href="<?php the_permalink() ?>" class="ripple text-link d-block" data-bs-ripple-color="light">
    <?php if(get_post_thumbnail_id()) : ?>
        <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
    <?php else: ?>
        <?php echo m_default(NULL); ?>
    <?php endif; ?>
    <?php
        $first_category = get_filtered_first_category();
        if ( $first_category ) {
            $cat_color = get_term_meta($first_category->term_id, 'cat_renk', true);
            echo '<div class="text-muted small-2 fw-normal mt-3">' . 
                esc_html($first_category->name) . 
            '</div>';
        }
    ?>
    <h3 class="satir-2"><?php the_title(); ?></h3>
</a>