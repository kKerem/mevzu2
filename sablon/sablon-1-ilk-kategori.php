SILLL
<div class="card card-kucuk ilk">
    <div class="row">
        <div class="col-12 col-lg-auto">
            <?php if(has_post_thumbnail()) : ?>
            <a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light">
                <?php the_post_thumbnail('gorsel-thumbnail-col-4', ['title' => get_the_title(), 'loading'=>'lazy', 'class' => 'w-100']); ?>
            </a>
            <?php else : get_template_part('sablon/sablon-noimage'); endif; ?>
        </div>
        <div class="col">
            <div class="card-body">
                <h3 class="card-title" style="position:unset"><a href="<?php the_permalink() ?>" data-bs-ripple-color="light"><?php the_title(); ?></a></h3>
                <p><?php echo mb_strimwidth(strip_tags(get_the_content()), 0, 200, '...'); ?></p>
                <?php if(get_the_tags()) : ?>
                    <div class="etiket">
                        <a href="<?php esc_url(get_tag_link(get_the_tags()[0]->term_id)); ?>" class="tag"><?php echo esc_html(get_the_tags()[0]->name); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
