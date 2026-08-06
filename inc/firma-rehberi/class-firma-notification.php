<?php
/**
 * Firma Rehberi — E-posta Bildirimleri
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Firma_Notification {

    /**
     * Yeni başvuru admin'e bildirim
     */
    public static function send_new_submission( $post_id ) {
        if ( ! Firma_Admin::get( 'notify_admin', true ) ) return;

        $to      = Firma_Admin::get( 'admin_email' ) ?: get_option( 'admin_email' );
        $firma   = get_the_title( $post_id );
        $link    = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
        $subject = sprintf( '[%s] Yeni Firma Başvurusu: %s', get_bloginfo('name'), $firma );
        $message = sprintf(
            "Merhaba,\n\n\"%s\" adlı firma için yeni bir başvuru yapıldı.\n\nİncelemek ve onaylamak için:\n%s\n\n-- %s",
            $firma, $link, get_bloginfo('name')
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Başvuru onaylandı — başvurana bildirim
     */
    public static function send_approval( $post_id ) {
        if ( ! Firma_Admin::get( 'notify_submitter', true ) ) return;

        $to = get_post_meta( $post_id, '_firma_submitter_email', true );
        if ( ! $to || ! is_email( $to ) ) return;

        $firma   = get_the_title( $post_id );
        $link    = get_permalink( $post_id );
        $subject = sprintf( '[%s] Firma Başvurunuz Onaylandı!', get_bloginfo('name') );
        $message = sprintf(
            "Merhaba %s,\n\n\"%s\" adlı firmanızın başvurusu onaylandı ve rehberde yayınlandı!\n\nFirmanızı görüntülemek için:\n%s\n\n-- %s",
            esc_html( get_post_meta( $post_id, '_firma_submitter_name', true ) ),
            $firma,
            $link,
            get_bloginfo('name')
        );

        wp_mail( $to, $subject, $message );
    }
}
