<?php
/**
 * Bookmarks module for user profiles
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add Bookmark AJAX Endpoint
add_action( 'wp_ajax_mevzu_toggle_bookmark', 'mevzu_toggle_bookmark' );
function mevzu_toggle_bookmark() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $user_id = get_current_user_id();

    if ( $post_id > 0 && $user_id > 0 ) {
        $bookmarked = get_user_meta( $user_id, 'mevzu_bookmarked_posts', true );
        if ( ! is_array( $bookmarked ) ) {
            $bookmarked = array();
        }

        if ( in_array( $post_id, $bookmarked ) ) {
            // Un-bookmark
            $bookmarked = array_diff( $bookmarked, array( $post_id ) );
            $action = 'unbookmarked';
        } else {
            // Bookmark
            $bookmarked[] = $post_id;
            $action = 'bookmarked';
        }

        update_user_meta( $user_id, 'mevzu_bookmarked_posts', $bookmarked );
        wp_send_json_success( array( 'action' => $action ) );
    }
    wp_send_json_error();
}

// Check if user has bookmarked a post
function mevzu_is_post_bookmarked( $user_id, $post_id ) {
    $bookmarked = get_user_meta( $user_id, 'mevzu_bookmarked_posts', true );
    if ( ! is_array( $bookmarked ) ) {
        return false;
    }
    return in_array( $post_id, $bookmarked );
}

// Render the Bookmark Button
function mevzu_render_bookmark_button( $post_id ) {
    if ( get_option('mevzu_membership_enabled', '1') !== '1' || ! is_user_logged_in() ) {
        return; // Alternatively, show a "Login to bookmark" link
    }

    $user_id = get_current_user_id();
    $is_bookmarked = mevzu_is_post_bookmarked( $user_id, $post_id );
    
    $btn_class = $is_bookmarked ? 'text-primary' : '';
    $icon = $is_bookmarked ? '<i class="ri-bookmark-2-fill fz-24"></i>' : '<i class="ri-bookmark-line fz-24"></i>';
    $btn_title = $is_bookmarked ? 'Yer İşaretini Kaldır' : 'Yer İşaretlerine Ekle';

    echo '<a href="javascript:void(0);" class="ripple nav-link rounded d-flex align-items-center gap-2 mevzu-toggle-bookmark position-relative ' . esc_attr($btn_class) . '" data-post-id="' . esc_attr($post_id) . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" data-bs-html="true" data-bs-title="<small>' . esc_attr($btn_title) . '</small>" style="right: -3px;margin-left:-3px">' . $icon . '</a>';
}
