<?php
/**
 * Auth module for User Profile functionality.
 * Handles update requests for profile data if needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Redirect non-logged in users from specific profile pages if they exist
add_action( 'template_redirect', 'mevzu_membership_redirects' );
function mevzu_membership_redirects() {
    if ( get_query_var( 'is_user_panel' ) && ! is_user_logged_in() ) {
        wp_redirect( home_url( '/hesabim/giris' ) );
        exit;
    }
}

// Handle form submits
add_action('template_redirect', 'mevzu_handle_auth_forms');
function mevzu_handle_auth_forms() {
    if ( isset($_POST['mevzu_login_submit']) && isset($_POST['mevzu_login_nonce']) && wp_verify_nonce($_POST['mevzu_login_nonce'], 'mevzu_login_action') ) {
        $creds = array(
            'user_login'    => sanitize_user($_POST['user_login']),
            'user_password' => $_POST['user_password'],
            'remember'      => isset($_POST['rememberme'])
        );
        $user = wp_signon($creds, is_ssl());
        if ( is_wp_error($user) ) {
            set_transient('mevzu_auth_error', $user->get_error_message(), 30);
        } else {
            wp_redirect( home_url('/hesabim/') );
            exit;
        }
    }

    if ( isset($_POST['mevzu_register_submit']) && isset($_POST['mevzu_register_nonce']) && wp_verify_nonce($_POST['mevzu_register_nonce'], 'mevzu_register_action') ) {
        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_password'];
        $display_name = sanitize_text_field($_POST['display_name']);

        $error = '';
        if ( username_exists($username) ) {
            $error = 'Bu kullanıcı adı alınmış.';
        } elseif ( email_exists($email) ) {
            $error = 'Bu e-posta adresi ile zaten kayıtlı bir hesap var.';
        } elseif ( empty($password) || strlen($password) < 6 ) {
            $error = 'Şifreniz en az 6 karakter olmalıdır.';
        }

        if ( !empty($error) ) {
            set_transient('mevzu_auth_error', $error, 30);
        } else {
            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'display_name' => $display_name,
                'role'       => 'subscriber'
            ));

            if ( is_wp_error($user_id) ) {
                set_transient('mevzu_auth_error', $user_id->get_error_message(), 30);
            } else {
                $creds = array(
                    'user_login'    => $username,
                    'user_password' => $password,
                    'remember'      => true
                );
                wp_signon($creds, is_ssl());
                wp_redirect( home_url('/hesabim/') );
                exit;
            }
        }
    }
}

// Add user panel query var and rewrite rule
add_action('init', 'mevzu_membership_rewrite_rules');
function mevzu_membership_rewrite_rules() {
    add_rewrite_rule(
        '^hesabim/giris/?$',
        'index.php?is_user_auth=login',
        'top'
    );
    add_rewrite_rule(
        '^hesabim/kayit/?$',
        'index.php?is_user_auth=register',
        'top'
    );
    add_rewrite_rule(
        '^hesabim/([^/]+)/?$',
        'index.php?is_user_panel=1&mevzu_tab=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^hesabim/?$',
        'index.php?is_user_panel=1',
        'top'
    );
    add_rewrite_rule(
        '^akis/page/([0-9]{1,})/?$',
        'index.php?is_user_feed=1&paged=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^akis/?$',
        'index.php?is_user_feed=1',
        'top'
    );
    flush_rewrite_rules(false); // Temporary flush for pagination rules
}

add_filter('query_vars', 'mevzu_membership_query_vars');
function mevzu_membership_query_vars($query_vars) {
    $query_vars[] = 'is_user_panel';
    $query_vars[] = 'mevzu_tab';
    $query_vars[] = 'is_user_auth';
    $query_vars[] = 'is_user_feed';
    return $query_vars;
}

add_action('template_include', 'mevzu_membership_template_include');
function mevzu_membership_template_include( $template ) {
    $auth_action = get_query_var( 'is_user_auth' );
    if ( $auth_action ) {
        if ( is_user_logged_in() ) {
            wp_redirect(home_url('/hesabim/'));
            exit;
        }

        get_header();
        echo '<div class="mevzu-auth-page py-5 my-md-5 container"><div class="row justify-content-center"><div class="col-12 col-md-6 col-lg-4">';
        
        $error = get_transient('mevzu_auth_error');
        if ( $error ) {
            echo '<div class="alert alert-danger shadow-sm border-0 small fw-medium">' . wp_kses_post($error) . '</div>';
            delete_transient('mevzu_auth_error');
        }

        if ( $auth_action === 'login' ) {
            ?>
            <div class="tema-widget bg-white shadow-sm rounded-3 mt-3 mt-lg-4 mx-2 mx-md-0">
                <h1 class="mb-0 text-center fw-bold">Giriş Yap</h1>
                <div class="p-3">
                    <form method="post" action="">
                        <?php wp_nonce_field('mevzu_login_action', 'mevzu_login_nonce'); ?>
                        <div class="mb-3">
                            <label class="form-label text-muted small-2 fw-normal">E-Posta veya Kullanıcı Adı</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-user-line"></i></span>
                                <input type="text" name="user_login" class="form-control bg-transparent border-0 p-0 shadow-none outline-none" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small-2 fw-normal">Şifre</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-lock-2-line"></i></span>
                                <input type="password" name="user_password" class="form-control bg-transparent border-0 p-0 shadow-none outline-none" required>
                            </div>
                        </div>
                        <div class="mb-4 form-check ps-0 d-flex align-items-center">
                            <input type="checkbox" name="rememberme" class="form-check-input me-2 mt-0" id="rememberme" value="forever" style="margin-left: 0;">
                            <label class="form-check-label text-muted small" for="rememberme">Beni Hatırla</label>
                        </div>
                        <button type="submit" name="mevzu_login_submit" class="btn btn-primary w-100 py-2 rounded fw-bold text-uppercase fs-6 text-capitalize">Giriş Yap</button>
                    </form>
                </div>
            </div>

            <div class="d-flex align-items-center my-3">
                <hr class="flex-grow-1 m-0 opacity-25">
                <span class="px-3 text-muted small-2 fw-normal">veya şununla devam et</span>
                <hr class="flex-grow-1 m-0 opacity-25">
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <a href="<?php echo esc_url(add_query_arg('kkerem-sl-login', 'google', wp_login_url())); ?>" class="btn btn-light border-0 text-capitalize shadow-sm w-100 py-2 d-flex align-items-center justify-content-center fw-semibold text-dark rounded"><i class="ri-google-fill text-danger fs-5 me-2"></i> Google</a>
                </div>
                <div class="col-6">
                    <a href="<?php echo esc_url(add_query_arg('kkerem-sl-login', 'facebook', wp_login_url())); ?>" class="btn btn-light border-0 text-capitalize shadow-sm w-100 py-2 d-flex align-items-center justify-content-center fw-semibold text-dark rounded" style="color: #1877F2 !important;"><i class="ri-facebook-circle-fill fs-5 me-2"></i> Facebook</a>
                </div>
            </div>
            <div class="mt-4 text-center small-2">
                <span class="text-muted">Hesabınız yok mu?</span> <a href="<?php echo esc_url(home_url('/hesabim/kayit')); ?>" class="text-decoration-underline fw-bold text-primary">Hemen Kayıt Ol</a>
            </div>
            <?php
        } elseif ( $auth_action === 'register' ) {
            ?>
            <div class="tema-widget bg-white shadow-sm rounded-3 mt-3 mt-lg-4 mx-2 mx-md-0">
                <h1 class="mb-0 text-center fw-bold">Kayıt Ol</h1>
                <div class="p-3">
                    <form method="post" action="">
                        <?php wp_nonce_field('mevzu_register_action', 'mevzu_register_nonce'); ?>
                        <div class="mb-3">
                            <label class="form-label text-muted small-2 fw-normal">Ad Soyad</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-user-smile-line"></i></span>
                                <input type="text" name="display_name" class="form-control bg-transparent border-0 p-0 shadow-none" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small-2 fw-normal">Kullanıcı Adı</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-user-line"></i></span>
                                <input type="text" name="user_login" class="form-control bg-transparent border-0 p-0 shadow-none" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small-2 fw-normal">E-Posta Adresi</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-mail-line"></i></span>
                                <input type="email" name="user_email" class="form-control bg-transparent border-0 p-0 shadow-none" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small-2 fw-normal">Şifre Belirle</label>
                            <div class="input-group bg-light rounded px-3 py-2 border align-items-center">
                                <span class="input-group-text bg-transparent border-0 p-0 me-2 text-secondary"><i class="ri-lock-2-line"></i></span>
                                <input type="password" name="user_password" class="form-control bg-transparent border-0 p-0 shadow-none" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="mevzu_register_submit" class="btn btn-primary w-100 py-2 rounded fw-bold text-uppercase fs-6 text-capitalize">Kayıt Ol</button>
                    </form>
                </div>
            </div>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 m-0 opacity-25">
                        <span class="px-3 text-muted small-2 fw-normal">veya şununla kayıt ol</span>
                        <hr class="flex-grow-1 m-0 opacity-25">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <a href="<?php echo esc_url(add_query_arg('kkerem-sl-login', 'google', wp_login_url())); ?>" class="btn btn-light border-0 text-capitalize shadow-sm w-100 py-2 d-flex align-items-center justify-content-center fw-semibold text-dark rounded"><i class="ri-google-fill text-danger fs-5 me-2"></i> Google</a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo esc_url(add_query_arg('kkerem-sl-login', 'facebook', wp_login_url())); ?>" class="btn btn-light border-0 text-capitalize shadow-sm w-100 py-2 d-flex align-items-center justify-content-center fw-semibold text-dark rounded" style="color: #1877F2 !important;"><i class="ri-facebook-circle-fill fs-5 me-2"></i> Facebook</a>
                        </div>
                    </div>
                    <div class="mt-4 text-center small-2">
                        <span class="text-muted">Zaten hesabınız var mı?</span> <a href="<?php echo esc_url(home_url('/hesabim/giris')); ?>" class="text-decoration-underline fw-bold text-primary">Giriş Yap</a>
                    </div>
            <?php
        }

        echo '</div></div></div>';
        get_footer();
        exit;
    }

    if ( get_query_var( 'is_user_panel' ) ) {
        // Output headers, the shortcode and footer, then exit.
        get_header();
        echo '<div class="mevzu-user-panel-wrapper">';
        echo do_shortcode('[mevzu_user_panel]');
        echo '</div>';
        get_footer();
        exit;
    }
    
    if ( get_query_var( 'is_user_feed' ) ) {
        $feed_template = get_template_directory() . '/page-akis.php';
        if ( file_exists( $feed_template ) ) {
            return $feed_template;
        }
    }
    
    return $template;
}
