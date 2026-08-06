<?php
$detaylar = function_exists( 'mevzu_yz_get_post_detail_settings' )
    ? mevzu_yz_get_post_detail_settings( get_the_ID() )
    : (array) get_option( 'options_detaylar', array() );
$show_okunma = in_array( 'okunma', $detaylar, true );
$show_sure   = in_array( 'sure', $detaylar, true );
?>
<div class="bg-white rounded shadow-sm mb-3 mb-lg-4">
    <div class="row px-2 pt-2 pb-0 p-md-3 pb-md-2">
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

    <h1 class="single-title m-0 px-2 px-md-3 pb-2 pb-md-3"><?php the_title(); ?></h1>

    <div class="icerik sablon-1<?php echo ' haber-'. $post->ID; ?>" property="articleBody" data-title="<?php the_title(); ?>">
        <?php
        $the_native_video_id = (int) get_post_meta( get_the_ID(), 'mevzu_native_video_id', true );
        $the_native_video_html = $the_native_video_id > 0 ? mevzu_get_native_video_html( get_the_ID() ) : '';
        if ( $the_native_video_html ) {
            echo $the_native_video_html;
        } elseif ( has_post_thumbnail() ) {
            the_post_thumbnail('gorsel-thumbnail-col-8', ['title' => get_the_title(), 'loading'=>'lazy', 'class' => 'onecikarilmisgorsel rounded-0 w-100']);
        } else {
            $ust_manset_id = (int) get_post_meta( get_the_ID(), 'ust_manset_gorseli_id', true );
            if ( $ust_manset_id ) {
                echo wp_get_attachment_image( $ust_manset_id, 'gorsel-thumbnail-col-8', false, ['title' => get_the_title(), 'loading' => 'lazy', 'class' => 'onecikarilmisgorsel rounded-0 w-100'] );
            }
        }
        ?>
        <div class="single-body p-2 p-md-3">
            <?php 
            $author_profile_url = get_author_posts_url(get_the_author_meta('ID'));
            $author_id = get_the_author_meta('ID');
            $user_roles = (array) get_the_author_meta('roles', $author_id); 

            $author_avatar_url = mevzu_get_user_avatar_url($author_id);
            $author_name = esc_html(get_the_author_meta('display_name'));
            if ($author_avatar_url) {
                $avatar_url = '<div class="me-3"><a href="' . $author_profile_url . '"><img class="w-42 h-42 rounded-circle m-0 object-fit-cover" src="' . esc_url($author_avatar_url) . '" alt="' . esc_attr($author_name) . ' Avatarı"></a></div>';
            } else {
                $avatar_url = '';
            }

            $show_tts = function_exists( 'mevzu_tts_post_can_display' ) && mevzu_tts_post_can_display( get_the_ID() ) && function_exists( 'mevzu_yz_detail_has_audio_player' ) && mevzu_yz_detail_has_audio_player( $detaylar );
            ?>
            <?php 
            $visible_elements = array('yazar', 'tarih', 'like', 'yorum', 'paylas', 'bookmark', 'okunma', 'sure', 'tts');
            if (array_intersect($visible_elements, $detaylar)) : ?>
            <div class="border-bottom pb-3 mb-3">
                <?php if (array_intersect(array('yazar', 'tarih', 'like', 'yorum', 'paylas', 'bookmark'), $detaylar)) : ?>
                <div class="row align-items-center justify-content-between w-100 g-0">
                    <div class="col-auto">
                        <?php if (array_intersect(array('yazar', 'tarih'), $detaylar)) : ?>
                        <div class="d-flex small single-bilgi align-items-center satir-1">
                            <?php if (in_array('yazar', $detaylar) && get_the_date() === get_the_modified_date()) { echo $avatar_url; } ?>
                            <div>
                                <?php if (in_array('yazar', $detaylar)) : ?>
                                    <a class="text-dark fw-semibold" href="<?php echo $author_profile_url ?>" alt="<?php echo get_the_author_meta('first_name') . ' ' . get_the_author_meta('last_name'); ?> Yazıları">
                                        <?php echo $author_name ?>
                                    </a>
                                    <?php if(!in_category( 'kose-yazilari')) : ?>
                                        <span class="small text-body fw-normal ms-1">tarafından</span>
                                    <?php else : ?>
                                        <?php if ( function_exists('mevzu_render_author_follow_button') ) { echo '<div class="d-inline-flex ms-2">'; mevzu_render_author_follow_button($author_id); echo '</div>'; } else { echo '<span class="fw-normal ps-1">tarafından</span>'; } ?>
                                    <?php endif;?>
                                <?php endif; ?>

                                <?php if (in_array('tarih', $detaylar)) : ?>
                                    <div class="small text-body fw-normal">
                                        <?php echo get_the_date('d F, Y H:i'); ?> tarihinde yayınlandı
                                        <?php //if (get_the_date() !== get_the_modified_date()) { echo '<span class="px-1 d-none d-md-inline-block">/</span><span class="d-none d-md-inline-block">Güncelleme: ' . get_the_modified_date("d.m.Y H:i") . '</span></span>'; } ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto col-md-auto ms-auto ms-lg-0 mt-2 mt-lg-0 d-flex align-items-center gap-2">
                        <?php if ( $show_tts ) : echo do_shortcode( '[kkerem_tts]' ); endif; ?>
                        <?php if ( in_array('like', $detaylar) && function_exists('mevzu_render_like_button') ) mevzu_render_like_button( get_the_ID(), 'post' ); ?>
                        <?php if (in_array('yorum', $detaylar) && comments_open()) { ?>
                            <a href="#comments" class="ripple btn btn-outline-secondary btn-sm fw-bolder rounded-pill py-1 px-3 d-inline-flex align-items-center gap-2 text-body">
                                <i class="ri-chat-3-line fz-16 me-1"></i>
                                <span class="count"><?php echo get_comments_number() ?></span>
                            </a>
                        <?php } ?>
                        <?php if (in_array('paylas', $detaylar)) : ?>
                        <button type="button" class="ripple btn btn-outline-secondary btn-sm fw-bolder rounded-pill py-1 px-3 d-inline-flex align-items-center gap-2 text-body" data-bs-toggle="modal" data-bs-target="#paylas">
                            <i class="ri-share-line me-2 fz-16"></i>Paylaş
                        </button>
                        <div class="modal fade" id="paylas" tabindex="-1" aria-labelledby="paylasLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content p-lg-3">
                                    <div class="modal-header border-0">
                                        <h1 class="modal-title fw-bolder fs-5" id="paylasLabel">Bu Yazıyı Paylaş</h1>
                                        <button type="button" class="btn-close bg-light rounded-circle" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php echo do_shortcode('[social]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ( in_array('bookmark', $detaylar) && function_exists('mevzu_render_bookmark_button') ) mevzu_render_bookmark_button( get_the_ID() ); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
			<?php echo anasayfa_reklam('icerik_oncesi'); ?>

            <div class="content single-content">
                <?php 
                $content = get_the_content();
                $content = str_replace('<!-- wp:post-featured-image {"className":"cikarilmis-gorsel"} /-->', '', $content);
                echo apply_filters('the_content', $content);
                ?>
            </div>

            <?php
            if ( function_exists( 'mevzu_render_embed_url_only' ) ) {
                mevzu_render_embed_url_only( get_the_ID() );
            }
            ?>

            <?php if ( !in_category( 'kose-yazilari' ) ) ilginizi_cekebilir(get_the_ID()); ?>

            <?php
            if ( get_post_type() === 'reklam' ) {
                // Reklam post type için yapılacaklar
                echo '<p class="text-center mt-3 fw-semibold"><span class="d-inline-block bg-success text-white py-2 px-4 rounded shadow-sm small-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m18.44 13.791l-5.499-9.585a2.1 2.1 0 0 0-1.274-.98a2.14 2.14 0 0 0-1.607.205l-1.196.696a.2.2 0 0 0-.078.06l-.069.087a.6.6 0 0 0-.147.275a25.4 25.4 0 0 1-1.588 3.783a6.1 6.1 0 0 1-1.754 1.892l-1.794 1.588a2.14 2.14 0 0 0-.686 1.244a2.3 2.3 0 0 0 .059.98c-.246.136-.46.324-.627.55a2.1 2.1 0 0 0-.363.744a2.2 2.2 0 0 0 0 .833a2.14 2.14 0 0 0 .806 1.397c.22.168.473.29.743.358q.264.071.539.068q.146.015.294 0c.272-.033.533-.127.764-.274c.216.243.489.428.794.54c.237.093.49.14.745.136q.209.005.412-.049l1.597 2.853a2.17 2.17 0 0 0 1.843 1.058a2.14 2.14 0 0 0 2.039-1.578a2.2 2.2 0 0 0-.206-1.607l-1.196-2.078q.484-.108.98-.118c1.395.059 2.782.236 4.146.53h.343l.157-.07l.147-.088l1.049-.607c.482-.279.835-.737.98-1.274a2.14 2.14 0 0 0-.353-1.569M6.551 16.948a.65.65 0 0 1-.431 0a.6.6 0 0 1-.324-.275l-1.49-2.607l-.097-.167a.66.66 0 0 1 .137-.823l1.107-.98l.412.725l2.127 3.725zm10.674-1.96a.7.7 0 0 1-.304.401l-.549.314l-6.136-10.694l.559-.323a.6.6 0 0 1 .48-.059a.63.63 0 0 1 .392.294l5.499 9.576a.66.66 0 0 1 .059.549zm-.255-5.823a.72.72 0 0 1-.637-.362a.735.735 0 0 1 .265-.98l2.45-1.422a.725.725 0 0 1 .98.265a.735.735 0 0 1-.265.98l-2.45 1.421a.7.7 0 0 1-.343.098m4.518 3.47h-2.832a.735.735 0 1 1 0-1.47h2.832a.735.735 0 0 1 0 1.47m-6.831-6.969a.7.7 0 0 1-.363-.098a.726.726 0 0 1-.274-.98l1.411-2.47a.736.736 0 0 1 1.274.735l-1.411 2.46a.73.73 0 0 1-.637.353"/></svg>
                Bu içerik bir reklamdır.</span></p>';
            }
            ?>
            <?php if (get_option('options_bizi_takip_edin_bolumu') == 1) echo do_shortcode('[takipedin]'); ?>
			<?php echo anasayfa_reklam('icerik_sonrasi'); ?>

        </div>
    </div><!-- ICERIK SON -->

</div>