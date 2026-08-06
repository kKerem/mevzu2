<?php
/**
 * Firma Rehberi — Archive / Taxonomy şablonu
 */
get_header();
set_post_view();

$per_page        = Firma_Admin::get( 'per_page', 12 );
$default_view    = Firma_Admin::get( 'default_view', 'kart' );
$paged           = max( 1, get_query_var('paged') );
$basvuru_page_id = Firma_Admin::get('basvuru_sayfasi');
$basvuru_url     = $basvuru_page_id ? get_permalink( $basvuru_page_id ) : '';
$login_required  = Firma_Admin::get('login_required', false);
$show_btn        = $basvuru_url && ( ! $login_required || is_user_logged_in() );

$kat   = sanitize_text_field( $_GET['firma_kat']   ?? '' );
$sehir = sanitize_text_field( $_GET['firma_sehir'] ?? '' );
$arama = sanitize_text_field( $_GET['firma_arama'] ?? '' );

$tax_query = [];
if ( is_tax( 'firma-kategori' ) ) {
    $tax_query[] = [ 'taxonomy' => 'firma-kategori', 'field' => 'term_id', 'terms' => get_queried_object_id() ];
} elseif ( $kat ) {
    $tax_query[] = [ 'taxonomy' => 'firma-kategori', 'field' => 'slug', 'terms' => $kat, 'include_children' => true ];
}
if ( is_tax( 'firma-sehir' ) ) {
    $tax_query[] = [ 'taxonomy' => 'firma-sehir', 'field' => 'term_id', 'terms' => get_queried_object_id() ];
} elseif ( $sehir ) {
    $tax_query[] = [ 'taxonomy' => 'firma-sehir', 'field' => 'slug', 'terms' => $sehir ];
}
if ( count( $tax_query ) > 1 ) $tax_query['relation'] = 'AND';

$query_args = [
    'post_type'      => 'firma',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ],
    'meta_key'       => '_firma_featured',
    'tax_query'      => $tax_query,
];
if ( $arama ) {
    $query_args['s'] = $arama;
    $query_args['post_type'] = 'firma';
}
$query = new WP_Query( $query_args );
?>

<div class="container">
    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>

    <?php echo anasayfa_reklam('govde_ust_reklam'); ?>

    <div class="tema-widget">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h1 class="fs-4 fw-bold mb-0">
                <?php echo is_tax() ? single_term_title( '', false ) : 'Firma Rehberi'; ?>
            </h1>
            <?php if ( is_tax() && term_description() ) : ?>
                <p class="text-muted small mb-0 mt-1"><?php echo term_description(); ?></p>
            <?php endif; ?>
            <?php if ( $show_btn ) : ?>
                <a href="<?php echo esc_url( $basvuru_url ); ?>" class="btn btn-primary btn-sm py-1 ps-2 pe-3 d-flex align-items-center fw-semibold">
                    <i class="ri-add-circle-line me-2 fz-16"></i>Firmamı Ekle
                </a>
            <?php endif; ?>
        </div>
    </div>

        <?php include FIRMA_REHBERI_PATH . 'templates/parts/firma-filter.php'; ?>

        <?php if ( $query->have_posts() ) : ?>
            <div class="firma-listesi firma-gorunum-<?php echo esc_attr( $default_view ); ?>" id="firma-listesi">
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <?php include FIRMA_REHBERI_PATH . 'templates/parts/firma-card.php'; ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="text-center mt-4">
                    <button id="firma-load-more" class="btn btn-outline-primary"
                        data-page="2" data-max="<?php echo $query->max_num_pages; ?>">
                        Daha Fazla Göster
                    </button>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="text-center py-5 text-muted">
                <i class="ri-store-2-line d-block mb-2" style="font-size:3rem"></i>
                <p>Bu kriterlere uygun firma bulunamadı.</p>
            </div>
        <?php endif; ?>

    <?php if ( $show_btn ) : ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 bg-white rounded shadow-sm p-3 mt-3 mb-4">
            <div>
                <span class="fs-6 fw-semibold mb-2">Firmanız burada yer alsın!</span>
                <p class="text-muted small mb-0">Ücretsiz başvurun, müşterileriniz sizi kolayca bulsun.</p>
            </div>
            <a href="<?php echo esc_url( $basvuru_url ); ?>" class="btn btn-primary btn-sm py-1 ps-2 pe-3 d-flex align-items-center fw-semibold">
                <i class="ri-add-circle-line me-2 fz-16"></i>Firmamı Ekle
            </a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
