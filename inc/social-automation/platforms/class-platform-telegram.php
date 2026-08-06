<?php
/**
 * Telegram Bot API entegrasyonu.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Telegram platformu.
 */
class Mevzu_Social_Platform_Telegram extends Mevzu_Social_Platform_Base {

	public function get_name() {
		return 'Telegram';
	}

	public function get_slug() {
		return 'telegram';
	}

	public function is_enabled() {
		return (bool) $this->opt( 'enabled' );
	}

	public function validate_credentials() {
		$token   = trim( (string) $this->opt( 'bot_token' ) );
		$chat_id = trim( (string) $this->opt( 'chat_id' ) );

		if ( ! $token || ! $chat_id ) {
			return array(
				'success' => false,
				'message' => 'Telegram Bot Token ve Chat ID gereklidir.',
			);
		}

		$url  = 'https://api.telegram.org/bot' . $token . '/getMe';
		$args = array( 'timeout' => 30 );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['ok'] ) && ! empty( $body['result']['username'] ) ) {
			return array(
				'success' => true,
				'message' => 'Bağlantı başarılı: @' . $body['result']['username'],
			);
		}

		$message = is_array( $body ) && ! empty( $body['description'] )
			? $body['description']
			: ( 'HTTP ' . $code );

		return array(
			'success' => false,
			'message' => $message,
		);
	}

	public function share( $post_id ) {
		$post_id = (int) $post_id;

		$token   = trim( (string) $this->opt( 'bot_token' ) );
		$chat_id = trim( (string) $this->opt( 'chat_id' ) );

		if ( ! $token || ! $chat_id ) {
			return array(
				'success' => false,
				'message' => 'Telegram Bot Token veya Chat ID eksik.',
			);
		}

		$message = $this->build_message( $post_id );
		$image   = $this->get_featured_image_url( $post_id, 'large' );

		if ( $image ) {
			$api_url = 'https://api.telegram.org/bot' . $token . '/sendPhoto';
			$args    = array(
				'timeout' => 90,
				'body'    => array(
					'chat_id' => $chat_id,
					'photo'   => $image,
					'caption' => $message,
				),
			);
		} else {
			$api_url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
			$args    = array(
				'timeout' => 60,
				'body'    => array(
					'chat_id'                  => $chat_id,
					'text'                     => $message,
					'disable_web_page_preview' => 'false',
				),
			);
		}

		$result = $this->remote_post( $api_url, $args );

		if ( ! $result['success'] ) {
			return $result;
		}

		$remote_id = '';
		if ( ! empty( $result['body']['result']['message_id'] ) ) {
			$remote_id = (string) $result['body']['result']['message_id'];
		}

		return array(
			'success'   => true,
			'message'   => 'Telegram\'da paylaşıldı.',
			'remote_id' => $remote_id,
		);
	}
}
