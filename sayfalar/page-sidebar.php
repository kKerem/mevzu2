<?php // Template Name: Sidebar ile ?>
<?php get_header(); ?>
<div class="container">
    <div class="row justify-content-between my-lg-4">
        <div class="col-12 col-lg-8">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php custom_breadcrumbs(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        
                    <h1 class="title-1 mx-0 mb-3"><?php the_title(); ?></h1>

                    <div class="entry-content">
                        <?php the_content();?>
                    </div>

                </article>

            <?php endwhile; ?>
        </div>
        <div class="col-12 col-lg-4">
            <div class="sticky-top">
                <?php dynamic_sidebar('sidebar-single'); ?>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
   