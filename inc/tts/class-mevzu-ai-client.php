<?php
/**
 * Mevzu² AI — lisans sunucusu üzerinden ses sentezi istemcisi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mevzu_AI_Client {

	const QUOTA_TRANSIENT = 'mevzu_ai_quota_cache';
	const QUOTA_TTL       = 120;

	/**
	 * Servis kullanılabilir mi? (modül + lisans + anahtar)
	 */
	public static function is_ready() {
		if ( ! mevzu_yz_module_active() ) {
			return false;
		}
		if ( ! class_exists( 'Mevzu_License' ) ) {
			return false;
		}
		if ( Mevzu_License::get_license_key() === '' ) {
			return false;
		}
		return Mevzu_License::can_use_ai_services();
	}

	/**
	 * @return string
	 */
	public static function get_unavailable_message() {
		if ( ! mevzu_yz_module_active() ) {
			return 'Mevzu² AI modülü kapalı.';
		}
		if ( ! class_exists( 'Mevzu_License' ) || Mevzu_License::get_license_key() === '' ) {
			return 'Mevzu² AI için geçerli bir lisans anahtarı gerekli.';
		}
		$status = Mevzu_License::get_license_status();
		if ( ( $status['status'] ?? '' ) === 'banned' ) {
			return ! empty( $status['ban_reason'] )
				? (string) $status['ban_reason']
				: 'Lisans erişiminiz kapatılmıştır.';
		}
		return 'Mevzu² AI şu an kullanılamıyor. Lisansınızı kontrol edin.';
	}

	/**
	 * Google TTS istek gövdesi ile sentez (tek parça).
	 *
	 * @param array $request_data prepare_request_data çıktısı.
	 * @return array|\WP_Error audio_content (base64) ve quota.
	 */
	public static function synthesize_request( array $request_data ) {
		if ( ! self::is_ready() ) {
			return new WP_Error( 'mevzu_ai_unavailable', self::get_unavailable_message() );
		}

		$text = isset( $request_data['input']['text'] ) ? (string) $request_data['input']['text'] : '';
		if ( $text === '' ) {
			return new WP_Error( 'empty_text', 'Okunacak metin boş.' );
		}

		$voice_name    = isset( $request_data['voice']['name'] ) ? (string) $request_data['voice']['name'] : 'tr-TR-Standard-A';
		$language_code = isset( $request_data['voice']['languageCode'] ) ? (string) $request_data['voice']['languageCode'] : 'tr-TR';
		$speaking_rate = isset( $request_data['audioConfig']['speakingRate'] )
			? (float) $request_data['audioConfig']['speakingRate']
			: (float) get_option( 'kkerem_tts_speaking_rate', '1.0' );
		$pitch = isset( $request_data['audioConfig']['pitch'] )
			? (float) $request_data['audioConfig']['pitch']
			: (float) get_option( 'kkerem_tts_pitch', '0.0' );

		return self::synthesize_text( $text, $voice_name, $language_code, $speaking_rate, $pitch );
	}

	/**
	 * @return array|\WP_Error
	 */
	public static function synthesize_text(
		$text,
		$voice_name = null,
		$language_code = null,
		$speaking_rate = null,
		$pitch = null
	) {
		if ( ! self::is_ready() ) {
			return new WP_Error( 'mevzu_ai_unavailable', self::get_unavailable_message() );
		}

		$text = (string) $text;
		if ( function_exists( 'wp_check_invalid_utf8' ) ) {
			$text = wp_check_invalid_utf8( $text, true );
		}
		if ( $text === '' ) {
			return new WP_Error( 'empty_text', 'Okunacak metin boş.' );
		}

		$voice_name    = $voice_name ?: get_option( 'kkerem_tts_voice_name', 'tr-TR-Standard-A' );
		$language_code = $language_code ?: get_option( 'kkerem_tts_language_code', 'tr-TR' );
		$speaking_rate = $speaking_rate !== null ? (float) $speaking_rate : (float) get_option( 'kkerem_tts_speaking_rate', '1.0' );
		$pitch         = $pitch !== null ? (float) $pitch : (float) get_option( 'kkerem_tts_pitch', '0.0' );

		$request_time  = time();
		$request_nonce = wp_generate_password( 20, false, false );
		$text_hash     = hash( 'sha256', $text );

		$sign_payload = array(
			'license_key'   => Mevzu_License::get_license_key(),
			'domain'        => self::get_domain(),
			'site_id'       => Mevzu_License::get_site_id(),
			'request_time'  => $request_time,
			'request_nonce' => $request_nonce,
			'text_hash'     => $text_hash,
			'voice_name'    => (string) $voice_name,
			'language_code' => (string) $language_code,
		);

		$body = array(
			'license_key'   => $sign_payload['license_key'],
			'domain'        => $sign_payload['domain'],
			'site_id'       => $sign_payload['site_id'],
			'request_time'  => $request_time,
			'request_nonce' => $request_nonce,
			'signature'     => Mevzu_License::sign_hmac_payload( $sign_payload ),
			'text'          => $text,
			'voice_name'    => $voice_name,
			'language_code' => $language_code,
			'speaking_rate' => $speaking_rate,
			'pitch'         => $pitch,
		);

		$url = ( defined( 'MEVZU_AI_API_BASE' ) ? MEVZU_AI_API_BASE : 'https://lisans.kkerem.com/api/v1/mevzu-ai/' ) . 'synthesize/';

		$json_body = wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json_body ) {
			return new WP_Error( 'mevzu_ai_encode', 'İstek gövdesi oluşturulamadı.' );
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 180,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => $json_body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'mevzu_ai_network', 'Mevzu² AI servisine ulaşılamadı.' );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'mevzu_ai_invalid', 'Mevzu² AI yanıtı geçersiz.' );
		}

		if ( ! empty( $data['quota'] ) && is_array( $data['quota'] ) ) {
			Mevzu_TTS_Daily_Limit::apply_remote_quota( $data['quota'] );
		}

		if ( empty( $data['success'] ) ) {
			$message = isset( $data['message'] ) ? (string) $data['message'] : 'Mevzu² AI ses üretimi başarısız.';
			if ( ! empty( $data['detail'] ) ) {
				$message .= ' [' . (string) $data['detail'] . ']';
			}
			$err_code = isset( $data['code'] ) ? (string) $data['code'] : 'mevzu_ai_failed';
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && WP_DEBUG_LOG ) {
				error_log( 'Mevzu² AI synthesize HTTP ' . $code . ' (' . $err_code . '): ' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) );
			}
			return new WP_Error( $err_code, $message, array( 'status' => $code ) );
		}

		$audio_content = '';
		if ( ! empty( $data['audio_content'] ) ) {
			$audio_content = (string) $data['audio_content'];
		} elseif ( ! empty( $data['audio_url'] ) ) {
			$audio_response = wp_remote_get(
				(string) $data['audio_url'],
				array(
					'timeout'   => 120,
					'sslverify' => true,
				)
			);
			if ( is_wp_error( $audio_response ) ) {
				return new WP_Error( 'mevzu_ai_audio_fetch', 'Mevzu² AI ses indirilemedi.' );
			}
			$audio_code = (int) wp_remote_retrieve_response_code( $audio_response );
			$binary     = wp_remote_retrieve_body( $audio_response );
			if ( $audio_code !== 200 || $binary === '' ) {
				return new WP_Error( 'mevzu_ai_audio_fetch', 'Mevzu² AI ses indirilemedi (HTTP ' . $audio_code . ').' );
			}
			$audio_content = base64_encode( $binary );
		}

		if ( $audio_content === '' ) {
			return new WP_Error( 'mevzu_ai_empty', 'Mevzu² AI boş ses yanıtı döndü.' );
		}

		return array(
			'audio_content' => $audio_content,
			'quota'         => isset( $data['quota'] ) ? $data['quota'] : array(),
		);
	}

	/**
	 * Kota bilgisini sunucudan al (önbellekli).
	 *
	 * @return array|null used, limit, remaining
	 */
	public static function fetch_quota( $force = false ) {
		if ( ! self::is_ready() ) {
			return null;
		}

		if ( ! $force ) {
			$cached = get_transient( self::QUOTA_TRANSIENT );
			if ( is_array( $cached ) && isset( $cached['date'] ) && $cached['date'] === gmdate( 'Y-m-d' ) ) {
				Mevzu_TTS_Daily_Limit::apply_remote_quota( $cached );
				return $cached;
			}
		}

		$request_time  = time();
		$request_nonce = wp_generate_password( 20, false, false );
		$sign_payload  = array(
			'license_key'   => Mevzu_License::get_license_key(),
			'domain'        => self::get_domain(),
			'site_id'       => Mevzu_License::get_site_id(),
			'request_time'  => $request_time,
			'request_nonce' => $request_nonce,
			'action'        => 'quota',
		);

		$url = ( defined( 'MEVZU_AI_API_BASE' ) ? MEVZU_AI_API_BASE : 'https://lisans.kkerem.com/api/v1/mevzu-ai/' ) . 'quota/';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key'   => $sign_payload['license_key'],
					'domain'        => $sign_payload['domain'],
					'site_id'       => $sign_payload['site_id'],
					'request_time'  => $request_time,
					'request_nonce' => $request_nonce,
					'signature'     => Mevzu_License::sign_hmac_payload( $sign_payload ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['success'] ) || empty( $data['quota'] ) ) {
			return null;
		}

		$quota = $data['quota'];
		set_transient( self::QUOTA_TRANSIENT, $quota, self::QUOTA_TTL );
		Mevzu_TTS_Daily_Limit::apply_remote_quota( $quota );

		return $quota;
	}

	/**
	 * @return string
	 */
	private static function get_domain() {
		if ( class_exists( 'Mevzu_License' ) && method_exists( 'Mevzu_License', 'normalize_domain_for_api' ) ) {
			return Mevzu_License::normalize_domain_for_api();
		}
		$host = (string) parse_url( get_site_url(), PHP_URL_HOST );
		$host = strtolower( trim( $host ) );
		if ( strpos( $host, 'www.' ) === 0 ) {
			$host = substr( $host, 4 );
		}
		return $host;
	}
}
