<?php
/**
 * Haber detay — Sade şablon (sidebarsız)
 */
$detaylar = function_exists( 'mevzu_yz_get_post_detail_settings' )
    ? mevzu_yz_get_post_detail_settings( get_the_ID() )
    : (array) get_option( 'options_detaylar', array() );
$show_okunma = in_array( 'okunma', $detaylar, true );
$show_sure   = in_array( 'sure', $detaylar, true );

$the_native_video_id   = (int) get_post_meta( get_the_ID(), 'mevzu_native_video_id', true );
$the_native_video_html = $the_native_video_id > 0 ? mevzu_get_native_video_html( get_the_ID() ) : '';
$author_profile_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
$author_id          = get_the_author_meta( 'ID' );
$author_avatar_url  = mevzu_get_user_avatar_url( $author_id );
$author_name        = esc_html( get_the_author_meta( 'display_name' ) );
$show_tts           = function_exists( 'mevzu_tts_post_can_display' ) && mevzu_tts_post_can_display( get_the_ID() )
    && function_exists( 'mevzu_yz_detail_has_audio_player' ) && mevzu_yz_detail_has_audio_player( $detaylar );
?>
<div class="row justify-content-center">
<div class="col-12 col-lg-10">

<div class="row align-items-center mb-3 px-3 px-md-0">
    <div class="col">
        <div class="single-breadcrumb">
            <?php custom_breadcrumbs(); ?>
        </div>
    </div>
    <?php if ( $show_okunma || $show_sure ) : ?>
        <div class="col-auto small">
            <?php foreach ( $detaylar as $detay ) : ?>
                <?php if ( $detay === 'sure' ) : ?>
                <span class="text-primary fw-bolder ms-1">/</span>
                <span class="small" data-bs-toggle="tooltip" data-bs-title="Okuma Süresi">
                    <i class="ri-timer-line fs-6 opacity-50"></i>
                    <span class="text-dark"><?php echo okumaSuresi(); ?></span>
                </span>
                <?php endif; ?>
                <?php if ( $detay === 'okunma' ) : ?>
                <span class="small" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( get_post_view() ); ?> kez okundu">
                    <i class="ri-eye-line fs-6 text-muted"></i>
                    <span class="text-dark"><?php echo get_post_view(); ?></span>
                </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<article class="icerik sablon-sade<?php echo ' haber-' . (int) $post->ID; ?>" property="articleBody" data-title="<?php the_title_attribute(); ?>">

    <header class="sablon-sade-header px-3 px-md-0 mb-3">
        <h1 class="single-title mb-3 fs-1"><?php the_title(); ?></h1>

        <?php
        $visible_meta = array( 'yazar', 'tarih', 'like', 'yorum', 'paylas', 'bookmark', 'tts' );
        if ( array_intersect( $visible_meta, $detaylar ) ) :
            ?>
        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 small text-body border-bottom pb-3 mb-0">
            <?php if ( in_array( 'yazar', $detaylar ) ) : ?>
                <span class="d-inline-flex align-items-center gap-2">
                    <?php if ( $author_avatar_url ) : ?>
                        <a href="<?php echo esc_url( $author_profile_url ); ?>">
                            <img class="w-32 h-32 rounded-circle object-fit-cover" src="<?php echo esc_url( $author_avatar_url ); ?>" alt="<?php echo esc_attr( $author_name ); ?>">
                        </a>
                    <?php endif; ?>
                    <a class="text-dark fw-semibold" href="<?php echo esc_url( $author_profile_url ); ?>"><?php echo $author_name; ?></a>
                </span>
            <?php endif; ?>
            <?php if ( in_array( 'tarih', $detaylar ) ) : ?>
                <span class="text-body"><?php echo esc_html( get_the_date( 'd F, Y H:i' ) ); ?> tarihinde yayınlandı</span>
            <?php endif; ?>
            <span class="ms-md-auto d-inline-flex flex-wrap align-items-center gap-2">
                <?php if ( $show_tts ) : echo do_shortcode( '[kkerem_tts]' ); endif; ?>
                <?php if ( in_array( 'like', $detaylar ) && function_exists( 'mevzu_render_like_button' ) ) {
                    mevzu_render_like_button( get_the_ID(), 'post' );
                } ?>
                <?php if ( in_array( 'yorum', $detaylar ) && comments_open() ) : ?>
                    <a href="#comments" class="btn btn-outline-secondary btn-sm rounded-pill py-0 px-3 text-body">
                        <i class="ri-chat-3-line me-1"></i><?php echo (int) get_comments_number(); ?>
                    </a>
                <?php endif; ?>
                <?php if ( in_array( 'paylas', $detaylar ) ) : ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill py-0 px-3 text-body" data-bs-toggle="modal" data-bs-target="#paylas-sablon-sade">
                        <i class="ri-share-line me-1"></i><?php esc_html_e( 'Paylaş', 'mevzu2' ); ?>
                    </button>
                <?php endif; ?>
                <?php if ( in_array( 'bookmark', $detaylar ) && function_exists( 'mevzu_render_bookmark_button' ) ) {
                    mevzu_render_bookmark_button( get_the_ID() );
                } ?>
            </span>
        </div>
        <?php endif; ?>
    </header>

    <?php if ( $the_native_video_html ) : ?>
        <div class="sablon-sade-media mb-3"><?php echo $the_native_video_html; ?></div>
    <?php elseif ( has_post_thumbnail() ) : ?>
        <div class="sablon-sade-media mb-3">
            <?php the_post_thumbnail( 'gorsel-thumbnail-col-8', array( 'title' => get_the_title(), 'loading' => 'lazy', 'class' => 'w-100 rounded' ) ); ?>
        </div>
    <?php else :
        $ust_manset_id = (int) get_post_meta( get_the_ID(), 'ust_manset_gorseli_id', true );
        if ( $ust_manset_id ) : ?>
        <div class="sablon-sade-media mb-3">
            <?php echo wp_get_attachment_image( $ust_manset_id, 'gorsel-thumbnail-col-8', false, array( 'title' => get_the_title(), 'loading' => 'lazy', 'class' => 'w-100 rounded' ) ); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="sablon-sade-body px-3 px-md-0">
        <?php echo anasayfa_reklam( 'icerik_oncesi' ); ?>

        <div class="content single-content">
            <?php
            $content = get_the_content();
            $content = str_replace( '<!-- wp:post-featured-image {"className":"cikarilmis-gorsel"} /-->', '', $content );
            echo apply_filters( 'the_content', $content );
            ?>
        </div>

        <?php
        if ( function_exists( 'mevzu_render_embed_block' ) ) {
            mevzu_render_embed_block( get_the_ID() );
        }
        ?>

        <?php if ( ! in_category( 'kose-yazilari' ) ) {
            ilginizi_cekebilir( get_the_ID() );
        } ?>

        <?php if ( get_post_type() === 'reklam' ) : ?>
            <p class="text-center mt-3 fw-semibold">
                <span class="d-inline-block bg-success text-white py-2 px-4 rounded shadow-sm small-2">
                    <?php esc_html_e( 'Bu içerik bir reklamdır.', 'mevzu2' ); ?>
                </span>
            </p>
        <?php endif; ?>

        <?php if ( ! in_category( 'kose-yazilari' ) && get_option( 'options_bizi_takip_edin_bolumu' ) == 1 ) {
            echo do_shortcode( '[takipedin]' );
        } ?>

        <?php echo anasayfa_reklam( 'icerik_sonrasi' ); ?>
    </div>
</article>

</div>
</div>

<?php if ( in_array( 'paylas', $detaylar, true ) ) : ?>
<div class="modal fade" id="paylas-sablon-sade" tabindex="-1" aria-labelledby="paylasSablonSadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-lg-3">
            <div class="modal-header border-0">
                <h2 class="modal-title fw-bolder fs-5" id="paylasSablonSadeLabel"><?php esc_html_e( 'Bu Yazıyı Paylaş', 'mevzu2' ); ?></h2>
                <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Kapat', 'mevzu2' ); ?>"></button>
            </div>
            <div class="modal-body">
                <?php echo do_shortcode( '[social]' ); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
