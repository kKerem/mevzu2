<?php
/**
 * Post and Comment Likes Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add Like AJAX Endpoint
add_action( 'wp_ajax_mevzu_toggle_like', 'mevzu_toggle_like' );
add_action( 'wp_ajax_nopriv_mevzu_toggle_like', 'mevzu_toggle_like' );
function mevzu_toggle_like() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'post'; // 'post' or 'comment'
    $user_id = get_current_user_id();
    $item_meta_key = 'mevzu_likes_count';

    // Guest (non-logged-in) like handling
    if ( $user_id === 0 ) {
        $like_public = get_option( 'mevzu_like_public_enabled', '0' );
        if ( $like_public !== '1' || $item_id <= 0 ) {
            wp_send_json_error();
        }

        // Cookie-based tracking for guests
        $cookie_key = 'mevzu_guest_likes_' . $type;
        $guest_likes = isset( $_COOKIE[ $cookie_key ] ) ? json_decode( stripslashes( $_COOKIE[ $cookie_key ] ), true ) : array();
        if ( ! is_array( $guest_likes ) ) {
            $guest_likes = array();
        }

        if ( in_array( $item_id, $guest_likes ) ) {
            // Unlike
            $guest_likes = array_values( array_diff( $guest_likes, array( $item_id ) ) );
            $action = 'unliked';
            if ( $type === 'comment' ) {
                $count = (int) get_comment_meta( $item_id, $item_meta_key, true );
                update_comment_meta( $item_id, $item_meta_key, max( 0, $count - 1 ) );
            } else {
                $count = (int) get_post_meta( $item_id, $item_meta_key, true );
                update_post_meta( $item_id, $item_meta_key, max( 0, $count - 1 ) );
            }
        } else {
            // Like
            $guest_likes[] = $item_id;
            $action = 'liked';
            if ( $type === 'comment' ) {
                $count = (int) get_comment_meta( $item_id, $item_meta_key, true );
                update_comment_meta( $item_id, $item_meta_key, $count + 1 );
            } else {
                $count = (int) get_post_meta( $item_id, $item_meta_key, true );
                update_post_meta( $item_id, $item_meta_key, $count + 1 );
            }
        }

        // Set cookie for 30 days
        setcookie( $cookie_key, json_encode( $guest_likes ), time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

        $new_count = ( $type === 'comment' ) ? get_comment_meta( $item_id, $item_meta_key, true ) : get_post_meta( $item_id, $item_meta_key, true );
        wp_send_json_success( array( 'action' => $action, 'count' => (int) $new_count ) );
    }

    // Logged-in user like handling
    if ( $item_id > 0 && $user_id > 0 ) {
        
        $user_meta_key = ( $type === 'comment' ) ? 'mevzu_liked_comments' : 'mevzu_liked_posts';

        $liked_items = get_user_meta( $user_id, $user_meta_key, true );
        if ( ! is_array( $liked_items ) ) {
            $liked_items = array();
        }

        if ( in_array( $item_id, $liked_items ) ) {
            // Remove Like
            $liked_items = array_diff( $liked_items, array( $item_id ) );
            $action = 'unliked';
            
            // Decrement item meta
            if ( $type === 'comment' ) {
                $count = (int) get_comment_meta( $item_id, $item_meta_key, true );
                update_comment_meta( $item_id, $item_meta_key, max(0, $count - 1) );
            } else {
                $count = (int) get_post_meta( $item_id, $item_meta_key, true );
                update_post_meta( $item_id, $item_meta_key, max(0, $count - 1) );
            }
        } else {
            // Add Like
            $liked_items[] = $item_id;
            $action = 'liked';

            // Increment item meta
            if ( $type === 'comment' ) {
                $count = (int) get_comment_meta( $item_id, $item_meta_key, true );
                update_comment_meta( $item_id, $item_meta_key, $count + 1 );
                
                // Add notification to comment author if not current user
                $comment = get_comment( $item_id );
                if ( $comment && $comment->user_id && $comment->user_id != $user_id ) {
                    $post = get_post( $comment->comment_post_ID );
                    $sender = get_userdata( $user_id );
                    $message = sprintf( '<strong>%s</strong>, "<a href="%s">%s</a>" haberindeki yorumunuzu beğendi.', esc_html($sender->display_name), esc_url(get_comment_link($comment)), esc_html(mb_substr($comment->comment_content, 0, 30)) . '...' );
                    mevzu_add_notification( $comment->user_id, $post->ID, $item_id, 'comment_like', $message );
                }
            } else {
                $count = (int) get_post_meta( $item_id, $item_meta_key, true );
                update_post_meta( $item_id, $item_meta_key, $count + 1 );
                
                // Add notification to post author if not current user
                $post = get_post( $item_id );
                if ( $post && $post->post_author && $post->post_author != $user_id ) {
                    $sender = get_userdata( $user_id );
                    $message = sprintf( '<strong>%s</strong>, "<a href="%s">%s</a>" adlı içeriğinizi beğendi.', esc_html($sender->display_name), esc_url(get_permalink( $post->ID )), esc_html( $post->post_title ) );
                    mevzu_add_notification( $post->post_author, $item_id, 0, 'post_like', $message );
                }
            }
        }

        update_user_meta( $user_id, $user_meta_key, $liked_items );
        
        $new_count = ( $type === 'comment' ) ? get_comment_meta( $item_id, $item_meta_key, true ) : get_post_meta( $item_id, $item_meta_key, true );

        wp_send_json_success( array( 'action' => $action, 'count' => (int) $new_count ) );
    }
    wp_send_json_error();
}

// Check if user liked an item
function mevzu_is_user_liked_item( $user_id, $item_id, $type = 'post' ) {
    $user_meta_key = ( $type === 'comment' ) ? 'mevzu_liked_comments' : 'mevzu_liked_posts';
    $liked_items = get_user_meta( $user_id, $user_meta_key, true );
    if ( ! is_array( $liked_items ) ) {
        return false;
    }
    return in_array( $item_id, $liked_items );
}

// Render the Like button
function mevzu_render_like_button( $item_id, $type = 'post' ) {
    $item_meta_key = 'mevzu_likes_count';
    $count = ( $type === 'comment' ) ? (int) get_comment_meta( $item_id, $item_meta_key, true ) : (int) get_post_meta( $item_id, $item_meta_key, true );

    if ( ! is_user_logged_in() ) {
        $like_public = get_option( 'mevzu_like_public_enabled', '0' );
        if ( $like_public === '1' ) {
            // Guest can like - check cookie for liked state
            $cookie_key = 'mevzu_guest_likes_' . $type;
            $guest_likes = isset( $_COOKIE[ $cookie_key ] ) ? json_decode( stripslashes( $_COOKIE[ $cookie_key ] ), true ) : array();
            if ( ! is_array( $guest_likes ) ) {
                $guest_likes = array();
            }
            $is_liked = in_array( $item_id, $guest_likes );
            $btn_class = $is_liked ? ' bg-primary text-white border-0' : '';
            $icon = $is_liked ? '<i class="ri-thumb-up-fill fz-16 me-1"></i>' : '<i class="ri-thumb-up-line fz-16 me-1"></i>';
            echo '<a href="#" class="ripple btn btn-outline-secondary btn-sm fw-bolder rounded-pill py-1 px-3 mevzu-toggle-like h-32 d-inline-flex align-items-center gap-2 bg-white' . esc_attr($btn_class) . '" data-item-id="' . esc_attr($item_id) . '" data-type="' . esc_attr($type) . '">' . $icon . ' <span class="count">' . esc_html($count) . '</span></a>';
        } else {
            // Just show count without button action
            echo '<div class="d-inline-flex align-items-center text-muted gap-2"><i class="ri-thumb-up-line fz-16 me-1"></i> <span>' . esc_html($count) . '</span></div>';
        }
        return;
    }

    $user_id = get_current_user_id();
    $is_liked = mevzu_is_user_liked_item( $user_id, $item_id, $type );
    
    $btn_class = $is_liked ? ' bg-primary text-white border-0' : '';
    $icon = $is_liked ? '<i class="ri-thumb-up-fill fz-16 me-1"></i>' : '<i class="ri-thumb-up-line fz-16 me-1"></i>';

    echo '<a href="#" class="ripple btn btn-outline-secondary btn-sm fw-bolder rounded-pill py-1 px-3 mevzu-toggle-like h-32 d-inline-flex align-items-center gap-2 bg-white' . esc_attr($btn_class) . '" data-item-id="' . esc_attr($item_id) . '" data-type="' . esc_attr($type) . '">' . $icon . ' <span class="count">' . esc_html($count) . '</span></a>';
}
