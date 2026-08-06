<?php
/**
 * Membership and Notification System Initializer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if membership system is enabled in settings
$is_membership_enabled = get_option('mevzu_membership_enabled', '1');

// Include settings/admin pages regardless to allow toggling
require_once get_template_directory() . '/inc/membership/admin.php';

if ( $is_membership_enabled === '1' ) {
    require_once get_template_directory() . '/inc/membership/auth.php';
    require_once get_template_directory() . '/inc/membership/notifications.php';
    require_once get_template_directory() . '/inc/membership/category-subscription.php';
    require_once get_template_directory() . '/inc/membership/tag-subscription.php';
    require_once get_template_directory() . '/inc/membership/author-subscription.php';
    require_once get_template_directory() . '/inc/membership/likes.php';
    require_once get_template_directory() . '/inc/membership/bookmarks.php';
    require_once get_template_directory() . '/inc/membership/user-panel.php';
}

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'mevzu_membership_scripts');
function mevzu_membership_scripts() {
    if ( get_option('mevzu_membership_enabled', '1') === '1' ) {
        wp_enqueue_script( 'mevzu-membership-js', get_template_directory_uri() . '/inc/membership/membership.js', array('jquery'), _S_VERSION, true );
        wp_localize_script( 'mevzu-membership-js', 'mevzu_membership', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mevzu_membership_nonce' )
        ) );
    }
}

// Custom Avatar Filter
add_filter('get_avatar', 'mevzu_membership_custom_avatar', 10, 5);
function mevzu_membership_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $user = false;

    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );
    }

    if ( $user && is_object( $user ) ) {
        $avatar_url = mevzu_get_user_avatar_url($user->ID);
        if ($avatar_url) {
            $avatar = '<img src="' . esc_url($avatar_url) . '" class="avatar avatar-' . esc_attr($size) . ' photo object-fit-cover rounded-circle" height="' . esc_attr($size) . '" width="' . esc_attr($size) . '" alt="' . esc_attr($alt) . '" />';
        }
    }

    return $avatar;
}
