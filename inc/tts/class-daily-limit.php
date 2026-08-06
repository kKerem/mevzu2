<?php
/**
 * Günlük Mevzu² AI kullanım kotası (sunucu ile senkron).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mevzu_TTS_Daily_Limit {

	private static $option_key = 'mevzu_tts_daily_usage';

	/**
	 * Sunucudan gelen kota anlık görüntüsünü uygula.
	 *
	 * @param array $quota used, limit, remaining, date
	 */
	public static function apply_remote_quota( array $quota ) {
		$today = gmdate( 'Y-m-d' );
		$date  = isset( $quota['date'] ) ? (string) $quota['date'] : $today;
		if ( $date !== $today ) {
			return;
		}
		$used  = isset( $quota['used'] ) ? (int) $quota['used'] : 0;
		$limit = isset( $quota['limit'] ) ? (int) $quota['limit'] : MEVZU_TTS_DAILY_LIMIT;
		if ( $limit < 1 ) {
			$limit = MEVZU_TTS_DAILY_LIMIT;
		}
		update_option(
			self::$option_key,
			array(
				'date'  => $today,
				'count' => $used,
				'limit' => $limit,
			),
			false
		);
	}

	/**
	 * Etkin günlük limit (sunucudan veya sabit).
	 */
	public static function get_limit() {
		$data = get_option( self::$option_key, array() );
		$today = gmdate( 'Y-m-d' );
		if ( isset( $data['date'], $data['limit'] ) && $data['date'] === $today && (int) $data['limit'] > 0 ) {
			return (int) $data['limit'];
		}
		return (int) MEVZU_TTS_DAILY_LIMIT;
	}

	public static function get_usage() {
		self::maybe_refresh_quota();
		$data  = get_option( self::$option_key, array() );
		$today = gmdate( 'Y-m-d' );

		if ( ! isset( $data['date'] ) || $data['date'] !== $today ) {
			$data = array(
				'date'  => $today,
				'count' => 0,
				'limit' => MEVZU_TTS_DAILY_LIMIT,
			);
			update_option( self::$option_key, $data, false );
		}

		return (int) $data['count'];
	}

	public static function can_use() {
		if ( ! class_exists( 'Mevzu_AI_Client' ) || ! Mevzu_AI_Client::is_ready() ) {
			return false;
		}
		self::maybe_refresh_quota();
		return self::get_usage() < self::get_limit();
	}

	public static function remaining() {
		return max( 0, self::get_limit() - self::get_usage() );
	}

	/**
	 * Yerel sayaç artırımı (sunucu zaten sayar; yalnızca geriye dönük uyumluluk).
	 */
	public static function increment() {
		$data  = get_option( self::$option_key, array() );
		$today = gmdate( 'Y-m-d' );

		if ( ! isset( $data['date'] ) || $data['date'] !== $today ) {
			$data = array(
				'date'  => $today,
				'count' => 0,
				'limit' => self::get_limit(),
			);
		}

		$data['count'] = (int) $data['count'] + 1;
		update_option( self::$option_key, $data, false );

		return (int) $data['count'];
	}

	private static function maybe_refresh_quota() {
		if ( ! class_exists( 'Mevzu_AI_Client' ) || ! Mevzu_AI_Client::is_ready() ) {
			return;
		}
		$data  = get_option( self::$option_key, array() );
		$today = gmdate( 'Y-m-d' );
		if ( isset( $data['date'] ) && $data['date'] === $today && isset( $data['limit'] ) ) {
			return;
		}
		Mevzu_AI_Client::fetch_quota( true );
	}
}
