<?php get_header(); ?>

<div class="container">
    
    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>
    
    <?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 1 ? reklam('govde_ust_reklam') : NULL); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('page-yazarlar'); ?>>
        <div class="tema-widget bg-white shadow-sm rounded-3 my-3">
            <h1><?php the_title(); ?></h1>
        </div>
        <?php 
        $authors = get_users(array(
            'role' => 'author'
        ));
        if (!empty($authors)) : ?>
            <div class="row gy-3">
                <?php foreach ($authors as $author) :
                    $author_id = $author->ID;
                    $author_name = $author->display_name;
                    $author_link = get_author_posts_url($author->ID);
                    // $avatar_url = get_avatar($author->ID, 100); 
                    $author_avatar_url = mevzu_get_user_avatar_url($author_id);
                    $avatar_img = '<img class="rounded-circle" src="' . esc_url($author_avatar_url) . '" alt="' . esc_attr($author_name) . ' Avatarı" style="width:50px;height:50px">';

                    $name_parts = explode(' ', $author_name);
                    $last_name = array_pop($name_parts);
                    $first_names = implode(' ', $name_parts);


                    $gizle = get_user_meta($author_id, 'kullaniciyi_gizle', true);
                    if ($gizle == 1) {
                        continue;
                    }

                    // Yazarın toplam yazı sayısını al
                    $total_posts = count_user_posts($author->ID, 'post'); // 'post' post type'ındaki yazı sayısı

                    // Yazarın son yazısını çek
                    $args = array(
                        'author'        => $author->ID,
                        'post_type'     => 'post',
                        'posts_per_page'=> 1,
                        'orderby'       => 'date',
                        'order'         => 'DESC'
                    );

                    $latest_post = new WP_Query($args);
                    if($total_posts > 0) :
                    ?>
                    <div class="col-12 col-md-4">
                        <?php if ($latest_post->have_posts()) { 
                            while ($latest_post->have_posts()) { 
                                $latest_post->the_post(); ?>
                                <div class="tema-widget bg-white shadow-sm rounded-3 p-3">
                                    <div class="row align-items-center">
                                        <div class="col-auto pe-1">
                                            <a href="<?php echo $author_link; ?>" class="ripple d-block" data-bs-ripple-color="light">
                                                <div class="img-hover yazar-img">
                                                    <?php echo $avatar_img; ?>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <h5 class="fz-14 satir-1 m-0">
                                                <a href="<?php echo $author_link; ?>" class="ripple link-hover" data-bs-ripple-color="light">
                                                    <?php echo $first_names . ' <b>' . $last_name . '</b>'; ?>
                                                </a>
                                            </h5>
                                            <?php if ( function_exists('mevzu_render_author_follow_button') ) : ?>
                                                <div class="mt-1"><?php mevzu_render_author_follow_button($author_id); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto text-center fw-semibold">
                                            <a href="<?php echo get_author_posts_url($author_id); ?>" class="d-block text-link small">
                                                <?php echo $total_posts; ?>
                                                <div class="fw-normal small">Yazı</div>
                                            </a>
                                        </div>
                                    </div>
                                    <a href="<?php the_permalink() ?>" class="ripple text-link d-block mt-3 pt-3 border-top" data-bs-ripple-color="light">
                                        <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'class' => 'rounded shadow-sm', 'loading'=>'lazy']); ?>
                                    </a>
                                </div>
                            <?php }
                        } ?>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        <?php else : 
            echo "Henüz yazar bulunmamaktadır.";
        endif;
        ?>
        <div class="tema-widget bg-white shadow-sm rounded-3 my-3">
            <h1>Editörler</h1>
        </div>
        <?php
        $editors = get_users(array('role' => 'editor'));
        if (!empty($editors)) :
            ?>
            <div class="row gy-3">
                <?php
                foreach ($editors as $editor) :
                    $editor_id = $editor->ID;
                    $editor_name = $editor->display_name;
                    $editor_link = get_author_posts_url($editor->ID);
                    $total_posts = count_user_posts($editor->ID, 'post');
                    $name_parts = explode(' ', $editor_name);
                    $last_name = array_pop($name_parts);
                    $first_names = implode(' ', $name_parts);
                    $editor_avatar_url = mevzu_get_user_avatar_url($editor_id);
                    $editor_avatar_img = '<img class="rounded-circle" src="' . esc_url($editor_avatar_url) . '" alt="' . esc_attr($editor_name) . ' Avatarı" width="50" height="50">';
                    $gizle = get_user_meta($editor_id, 'kullaniciyi_gizle', true);
                    if ($gizle == 1) {continue;}
                    $args = array(
                        'author'        => $editor->ID,
                        'post_type'     => 'post',
                        'posts_per_page' => 1,
                        'orderby'       => 'date',
                        'order'         => 'DESC'
                    );
                    $latest_post = new WP_Query($args);
                    ?>
                    <div class="col-12">
                        <div class="card card-kucuk sablon-1 shadow-none">
                            <div class="row align-items-center justify-content-between gy-3">
                                <div class="col">
                                    <h5 class="yazar-baslik satir-2 fw-normal m-0" style="font-size: 16px;">
                                        <a href="<?php echo $editor_link; ?>" class="text-dark text-decoration-none"><?php echo $first_names . ' <b>' . $last_name . '</b>'; ?></a>
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <?php if ( function_exists('mevzu_render_author_follow_button') ) mevzu_render_author_follow_button($editor_id); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php else :
            echo "Henüz editor bulunmamaktadır.";
        endif;
        ?>
    </article>

</div>

<?php get_footer(); ?>
