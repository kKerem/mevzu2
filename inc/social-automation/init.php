<?php
/**
 * Mevzu² Sosyal Otomasyon Modülü
 *
 * Yazılar yayınlandığında (zamanlanmış dahil) Facebook, Twitter/X,
 * Telegram, Instagram ve webhook üzerine otomatik paylaşım yapar.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Modül aktif değilse hiçbir şey yapma.
if ( ! class_exists( 'Mevzu_Module_Manager' ) || ! Mevzu_Module_Manager::is_active( 'social-automation' ) ) {
	return;
}

// Sabitler.
if ( ! defined( 'MEVZU_SOCIAL_AUTOMATION_PATH' ) ) {
	define( 'MEVZU_SOCIAL_AUTOMATION_PATH', __DIR__ . '/' );
}
if ( ! defined( 'MEVZU_SOCIAL_AUTOMATION_URL' ) ) {
	define( 'MEVZU_SOCIAL_AUTOMATION_URL', get_stylesheet_directory_uri() . '/inc/social-automation/' );
}
if ( ! defined( 'MEVZU_SOCIAL_VERSION' ) ) {
	define( 'MEVZU_SOCIAL_VERSION', '1.0.0' );
}

// Temel sınıflar.
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-platform-base.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-logger.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-social-automation.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-admin.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-post-meta.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'class-publisher.php';

// Platform sınıfları.
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'platforms/class-platform-facebook.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'platforms/class-platform-twitter.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'platforms/class-platform-telegram.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'platforms/class-platform-instagram.php';
require_once MEVZU_SOCIAL_AUTOMATION_PATH . 'platforms/class-platform-webhook.php';

// Başlat.
new Mevzu_Social_Automation();
