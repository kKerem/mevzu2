<?php while ( have_posts() ) : the_post(); ?>

    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>

    <article id="post-<?php the_ID(); ?>" <?php post_class('tema-widget bg-white shadow-sm rounded-3 pt-3 mt-3'); ?>>
            
        <h1 class="m-0"><?php the_title(); ?></h1>

        <div class="entry-content p-3">
            <?php the_content();?>
        </div>

    </article>

<?php endwhile; ?>
   