<?php
/**
 * Author Subscription Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add Follow Author AJAX Endpoint
add_action( 'wp_ajax_mevzu_toggle_author_subscription', 'mevzu_toggle_author_subscription' );
function mevzu_toggle_author_subscription() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $author_id = isset( $_POST['author_id'] ) ? intval( $_POST['author_id'] ) : 0;
    $user_id = get_current_user_id();

    if ( $author_id > 0 && $user_id > 0 && $author_id !== $user_id ) {
        $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_authors', true );
        if ( ! is_array( $subscribed ) ) {
            $subscribed = array();
        }

        if ( in_array( $author_id, $subscribed ) ) {
            // Un-subscribe
            $subscribed = array_diff( $subscribed, array( $author_id ) );
            $action = 'unsubscribed';
        } else {
            // Subscribe
            $subscribed[] = $author_id;
            $action = 'subscribed';
        }

        update_user_meta( $user_id, 'mevzu_subscribed_authors', $subscribed );
        wp_send_json_success( array( 'action' => $action ) );
    }
    wp_send_json_error();
}

// Check if user is subscribed to an author
function mevzu_is_user_subscribed_to_author( $user_id, $author_id ) {
    $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_authors', true );
    if ( ! is_array( $subscribed ) ) {
        return false;
    }
    return in_array( $author_id, $subscribed );
}

// Render the Follow button
function mevzu_render_author_follow_button( $author_id ) {
    if ( ! is_user_logged_in() || get_current_user_id() == $author_id ) {
        return;
    }

    $user_id = get_current_user_id();
    $is_subscribed = mevzu_is_user_subscribed_to_author( $user_id, $author_id );
    
    $btn_class = $is_subscribed ? 'btn-dark' : 'btn-outline-dark';
    $btn_text = $is_subscribed ? 'Takipten Çık' : 'Takip Et';
    $icon = $is_subscribed ? '<i class="ri-user-unfollow-line fz-12"></i>' : '<i class="ri-user-follow-line fz-12"></i>';

    echo '<button class="btn btn-sm rounded-pill py-1 px-3 text-capitalize shadow-none ' . esc_attr($btn_class) . ' mevzu-follow-author" data-author-id="' . esc_attr($author_id) . '">' . $icon . ' <span class="text">' . esc_html($btn_text) . '</span></button>';
}
add_shortcode('mevzu_author_follow_button', function($atts) {
    $atts = shortcode_atts(array('id' => get_the_author_meta('ID')), $atts);
    ob_start();
    mevzu_render_author_follow_button($atts['id']);
    return ob_get_clean();
});

// Automatically create notifications on post publish
add_action( 'transition_post_status', 'mevzu_send_author_notifications', 10, 3 );
function mevzu_send_author_notifications( $new_status, $old_status, $post ) {
    // Only fire if publishing a new post
    if ( 'publish' === $new_status && 'publish' !== $old_status && $post->post_type === 'post' ) {
        
        $author_id = $post->post_author;
        if ( ! $author_id ) {
            return;
        }

        // Find all users who subscribed to this author
        global $wpdb;
        $subscribed_users = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'mevzu_subscribed_authors'" );
        
        $author_name = get_the_author_meta( 'display_name', $author_id );

        foreach ( $subscribed_users as $row ) {
            $user_id = $row->user_id;
            // Unserialize
            $user_authors = maybe_unserialize( $row->meta_value );
            if ( ! is_array( $user_authors ) ) continue;

            if ( in_array( $author_id, $user_authors ) ) {
                
                // Add to notification queue
                $message = sprintf( 'Takip ettiğiniz yazar "<strong>%s</strong>" yeni bir yazı yayınladı: <a href="%s">%s</a>', esc_html($author_name), esc_url(get_permalink( $post->ID )), esc_html( $post->post_title ) );
                
                mevzu_add_notification( $user_id, $post->ID, 0, 'author_post', $message );
            }
        }
    }
}
