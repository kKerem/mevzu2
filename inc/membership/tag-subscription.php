<?php
/**
 * Tag Subscription Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add Follow Tag AJAX Endpoint
add_action( 'wp_ajax_mevzu_toggle_tag_subscription', 'mevzu_toggle_tag_subscription' );
function mevzu_toggle_tag_subscription() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $tag_id = isset( $_POST['tag_id'] ) ? intval( $_POST['tag_id'] ) : 0;
    $user_id = get_current_user_id();

    if ( $tag_id > 0 && $user_id > 0 ) {
        $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_tags', true );
        if ( ! is_array( $subscribed ) ) {
            $subscribed = array();
        }

        if ( in_array( $tag_id, $subscribed ) ) {
            // Un-subscribe
            $subscribed = array_diff( $subscribed, array( $tag_id ) );
            $action = 'unsubscribed';
        } else {
            // Subscribe
            $subscribed[] = $tag_id;
            $action = 'subscribed';
        }

        update_user_meta( $user_id, 'mevzu_subscribed_tags', $subscribed );
        wp_send_json_success( array( 'action' => $action ) );
    }
    wp_send_json_error();
}

// Add AJAX Search for Tags
add_action( 'wp_ajax_mevzu_search_tags', 'mevzu_search_tags' );
function mevzu_search_tags() {
    $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    
    $args = array(
        'taxonomy'   => 'post_tag',
        'hide_empty' => false,
        'search'     => $search,
        'number'     => 20
    );
    
    $terms = get_terms($args);
    $results = array();
    
    if ( ! is_wp_error($terms) && ! empty($terms) ) {
        foreach ( $terms as $term ) {
            $results[] = array(
                'id' => $term->term_id,
                'text' => $term->name
            );
        }
    }
    
    wp_send_json( array('results' => $results) );
}

// Check if user is subscribed to a tag
function mevzu_is_user_subscribed_to_tag( $user_id, $tag_id ) {
    $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_tags', true );
    if ( ! is_array( $subscribed ) ) {
        return false;
    }
    return in_array( $tag_id, $subscribed );
}

// Render the Follow button
function mevzu_render_tag_follow_button( $tag_id, $show_text = false, $is_light = false ) {
    if ( get_option('mevzu_tag_subscription_enabled', '1') !== '1' || ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    $is_subscribed = mevzu_is_user_subscribed_to_tag( $user_id, $tag_id );
    
    if($show_text) {
        $btn_class = $is_subscribed ? 'btn-light border' : 'btn-dark';
        $btn_text = $is_subscribed ? 'Takipten Çık' : 'Etiketi takip et';
        $icon = $is_subscribed ? '<i class="ri-check-line"></i>' : '<i class="ri-add-line"></i>';
        echo '<button class="btn py-0 px-2 d-inline-flex align-items-center justify-content-center text-capitalize shadow-none ' . esc_attr($btn_class) . ' mevzu-follow-tag" data-tag-id="' . esc_attr($tag_id) . '">' . $icon . ' <span class="text ps-1 small">' . esc_html($btn_text) . '</span></button>';
    } else {
        $btn_class = $is_light ? 'btn-white' : ($is_subscribed ? 'btn-outline-danger' : 'btn-dark');
        $btn_title = $is_subscribed ? 'Takipten Çıkar' : 'Etiketi takip et';
        $icon = $is_subscribed ? '<i class="ri-subtract-line fz-12"></i>' : '<i class="ri-add-line fz-12"></i>';
        echo '<button class="btn py-0 px-1 d-inline-flex align-items-center justify-content-center text-capitalize shadow-none ' . esc_attr($btn_class) . ' mevzu-follow-tag" data-tag-id="' . esc_attr($tag_id) . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" data-bs-html="true" data-bs-title="<small>' . esc_attr($btn_title) . '</small>">' . $icon . '</button>';
    }
}

// Automatically create notifications on post publish
add_action( 'transition_post_status', 'mevzu_send_tag_notifications', 10, 3 );
function mevzu_send_tag_notifications( $new_status, $old_status, $post ) {
    // Only fire if publishing a new post
    if ( 'publish' === $new_status && 'publish' !== $old_status && $post->post_type === 'post' ) {
        
        if ( get_option('mevzu_tag_subscription_enabled', '1') !== '1' ) {
            return;
        }

        // Get post tags
        $tags = wp_get_post_tags( $post->ID, array('fields' => 'ids') );
        if ( empty( $tags ) ) {
            return;
        }

        // Find all users who subscribed to ANY of these tags
        global $wpdb;
        $subscribed_users = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'mevzu_subscribed_tags'" );
        
        $notified_users = array();

        foreach ( $subscribed_users as $row ) {
            $user_id = $row->user_id;
            // Unserialize
            $user_tags = maybe_unserialize( $row->meta_value );
            if ( ! is_array( $user_tags ) ) continue;

            // Intersect to see if user follows any tag of this post
            $intersect = array_intersect( $tags, $user_tags );
            if ( ! empty( $intersect ) && ! in_array( $user_id, $notified_users ) ) {
                
                // Get one matching tag for message
                $tag = get_term( reset($intersect), 'post_tag' );
                $tag_name = $tag ? $tag->name : 'bir etiket';

                // Add to notification queue
                $message = sprintf( 'Takip ettiğiniz "<strong>%s</strong>" etiketinde yeni bir haber yayınlandı: <a href="%s">%s</a>', esc_html($tag_name), esc_url(get_permalink( $post->ID )), esc_html( $post->post_title ) );
                
                mevzu_add_notification( $user_id, $post->ID, 0, 'tag_post', $message );
                $notified_users[] = $user_id;
            }
        }
    }
}
