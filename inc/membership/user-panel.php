<?php
/**
 * User Panel Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register shortcode
add_shortcode( 'mevzu_user_panel', 'mevzu_user_panel_shortcode' );
function mevzu_user_panel_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<div class="alert alert-warning">Bu sayfayı görüntüleyebilmek için giriş yapmalısınız. <a href="' . esc_url( home_url('/hesabim/giris') ) . '">Giriş Yap</a></div>';
    }

    $user = wp_get_current_user();
    $tab = get_query_var( 'mevzu_tab' );
    if ( empty( $tab ) ) {
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'pano';
    }

    // Handle avatar update independently
    if ( isset( $_POST['mevzu_update_avatar'] ) && wp_verify_nonce( $_POST['mevzu_avatar_nonce'], 'update_avatar' ) ) {
        if ( ! empty( $_FILES['m_avatar']['name'] ) ) {
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            $uploadedfile = $_FILES['m_avatar'];
            $upload_overrides = array( 'test_form' => false );
            $mimes = array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp'
            );
            $upload_overrides['mimes'] = $mimes;
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

            if ( $movefile && ! isset( $movefile['error'] ) ) {
                update_user_meta( $user->ID, 'mevzu_user_avatar', $movefile['url'] );
                $success_msg = 'Profil fotoğrafınız başarıyla güncellendi.';
                $user = wp_get_current_user(); // Refresh
            } else {
                $error_msg = $movefile['error'];
            }
        }
    }

    // Handle profile update
    if ( isset( $_POST['mevzu_update_profile'] ) && wp_verify_nonce( $_POST['mevzu_profile_nonce'], 'update_profile' ) ) {
        $userdata = array(
            'ID' => $user->ID,
            'first_name' => sanitize_text_field( $_POST['first_name'] ),
            'last_name' => sanitize_text_field( $_POST['last_name'] ),
            'display_name' => sanitize_text_field( $_POST['display_name'] )
        );

        if ( ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) {
            if ( $_POST['pass1'] === $_POST['pass2'] ) {
                $userdata['user_pass'] = $_POST['pass1'];
            } else {
                $error_msg = 'Şifreler uyuşmuyor.';
            }
        }

        $user_id = wp_update_user( $userdata );

        if ( ! is_wp_error( $user_id ) && ! isset( $error_msg ) ) {
            // E-posta tercihlerini kaydet
            update_user_meta($user_id, 'mevzu_email_pref_category_post', isset($_POST['email_pref_category_post']) ? '1' : '0');
            update_user_meta($user_id, 'mevzu_email_pref_comment_reply', isset($_POST['email_pref_comment_reply']) ? '1' : '0');
            update_user_meta($user_id, 'mevzu_email_pref_author_post', isset($_POST['email_pref_author_post']) ? '1' : '0');

            $success_msg = 'Profiliniz başarıyla güncellendi.';
            $user = wp_get_current_user(); // Refresh
        } elseif( is_wp_error( $user_id ) ) {
            $error_msg = $user_id->get_error_message();
        }
    }

    // Handle Category Subscription form
    if ( isset( $_POST['mevzu_submit_cat_subs'] ) && wp_verify_nonce( $_POST['m_add_cat_subs_nonce'], 'mevzu_add_cat_subs' ) ) {
        $new_cats = isset($_POST['new_category_subs']) ? array_map('intval', $_POST['new_category_subs']) : [];
        if (!empty($new_cats)) {
            $subscribed = get_user_meta( $user->ID, 'mevzu_subscribed_categories', true );
            if ( ! is_array( $subscribed ) ) $subscribed = [];
            $subscribed = array_unique(array_merge($subscribed, $new_cats));
            update_user_meta( $user->ID, 'mevzu_subscribed_categories', $subscribed );
            $success_msg = 'Kategoriler başarıyla eklendi.';
        }
    }

    // Handle Tag Subscription form
    if ( isset( $_POST['mevzu_submit_tag_subs'] ) && wp_verify_nonce( $_POST['m_add_tag_subs_nonce'], 'mevzu_add_tag_subs' ) ) {
        $new_tags = isset($_POST['new_tag_subs']) ? array_map('intval', $_POST['new_tag_subs']) : [];
        if (!empty($new_tags)) {
            $subscribed = get_user_meta( $user->ID, 'mevzu_subscribed_tags', true );
            if ( ! is_array( $subscribed ) ) $subscribed = [];
            $subscribed = array_unique(array_merge($subscribed, $new_tags));
            update_user_meta( $user->ID, 'mevzu_subscribed_tags', $subscribed );
            $success_msg = 'Etiketler başarıyla eklendi.';
        }
    }

    ob_start();
    ?>
    <div class="mevzu-user-panel container">
        <div class="row">
            <!-- Sidebar / Offcanvas -->
            <div class="col-lg-3">
                <div class="offcanvas-lg offcanvas-bottom rounded-top rounded-lg-0" tabindex="-1" id="mevzuUserMenuOffcanvas" aria-labelledby="mevzuUserMenuOffcanvasLabel" style="--mevzu-offcanvas-height: 80vh;">
                    <div class="offcanvas-header d-lg-none pt-2 px-3 d-flex align-items-center">
                        <h5 class="offcanvas-title fw-semibold fs-6 py-2" id="mevzuUserMenuOffcanvasLabel">Kullanıcı Menüsü</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#mevzuUserMenuOffcanvas" aria-label="Kapat"></button>
                    </div>
                    <div class="offcanvas-body p-0 d-block">
                        <div class="bg-white rounded shadow-sm w-100">
                            <div class="p-3 pt-2">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <form id="mevzu-avatar-form" method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field( 'update_avatar', 'mevzu_avatar_nonce' ); ?>
                                            <input type="hidden" name="mevzu_update_avatar" value="1">
                                            <label class="position-relative d-inline-block mevzu-avatar-wrapper" style="cursor: pointer;">
                                                <?php 
                                                $custom_avatar = get_user_meta($user->ID, 'mevzu_user_avatar', true);
                                                if ( $custom_avatar ) {
                                                    echo '<img src="' . esc_url($custom_avatar) . '" class="rounded-circle shadow-sm object-fit-cover mevzu-avatar-img" style="height:70.22px;width:70.22px">';
                                                } else {
                                                    echo get_avatar( $user->ID, 80, '', '', array('class' => 'rounded-circle shadow-sm mevzu-avatar-img') ); 
                                                }
                                                ?>
                                                <div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle d-flex justify-content-center align-items-center mevzu-avatar-overlay shadow-sm" style="background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.2s;">
                                                    <i class="ri-camera-fill text-white fz-24"></i>
                                                </div>
                                                <input type="file" name="m_avatar" class="d-none" id="mevzu-avatar-input" accept="image/png, image/jpeg, image/webp" onchange="document.getElementById('mevzu-avatar-form').submit();">
                                            </label>
                                        </form>
                                    </div>
                                    <div class="col px-lg-0 text-start d-flex flex-column justify-content-center mt-0">
                                        <?php 
                                        global $wp_roles;
                                        $user_role = !empty($user->roles) ? reset($user->roles) : false;
                                        $role_name = $user_role && isset($wp_roles->roles[$user_role]) ? translate_user_role($wp_roles->roles[$user_role]['name']) : 'Üye';
                                        ?>
                                        <div><span class="badge bg-primary rounded-pill mb-1 fw-semibold text-capitalize"><?php echo esc_html($role_name); ?></span></div>
                                        <h5 class="m-0 fw-semibold satir-1 fs-6"><?php echo esc_html( $user->display_name ); ?></h5>
                                        <span class="d-block text-secondary mt-1 small-2 satir-1">
                                            <?php 
                                            $email = $user->user_email;
                                            $parts = explode('@', $email);
                                            echo esc_html(count($parts) === 2 ? $parts[0] . '@' . substr($parts[1], 0, 8) . '...' : $email);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <ul class="nav flex-column nav-pills m-0 pb-3 pb-lg-0">
                                                        
                                <li class="nav-item text-start ps-3 small fw-semibold mt-3" style="padding-left:20px !important">Genel</li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'pano' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/pano') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-dashboard-line fs-5"></i></div><div class="col-10">Pano</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start" href="<?php echo esc_url( home_url('/akis') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-flashlight-fill fs-5"></i></div><div class="col-10">Akış</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'bildirimlerim' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/bildirimlerim') ); ?>">
                                        <div class="row gx-2 align-items-center">
                                            <div class="col-2 text-end"><i class="ri-notification-3-line fs-5"></i></div>
                                            <div class="col-10">
                                                Bildirimlerim
                                                <?php 
                                                if ( function_exists('mevzu_get_unread_notifications_count') ) {
                                                    $unread_count = mevzu_get_unread_notifications_count($user->ID);
                                                    if ($unread_count > 0) {
                                                        echo '<span class="badge bg-danger ms-2 rounded-pill">' . esc_html($unread_count) . '</span>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                        
                                <li class="nav-item text-start ps-3 small fw-semibold my-2 mt-3" style="padding-left:20px !important">İçerikler</li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'kaydedilenler' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/kaydedilenler') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-bookmark-line fs-5"></i></div><div class="col-10">Kaydedilen Haberler</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'begenilenler' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/begenilenler') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-heart-3-line fs-5"></i></div><div class="col-10">Beğenilen Haberler</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'yorumlarim' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/yorumlarim') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-chat-3-line fs-5"></i></div><div class="col-10">Yorumlarım</div></div></a>
                                </li>
                        
                                <li class="nav-item text-start ps-3 small fw-semibold my-2 mt-3" style="padding-left:20px !important">Aboneliklerim</li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'takip' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/takip') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-folder-open-line fs-5"></i></div><div class="col-10">Takip Ettiğim Kategoriler</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'etkilesimler' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/etkilesimler') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-hashtag fs-5"></i></div><div class="col-10">Takip Ettiğim Etiketler</div></div></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'takipyazar' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/takipyazar') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-quill-pen-line fs-5"></i></div><div class="col-10">Takip Ettiğim Yazarlar</div></div></a>
                                </li>
                        
                                <li class="nav-item text-start ps-3 small fw-semibold my-2 mt-3" style="padding-left:20px !important">Hesap</li>
                                <li class="nav-item">
                                    <a class="nav-link text-start<?php echo ( $tab === 'profil' ) ? ' active' : ''; ?>" href="<?php echo esc_url( home_url('/hesabim/profil') ); ?>"><div class="row gx-2 align-items-center"><div class="col-2 text-end"><i class="ri-user-settings-line fs-5"></i></div><div class="col-10">Profil Ayarlarım</div></div></a>
                                </li>
                                <li class="nav-item mt-1">
                                    <a class="nav-link text-start text-danger" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                                        <div class="row gx-2 align-items-center">
                                            <div class="col-2 text-end"><i class="ri-logout-box-line fs-5"></i></div>
                                            <div class="col-10">Çıkış Yap</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9 pt-3 pt-lg-0">
                <?php if ( isset( $success_msg ) ) : ?>
                    <div class="alert alert-success mb-3 small p-3"><?php echo esc_html( $success_msg ); ?></div>
                <?php endif; ?>
                <?php if ( isset( $error_msg ) ) : ?>
                    <div class="alert alert-danger mb-3 small p-3"><?php echo esc_html( $error_msg ); ?></div>
                <?php endif; ?>

                <div class="tema-widget bg-white shadow-sm rounded">
                    <?php if ( $tab === 'pano' ) : ?>
                        <h1 class="mx-0 mb-0">Pano</h1>
                        <div class="p-3">
                            Merhaba <strong><?php echo esc_html($user->display_name); ?> 👋</strong>
                            <?php
                            $bookmarked = get_user_meta( $user->ID, 'mevzu_bookmarked_posts', true );
                            $bookmarked_count = is_array($bookmarked) ? count($bookmarked) : 0;
                            
                            $liked = get_user_meta( $user->ID, 'mevzu_liked_posts', true );
                            $liked_count = is_array($liked) ? count($liked) : 0;

                            $sub_cats = get_user_meta( $user->ID, 'mevzu_subscribed_categories', true );
                            $sub_cats_count = is_array($sub_cats) ? count($sub_cats) : 0;

                            $sub_tags = get_user_meta( $user->ID, 'mevzu_subscribed_tags', true );
                            $sub_tags_count = is_array($sub_tags) ? count($sub_tags) : 0;

                            $sub_authors = get_user_meta( $user->ID, 'mevzu_subscribed_authors', true );
                            $sub_authors_count = is_array($sub_authors) ? count($sub_authors) : 0;

                            $comments_count = get_comments( array('user_id' => $user->ID, 'status' => 'all', 'post_type' => 'post', 'count' => true) );

                            $reg_date = date_i18n( 'd F Y', strtotime( $user->user_registered ) );
                            ?>
                            <h6 class="fw-normal mb-1 text-secondary small-2 mb-3">Kayıt Tarihi: <span class="fw-semibold"><?php echo esc_html($reg_date); ?></span></h6>
                            
                            <div class="row g-4 justify-content-center">
                                <div class="col-12 col-md-6 col-lg">
                                    <a href="<?php echo esc_url( home_url('/hesabim/yorumlarim') ); ?>" class="text-decoration-none d-block h-100 d-flex align-items-center justify-content-start hover-bg-light transition-base gap-3">
                                        <div class="bg-success bg-opacity-10 d-flex align-items-center justify-content-center rounded" style="width: 48px; height: 48px;">
                                            <i class="ri-chat-3-line text-success fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-normal mb-1 text-dark small">Yorumlar</h6>
                                            <span class="text-dark fs-6 fw-bold lh-1 d-block"><?php echo esc_html($comments_count); ?></span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6 col-lg">
                                    <a href="<?php echo esc_url( home_url('/hesabim/kaydedilenler') ); ?>" class="text-decoration-none d-block h-100 d-flex align-items-center justify-content-start hover-bg-light transition-base gap-3">
                                        <div class="bg-warning bg-opacity-10 d-flex align-items-center justify-content-center rounded" style="width: 48px; height: 48px;">
                                            <i class="ri-bookmark-line text-warning fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-normal mb-1 text-dark small">Kaydedilenler</h6>
                                            <span class="text-dark fs-6 fw-bold lh-1 d-block"><?php echo esc_html($bookmarked_count); ?></span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6 col-lg">
                                    <a href="<?php echo esc_url( home_url('/hesabim/begenilenler') ); ?>" class="text-decoration-none d-block h-100 d-flex align-items-center justify-content-start hover-bg-light transition-base gap-3">
                                        <div class="bg-danger bg-opacity-10 d-flex align-items-center justify-content-center rounded" style="width: 48px; height: 48px;">
                                            <i class="ri-heart-3-line text-danger fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-normal mb-1 text-dark small">Beğenilenler</h6>
                                            <span class="text-dark fs-6 fw-bold lh-1 d-block"><?php echo esc_html($liked_count); ?></span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6 col-lg">
                                    <a href="<?php echo esc_url( home_url('/hesabim/takip') ); ?>" class="text-decoration-none d-block h-100 d-flex align-items-center justify-content-start hover-bg-light transition-base gap-3">
                                        <div class="bg-info bg-opacity-10 d-flex align-items-center justify-content-center rounded" style="width: 48px; height: 48px;">
                                            <i class="ri-folder-open-line text-info fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-normal mb-1 text-dark small">Kategoriler</h6>
                                            <span class="text-dark fs-6 fw-bold lh-1 d-block"><?php echo esc_html($sub_cats_count); ?></span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6 col-lg">
                                    <a href="<?php echo esc_url( home_url('/hesabim/etkilesimler') ); ?>" class="text-decoration-none d-block h-100 d-flex align-items-center justify-content-start hover-bg-light transition-base gap-3">
                                        <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center rounded" style="width: 48px; height: 48px;">
                                            <i class="ri-hashtag text-secondary fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-normal mb-1 text-dark small">Etiketler</h6>
                                            <span class="text-dark fs-6 fw-bold lh-1 d-block"><?php echo esc_html($sub_tags_count); ?></span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        </div>
                            <!-- RECOMMENDED 3-BLOCK -->
                            <div class="row g-3 mt-0">
                                <div class="col-12 col-lg-6">
                                    <div class="tema-widget bg-white shadow-sm rounded-3 h-100">
                                        <h6 class="fz-16 fw-semibold p-3 mb-0 d-flex align-items-center"><i class="ri-quill-pen-line text-primary me-2 fs-5"></i> Önerilen Yazarlar</h6>
                                        <div class="list-group list-group-flush px-3">
                                            <?php
                                            $sub_authors_arr = is_array($sub_authors) ? $sub_authors : [];
                                            $rec_authors = get_users([
                                                'number' => 5,
                                                'exclude' => array_merge([$user->ID], $sub_authors_arr),
                                                'orderby' => 'post_count',
                                                'order' => 'DESC',
                                                'has_published_posts' => array('post')
                                            ]);
                                            if (!empty($rec_authors)) {
                                                foreach ($rec_authors as $rec_author) {
                                                    $author_link = esc_url(get_author_posts_url($rec_author->ID));
                                                    echo '<div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-light">';
                                                    echo '<div class="d-flex align-items-center gap-2">';
                                                    echo '<div class="flex-shrink-0">';
                                                    $rec_author_avatar_url = mevzu_get_user_avatar_url($rec_author->ID);
                                                    echo '<img src="' . esc_url($rec_author_avatar_url) . '" class="rounded-circle shadow-sm" style="width:32px; height:32px; object-fit:cover;">';
                                                    echo '</div>';
                                                    echo '<div><a href="'.$author_link.'" class="text-dark fw-semibold small text-decoration-none">'.esc_html($rec_author->display_name).'</a>';
                                                    echo '<div class="small-2 text-muted">'.count_user_posts($rec_author->ID, 'post', false).' Yazı</div></div></div>';
                                                    if (function_exists('mevzu_render_author_follow_button')) {
                                                        echo '<div>';
                                                        mevzu_render_author_follow_button($rec_author->ID);
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="small text-muted">Tavsiye edilecek yazar bulunamadı.</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">

                                    <div class="tema-widget bg-white shadow-sm rounded-3 h-100">
                                        <h6 class="fz-16 fw-semibold p-3 mb-0 d-flex align-items-center"><i class="ri-article-line text-success me-2 fs-5"></i> Son Köşe Yazıları</h6>
                                        <div class="list-group list-group-flush px-3">
                                            <?php
                                            $kose_args = [
                                                'post_type' => 'post',
                                                'posts_per_page' => 5,
                                                'tax_query' => [
                                                    [
                                                        'taxonomy' => 'category',
                                                        'field' => 'slug',
                                                        'terms' => 'kose-yazilari'
                                                    ]
                                                ],
                                                'orderby' => 'date',
                                                'order' => 'DESC',
                                            ];
                                            $kose_query = new WP_Query($kose_args);
                                            
                                            if ($kose_query->have_posts()) :
                                                while ($kose_query->have_posts()) : $kose_query->the_post();
                                                    $post_author_id = get_the_author_meta('ID');
                                                ?>
                                                <div class="d-flex align-items-center mb-2 pb-2 border-bottom border-light">
                                                    <div class="flex-shrink-0 me-3">
                                                        <a href="<?php echo esc_url(get_author_posts_url($post_author_id)); ?>">
                                                            <?php
                                                                $post_author_avatar_url = mevzu_get_user_avatar_url($post_author_id);
                                                                echo '<img src="' . esc_url($post_author_avatar_url) . '" class="rounded-circle shadow-sm" style="width:43.5px; height:43.5px; object-fit:cover;">';
                                                            ?>
                                                        </a>
                                                    </div>
                                                    <div>
                                                        <a href="<?php the_permalink(); ?>" class="text-dark fw-semibold small text-decoration-none lh-sm d-block mb-1 text-hover">
                                                            <?php echo wp_trim_words(get_the_title(), 10, '...'); ?>
                                                        </a>
                                                        <div class="small-2 text-muted fw-bold"><?php the_author(); ?> &bull; <span class="fw-normal"><?php echo get_the_date(); ?></span></div>
                                                    </div>
                                                </div>
                                                <?php
                                                endwhile; wp_reset_postdata();
                                            else : ?>
                                                <div class="small text-muted">Henüz köşe yazısı bulunmuyor.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="tema-widget bg-white shadow-sm rounded-3 h-100">
                                        <h6 class="fz-16 fw-semibold p-3 mb-0 d-flex align-items-center"><i class="ri-folder-add-line text-info me-2 fs-5"></i> Önerilen Kategoriler</h6>
                                        <div class="list-group list-group-flush px-3">
                                            <?php
                                            $sub_cats_arr = is_array($sub_cats) ? $sub_cats : [];
                                            $rec_cats = get_categories([
                                                'number' => 5,
                                                'exclude' => $sub_cats_arr,
                                                'orderby' => 'count',
                                                'order' => 'DESC',
                                                'hide_empty' => true
                                            ]);
                                            if (!empty($rec_cats)) {
                                                foreach ($rec_cats as $cat) {
                                                    $cat_link = esc_url(get_category_link($cat->term_id));
                                                    echo '<div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-light">';
                                                    echo '<div><a href="'.$cat_link.'" class="text-dark fw-semibold small text-decoration-none text-hover">'.esc_html($cat->name).'</a><div class="small-2 text-muted">'.esc_html($cat->count).' Haber</div></div>';
                                                    if (function_exists('mevzu_render_category_follow_button')) {
                                                        echo '<div>';
                                                        mevzu_render_category_follow_button($cat->term_id);
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="small text-muted">Tavsiye edilecek kategori bulunamadı.</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="tema-widget bg-white shadow-sm rounded-3 h-100">
                                        <h6 class="fz-16 fw-semibold p-3 mb-0 d-flex align-items-center"><i class="ri-hashtag text-secondary me-2 fs-5"></i> Önerilen Etiketler</h6>
                                        <div class="list-group list-group-flush px-3">
                                            <?php
                                            $sub_tags_arr = is_array($sub_tags) ? $sub_tags : [];
                                            $rec_tags = get_tags([
                                                'number' => 5,
                                                'exclude' => $sub_tags_arr,
                                                'orderby' => 'count',
                                                'order' => 'DESC',
                                                'hide_empty' => true
                                            ]);
                                            if (!empty($rec_tags)) {
                                                foreach ($rec_tags as $tag) {
                                                    $tag_link = esc_url(get_tag_link($tag->term_id));
                                                    echo '<div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-light">';
                                                    echo '<div><a href="'.$tag_link.'" class="text-dark fw-semibold small text-decoration-none text-hover">'.esc_html($tag->name).'</a><div class="small-2 text-muted">'.esc_html($tag->count).' İçerik</div></div>';
                                                    if (function_exists('mevzu_render_tag_follow_button')) {
                                                        echo '<div>';
                                                        mevzu_render_tag_follow_button($tag->term_id);
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="small text-muted">Tavsiye edilecek etiket bulunamadı.</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-12">
                                    <div class="tema-widget bg-white shadow-sm rounded">
                                        <h6 class="fz-16 fw-semibold p-3 mb-0 d-flex align-items-center"><i class="ri-fire-line text-danger me-2 fs-5"></i> Haftanın Popüler Haberleri</h6>
                                        <div class="list-group list-group-flush px-3">
                                            <?php
                                            $popular_args = [
                                                'post_type' => 'post',
                                                'posts_per_page' => 10,
                                                'orderby' => 'views_count',
                                                'order' => 'DESC',
                                                'date_query' => [
                                                    [
                                                        'after' => '1 week ago'
                                                    ]
                                                ]
                                            ];
                                            $popular_query = new WP_Query($popular_args);
                                            // Fallback if no comments in a week, just fetch all time popular
                                            if (!$popular_query->have_posts()) {
                                                $popular_query = new WP_Query([
                                                    'post_type' => 'post',
                                                    'posts_per_page' => 10,
                                                    'orderby' => 'views_count',
                                                    'order' => 'DESC'
                                                ]);
                                            }
                                            if ($popular_query->have_posts()) : ?>
                                            <div class="swiper swiper-pano-populer">
                                                <div class="swiper-wrapper pb-4">
                                                    <?php while ($popular_query->have_posts()) : $popular_query->the_post(); ?>
                                                    <div class="swiper-slide">
                                                        <?php get_template_part("sablon/sablon-1-nobox"); ?>
                                                    </div>
                                                    <?php endwhile; wp_reset_postdata(); ?>
                                                </div>
                                                <div class="swiper-pagination position-relative mt-3"></div>
                                            </div>
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function() {
                                                    new Swiper('.swiper-pano-populer', {
                                                        slidesPerView: 1.2,
                                                        spaceBetween: 16,
                                                        pagination: {
                                                            el: '.swiper-pano-populer .swiper-pagination',
                                                            clickable: true,
                                                        },
                                                        breakpoints: {
                                                            576: { slidesPerView: 2, spaceBetween: 16 },
                                                            768: { slidesPerView: 3, spaceBetween: 16 },
                                                            992: { slidesPerView: 2.2, spaceBetween: 16 },
                                                            1200: { slidesPerView: 3, spaceBetween: 16 }
                                                        }
                                                    });
                                                });
                                            </script>
                                            <?php else : ?>
                                                <div class="small text-muted">Popüler haber bulunamadı.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    <?php elseif ( $tab === 'profil' ) : ?>
                        <h1 class="mx-0 mb-0">Profil Ayarları</h1>
                        <div class="p-3">
                            <form method="post" action="">
                                <?php wp_nonce_field( 'update_profile', 'mevzu_profile_nonce' ); ?>
                                <div class="row g-3 g-lg-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="m_first_name" name="first_name" placeholder="Ad" value="<?php echo esc_attr( $user->first_name ); ?>">
                                            <label for="m_first_name">Ad</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="m_last_name" name="last_name" placeholder="Soyad" value="<?php echo esc_attr( $user->last_name ); ?>">
                                            <label for="m_last_name">Soyad</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="m_display_name" name="display_name" placeholder="Görünen İsim" value="<?php echo esc_attr( $user->display_name ); ?>">
                                            <label for="m_display_name">Görünen İsim</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="m_pass1" name="pass1" placeholder="Yeni Şifre">
                                            <label for="m_pass1">Yeni Şifre (Boş Bırakabilirsiniz)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="m_pass2" name="pass2" placeholder="Şifrenizi Doğrulayın">
                                            <label for="m_pass2">Yeni Şifre (Tekrar)</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <h5 class="fw-semibold mb-3 fs-6 border-bottom pb-2">E-Posta Bildirim Tercihleri</h5>
                                        <?php
                                        // E-posta bildirim ayarlarını tanımla
                                        $pref_cat_post = get_user_meta($user->ID, 'mevzu_email_pref_category_post', true);
                                        $pref_comment = get_user_meta($user->ID, 'mevzu_email_pref_comment_reply', true);
                                        $pref_author_post = get_user_meta($user->ID, 'mevzu_email_pref_author_post', true);

                                        // Hiç kaydedilmemişse varsayılan aktif say (yani '1' dönsün)
                                        $pref_cat_post = ($pref_cat_post === '') ? '1' : $pref_cat_post;
                                        $pref_comment = ($pref_comment === '') ? '1' : $pref_comment;
                                        $pref_author_post = ($pref_author_post === '') ? '1' : $pref_author_post;
                                        ?>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="epf_cat" name="email_pref_category_post" value="1" <?php checked($pref_cat_post, '1'); ?>>
                                            <label class="form-check-label" for="epf_cat">Takip ettiğim kategorilere yeni haber eklendiğinde bildir</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="epf_author" name="email_pref_author_post" value="1" <?php checked($pref_author_post, '1'); ?>>
                                            <label class="form-check-label" for="epf_author">Takip ettiğim yazar yeni içerik yazdığında bildir</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" id="epf_com" name="email_pref_comment_reply" value="1" <?php checked($pref_comment, '1'); ?>>
                                            <label class="form-check-label" for="epf_com">Yorumlarıma yeni bir yanıt geldiğinde bildir</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4 text-end">
                                        <button type="submit" name="mevzu_update_profile" class="btn btn-primary px-4">Değişiklikleri Kaydet</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    <?php elseif ( $tab === 'kaydedilenler' ) : ?>
                        <h1 class="mx-0 mb-0">Kaydettiğim Haberler</h1>
                        <div class="p-3">
                            <div class="row g-3 g-lg-4">
                                <?php
                                $bookmarked = get_user_meta( $user->ID, 'mevzu_bookmarked_posts', true );
                                if ( ! empty( $bookmarked ) && is_array( $bookmarked ) ) {
                                    $query = new WP_Query( array(
                                        'post_type' => 'post',
                                        'post__in' => $bookmarked,
                                        'posts_per_page' => -1
                                    ) );

                                    if ( $query->have_posts() ) {
                                        while ( $query->have_posts() ) {
                                            $query->the_post();
                                            ?>
                                            <div class="col-12 col-md-4">
                                                <?php get_template_part("sablon/sablon-1-nobox"); ?>
                                            </div>
                                            <?php
                                        }
                                        wp_reset_postdata();
                                    } else {
                                        echo '<div class="col-12"><div class="p-3 py-5 small text-muted text-center">Hiç kaydedilmiş haberiniz yok.</div></div>';
                                    }
                                } else {
                                    echo '<div class="col-12"><div class="p-3 py-5 small text-muted text-center">Hiç kaydedilmiş haberiniz yok.</div></div>';
                                }
                                ?>
                            </div>
                        </div>

                    <?php elseif ( $tab === 'begenilenler' ) : ?>
                        <h1 class="mx-0 mb-0">Beğendiğim Haberler</h1>
                        <div class="p-3">
                            <div class="row g-3 g-lg-4">
                                <?php
                                $liked_posts = get_user_meta( $user->ID, 'mevzu_liked_posts', true );
                                if ( ! empty( $liked_posts ) && is_array( $liked_posts ) ) {
                                    $query = new WP_Query( array(
                                        'post_type' => 'post',
                                        'post__in' => $liked_posts,
                                        'posts_per_page' => -1
                                    ) );

                                    if ( $query->have_posts() ) {
                                        while ( $query->have_posts() ) {
                                            $query->the_post();
                                            ?>
                                            <div class="col-12 col-md-4">
                                                <?php get_template_part("sablon/sablon-1-nobox"); ?>
                                            </div>
                                            <?php
                                        }
                                        wp_reset_postdata();
                                    } else {
                                        echo '<div class="col-12"><div class="p-3 py-5 small text-muted text-center">Hiç beğendiğiniz haber yok.</div></div>';
                                    }
                                } else {
                                    echo '<div class="col-12"><div class="p-3 py-5 small text-muted text-center">Hiç beğendiğiniz haber yok.</div></div>';
                                }
                                ?>
                            </div>
                        </div>

                    <?php elseif ( $tab === 'takip' ) : ?>
                        <h1 class="mx-0 mb-0">Takip Ettiğim Kategoriler</h1>
                        <form method="post" action="" class="border-bottom">
                            <?php wp_nonce_field('mevzu_add_cat_subs', 'm_add_cat_subs_nonce'); ?>
                            <div class="p-3 small">
                                <p class="small text-muted">İlgilendiğin kategorilere yenilereni eklemek ister misin? Tercihlerine göre sana özel bir akış sunacağız!</p>
                                <div class="row g-2">
                                    <div class="col-8 col-lg">
                                        <select name="new_category_subs[]" class="form-control select2-categories w-100" multiple="multiple" data-placeholder="Kategori ara ve ekle..."></select>
                                    </div>
                                    <div class="col-4 col-lg-auto d-flex gap-2">
                                        <button type="submit" name="mevzu_submit_cat_subs" class="btn btn-primary btn-sm fw-semibold rounded shadow-sm text-capitalize px-3 py-1 d-flex align-items-center"><i class="ri-add-fill fz-14 me-1"></i>Takip Et</button>
                                    </div>
                                    <div class="col-12 col-lg-auto d-lg-flex text-center">
                                        <span class="text-muted d-lg-flex align-items-center small-2 px-2">yada</span>
                                    </div>
                                    <div class="col-12 col-lg-auto d-lg-flex">
                                        <button type="button" class="btn btn-outline-dark btn-sm fw-semibold rounded shadow-sm text-capitalize px-3 py-2 d-lg-flex align-items-center w-100" data-bs-toggle="modal" data-bs-target="#allCategoriesModal"><i class="ri-list-check-2 me-1"></i> Tüm Kategorileri Listele</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- List All Categories Modal -->
                        <div class="modal fade" id="allCategoriesModal" tabindex="-1" aria-labelledby="allCategoriesModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold fs-6" id="allCategoriesModalLabel">Tüm Kategoriler</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-0" style="max-height: 400px;">
                                        <div class="list-group list-group-flush">
                                        <?php
                                        // Function to recursively render categories with hierarchy
                                        if (!function_exists('render_category_list_item_for_modal')) {
                                            function render_category_list_item_for_modal($cat, $level = 0) {
                                                $padding = $level * 20 + 20;
                                                $cat_color_field = get_term_meta($cat->term_id, 'cat_renk', true);
                                                $cat_color = $cat_color_field ? $cat_color_field : 'primary';
                                                
                                                echo '<div class="list-group-item d-flex justify-content-between align-items-center border-bottom border-light hover-bg-light" style="padding-left: '.$padding.'px; padding-right: 20px; padding-top: 12px; padding-bottom: 12px;">';
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<i class="ri-folder-3-fill me-3 fz-24 text-'.$cat_color.'"></i>';
                                                echo '<div>';
                                                echo '<a href="'.esc_url(get_category_link($cat->term_id)).'" class="text-dark fw-bold text-decoration-none d-block small">'.esc_html($cat->name).'</a>';
                                                echo '<span class="small-2 text-muted fw-semibold">Haber sayısı: '.esc_html($cat->count).'</span>';
                                                echo '</div>';
                                                echo '</div>';
                                                
                                                echo '<div class="d-flex align-items-center">';
                                                if ( function_exists('mevzu_render_category_follow_button') ) {
                                                    // Use the category follow button renderer with text enabled
                                                    echo '<div class="bg-'.$cat_color.' rounded flex-shrink-0 d-flex align-items-center justify-content-center">';
                                                    mevzu_render_category_follow_button($cat->term_id, false, true);
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                                echo '</div>';

                                                $children = get_categories(array('parent' => $cat->term_id, 'hide_empty' => 0, 'orderby' => 'name'));
                                                if ($children) {
                                                    foreach ($children as $child) {
                                                        render_category_list_item_for_modal($child, $level + 1);
                                                    }
                                                }
                                            }
                                        }

                                        $top_cats = get_categories(array('parent' => 0, 'hide_empty' => 0, 'orderby' => 'name'));
                                        if ($top_cats) {
                                            foreach ($top_cats as $cat) {
                                                render_category_list_item_for_modal($cat, 0);
                                            }
                                        }
                                        ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="list-group small">
                            <?php
                            $subscribed = get_user_meta( $user->ID, 'mevzu_subscribed_categories', true );
                            if ( ! empty( $subscribed ) && is_array( $subscribed ) ) {
                                $categories = get_categories( array( 'include' => $subscribed, 'hide_empty' => 0 ) );
                                if ( ! empty( $categories ) ) {
                                    // Ağaç yapısı oluştur (sadece takip edilenler arasında)
                                    $tree = array();
                                    foreach ($categories as $cat) {
                                        if ($cat->parent == 0 || !in_array($cat->parent, $subscribed)) {
                                            $tree[0][] = $cat;
                                        } else {
                                            $tree[$cat->parent][] = $cat;
                                        }
                                    }

                                    if (!function_exists('render_subscribed_categories')) {
                                        function render_subscribed_categories($parent_id, $tree, $level = 0) {
                                            if (!isset($tree[$parent_id])) return;
                                            
                                            foreach ($tree[$parent_id] as $cat) {
                                                $padding = $level * 20 + 16; // 16px base padding
                                                $cat_color_field = get_term_meta($cat->term_id, 'cat_renk', true);
                                                $cat_color = $cat_color_field ? $cat_color_field : 'primary';
                                                
                                                echo '<div class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3" style="padding-left: '.$padding.'px;">';
                                                
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<div class="bg-'.$cat_color.' bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3" style="width:36px; height:36px;">';
                                                echo '<i class="ri-folder-open-line fz-18 text-'.$cat_color.'"></i>';
                                                echo '</div>';
                                                echo '<div>';
                                                echo '<a href="'.esc_url(get_category_link($cat->term_id)).'" class="text-dark text-decoration-none fw-semibold text-hover d-block lh-sm mb-1">'.esc_html($cat->name).'</a>';
                                                echo '<span class="small-2 text-muted fw-normal">Haber sayısı: '.esc_html($cat->count).'</span>';
                                                echo '</div>';
                                                echo '</div>';
                                                
                                                echo '<button class="btn btn-outline-dark btn-sm py-1 px-3 border-1 fw-semibold rounded-pill mevzu-follow-category" data-cat-id="'.esc_attr($cat->term_id).'"><i class="ri-close-line align-middle"></i> <span class="align-middle">Takipten Çık</span></button>';
                                                echo '</div>';

                                                render_subscribed_categories($cat->term_id, $tree, $level + 1);
                                            }
                                        }
                                    }

                                    render_subscribed_categories(0, $tree, 0);
                                } else {
                                    echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir kategori bulunmuyor.</div>';
                                }
                            } else {
                                echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir kategori bulunmuyor.</div>';
                            }
                            ?>
                        </div>
                    <?php elseif ( $tab === 'takipyazar' ) : ?>
                        <h1 class="mx-0 mb-0">Takip Ettiğim Yazarlar</h1>
                        <div class="list-group small">
                            <?php
                            $subscribed_authors = get_user_meta( $user->ID, 'mevzu_subscribed_authors', true );
                            if ( ! empty( $subscribed_authors ) && is_array( $subscribed_authors ) ) {
                                $authors = get_users( array( 'include' => $subscribed_authors ) );
                                if ( ! empty( $authors ) ) {
                                    foreach ( $authors as $author ) {
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
                                            <span class="d-flex align-items-center">
                                                <div class="me-3"><?php echo get_avatar($author->ID, 40, '', '', array('class' => 'rounded-circle shadow-sm object-fit-cover', 'style' => 'width:40px;height:40px;')); ?></div>
                                                <a href="<?php echo esc_url( get_author_posts_url( $author->ID ) ); ?>" class="text-dark text-decoration-none fw-semibold text-hover"><?php echo esc_html( $author->display_name ); ?></a>
                                            </span>
                                            <button class="btn btn-outline-dark btn-sm py-2 px-3 border-1 fw-semibold rounded-pill mevzu-follow-author" data-author-id="<?php echo esc_attr( $author->ID ); ?>"><i class="ri-close-line"></i> Takipten Çık</button>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir yazar bulunmuyor.</div>';
                                }
                            } else {
                                echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir yazar bulunmuyor.</div>';
                            }
                            ?>
                        </div>
                    <?php elseif ( $tab === 'etkilesimler' ) : ?>
                        <h1 class="mx-0 mb-0">Etiket Takip</h1>
                        <form method="post" action="" class="border-bottom">
                            <?php wp_nonce_field('mevzu_add_tag_subs', 'm_add_tag_subs_nonce'); ?>
                            <div class="p-3 small">
                                <p class="small text-muted">İlgilendiğin etiketlere yenilereni eklemek ister misin? Tercihlerine göre sana özel bir akış sunacağız!</p>
                                <div class="row g-2">
                                    <div class="col-8 col-lg">
                                        <select name="new_tag_subs[]" class="form-control select2-tags w-100" multiple="multiple" data-placeholder="Etiket ara ve ekle..."></select>
                                    </div>
                                    <div class="col-4 col-lg-auto d-flex gap-2">
                                        <button type="submit" name="mevzu_submit_tag_subs" class="btn btn-primary btn-sm fw-semibold rounded shadow-sm text-capitalize px-3 py-1 d-flex align-items-center"><i class="ri-add-fill fz-14 me-1"></i>Takip Et</button>
                                    </div>
                                    <div class="col-12 col-lg-auto d-lg-flex text-center">
                                        <span class="text-muted d-lg-flex align-items-center small-2 px-2">yada</span>
                                    </div>
                                    <div class="col-12 col-lg-auto d-lg-flex">
                                        <button type="button" class="btn btn-outline-dark btn-sm fw-semibold rounded shadow-sm text-capitalize px-3 py-2 d-lg-flex align-items-center w-100 justify-content-center" data-bs-toggle="collapse" data-bs-target="#popularTagsCollapse" aria-expanded="false" aria-controls="popularTagsCollapse"><i class="ri-fire-fill me-1"></i> Popüler Etiketlerde Ara</button>
                                    </div>
                                </div>
                                <div class="collapse mt-3" id="popularTagsCollapse">
                                    <h6 class="fw-bold mt-3 fz-15">En Popüler Etiketler</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php
                                        $popular_tags = get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 30));
                                        if ($popular_tags) {
                                            foreach ($popular_tags as $tag_item) {
                                                echo '<div class="d-inline-flex bg-primary overflow-hidden align-items-center kategori-dugme-takip rounded">';
                                                echo '<a class="px-2 py-1 fw-semibold text-decoration-none small text-white text-capitalize" href="' . esc_url( get_tag_link( $tag_item->term_id ) ) . '">' . esc_html($tag_item->name) . ' <span class="opacity-75 small-2 fw-normal">(' . esc_html($tag_item->count) . ')</span></a>';
                                                if ( function_exists('mevzu_render_tag_follow_button') ) {
                                                    echo '<div class="pe-1 d-flex align-items-center">';
                                                    mevzu_render_tag_follow_button($tag_item->term_id, false, true);
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="list-group small">
                            <?php
                            $subscribed = get_user_meta( $user->ID, 'mevzu_subscribed_tags', true );
                            if ( ! empty( $subscribed ) && is_array( $subscribed ) ) {
                                $tags = get_tags( array( 'include' => $subscribed, 'hide_empty' => 0 ) );
                                if ( ! empty( $tags ) ) {
                                    foreach ( $tags as $tag ) {
                                        echo '<div class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3 px-3">';
                                        
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<div class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3" style="width:36px; height:36px;">';
                                        echo '<i class="ri-hashtag fz-18 text-primary"></i>';
                                        echo '</div>';
                                        echo '<div>';
                                        echo '<a href="'.esc_url(get_tag_link($tag->term_id)).'" class="text-dark text-decoration-none fw-semibold text-hover d-block lh-sm mb-1">'.esc_html($tag->name).'</a>';
                                        echo '<span class="small-2 text-muted fw-normal">Haber sayısı: '.esc_html($tag->count).'</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        echo '<button class="btn btn-outline-dark btn-sm py-1 px-3 border-1 fw-semibold rounded-pill mevzu-follow-tag" data-tag-id="'.esc_attr($tag->term_id).'"><i class="ri-close-line align-middle"></i> <span class="align-middle">Takipten Çık</span></button>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir etiket bulunmuyor.</div>';
                                }
                            } else {
                                echo '<div class="p-3 py-5 small text-muted text-center">Takip ettiğiniz bir etiket bulunmuyor.</div>';
                            }
                            ?>
                        </div>
                    <?php elseif ( $tab === 'yorumlarim' ) : ?>
                        <h1 class="mx-0 mb-0">Yorumlarım</h1>
                        <div class="list-group small">
                            <?php
                            $comments_args = array(
                                'user_id' => $user->ID,
                                'status' => 'all', // Get approved, unapproved, etc.
                                'post_status' => 'publish', // Sadece yayında olan haberlerin yorumları
                                'post_type' => 'post', // Sadece blog postlarındaki yorumlar
                                'number' => 20 // Let's limit to recent 20 for now
                            );
                            $user_comments = get_comments( $comments_args );

                            // Yorumları tutacağımız sayaç
                            $displayed_comments_count = 0;

                            if ( ! empty( $user_comments ) ) {
                                foreach ( $user_comments as $comment ) {
                                    $post_id = (int) $comment->comment_post_ID;

                                    // Gönderi gerçekten var mı kontrol edelim
                                    $post_title = get_the_title( $post_id );
                                    $post_link = get_permalink( $post_id );

                                    if ( empty($post_title) || empty($post_link) ) {
                                        continue; // Başlığı veya linki olmayan, silinmiş postların yorumlarını gösterme
                                    }

                                    $displayed_comments_count++;

                                    $status_badge = '';
                                    if ( $comment->comment_approved == '0' ) {
                                        $status_badge = '<span class="badge bg-warning small">Onay Bekliyor</span><br>';
                                    }
                                    ?>
                                    <div class="list-group-item p-3 border-0 border-bottom">
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">
                                                <i class="ri-time-line align-middle"></i> <?php echo get_comment_date( 'd M Y H:i', $comment->comment_ID ); ?>
                                            </small>
                                            <h6 class="mb-1 fw-semibold">
                                                <a href="<?php echo esc_url( $post_link ); ?>" class="text-dark text-decoration-none text-hover d-block">
                                                    <i class="ri-article-line me-1 text-primary align-middle"></i> <?php echo esc_html( $post_title ); ?>
                                                </a>
                                            </h6>
                                        </div>
                                        <div class="ps-3 fw-medium position-relative border-start border-3 border-dark">
                                            <?php echo $status_badge; ?>
                                            <?php echo esc_html( wp_trim_words( $comment->comment_content, 30, '...' ) ); ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            
                            if ( empty( $user_comments ) || $displayed_comments_count === 0 ) {
                                echo '<div class="p-3 py-5 small text-muted text-center">Yayında olan haberlerde henüz hiç yorum yapmamışsınız.</div>';
                            }
                            ?>
                        </div>
                    <?php elseif ( $tab === 'bildirimlerim' ) : ?>
                        <div class="d-flex justify-content-between align-items-center mt-2 me-2">
                            <h1 class="my-0">Bildirimlerim</h1>
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center" id="mevzu-mark-all-read"><i class="ri-check-double-line me-1 fs-6"></i>Tümünü Oku</button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-flex align-items-center" id="mevzu-delete-all-notifications"><i class="ri-delete-bin-line me-1 fs-6"></i>Tümünü Sil</button>
                            </div>
                        </div>
                        <div class="list-group small">
                            <?php
                            if ( function_exists('mevzu_get_user_notifications') ) {
                                // Fetch up to 30 notifications
                                $notifications = mevzu_get_user_notifications( $user->ID, null, 30, false );
                                
                                if ( ! empty( $notifications ) ) {
                                    foreach ( $notifications as $notif ) {
                                        $icon = 'ri-notification-3-fill text-primary';
                                        $bg_class = $notif->is_read ? 'bg-transparent' : 'bg-light';
                                        $post_link = get_permalink($notif->post_id);

                                        if ( $notif->type === 'comment_reply' ) {
                                            $icon = 'ri-reply-fill text-success';
                                            $post_link .= '#comment-' . $notif->comment_id;
                                        } else if ( $notif->type === 'category_post' ) {
                                            $icon = 'ri-article-fill text-info';
                                        }
                                        
                                        // Handle AJAX read marking implicitly via URL visit, but also give visual cues
                                        $read_status = $notif->is_read ? '' : '<span class="badge bg-danger ms-2" style="font-size: 0.65rem;">YENİ</span>';
                                        $unread_class = $notif->is_read ? '' : 'unread bg-info bg-opacity-10';
                                        ?>
                                        <a href="<?php echo esc_url( $post_link ); ?>" class="list-group-item list-group-item-action p-3 border-0 border-bottom <?php echo $bg_class; ?> mevzu-notification-item <?php echo $unread_class; ?>" data-notif-id="<?php echo esc_attr( $notif->id ); ?>">
                                            <div class="d-flex w-100 align-items-center position-relative">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="m-0 d-flex align-items-center justify-content-center">
                                                        <i class="<?php echo esc_attr($icon); ?> fz-20"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 pe-4">
                                                    <p class="mb-0 text-dark">
                                                        <?php echo wp_kses_post($notif->message); ?>
                                                        <?php echo $read_status; ?>
                                                    </p>
                                                    <small class="text-muted"><i class="ri-time-line align-middle"></i> <?php echo date('d M Y H:i', strtotime($notif->date_created)); ?></small>
                                                </div>
                                                <button class="btn btn-sm btn-link text-danger position-absolute end-0 top-50 translate-middle-y rounded-circle p-2 shadow-none mevzu-delete-notification z-3" data-notif-id="<?php echo esc_attr($notif->id); ?>" data-bs-toggle="tooltip" data-bs-title="Bildirimi Sil">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </a>
                                        <?php
                                    }
                                } else {
                                    echo '<div class="p-3 py-5 small text-muted text-center">Henüz hiç bildiriminiz bulunmuyor.</div>';
                                }
                            } else {
                                echo '<div class="p-3 py-5 small text-muted text-center">Bildirim sistemi şu anda devredışı.</div>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Bottom Menu Trigger -->
    <div class="position-fixed bottom-0 start-0 w-100 d-lg-none d-flex justify-content-center p-3" style="pointer-events: none;">
        <button class="btn btn-primary rounded-pill shadow-lg px-4 d-inline-flex align-items-center fw-semibold text-white border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mevzuUserMenuOffcanvas" aria-controls="mevzuUserMenuOffcanvas" style="pointer-events: auto;">
            <i class="ri-menu-line me-2"></i> Kullanıcı Menüsü
        </button>
    </div>

    <style>
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .mevzu-avatar-wrapper:hover .mevzu-avatar-overlay { opacity: 1 !important; }
    </style>
    <?php
    $content = ob_get_clean();

    // Select2'yu düzgün şekilde enqueue edelim
    wp_enqueue_style( 'select2-custom', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
    wp_enqueue_style( 'select2-bootstrap-5', 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css' );
    wp_enqueue_script( 'select2-custom-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

    $inline_script = "
    jQuery(document).ready(function($) {
        if ($('.select2-categories').length) {
            $('.select2-categories').select2({
                theme: 'bootstrap-5',
                width: '100%',
                language: {
                    inputTooShort: function(args) { return 'En az ' + args.minimum + ' karakter girmelisiniz'; },
                    noResults: function() { return 'Kategori bulunamadı'; },
                    searching: function() { return 'Aranıyor...'; }
                },
                ajax: {
                    url: mevzu_membership.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            action: 'mevzu_search_categories'
                        };
                    },
                    processResults: function(data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                minimumInputLength: 2,
            });
        }
        if ($('.select2-tags').length) {
            $('.select2-tags').select2({
                theme: 'bootstrap-5',
                width: '100%',
                language: {
                    inputTooShort: function(args) { return 'En az ' + args.minimum + ' karakter girmelisiniz'; },
                    noResults: function() { return 'Etiket bulunamadı'; },
                    searching: function() { return 'Aranıyor...'; }
                },
                ajax: {
                    url: mevzu_membership.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            action: 'mevzu_search_tags'
                        };
                    },
                    processResults: function(data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                minimumInputLength: 2,
            });
        }
    });
    ";
    wp_add_inline_script( 'select2-custom-js', $inline_script );

    return $content;
}
