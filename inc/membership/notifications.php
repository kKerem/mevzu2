<?php
/**
 * Notifications module for the membership system.
 * Handles DB operations and AJAX read states.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the bespoke table exists
add_action('admin_init', 'mevzu_membership_update_db_structure');
function mevzu_membership_update_db_structure() {
    $version = get_option('mevzu_notifications_db_version');
    $current_version = '1.0.0';

    if ( $version !== $current_version ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        $charset_collate = $wpdb->get_charset_collate();

        // Types: 'category_post', 'comment_reply'
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            comment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'mevzu_notifications_db_version', $current_version );
    }
}

// Automatically delete old notifications
add_action('wp_scheduled_delete', 'mevzu_cleanup_old_notifications');
function mevzu_cleanup_old_notifications() {
    $auto_delete_days = intval(get_option('mevzu_notification_auto_delete', '0'));
    if ($auto_delete_days > 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE date_created < SUBDATE(NOW(), INTERVAL %d DAY)",
                $auto_delete_days
            )
        );
    }
}

// Function to add a notification
function mevzu_add_notification( $user_id, $post_id, $comment_id, $type, $message ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mevzu_notifications';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'comment_id' => $comment_id,
            'type' => $type,
            'message' => $message,
            'date_created' => current_time( 'mysql' ),
            'is_read' => 0
        )
    );

    // E-posta gönderim mantığı
    if ( get_option('mevzu_email_notifications_enabled', '0') === '1' ) {
        // Kullanıcının e-posta tercihlerini kontrol et (varsayılan: aktif)
        $user_pref = get_user_meta( $user_id, 'mevzu_email_pref_' . $type, true );
        if ( $user_pref === '' || $user_pref === '1' ) {
            $user = get_userdata( $user_id );
            if ( $user && is_email( $user->user_email ) ) {
                $site_name = get_bloginfo('name');
                $post_url = ($post_id > 0) ? get_permalink($post_id) : home_url();
                
                // Başlık
                $subject = "{$site_name}: Yeni bir bildiriminiz var!";

                // HTML E-posta Şablonu
                $html = '<html><head><style>';
                $html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; color: #333; line-height: 1.6; }';
                $html .= '.container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }';
                $html .= '.header { background: #0d6efd; color: #fff; padding: 20px; text-align: center; }';
                $html .= '.body { padding: 30px; }';
                $html .= '.message { font-size: 16px; margin-bottom: 25px; }';
                $html .= '.btn { display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; }';
                $html .= '.footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 13px; color: #777; }';
                $html .= '</style></head><body>';
                $html .= '<div class="container">';
                $html .= '<div class="header"><h2>' . esc_html($site_name) . '</h2></div>';
                $html .= '<div class="body">';
                $html .= '<p class="message">Merhaba <strong>' . esc_html($user->display_name) . '</strong>,</p>';
                $html .= '<div class="message">' . wp_kses_post($message) . '</div>';
                $html .= '<a href="' . esc_url($post_url) . '" class="btn">İçeriğe Git</a>';
                $html .= '</div>';
                $html .= '<div class="footer">E-posta bildirim tercihlerinizi sitemizdeki Profil Ayarları sekmesinden değiştirebilirsiniz.</div>';
                $html .= '</div></body></html>';

                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail( $user->user_email, $subject, $html, $headers );
            }
        }
    }
}

// Fetch notifications for a user by type ('category_post' or 'comment_reply')
function mevzu_get_user_notifications( $user_id, $type = null, $limit = 10, $unread_only = false ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mevzu_notifications';

    $where = "WHERE user_id = %d";
    $args = array( $user_id );

    if ( $type ) {
        $where .= " AND type = %s";
        $args[] = $type;
    }

    if ( $unread_only ) {
        $where .= " AND is_read = 0";
    }

    $query = $wpdb->prepare( "SELECT * FROM $table_name $where ORDER BY date_created DESC LIMIT %d", array_merge( $args, array( $limit ) ) );
    return $wpdb->get_results( $query );
}

// Fetch unread count
function mevzu_get_unread_notifications_count( $user_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mevzu_notifications';
    $query = $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE user_id = %d AND is_read = 0", $user_id );
    return $wpdb->get_var( $query );
}

// AJAX: Mark notification as read
add_action( 'wp_ajax_mevzu_mark_notification_read', 'mevzu_ajax_mark_notification_read' );
function mevzu_ajax_mark_notification_read() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $notif_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();

    if ( $notif_id > 0 && $user_id > 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        
        $wpdb->update(
            $table_name,
            array( 'is_read' => 1 ),
            array( 'id' => $notif_id, 'user_id' => $user_id ),
            array( '%d' ),
            array( '%d', '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error();
}
// AJAX: Delete notification
add_action( 'wp_ajax_mevzu_delete_notification', 'mevzu_ajax_delete_notification' );
function mevzu_ajax_delete_notification() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $notif_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();

    if ( $notif_id > 0 && $user_id > 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        
        $wpdb->delete(
            $table_name,
            array( 'id' => $notif_id, 'user_id' => $user_id ),
            array( '%d', '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error();
}
// AJAX: Mark all notifications as read
add_action( 'wp_ajax_mevzu_mark_all_notifications_read', 'mevzu_ajax_mark_all_notifications_read' );
function mevzu_ajax_mark_all_notifications_read() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( $user_id > 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        $wpdb->update(
            $table_name,
            array( 'is_read' => 1 ),
            array( 'user_id' => $user_id, 'is_read' => 0 ),
            array( '%d' ),
            array( '%d', '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error();
}

// AJAX: Delete all notifications
add_action( 'wp_ajax_mevzu_delete_all_notifications', 'mevzu_ajax_delete_all_notifications' );
function mevzu_ajax_delete_all_notifications() {
    check_ajax_referer( 'mevzu_membership_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( $user_id > 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevzu_notifications';
        $wpdb->delete(
            $table_name,
            array( 'user_id' => $user_id ),
            array( '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error();
}

// Hook into comment replies
add_action( 'wp_insert_comment', 'mevzu_handle_comment_notification', 10, 2 );
function mevzu_handle_comment_notification( $id, $comment ) {
    if ( get_option('mevzu_comment_notification_enabled', '1') !== '1' ) return;

    if ( $comment->comment_parent > 0 ) {
        $parent_comment = get_comment( $comment->comment_parent );
        // Only notify if parent is written by a registered user, and not replying to oneself
        if ( $parent_comment && $parent_comment->user_id > 0 && $parent_comment->user_id != $comment->user_id ) {
            $post_title = get_the_title( $comment->comment_post_ID );
            $message = sprintf( '<strong>%s</strong> yorumunuza yanıt verdi', esc_html($comment->comment_author) );
            mevzu_add_notification( $parent_comment->user_id, $comment->comment_post_ID, $id, 'comment_reply', $message );
        }
    }
}
