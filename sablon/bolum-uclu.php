<?php
// 3 Bloklu Alan toggle kontrolü
if ( get_option( 'options_bolum_uclu_goster', '1' ) !== '1' ) return;

$kategori_ids = [];
for ( $i = 1; $i <= 3; $i++ ) {
    $kat = (int) get_option( 'options_bolum_uclu_kat_' . $i, 0 );
    if ( $kat > 0 ) $kategori_ids[] = $kat;
}

if ( empty( $kategori_ids ) ) return;

echo '<div class="row mt-3 mt-md-4">';
foreach ( $kategori_ids as $kategori_id ) :
    $transient_key = 'bolum_uclu_' . $kategori_id;
    $q = get_transient( $transient_key );

    if ( false === $q ) {
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'category__in'   => array( $kategori_id ),
            'meta_query'     => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        $q = new WP_Query( $args );
        set_transient( $transient_key, $q, 5 * MINUTE_IN_SECONDS );
    }
?>
<div class="col-12 col-md">
    <div class="tema-widget bg-white rounded shadow-sm px-3 h-100">
        <?php if ( $q->have_posts() ) : $count = 0; ?>
            <h2><?php echo esc_html( get_cat_name( $kategori_id ) ); ?></h2>
            <div class="d-flex flex-column border-bottom">
            <?php
            while ( $q->have_posts() ) : $q->the_post();
                if ( $count == 0 ) :
                    get_template_part( 'sablon/card-bolunmus-ilk' );
                else :
                    get_template_part( 'sablon/card-bolunmus' );
                endif;
                $count++;
            endwhile;
            wp_reset_postdata();
            ?>
            </div>
            <div class="text-center my-3">
                <a href="<?php echo get_category_link( $kategori_id ); ?>" class="btn btn-dark btn-sm d-inline-block py-2 px-4 rounded-4 view-all-link fw-semibold bg-body-secondary text-body small-2 border-0">Daha Fazla</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
endforeach;
echo '</div>';