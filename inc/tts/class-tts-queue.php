<?php
/**
 * Mevzu² AI — arka plan ses üretim kuyruğu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mevzu_TTS_Queue {

	const META_STATUS     = 'mevzu_tts_job_status';
	const META_MESSAGE    = 'mevzu_tts_job_message';
	const META_QUEUED_AT  = 'mevzu_tts_job_queued_at';
	const META_BG_SECRET  = '_mevzu_tts_bg_secret';

	const STATUS_IDLE       = 'idle';
	const STATUS_QUEUED     = 'queued';
	const STATUS_PROCESSING = 'processing';
	const STATUS_DONE       = 'done';
	const STATUS_ERROR      = 'error';

	public static function init() {
		add_action( 'mevzu_tts_generate_audio', array( __CLASS__, 'run_job' ) );
		add_action( 'wp_ajax_mevzu_yz_run_tts_queue', array( __CLASS__, 'ajax_run_background' ) );
		add_action( 'wp_ajax_nopriv_mevzu_yz_run_tts_queue', array( __CLASS__, 'ajax_run_background' ) );
	}

	/**
	 * @return string idle|queued|processing|done|error
	 */
	public static function get_status( $post_id ) {
		$status = get_post_meta( (int) $post_id, self::META_STATUS, true );
		return $status ? (string) $status : self::STATUS_IDLE;
	}

	public static function get_message( $post_id ) {
		return (string) get_post_meta( (int) $post_id, self::META_MESSAGE, true );
	}

	public static function set_status( $post_id, $status, $message = '' ) {
		$post_id = (int) $post_id;
		update_post_meta( $post_id, self::META_STATUS, (string) $status );
		update_post_meta( $post_id, self::META_MESSAGE, (string) $message );
		if ( $status === self::STATUS_QUEUED ) {
			update_post_meta( $post_id, self::META_QUEUED_AT, time() );
		}
	}

	/**
	 * Yazıyı kuyruğa al (yayın / güncelleme / manuel).
	 *
	 * @return bool|\WP_Error
	 */
	public static function enqueue( $post_id, $source = 'publish' ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return new WP_Error( 'invalid_post', 'Geçersiz yazı.' );
		}

		if ( ! mevzu_yz_module_active() ) {
			return new WP_Error( 'module_off', 'Mevzu² AI modülü kapalı.' );
		}

		if ( ! Mevzu_AI_Client::is_ready() ) {
			return new WP_Error( 'ai_unavailable', Mevzu_AI_Client::get_unavailable_message() );
		}

		if ( ! Mevzu_TTS_Daily_Limit::can_use() ) {
			return new WP_Error( 'quota', 'Mevzu² AI günlük kotanız doldu.' );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return new WP_Error( 'not_published', 'Yazı henüz yayında değil.' );
		}

		if ( ! mevzu_tts_post_should_process( $post_id ) ) {
			return new WP_Error( 'not_eligible', 'Bu yazı için ses üretimi gerekmiyor.' );
		}

		$current = self::get_status( $post_id );
		if ( in_array( $current, array( self::STATUS_QUEUED, self::STATUS_PROCESSING ), true ) ) {
			return true;
		}

		self::set_status( $post_id, self::STATUS_QUEUED, __( 'Ses dosyası kuyruğa alındı.', 'mevzu2' ) );

		// Önceki zamanlanmış işi temizle, yenisi planla.
		$timestamp = wp_next_scheduled( 'mevzu_tts_generate_audio', array( $post_id ) );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'mevzu_tts_generate_audio', array( $post_id ) );
		}
		wp_schedule_single_event( time() + 2, 'mevzu_tts_generate_audio', array( $post_id ) );

		self::dispatch_background( $post_id );

		KKEREM_TTS_Admin::debug_log( 'TTS kuyruk: post #' . $post_id . ' (' . $source . ')' );

		return true;
	}

	/**
	 * WP-Cron + engellenmeyen HTTP ile arka plan tetikleme (MAMP / düşük trafik).
	 */
	public static function dispatch_background( $post_id ) {
		$post_id = (int) $post_id;
		$secret  = wp_generate_password( 32, false, false );
		update_post_meta( $post_id, self::META_BG_SECRET, $secret );

		$url = admin_url( 'admin-ajax.php' );

		wp_remote_post(
			$url,
			array(
				'timeout'   => 0.5,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'body'      => array(
					'action'  => 'mevzu_yz_run_tts_queue',
					'post_id' => $post_id,
					'secret'  => $secret,
				),
			)
		);

		if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
			spawn_cron();
		}
	}

	/**
	 * Arka plan AJAX (oturum gerekmez; yazıya özel gizli anahtar).
	 */
	public static function ajax_run_background() {
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$secret  = isset( $_POST['secret'] ) ? sanitize_text_field( wp_unslash( $_POST['secret'] ) ) : '';

		if ( $post_id < 1 || $secret === '' ) {
			wp_die( '', '', array( 'response' => 400 ) );
		}

		$stored = (string) get_post_meta( $post_id, self::META_BG_SECRET, true );
		if ( $stored === '' || ! hash_equals( $stored, $secret ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		delete_post_meta( $post_id, self::META_BG_SECRET );

		self::run_job( $post_id );
		wp_die( 'ok' );
	}

	/**
	 * Cron / arka plan işi.
	 */
	public static function run_job( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 ) {
			return;
		}

		@set_time_limit( 600 );
		@ini_set( 'memory_limit', '512M' );

		if ( self::get_status( $post_id ) === self::STATUS_PROCESSING ) {
			return;
		}

		self::set_status( $post_id, self::STATUS_PROCESSING, __( 'Ses dosyası oluşturuluyor…', 'mevzu2' ) );

		if ( ! Mevzu_TTS_Daily_Limit::can_use() ) {
			self::set_status( $post_id, self::STATUS_ERROR, __( 'Günlük kota doldu.', 'mevzu2' ) );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			self::set_status( $post_id, self::STATUS_ERROR, __( 'Yazı yayında değil.', 'mevzu2' ) );
			return;
		}

		if ( ! mevzu_tts_post_should_process( $post_id ) ) {
			self::set_status( $post_id, self::STATUS_IDLE, '' );
			return;
		}

		if ( ! Mevzu_AI_Client::is_ready() ) {
			self::set_status( $post_id, self::STATUS_ERROR, Mevzu_AI_Client::get_unavailable_message() );
			return;
		}

		$file_manager = new KKEREM_TTS_File_Manager();
		$tts_service  = new KKEREM_TTS_Service();

		$file_manager->delete_audio_file( $post_id );

		$content = mevzu_tts_get_post_content_for_audio( $post_id );
		if ( $content === '' ) {
			self::set_status( $post_id, self::STATUS_ERROR, __( 'Yazı içeriği boş.', 'mevzu2' ) );
			return;
		}

		$result = $tts_service->synthesize_text( $content, $post_id );

		if ( $result && ! empty( $result['file_url'] ) ) {
			self::set_status( $post_id, self::STATUS_DONE, __( 'Ses dosyası hazır.', 'mevzu2' ) );
			KKEREM_TTS_Admin::debug_log( 'TTS kuyruk tamam: #' . $post_id );
			return;
		}

		self::set_status( $post_id, self::STATUS_ERROR, __( 'Ses oluşturulamadı. Yeniden deneyin.', 'mevzu2' ) );
		KKEREM_TTS_Admin::debug_log( 'TTS kuyruk hata: #' . $post_id );
	}

	/**
	 * Editör API yanıtı.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_editor_state( $post_id ) {
		$post_id = (int) $post_id;
		$file_manager = new KKEREM_TTS_File_Manager();
		$audio_exists   = $file_manager->audio_file_exists( $post_id );
		$file_info      = $audio_exists ? $file_manager->get_audio_file_info( $post_id ) : null;

		return array(
			'job_status'      => self::get_status( $post_id ),
			'job_message'     => self::get_message( $post_id ),
			'panel_visible'   => mevzu_tts_post_should_process( $post_id ),
			'yz_manset'       => mevzu_tts_post_has_yapay_zeka_manset( $post_id ),
			'in_target_cat'   => mevzu_tts_post_in_target_category( $post_id ),
			'target_category' => mevzu_tts_target_category_id(),
			'audio_exists'    => $audio_exists,
			'file_info'       => $file_info,
			'quota'           => array(
				'used'      => Mevzu_TTS_Daily_Limit::get_usage(),
				'limit'     => Mevzu_TTS_Daily_Limit::get_limit(),
				'remaining' => Mevzu_TTS_Daily_Limit::remaining(),
			),
		);
	}
}

Mevzu_TTS_Queue::init();
