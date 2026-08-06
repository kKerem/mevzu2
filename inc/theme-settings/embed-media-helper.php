<?php
/**
 * Haber yazıları için çoklu kaynak embed: URL (YouTube/Facebook/Instagram/oEmbed)
 * ve WordPress native video (attachment).
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ============================================================
 *  YARDIMCI FONKSİYONLAR
 * ============================================================ */

/**
 * YouTube URL'sinden video kimliği çıkarır.
 * Desteklenen formatlar: watch?v=, /embed/, /live/, youtu.be/, /shorts/
 */
function mevzu_extract_youtube_id( $url ) {
    $url = trim( (string) $url );
    if ( $url === '' ) return '';
    $patterns = [
        '/(?:youtube\.com\/watch\?(?:[^&]*&)*v=|youtube\.com\/embed\/|youtube\.com\/live\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
    ];
    foreach ( $patterns as $p ) {
        if ( preg_match( $p, $url, $m ) ) return $m[1];
    }
    return '';
}

/**
 * Ortak 16/9 video iframe sarmalayıcı.
 */
function mevzu_video_wrapper( $iframe_html ) {
    return '<div class="mevzu-embed-block ratio ratio-16x9 mb-4 rounded-3 overflow-hidden">' . $iframe_html . '</div>';
}

/**
 * Facebook / Instagram için "izlemek için tıklayın" bloğu.
 *
 * @param string $url      Orijinal bağlantı.
 * @param string $platform 'facebook' veya 'instagram'
 */
function mevzu_social_click_block( $url, $platform ) {
    $icons = [
        'facebook'  => '<i class="ri-facebook-circle-fill h1 m-0 ps-1 text-facebook"></i>',
        'instagram' => '<i class="ri-instagram-line h1 m-0 ps-1 text-instagram"></i>',
        'youtube'   => '<i class="ri-youtube-fill h1 m-0 ps-1 text-youtube"></i>',
        'twitter'   => '<i class="ri-twitter-x-fill h1 m-0 ps-1 text-dark"></i>',
    ];

    $labels = [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
        'twitter'   => 'X (Twitter)',
    ];

    $icon      = $icons[ $platform ] ?? '';
    $platform_label = $labels[ $platform ] ?? $platform;

    $colors = [
        'facebook'  => '#1877F2',
        'instagram' => '#fc3a4f',
        'youtube'   => '#FF0000',
        'twitter'   => '#000000',
    ];
    $color = $colors[ $platform ] ?? '#333';

    return '<div class="mevzu-embed-block mevzu-social-click mt-3">'
        . '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="mevzu-social-click-link p-2" style="--social-color:' . esc_attr( $color ) . '">'
        . '<span class="mevzu-social-click-icon">' . $icon . '</span>'
        . '<span class="mevzu-social-click-text text-dark">'
        . '<span class="d-block text-body opacity-75 small">' . esc_html( $platform_label ) . '</span>Haberin videosunu izlemek için tıklayın'
        . '</span>'
        . '<span class="mevzu-social-click-arrow"><i class="ri-arrow-right-up-line h4 m-0"></i></span>'
        . '</a>'
        . '</div>';
}

/* ============================================================
 *  ANA FONKSİYON: URL → Embed HTML
 * ============================================================ */

/**
 * Verilen URL için en uygun gömülü HTML üretir.
 *
 * @param  string $url Tam HTTPS bağlantısı.
 * @return string      Güvenli HTML veya boş dize.
 */
