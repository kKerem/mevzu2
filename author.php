<?php
/**
 * The template for displaying author archive pages
 *
 * @package Mevzu
 */

get_header();
?>

<div class="container">
    <?php get_template_part("sablon/reklamlar"); ?>

    <?php ramazan(); ?>

	<?php
	// Yazarın ID'sini doğru şekilde alalım
	$queried_object = get_queried_object();
	$author_id = $queried_object->ID;

	// print_r(get_user_meta($author_id));
	

	$name_parts = explode(' ', get_the_author_meta('display_name', $author_id));
	$last_name = array_pop($name_parts);
	$first_names = implode(' ', $name_parts);

	// Yazarın rollerini alalım
	$author_roles = get_the_author_meta('roles', $author_id);
	$author_role = !empty($author_roles) ? $author_roles[0] : '';
    switch ($author_role) {
        case 'author':
            $author_role_tr = 'Yazar';
            break;
        case 'editor':
            $author_role_tr = 'Editör';
            break;
        
        default:
            $author_role = 'Kullanıcı';
            break;
    }

	// Eğer yazarın rolü "author" ise 'yazilar' post tipini kullan, değilse 'post'
	$post_type = ($author_role === 'author') ? 'post' : 'post';
	?>

    <div class="row justify-content-between">
        <div class="col-12 col-lg-8">

        <div class="tema-widget bg-white shadow-sm rounded">
            <div class="p-3 border-bottom mb-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <?php
                            $author_avatar_url = mevzu_get_user_avatar_url($author_id);
                            echo '<img class="rounded-circle m-0 shadow-sm" src="' . esc_url($author_avatar_url) . '" alt="' . esc_attr(get_the_author_meta('display_name', $author_id)) . ' Avatarı" width="79" height="79">';
                        ?>
                    </div>
                    <div class="col">
                        <h3 class="fz-20 fw-normal my-1"><?php echo $first_names . ' <b>' . $last_name . '</b>'; ?></h3>
                        <?php if ( function_exists('mevzu_render_author_follow_button') ) : ?>
                            <div class="mt-1"><?php mevzu_render_author_follow_button($author_id); ?></div>
                        <?php endif; ?>
                        <?php if ( function_exists('mevzu_render_user_social_links') ) : ?>
                            <?php mevzu_render_user_social_links($author_id); ?>
                        <?php endif; ?>
                        <?=(get_the_author_meta('user_description', $author_id) ? '<i class="text-body"><span class="text-dark">❝</span> '.get_the_author_meta('user_description', $author_id).' <span class="text-dark">❞</span></i>' : '')?>
                    </div>
                    <div class="col-auto text-center">
                        <?php
                        $yazi_sayisi = count(get_posts([
                            'author'        => $author_id,
                            'post_type'     => 'post',
                            'posts_per_page'=> -1,
                            'tax_query'     => [
                                [
                                    'taxonomy' => 'category',
                                    'field'    => 'slug',
                                    'terms'    => 'kose-yazilari',
                                ],
                            ],
                        ]));
                        $toplam_gonderi = count_user_posts($author_id, 'post');
                        $haber_sayisi = $toplam_gonderi - $yazi_sayisi;
                        ?>
                        <div class="d-flex align-items-center gap-3">
                        <?php if ($haber_sayisi > 0) : ?>
                            <div>
                                <?='<b>'.$haber_sayisi.'</b>'?><div class="small">Haber</div>
                            </div>
                        <?php endif; ?>
                        <?php if ($yazi_sayisi > 0) : ?>
                            <div>
                                <?='<b>'.$yazi_sayisi.'</b>'?><div class="small">Yazı</div>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            // Post sorgusu, yazarın rolüne göre doğru post type kullanarak
            $args = array(
                'author' => $author_id,
                'post_type' => $post_type,
                'paged' => get_query_var('paged', 1),
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) :
            ?>

                <h1>Son Yazılar</h1>

                <div class="widget-sondakika">
                    <?php while (have_posts()) : the_post(); ?>
                        <li class="mb-3 mb-lg-4">
                            <span class="post-date d-block text-body"><?php echo get_the_date('j F, Y');?></span>
                            <a href="<?php the_permalink() ?>" class="ripple text-link py-1" data-bs-ripple-color="light" title="<?php the_title(); ?>">
                                <div class="row w-100 align-items-center">
                                    <div class="col-4 col-md-2 col-lg">
                                        <div class="img-hover">
                                        <?php the_post_thumbnail('gorsel-thumbnail-col-4', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                        </div>
                                    </div>
                                    <div class="col-8 col-md-10 col-lg-7">
                                        <h3 class="fz-18 mb-2 satir-3"><?php the_title(); ?></h3>
                                        <div class="text-body satir-4 fw-normal">
                                            <?php echo mb_substr(strip_tags(get_the_content()), 0, 350) . '...'; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                    </li>
                    <?php endwhile; ?>
                </div>

            </div>

            <?php echo bootstrap_pagination($query); ?>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>

            <p>Yazarın bu post tipinde yazısı bulunmamaktadır.</p>

        <?php endif; ?>
        </div>

        <div class="col-12 col-lg-4">
            <div class="sticky-top">
                <?php dynamic_sidebar('sidebar-koseyazilari'); ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
