<div class="card card-kucuk">
    <div class="row">
        <div class="col-12 col-lg-5 pe-lg-0">
            <?php if(has_post_thumbnail()) : ?>
            <a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light">
                <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
            </a>
            <?php else : get_template_part('sablon/sablon-noimage'); endif; ?>
        </div>
        <div class="col">
            <div class="card-body">
                <h3 class="card-title"><a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light"><?php echo the_title() ?></a></h3>
                <?php if(get_the_tags()) : ?>
                    <div class="etiket satir-1">
                        <a href="<?php esc_url(get_tag_link(get_the_tags()[0]->term_id)); ?>" class="tag"><?php echo esc_html(get_the_tags()[0]->name); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
