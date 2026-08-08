<?php
/**
 * Köşe yazısı şablonu (sidebarsız)
 */
$author_profile_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
$author_id          = get_the_author_meta( 'ID' );
$author_avatar_url  = mevzu_get_user_avatar_url( $author_id );
$display_name       = get_the_author_meta( 'display_name' );
$first_name         = get_the_author_meta( 'first_name' );
$last_name          = get_the_author_meta( 'last_name' );
$author_bio         = get_the_author_meta( 'description' );

$detaylar = function_exists( 'mevzu_yz_get_post_detail_settings' )
    ? mevzu_yz_get_post_detail_settings( get_the_ID() )
    : (array) get_option( 'options_detaylar_koseyazisi', array() );

$show_tts = function_exists( 'mevzu_tts_post_can_display' ) && mevzu_tts_post_can_display( get_the_ID() )
    && function_exists( 'mevzu_yz_detail_has_audio_player' ) && mevzu_yz_detail_has_audio_player( $detaylar );

$show_okunma = in_array( 'okunma', $detaylar, true );
$show_sure   = in_array( 'sure', $detaylar, true );
$show_tarih  = in_array( 'tarih', $detaylar, true );

$action_meta     = array( 'like', 'yorum', 'paylas', 'bookmark', 'tts' );
$show_actions    = $show_tts || array_intersect( $action_meta, $detaylar );
$show_meta_strip = $show_tarih || $show_okunma || $show_sure || $show_actions;
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <div class="mb-3 mb-lg-4 bg-white rounded-3 overflow-hidden shadow-sm">
            <div class="icerik kose-yazi-icerik<?php echo ' haber-' . (int) $post->ID; ?>" property="articleBody" data-title="<?php the_title_attribute(); ?>">

                <div class="bg-light p-3 p-md-4 mb-3 border-bottom border-2">
                    <div class="d-flex gap-3 gap-md-4">
                        <a href="<?php echo esc_url( $author_profile_url ); ?>" class="kose-yazar-hero__avatar-link flex-shrink-0">
                            <?php if ( $author_avatar_url ) : ?>
                                <img class="kose-yazar-hero__avatar rounded-3 w-64" src="<?php echo esc_url( $author_avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
                            <?php else : ?>
                                <span class="kose-yazar-hero__avatar kose-yazar-hero__avatar--placeholder rounded-circle d-flex align-items-center justify-content-center">
                                    <?php echo m_default( 'avatar' ); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="kose-yazar-hero__body min-w-0">
                            <h2 class="kose-yazar-hero__name m-0 h5">
                                <a class="text-dark text-decoration-none me-2" href="<?php echo esc_url( $author_profile_url ); ?>">
                                    <?php if ( $first_name || $last_name ) : ?>
                                        <?php echo esc_html( $first_name ); ?>
                                        <?php if ( $last_name ) : ?><strong><?php echo esc_html( $last_name ); ?></strong><?php endif; ?>
                                    <?php else : ?>
                                        <?php echo esc_html( $display_name ); ?>
                                    <?php endif; ?>
                                </a>
                                <?php if ( function_exists( 'mevzu_render_author_follow_button' ) ) : ?>
                                    <?php mevzu_render_author_follow_button( $author_id ); ?>
                                <?php endif; ?>
                            </h2>
                            <?php if ( $author_bio ) : ?>
                                <p class="text-body-secondary mb-0 mt-2 satir-2"><?php echo esc_html( $author_bio ); ?></p>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <?php if ( function_exists( 'mevzu_render_user_social_links' ) ) : ?>
                                    <?php mevzu_render_user_social_links( $author_id, black:1 ); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ( $show_meta_strip ) : ?>
                <div class="kose-yazi-meta px-3 px-md-4">
                    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 small text-body">
                            <?php if ( $show_tarih ) : ?>
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="d-inline-flex align-items-center gap-1">
                                    <i class="ri-calendar-line"></i>
                                    <?php echo esc_html( get_the_date( 'd F Y, H:i' ) ); ?>
                                </time>
                            <?php endif; ?>
                            <?php if ( $show_sure ) : ?>
                                <span class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" data-bs-title="Okuma Süresi">
                                    <i class="ri-timer-line"></i>
                                    <?php echo okumaSuresi(); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $show_okunma ) : ?>
                                <span class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( get_post_view() ); ?> kez okundu">
                                    <i class="ri-eye-line"></i>
                                    <?php echo get_post_view(); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $show_actions ) : ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                            <?php if ( $show_tts ) {
                                echo do_shortcode( '[kkerem_tts]' );
                            } ?>
                            <?php if ( in_array( 'like', $detaylar, true ) && function_exists( 'mevzu_render_like_button' ) ) {
                                mevzu_render_like_button( get_the_ID(), 'post' );
                            } ?>
                            <?php if ( in_array( 'yorum', $detaylar, true ) && comments_open() ) : ?>
                                <a href="#comments" class="btn btn-outline-secondary btn-sm rounded-pill py-0 px-3 text-body">
                                    <i class="ri-chat-3-line me-1"></i><?php echo (int) get_comments_number(); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( in_array( 'paylas', $detaylar, true ) ) : ?>
                                <button type="button" class="ripple btn btn-outline-secondary btn-sm fw-bolder rounded-pill py-1 px-3 d-inline-flex align-items-center gap-2 text-body" data-bs-toggle="modal" data-bs-target="#paylas-kose">
                                    <i class="ri-share-line fz-16"></i><?php esc_html_e( 'Paylaş', 'mevzu2' ); ?>
                                </button>
                            <?php endif; ?>
                            <?php if ( in_array( 'bookmark', $detaylar, true ) && function_exists( 'mevzu_render_bookmark_button' ) ) {
                                mevzu_render_bookmark_button( get_the_ID() );
                            } ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="kose-yazi-body single-body p-3 p-md-4 pt-2 pt-md-2">
                    
                    <h1 class="single-title kose-yazi-title m-0 px-2 px-md-0 mb-3"><?php the_title(); ?></h1>

                    <div class="content single-content">
                        <?php
                        $content = get_the_content();
                        $content = str_replace( '<!-- wp:post-featured-image {"className":"cikarilmis-gorsel"} /-->', '', $content );
                        echo apply_filters( 'the_content', $content );
                        ?>
                    </div>

                    <?php if ( function_exists( 'mevzu_kaynak_the_badge' ) ) mevzu_kaynak_the_badge(); ?>
                </div>
            </div>
        </div>

    </div>

    <div class="col-12 col-lg">
        <?php
        $author_posts = new WP_Query(
            array(
                'author'              => $author_id,
                'posts_per_page'      => 6,
                'post__not_in'        => array( get_the_ID() ),
                'ignore_sticky_posts' => 1,
            )
        );
        ?>
        <div class="widget yazarinyazarlari">
            <div class="d-flex align-items-center justify-content-between">
                <h2>Yazarın Kaleminden</h2>
                <a href="<?php echo esc_url( $author_profile_url ); ?>" class="bg-hepsinigoster me-3">Tümünü Oku</a>
            </div>
            <?php
            if ( $author_posts->have_posts() ) :
                $count = 0;
                while ( $author_posts->have_posts() ) :
                    $author_posts->the_post();
                    $count++;
                    ?>
                <div class="border-bottom mb-2 son-border px-3 py-2">
                    <a href="<?php the_permalink(); ?>" class="ripple text-link d-block" data-bs-ripple-color="light" title="<?php the_title_attribute(); ?>">
                        <?php if ( $count === 1 ) {
                            the_post_thumbnail( 'gorsel-thumbnail-col-3', array( 'class' => 'rounded w-100 mb-2 shadow-sm', 'loading' => 'lazy', 'title' => get_the_title() ) );
                        } ?>
                        <h3 class="satir-1 fw-semibold m-0"><?php the_title(); ?></h3>
                        <div class="text-body small fw-normal <?php echo $count === 1 ? 'satir-4' : 'satir-2'; ?>">
                            <?php echo esc_html( mb_substr( strip_tags( get_the_content() ), 0, $count === 1 ? 240 : 100 ) ) . '...'; ?>
                        </div>
                    </a>
                </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p class="px-3">Bu yazarın başka yazısı bulunmamaktadır.</p>';
            endif;
            ?>
            <?php if ( $author_posts->post_count > 0 ) : ?>
            <div class="text-center px-3 pt-2 pb-3">
                <a href="<?php echo esc_url( $author_profile_url ); ?>" class="btn btn-outline-dark fw-semibold border-2 d-inline-flex mx-auto">
                    Tümünü Göster</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php if ( in_array( 'paylas', $detaylar, true ) ) : ?>
<div class="modal fade" id="paylas-kose" tabindex="-1" aria-labelledby="paylasKoseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-lg-3">
            <div class="modal-header border-0">
                <h2 class="modal-title fw-bolder fs-5" id="paylasKoseLabel"><?php esc_html_e( 'Bu Yazıyı Paylaş', 'mevzu2' ); ?></h2>
                <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Kapat', 'mevzu2' ); ?>"></button>
            </div>
            <div class="modal-body">
                <?php echo do_shortcode( '[social]' ); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
