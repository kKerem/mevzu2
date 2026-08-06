<?php
/**
 * Instagram Graph API entegrasyonu.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instagram platformu.
 */
class Mevzu_Social_Platform_Instagram extends Mevzu_Social_Platform_Base {

	public function get_name() {
		return 'Instagram';
	}

	public function get_slug() {
		return 'instagram';
	}

	public function is_enabled() {
		return (bool) $this->opt( 'enabled' );
	}

	public function validate_credentials() {
		$ig_user_id = trim( (string) $this->opt( 'ig_user_id' ) );
		$token      = trim( (string) $this->opt( 'access_token' ) );

		if ( ! $ig_user_id || ! $token ) {
			return array(
				'success' => false,
				'message' => 'Instagram User ID ve Access Token gereklidir.',
			);
		}

		$url  = 'https://graph.facebook.com/v19.0/' . $ig_user_id;
		$args = array(
			'timeout' => 30,
			'body'    => array(
				'access_token' => $token,
				'fields'       => 'username,id',
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
				'message' => 'Bağlantı başarılı: @' . ( $body['username'] ?? $body['id'] ),
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

		$ig_user_id = trim( (string) $this->opt( 'ig_user_id' ) );
		$token      = trim( (string) $this->opt( 'access_token' ) );

		if ( ! $ig_user_id || ! $token ) {
			return array(
				'success' => false,
				'message' => 'Instagram API bilgileri eksik.',
			);
		}

		$image_url = $this->get_featured_image_url( $post_id, 'full' );
		if ( ! $image_url ) {
			return array(
				'success' => false,
				'message' => 'Instagram paylaşımı için öne çıkarılmış görsel zorunludur.',
			);
		}

		$caption = $this->build_message( $post_id );

		// 1. Medya konteynerı oluştur.
		$create_url = 'https://graph.facebook.com/v19.0/' . $ig_user_id . '/media';
		$create_args = array(
			'timeout' => 60,
			'body'    => array(
				'image_url'    => $image_url,
				'caption'      => $caption,
				'access_token' => $token,
			),
		);

		$create = $this->remote_post( $create_url, $create_args );

		if ( ! $create['success'] ) {
			return $create;
		}

		$creation_id = $create['body']['id'] ?? '';
		if ( ! $creation_id ) {
			return array(
				'success' => false,
				'message' => 'Instagram medya konteynerı oluşturulamadı.',
			);
		}

		// 2. Konteynerı yayınla.
		$publish_url  = 'https://graph.facebook.com/v19.0/' . $ig_user_id . '/media_publish';
		$publish_args = array(
			'timeout' => 60,
			'body'    => array(
				'creation_id'  => $creation_id,
				'access_token' => $token,
			),
		);

		$publish = $this->remote_post( $publish_url, $publish_args );

		if ( ! $publish['success'] ) {
			return $publish;
		}

		$remote_id = $publish['body']['id'] ?? '';

		return array(
			'success'   => true,
			'message'   => 'Instagram\'da paylaşıldı.',
			'remote_id' => $remote_id,
		);
	}
}
