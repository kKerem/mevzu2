<?php
/**
 * Category Subscription Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add Follow Category AJAX Endpoint
add_action( 'wp_ajax_mevzu_toggle_category_subscription', 'mevzu_toggle_category_subscription' );
function mevzu_toggle_category_subscription() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;
    $user_id = get_current_user_id();

    if ( $category_id > 0 && $user_id > 0 ) {
        $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_categories', true );
        if ( ! is_array( $subscribed ) ) {
            $subscribed = array();
        }

        if ( in_array( $category_id, $subscribed ) ) {
            // Un-subscribe
            $subscribed = array_diff( $subscribed, array( $category_id ) );
            $action = 'unsubscribed';
        } else {
            // Subscribe
            $subscribed[] = $category_id;
            $action = 'subscribed';
        }

        update_user_meta( $user_id, 'mevzu_subscribed_categories', $subscribed );
        wp_send_json_success( array( 'action' => $action ) );
    }
    wp_send_json_error();
}

// Add AJAX Search for Categories
add_action( 'wp_ajax_mevzu_search_categories', 'mevzu_search_categories' );
function mevzu_search_categories() {
    $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    
    $args = array(
        'taxonomy'   => 'category',
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

// Check if user is subscribed to a category
function mevzu_is_user_subscribed_to_category( $user_id, $category_id ) {
    $subscribed = get_user_meta( $user_id, 'mevzu_subscribed_categories', true );
    if ( ! is_array( $subscribed ) ) {
        return false;
    }
    return in_array( $category_id, $subscribed );
}

// Render the Follow button
function mevzu_render_category_follow_button( $category_id, $show_text = false, $is_light = false ) {
    if ( get_option('mevzu_category_subscription_enabled', '1') !== '1' || ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    $is_subscribed = mevzu_is_user_subscribed_to_category( $user_id, $category_id );
    
    if($show_text) {
        $btn_class = $is_subscribed ? 'btn-light border' : 'btn-dark';
        $btn_text = $is_subscribed ? 'Takipten Çık' : 'Kategoriyi takip et';
        $icon = $is_subscribed ? '<i class="ri-check-line"></i>' : '<i class="ri-add-line"></i>';
        echo '<button class="btn py-0 px-2 d-inline-flex align-items-center justify-content-center text-capitalize shadow-none ' . esc_attr($btn_class) . ' mevzu-follow-category" data-cat-id="' . esc_attr($category_id) . '">' . $icon . ' <span class="text ps-1 small">' . esc_html($btn_text) . '</span></button>';
    } else {
        $btn_class = $is_light ? 'btn-white' : ($is_subscribed ? 'btn-outline-danger' : 'btn-dark');
        $btn_title = $is_subscribed ? 'Takipten Çıkar' : 'Kategoriyi takip et';
        $icon = $is_subscribed ? '<i class="ri-subtract-line fz-12"></i>' : '<i class="ri-add-line fz-12"></i>';
        echo '<button class="btn py-0 px-1 d-inline-flex align-items-center justify-content-center text-capitalize shadow-none ' . esc_attr($btn_class) . ' mevzu-follow-category" data-cat-id="' . esc_attr($category_id) . '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" data-bs-html="true" data-bs-title="<small>' . esc_attr($btn_title) . '</small>">' . $icon . '</button>';
    }
}

// Automatically create notifications on post publish
add_action( 'transition_post_status', 'mevzu_send_category_notifications', 10, 3 );
function mevzu_send_category_notifications( $new_status, $old_status, $post ) {
    // Only fire if publishing a new post
    if ( 'publish' === $new_status && 'publish' !== $old_status && $post->post_type === 'post' ) {
        
        if ( get_option('mevzu_category_subscription_enabled', '1') !== '1' ) {
            return;
        }

        // Get post categories
        $categories = wp_get_post_categories( $post->ID );
        if ( empty( $categories ) ) {
            return;
        }

        // Find all users who subscribed to ANY of these categories
        global $wpdb;
        $users = get_users( array(
            'meta_query' => array(
                'relation' => 'OR',
            )
        ) );
        // wait, meta_query for serialized arrays is very slow. We'll fetch all users with the meta key and filter them.
        
        $subscribed_users = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'mevzu_subscribed_categories'" );
        
        $notified_users = array();

        foreach ( $subscribed_users as $row ) {
            $user_id = $row->user_id;
            // Unserialize
            $user_cats = maybe_unserialize( $row->meta_value );
            if ( ! is_array( $user_cats ) ) continue;

            // Intersect to see if user follows any category of this post
            $intersect = array_intersect( $categories, $user_cats );
            if ( ! empty( $intersect ) && ! in_array( $user_id, $notified_users ) ) {
                
                // Get one matching category for message
                $cat = get_category( reset($intersect) );
                $cat_name = $cat ? $cat->name : 'bir kategori';

                // Add to notification queue
                $message = sprintf( 'Takip ettiğiniz "<strong>%s</strong>" kategorisinde yeni bir haber yayınlandı: <a href="%s">%s</a>', esc_html($cat_name), esc_url(get_permalink( $post->ID )), esc_html( $post->post_title ) );
                
                mevzu_add_notification( $user_id, $post->ID, 0, 'category_post', $message );
                $notified_users[] = $user_id;
            }
        }
    }
}