function mevzu_get_embed_html_from_url( $url ) {
    $url = esc_url_raw( trim( (string) $url ) );
    if ( $url === '' || ! filter_var( $url, FILTER_VALIDATE_URL ) ) return '';

    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

    /* ── Facebook → tıkla bloğu ── */
    if ( str_contains( $host, 'facebook.com' ) || str_contains( $host, 'fb.watch' ) || str_contains( $host, 'fb.com' ) ) {
        $is_video = str_contains( $host, 'fb.watch' )
            || preg_match( '~/videos?/~i', $url )
            || preg_match( '~/reels?/~i', $url );
        $type = $is_video ? ( preg_match( '~/reels?/~i', $url ) ? 'reel' : 'video' ) : 'post';
        return mevzu_social_click_block( $url, 'facebook', $type );
    }

    /* ── Instagram → tıkla bloğu ── */
    if ( preg_match( '~instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)~i', $url, $m ) ) {
        $type_map = [ 'p' => 'post', 'reel' => 'reel', 'tv' => 'tv' ];
        $type     = $type_map[ strtolower( $m[1] ) ] ?? '';
        return mevzu_social_click_block( $url, 'instagram', $type );
    }

    /* ── YouTube → tıkla bloğu ── */
    $yt_id = mevzu_extract_youtube_id( $url );
    if ( $yt_id !== '' ) {
        return mevzu_social_click_block( $url, 'youtube' );
    }
    /* ── Twitter / X → tıkla bloğu ── */
    if ( preg_match( '~(?:twitter\.com|x\.com)/~i', $url ) ) {
        return mevzu_social_click_block( $url, 'twitter' );
    }


    return '';
}

/* ============================================================
 *  NATIVE VIDEO: WordPress attachment → <video> etiketi
 * ============================================================ */

function mevzu_get_native_video_html( $post_id ) {
    $video_id = (int) get_post_meta( $post_id, 'mevzu_native_video_id', true );
    if ( $video_id <= 0 ) return '';

    // R2 URL varsa onu kullan, yoksa yerel attachment URL'ye dön
    $src = (string) get_post_meta( $post_id, 'mevzu_native_video_url', true );
    if ( ! $src ) {
        $src = wp_get_attachment_url( $video_id );
    }
    if ( ! $src ) return '';

    $mime = get_post_mime_type( $video_id );

    return '
<div class="mevzu-embed-block">
  <div class="mvp-wrapper" role="region" aria-label="' . esc_attr__( 'Video oynatıcı', 'mevzu2' ) . '">
    <video class="mvp-video" preload="auto" playsinline>
      <source src="' . esc_url( $src ) . '" type="' . esc_attr( $mime ) . '">
    </video>

    <div class="mvp-overlay"></div>
    <div class="mvp-big-play"></div>
    <div class="mvp-spinner"></div>

    <div class="mvp-controls">
      <div class="mvp-progress">
        <div class="mvp-progress-buffered"></div>
        <div class="mvp-progress-filled"></div>
      </div>
      <div class="mvp-controls-row">
        <button type="button" class="mvp-btn mvp-btn-play" aria-label="Oynat / Duraklat"></button>
        <button type="button" class="mvp-btn mvp-btn-mute" aria-label="Ses"></button>
        <div class="mvp-volume"><div class="mvp-volume-filled"></div></div>
        <span class="mvp-time">
          <span class="mvp-time-cur">0:00</span>
          <span class="mvp-time-sep">/</span>
          <span class="mvp-time-dur">0:00</span>
        </span>
        <button type="button" class="mvp-btn mvp-btn-fullscreen" aria-label="Tam ekran"></button>
        <button type="button" class="mvp-btn mvp-btn-download" aria-label="İndir"></button>
      </div>
    </div>
  </div>
</div>';
}

/* ============================================================
 *  ŞABLON FONKSİYONU
 * ============================================================ */

/**
 * Sadece URL embed'i gösterir (native video üstte gösterildiğinde kullanılır).
 */
function mevzu_render_embed_url_only( $post_id ) {
    if ( get_post_type( $post_id ) !== 'post' ) return;

    $embed_url = get_post_meta( $post_id, 'mevzu_embed_media_url', true );
    if ( ! $embed_url ) {
        $embed_url = get_post_meta( $post_id, 'youtube_url', true );
    }
    if ( $embed_url ) {
        $html = mevzu_get_embed_html_from_url( $embed_url );
        if ( $html ) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}

function mevzu_render_embed_block( $post_id ) {
    if ( get_post_type( $post_id ) !== 'post' ) return;

    $embed_url = get_post_meta( $post_id, 'mevzu_embed_media_url', true );
    if ( ! $embed_url ) {
        $embed_url = get_post_meta( $post_id, 'youtube_url', true );
    }
    if ( $embed_url ) {
        $html = mevzu_get_embed_html_from_url( $embed_url );
        if ( $html ) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    $native = mevzu_get_native_video_html( $post_id );
    if ( $native ) {
        echo $native; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}