<?php
/**
 * Generic webhook entegrasyonu.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook platformu.
 */
class Mevzu_Social_Platform_Webhook extends Mevzu_Social_Platform_Base {

	public function get_name() {
		return 'Webhook';
	}

	public function get_slug() {
		return 'webhook';
	}

	public function is_enabled() {
		return (bool) $this->opt( 'enabled' );
	}

	public function validate_credentials() {
		$url = trim( (string) $this->opt( 'url' ) );

		if ( ! $url ) {
			return array(
				'success' => false,
				'message' => 'Webhook URL adresi gereklidir.',
			);
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return array(
				'success' => false,
				'message' => 'Geçerli bir URL girilmedi.',
			);
		}

		// Basit bir HEAD/GET isteği ile URL'nin erişilebilir olduğunu kontrol et.
		$response = wp_remote_head( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		return array(
			'success' => true,
			'message' => 'Webhook URL\'si erişilebilir görünüyor (HTTP ' . $code . ').',
		);
	}

	public function share( $post_id ) {
		$post_id = (int) $post_id;

		$url = trim( (string) $this->opt( 'url' ) );
		if ( ! $url ) {
			return array(
				'success' => false,
				'message' => 'Webhook URL adresi eksik.',
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => 'Yazı bulunamadı.',
			);
		}

		$payload = array(
			'site'        => get_bloginfo( 'name' ),
			'site_url'    => home_url(),
			'post_id'     => $post_id,
			'title'       => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
			'excerpt'     => html_entity_decode( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 55, '…' ), ENT_QUOTES, 'UTF-8' ),
			'url'         => get_permalink( $post_id ),
			'image'       => $this->get_featured_image_url( $post_id, 'full' ),
			'published_at' => get_post_time( 'c', false, $post ),
		);

		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Source'     => 'Mevzu2/SocialAutomation',
			),
			'body'    => wp_json_encode( $payload ),
		);

		$result = $this->remote_post( $url, $args );

		if ( ! $result['success'] ) {
			return $result;
		}

		return array(
			'success'   => true,
			'message'   => 'Webhook gönderildi.',
			'remote_id' => '',
		);
	}
}
