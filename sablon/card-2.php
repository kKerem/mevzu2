<div class="card card-kucuk sablon-1 tema-widget bg-white rounded">
    <a href="<?php the_permalink() ?>" class="ripple text-link" data-bs-ripple-color="light">

        <div class="row align-items-center align-items-center mb-2">
            <?php
            // Yazar bilgilerini al
            $author_id = get_the_author_meta('ID'); // Yazar ID'si
            $author_name = get_the_author_meta('display_name'); // Yazar adı
            $author_avatar_url = mevzu_get_user_avatar_url($author_id); 
            ?>
            <!-- Yazar Avatarı -->
            <div class="col-auto pe-0">
                <img class="avatar rounded-circle w-40 h-40" src="<?php echo esc_url($author_avatar_url); ?>" alt="<?php echo esc_attr($author_name); ?>" loading="lazy" />
            </div>
            <!-- Yazar Adı -->
            <div class="col small fw-semibold text-dark">
                <?php echo esc_html($author_name); ?>
                <div class="text-body small fw-normal"><span class="small"><?php echo (time() - strtotime(get_the_date('Y-m-d H:i:s')) > 30*24*60*60) ? get_the_date('j F, Y') : time_ago(get_the_date('Y-m-d H:i:s')); ?></span></div>
            </div>
        </div>
        <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
        <div class="card-body p-0">
            <h3 class="card-title satir-1 fw-semibold my-2"><?php the_title(); ?></h3>
            <div class="text-body small satir-4">
                <?php echo mb_substr(strip_tags(get_the_content()), 0, 150) . '...'; ?>
            </div>
        </div>
    </a>
</div>
