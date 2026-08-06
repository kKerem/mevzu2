<?php
/**
 * Sosyal Otomasyon ana yönetici sınıfı.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ana sınıf.
 */
class Mevzu_Social_Automation {

	/**
	 * Kayıtlı platform sınıfları.
	 *
	 * @var array<string, string>
	 */
	private static $platforms = array(
		'facebook'  => 'Mevzu_Social_Platform_Facebook',
		'twitter'   => 'Mevzu_Social_Platform_Twitter',
		'telegram'  => 'Mevzu_Social_Platform_Telegram',
		'instagram' => 'Mevzu_Social_Platform_Instagram',
		'webhook'   => 'Mevzu_Social_Platform_Webhook',
	);

	public function __construct() {
		new Mevzu_Social_Admin();
		new Mevzu_Social_Post_Meta();
		new Mevzu_Social_Publisher();
	}

	/**
	 * Tüm platform slug'larını döndür.
	 *
	 * @return string[]
	 */
	public static function get_platform_slugs() {
		return array_keys( self::$platforms );
	}

	/**
	 * Bir platformun instance'ını döndür.
	 *
	 * @param string $slug Platform slug.
	 * @return Mevzu_Social_Platform_Base|null
	 */
	public static function get_platform( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! isset( self::$platforms[ $slug ] ) ) {
			return null;
		}
		$class = self::$platforms[ $slug ];
		if ( ! class_exists( $class ) ) {
			return null;
		}
		return new $class();
	}

	/**
	 * Aktif platformların instance listesini döndür.
	 *
	 * @return Mevzu_Social_Platform_Base[]
	 */
	public static function get_active_platforms() {
		$active = array();
		foreach ( self::$platforms as $slug => $class ) {
			$platform = self::get_platform( $slug );
			if ( $platform && $platform->is_enabled() ) {
				$active[ $slug ] = $platform;
			}
		}
		return $active;
	}
}
