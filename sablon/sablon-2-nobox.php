<div class="bg-white shadow-sm rounded-3 h-100">
    <a href="<?php the_permalink() ?>" class="ripple text-link d-block p-1" data-bs-ripple-color="light">
        <?php if(get_post_thumbnail_id()) : ?>
            <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
        <?php else: ?>
            <?php echo m_default(NULL); ?>
        <?php endif; ?>
        <h3 class="satir-2 m-0 p-2 pb-0"><?php the_title(); ?></h3>
    </a>
</div>