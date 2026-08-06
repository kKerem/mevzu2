<a href="<?php the_permalink(); ?>" class="py-3 border-bottom ripple text-link" data-bs-ripple-color="light"
    title="<?php the_title(); ?>">
    <div class="row w-100 mx-0 align-items-center">
        <div class="col-4 col-md-2 col-lg ps-0">
            <?php the_post_thumbnail('gorsel-thumbnail-widget', ['title' => get_the_title(), 'loading' => 'lazy']); ?>
        </div>
        <div class="col-8 col-md-10 col-lg-8 px-0">
            <h3 class="satir-2"><?php the_title(); ?></h3>
        </div>
    </div>
</a>