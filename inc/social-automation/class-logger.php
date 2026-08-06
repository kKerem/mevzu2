<?php
/**
 * Sosyal paylaşım loglarını yönetir.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paylaşım geçmişini post meta'ya kaydeder.
 */
class Mevzu_Social_Logger {

	const META_KEY = 'mevzu_social_log';

	/**
	 * Yeni bir log kaydı ekle.
	 *
	 * @param int    $post_id    Yazı ID.
	 * @param string $platform   Platform slug.
	 * @param bool   $success    Başarılı mı?
	 * @param string $message    Durum mesajı.
	 * @param string $remote_id  Dış platformdaki paylaşım ID (opsiyonel).
	 */
	public static function add( $post_id, $platform, $success, $message, $remote_id = '' ) {
		$post_id  = (int) $post_id;
		$platform = sanitize_key( $platform );

		$log = (array) get_post_meta( $post_id, self::META_KEY, true );

		$log[] = array(
			'time'      => current_time( 'mysql' ),
			'platform'  => $platform,
			'success'   => (bool) $success,
			'message'   => sanitize_text_field( $message ),
			'remote_id' => sanitize_text_field( $remote_id ),
		);

		// Son 50 kaydı tut.
		$log = array_slice( $log, -50 );

		update_post_meta( $post_id, self::META_KEY, $log );
	}

	/**
	 * Bir yazının tüm log kayıtlarını döndür.
	 *
	 * @param int $post_id Yazı ID.
	 * @return array
	 */
	public static function get( $post_id ) {
		$log = get_post_meta( (int) $post_id, self::META_KEY, true );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Belirli bir platform için son başarılı paylaşım zamanını döndür.
	 *
	 * @param int    $post_id  Yazı ID.
	 * @param string $platform Platform slug.
	 * @return string|null
	 */
	public static function last_success_time( $post_id, $platform ) {
		$logs = self::get( $post_id );
		foreach ( array_reverse( $logs ) as $entry ) {
			if ( ! empty( $entry['success'] ) && $entry['platform'] === $platform ) {
				return $entry['time'];
			}
		}
		return null;
	}
}
