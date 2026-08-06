<?php
/**
 * TTS ses dosyaları — otomatik silme.
 *
 * - Hedef kategori: dosya yaşına göre (X gün).
 * - YZ manşet: takvim gününe göre (günün haberleri; tipik değer 1 = sadece bugün).
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mevzu_TTS_Audio_Retention {

	const CRON_HOOK = 'mevzu_tts_audio_retention_cleanup';

	public function __construct() {
		add_action( 'init', array( $this, 'maybe_schedule_cron' ) );
		add_action( self::CRON_HOOK, array( $this, 'run_cleanup' ) );
	}

	/**
	 * Günlük temizlik görevini planla.
	 */
	public function maybe_schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * 0–3650 gün; 0 = otomatik silme kapalı.
	 *
	 * @param mixed $value Form değeri.
	 * @return int
	 */
	public static function sanitize_retention_days( $value ) {
		$days = absint( $value );
		return min( $days, 3650 );
	}

	/**
	 * Hedef kategori yazıları için saklama süresi (dosya yaşı, gün).
	 */
	public static function get_category_retention_days() {
		return max( 0, (int) get_option( 'kkerem_tts_category_audio_retention_days', 0 ) );
	}

	/**
	 * YZ manşet yazıları için saklama (takvim günü sayısı).
	 */
	public static function get_yzm_retention_days() {
		return max( 0, (int) get_option( 'kkerem_tts_yzm_audio_retention_days', 0 ) );
	}

	/**
	 * @return array{category_posts:int,yzm_posts:int}
	 */
	public function run_cleanup() {
		$cat_days = self::get_category_retention_days();
		$yzm_days = self::get_yzm_retention_days();
		$deleted  = array(
			'category_posts' => 0,
			'yzm_posts'      => 0,
		);

		if ( $cat_days <= 0 && $yzm_days <= 0 ) {
			return $deleted;
		}

		$file_manager = new KKEREM_TTS_File_Manager();
		$files        = $file_manager->list_all_audio_files();
		$now          = time();

		foreach ( $files as $file ) {
			if ( empty( $file['post_id'] ) || empty( $file['created_time'] ) ) {
				continue;
			}

			$post_id = (int) $file['post_id'];
			$mtime   = (int) $file['created_time'];

			if ( mevzu_tts_post_has_yapay_zeka_manset( $post_id ) && $yzm_days > 0 ) {
				if ( $this->is_yzm_audio_expired_by_calendar( $mtime, $yzm_days ) && $file_manager->delete_audio_file( $post_id ) ) {
					++$deleted['yzm_posts'];
				}
				continue;
			}

			if ( mevzu_tts_post_in_target_category( $post_id ) && $cat_days > 0 ) {
				$age_days = (int) floor( ( $now - $mtime ) / DAY_IN_SECONDS );
				if ( $age_days >= $cat_days && $file_manager->delete_audio_file( $post_id ) ) {
					++$deleted['category_posts'];
				}
			}
		}

		if ( $deleted['category_posts'] > 0 || $deleted['yzm_posts'] > 0 ) {
			KKEREM_TTS_Admin::debug_log(
				sprintf(
					'Otomatik silme: %d hedef kategori, %d YZ manşet sesi.',
					$deleted['category_posts'],
					$deleted['yzm_posts']
				)
			);
		}

		return $deleted;
	}

	/**
	 * YZ manşet: site saat diliminde takvim günü.
	 *
	 * Örn. saklama = 1 → yalnızca bugünün dosyaları kalır; dün ve öncesi silinir.
	 * saklama = 2 → bugün + dün kalır.
	 *
	 * @param int $file_mtime Unix zamanı (dosya oluşturma).
	 * @param int $keep_calendar_days Saklanacak takvim günü sayısı (≥1).
	 */
	private function is_yzm_audio_expired_by_calendar( $file_mtime, $keep_calendar_days ) {
		if ( $keep_calendar_days <= 0 ) {
			return false;
		}

		$file_date = wp_date( 'Y-m-d', $file_mtime );
		$keep_from = wp_date(
			'Y-m-d',
			strtotime( '-' . ( $keep_calendar_days - 1 ) . ' days', current_time( 'timestamp' ) )
		);

		return $file_date < $keep_from;
	}
}
