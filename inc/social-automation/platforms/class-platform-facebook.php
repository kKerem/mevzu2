<?php
/**
 * Facebook Graph API entegrasyonu.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Facebook platformu.
 */
class Mevzu_Social_Platform_Facebook extends Mevzu_Social_Platform_Base {

	public function get_name() {
		return 'Facebook';
	}

	public function get_slug() {
		return 'facebook';
	}

	public function is_enabled() {
		return (bool) $this->opt( 'enabled' );
	}

	public function validate_credentials() {
		$page_id   = trim( (string) $this->opt( 'page_id' ) );
		$token     = trim( (string) $this->opt( 'access_token' ) );

		if ( ! $page_id || ! $token ) {
			return array(
				'success' => false,
				'message' => 'Facebook Page ID ve Access Token gereklidir.',
			);
		}

		$url  = 'https://graph.facebook.com/v19.0/' . $page_id;
		$args = array(
			'timeout' => 30,
			'body'    => array(
				'access_token' => $token,
				'fields'       => 'name,id',
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['id'] ) ) {
			return array(
				'success' => true,
				'message' => 'Bağlantı başarılı: ' . ( $body['name'] ?? $body['id'] ),
			);
		}

		$message = is_array( $body ) && ! empty( $body['error']['message'] )
			? $body['error']['message']
			: ( 'HTTP ' . $code );

		return array(
			'success' => false,
			'message' => $message,
		);
	}

	public function share( $post_id ) {
		$post_id = (int) $post_id;

		$page_id = trim( (string) $this->opt( 'page_id' ) );
		$token   = trim( (string) $this->opt( 'access_token' ) );

		if ( ! $page_id || ! $token ) {
			return array(
				'success' => false,
				'message' => 'Facebook Page ID veya Access Token eksik.',
			);
		}

		$message = $this->build_message( $post_id );
		$url     = get_permalink( $post_id );

		$api_url = 'https://graph.facebook.com/v19.0/' . $page_id . '/feed';
		$args    = array(
			'timeout' => 60,
			'body'    => array(
				'access_token' => $token,
				'message'      => $message,
				'link'         => $url,
			),
		);

		$result = $this->remote_post( $api_url, $args );

		if ( ! $result['success'] ) {
			return $result;
		}

		$remote_id = $result['body']['id'] ?? '';

		return array(
			'success'   => true,
			'message'   => 'Facebook\'ta paylaşıldı.',
			'remote_id' => $remote_id,
		);
	}
}
