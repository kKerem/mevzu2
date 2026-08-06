<div class="row mt-3 mt-md-4">
    <?php
    $ikili_kategoriler = get_query_var('ikili_blok');
    
    if ($ikili_kategoriler && is_array($ikili_kategoriler)) :
        foreach ($ikili_kategoriler as $kategori_id) :
            if (!$kategori_id) continue;

            $transient_key = 'category_posts_query_' . $kategori_id;
            // delete_transient($transient_key);
            $q = get_transient($transient_key);

            if (false === $q) {
                $args = array(
                    'post_type'      => 'post',
                    'posts_per_page' => 6, // Her kategori için gösterilecek maksimum yazı sayısı
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'category__in'   => array($kategori_id),
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => '_thumbnail_id',
                            'compare' => 'EXISTS', // Sadece öne çıkarılmış görseli olan yazıları al
                        ),
                    ),
                );
                $q = new WP_Query($args);
                set_transient($transient_key, $q, 5 * MINUTE_IN_SECONDS); // 5 dakikalık önbellek
            }
    ?>
    <div class="col-12 col-md">
        <div class="gununhaberleri">
            <?php if ($q->have_posts()) : $count = 0; $posts = []; ?>
            <div class="tema-widget bg-white rounded shadow-sm px-3 h-100">
                <h2>
                    <?php echo get_cat_name($kategori_id); // Kategori adını yazdır ?>
                </h2>
                <div class="d-flex flex-column border-bottom">
                    <?php
                    // İlk döngüde geçerli postları topla (18-22KB dışındaki)
                    while ($q->have_posts()) : $q->the_post();
                        $post_id = get_the_ID();
                        $image_size_kb = get_thumbnail_size_in_kb2($post_id);

                        // Görsel boyutu 18-22KB arasında ise geç
                        if ($image_size_kb !== false && $image_size_kb >= 18 && $image_size_kb <= 22) {
                            continue;
                        }
                        $posts[] = get_the_ID();
                    endwhile;
                    wp_reset_postdata();

                    if (count($posts) < 6) {
                        $remaining_posts = 6 - count($posts);
                        $args['posts_per_page'] = $remaining_posts;
                        $additional_posts = new WP_Query($args);
                        while ($additional_posts->have_posts()) : $additional_posts->the_post();
                            $post_id = get_the_ID();
                            $image_size_kb = get_thumbnail_size_in_kb2($post_id);
                            // Görsel boyutu 18-22KB arasında ise geç
                            if ($image_size_kb !== false && $image_size_kb >= 18 && $image_size_kb <= 22) {
                                continue;
                            }
                            $posts[] = get_the_ID();
                        endwhile;
                        wp_reset_postdata();
                    }

                    $count = 0;
                    foreach ($posts as $post_id) :
                        // Her bir postu yazdır
                        $post = get_post($post_id);
                        setup_postdata($post);
                        if ($count == 0) :
                            get_template_part('sablon/card-bolunmus-ilk');
                        else :
                            get_template_part('sablon/card-bolunmus');
                        endif;
                        $count++;
                    endforeach;
                    ?>
                </div>
                <div class="text-center my-3">
                    <a href="<?php echo get_category_link($kategori_id); ?>" class="btn btn-dark btn-sm d-inline-block py-2 px-4 rounded-4 view-all-link fw-semibold bg-body-secondary text-body small-2 border-0">Daha Fazla</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
        endforeach;
    endif;
    ?>
</div>