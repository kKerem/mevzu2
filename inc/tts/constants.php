<?php
/**
 * Mevzu² AI — slug, admin sayfa ve geçiş sabitleri.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MEVZU_YZ_MODULE_SLUG' ) ) {
	define( 'MEVZU_YZ_MODULE_SLUG', 'yapay-zeka' );
}

if ( ! defined( 'MEVZU_YZ_MODULE_LABEL' ) ) {
	define( 'MEVZU_YZ_MODULE_LABEL', 'Mevzu² AI' );
}

if ( ! defined( 'MEVZU_YZ_ADMIN_PAGE' ) ) {
	define( 'MEVZU_YZ_ADMIN_PAGE', 'mevzu-yapay-zeka' );
}

if ( ! defined( 'MEVZU_YZ_ADMIN_PAGE_BULK' ) ) {
	define( 'MEVZU_YZ_ADMIN_PAGE_BULK', 'mevzu-yapay-zeka-toplu' );
}

if ( ! defined( 'MEVZU_YZ_ADMIN_PAGE_DEBUG' ) ) {
	define( 'MEVZU_YZ_ADMIN_PAGE_DEBUG', 'mevzu-yapay-zeka-debug' );
}

/** Eski TTS admin slug’ları → yeni (301 benzeri yönlendirme). */
if ( ! defined( 'MEVZU_YZ_LEGACY_ADMIN_PAGES' ) ) {
	define(
		'MEVZU_YZ_LEGACY_ADMIN_PAGES',
		array(
			'mevzu-tts-settings'       => MEVZU_YZ_ADMIN_PAGE,
			'mevzu-tts-bulk-generator' => MEVZU_YZ_ADMIN_PAGE_BULK,
			'mevzu-tts-debug'          => MEVZU_YZ_ADMIN_PAGE_DEBUG,
		)
	);
}

/**
 * Modül aktif mi? (yeni slug + eski «tts» slug uyumluluğu).
 */
function mevzu_yz_module_active() {
	if ( ! class_exists( 'Mevzu_Module_Manager' ) ) {
		return false;
	}
	if ( Mevzu_Module_Manager::is_active( MEVZU_YZ_MODULE_SLUG ) ) {
		return true;
	}
	return Mevzu_Module_Manager::is_active( 'tts' );
}

/**
 * Yapay Zeka ayarları admin URL.
 *
 * @param string $page MEVZU_YZ_ADMIN_PAGE | _BULK | _DEBUG
 */
function mevzu_yz_admin_url( $page = '' ) {
	if ( $page === '' ) {
		$page = MEVZU_YZ_ADMIN_PAGE;
	}
	return admin_url( 'admin.php?page=' . $page );
}

/**
 * İçe aktarma veya modül güncellemesi sonrası uyumluluk (modül slug, detaylar).
 */
function mevzu_yz_import_normalize_options() {
	mevzu_yz_run_legacy_migrations();
}

/**
 * Eski modül slug ve detay anahtarı geçişleri (bir kez).
 */
function mevzu_yz_run_legacy_migrations() {
	static $ran = false;
	if ( $ran ) {
		return;
	}
	$ran = true;

	$modules = get_option( 'mevzu_modules', array() );
	if ( is_array( $modules ) && isset( $modules['tts'] ) && ! isset( $modules[ MEVZU_YZ_MODULE_SLUG ] ) ) {
		$modules[ MEVZU_YZ_MODULE_SLUG ] = $modules['tts'];
		unset( $modules['tts'] );
		update_option( 'mevzu_modules', $modules );
	}

	foreach ( array( 'options_detaylar', 'options_detaylar_koseyazisi' ) as $option_key ) {
		$detaylar = get_option( $option_key );
		if ( ! is_array( $detaylar ) ) {
			continue;
		}
		if ( in_array( 'tts', $detaylar, true ) && ! in_array( 'sesli_dinle', $detaylar, true ) ) {
			$detaylar[] = 'sesli_dinle';
			update_option( $option_key, $detaylar );
		}
	}
}

/**
 * Haber detayında sesli dinle öğesi seçili mi?
 *
 * @param array<int,string>|mixed $detaylar
 */
function mevzu_yz_detail_has_audio_player( $detaylar ) {
	return is_array( $detaylar )
		&& ( in_array( 'sesli_dinle', $detaylar, true ) || in_array( 'tts', $detaylar, true ) );
}

/**
 * Eski admin.php?page=mevzu-tts-* adreslerini yönlendir.
 */
function mevzu_yz_redirect_legacy_admin_pages() {
	if ( ! is_admin() || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$current = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$map     = MEVZU_YZ_LEGACY_ADMIN_PAGES;
	if ( isset( $map[ $current ] ) ) {
		wp_safe_redirect( mevzu_yz_admin_url( $map[ $current ] ) );
		exit;
	}
}

/**
 * AJAX nonce (yeni + eski uyumluluk).
 *
 * @param string $legacy_action Eski nonce action (ör. kkerem_tts_nonce).
 */
function mevzu_yz_verify_ajax_nonce( $legacy_action ) {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( wp_verify_nonce( $nonce, 'mevzu_yz_nonce' ) ) {
		return true;
	}
	if ( $legacy_action && wp_verify_nonce( $nonce, $legacy_action ) ) {
		return true;
	}
	wp_send_json_error( array( 'message' => 'Geçersiz istek.' ), 403 );
}

add_action( 'init', 'mevzu_yz_run_legacy_migrations', 1 );
add_action( 'admin_init', 'mevzu_yz_redirect_legacy_admin_pages', 1 );
