<?php
/**
 * Twitter / X API v2 entegrasyonu (OAuth 1.0a user context).
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twitter / X platformu.
 */
class Mevzu_Social_Platform_Twitter extends Mevzu_Social_Platform_Base {

	public function get_name() {
		return 'Twitter / X';
	}

	public function get_slug() {
		return 'twitter';
	}

	public function is_enabled() {
		return (bool) $this->opt( 'enabled' );
	}

	public function validate_credentials() {
		$required = array(
			'api_key'             => trim( (string) $this->opt( 'api_key' ) ),
			'api_secret'          => trim( (string) $this->opt( 'api_secret' ) ),
			'access_token'        => trim( (string) $this->opt( 'access_token' ) ),
			'access_token_secret' => trim( (string) $this->opt( 'access_token_secret' ) ),
		);

		foreach ( $required as $key => $value ) {
			if ( ! $value ) {
				return array(
					'success' => false,
					'message' => 'Twitter API Key, Secret, Access Token ve Access Token Secret gereklidir.',
				);
			}
		}

		// Kimlik bilgilerini test etmek için basit bir hesap bilgisi isteği gönder.
		$url  = 'https://api.twitter.com/2/users/me';
		$args = $this->oauth_request( $url, 'GET', array(), $required );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['data']['username'] ) ) {
			return array(
				'success' => true,
				'message' => 'Bağlantı başarılı: @' . $body['data']['username'],
			);
		}

		$message = is_array( $body ) && ! empty( $body['detail'] )
			? $body['detail']
			: ( is_array( $body ) && ! empty( $body['errors'][0]['message'] )
				? $body['errors'][0]['message']
				: ( 'HTTP ' . $code ) );

		return array(
			'success' => false,
			'message' => $message,
		);
	}

	public function share( $post_id ) {
		$post_id = (int) $post_id;

		$credentials = array(
			'api_key'             => trim( (string) $this->opt( 'api_key' ) ),
			'api_secret'          => trim( (string) $this->opt( 'api_secret' ) ),
			'access_token'        => trim( (string) $this->opt( 'access_token' ) ),
			'access_token_secret' => trim( (string) $this->opt( 'access_token_secret' ) ),
		);

		foreach ( $credentials as $value ) {
			if ( ! $value ) {
				return array(
					'success' => false,
					'message' => 'Twitter API bilgileri eksik.',
				);
			}
		}

		$text = $this->build_message( $post_id );
		// X'te maksimum 280 karakter (düz metin). URL otomatik 23 karakter sayılır,
		// ancak biz basitçe 250 karakter sınırı uygulayalım.
		if ( mb_strlen( $text, 'UTF-8' ) > 250 ) {
			$text = mb_substr( $text, 0, 247, 'UTF-8' ) . '…';
		}

		$url  = 'https://api.twitter.com/2/tweets';
		$args = $this->oauth_request(
			$url,
			'POST',
			array( 'text' => $text ),
			$credentials
		);

		$result = $this->remote_post( $url, $args );

		if ( ! $result['success'] ) {
			return $result;
		}

		$remote_id = $result['body']['data']['id'] ?? '';

		return array(
			'success'   => true,
			'message'   => 'Twitter\'da paylaşıldı.',
			'remote_id' => $remote_id,
		);
	}

	/**
	 * OAuth 1.0a imzalı istek argümanları oluştur.
	 *
	 * @param string $url         Endpoint URL.
	 * @param string $method      HTTP metodu.
	 * @param array  $body_params JSON body payload.
	 * @param array  $credentials API bilgileri.
	 * @return array
	 */
	private function oauth_request( $url, $method, $body_params, $credentials ) {
		$oauth_params = array(
			'oauth_consumer_key'     => $credentials['api_key'],
			'oauth_nonce'            => md5( uniqid( wp_rand(), true ) ),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => time(),
			'oauth_token'            => $credentials['access_token'],
			'oauth_version'          => '1.0',
		);

		// JSON body imzaya dahil edilmez.
		ksort( $oauth_params );

		$encoded_params = array();
		foreach ( $oauth_params as $key => $value ) {
			$encoded_params[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
		}

		$base_string = strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( implode( '&', $encoded_params ) );
		$signing_key = rawurlencode( $credentials['api_secret'] ) . '&' . rawurlencode( $credentials['access_token_secret'] );

		$oauth_params['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) );

		// Authorization header.
		$header_parts = array();
		foreach ( $oauth_params as $key => $value ) {
			$header_parts[] = $key . '="' . rawurlencode( $value ) . '"';
		}

		$args = array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'OAuth ' . implode( ', ', $header_parts ),
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $body_params ) ) {
			$args['body'] = wp_json_encode( $body_params );
		}

		return $args;
	}
}
