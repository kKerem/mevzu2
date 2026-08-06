<div class="col-12 col-md-12 mb-3 mb-lg-4">
    <a href="<?php the_permalink() ?>" class="manset-haber py-1 ripple" data-bs-ripple-color="light" title="<?php the_title(); ?>">
        <div class="row align-items-center">
            <div class="col-12 col-md-4 mb-3 mb-md-0">
                <div class="img-hover">
                <?php the_post_thumbnail('gorsel-thumbnail-col-4', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="d-inline-flex align-items-center bg-body-secondary text-secondary-emphasis rounded-3 px-2 py-1 mb-2">
                    <i class="ri-fire-line me-1 text-danger"></i>
                    <span class="small-2 fw-semibold">
                        En çok okunanlarda 1. sırada!
                    </span>
                </div>
                <br>
                <?php
                $first_category = get_filtered_first_category();
                if ( $first_category ) {
                    $cat_color = get_term_meta($first_category->term_id, 'cat_renk', true);
                    echo '<div class="badge badge-primary bg-' . esc_attr($cat_color) . ' bg-' . esc_attr($first_category->slug) . '">' . 
                        esc_html($first_category->name) . 
                    '</div>';
                }
                ?>
                <span class="text-dark small-2 fw-normal ms-md-2 d-block d-md-inline-block mt-2 mt-md-0"><?php echo time_ago(get_the_date('Y-m-d H:i:s'));?></span>
                <h3 class="card-title my-2 satir-3 fs-5"><?php the_title(); ?></h3>
                <div class="text-body small satir-4">
                    <?php echo mb_substr(strip_tags(get_the_content()), 0, 270) . '...'; ?>
                </div>
            </div>
        </div>
    </a>
</div>