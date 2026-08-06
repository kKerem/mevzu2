<?php
/**
 * Sosyal platformlar için temel arayüz.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tüm sosyal medya platformlarının uygulaması gereken abstract sınıf.
 */
abstract class Mevzu_Social_Platform_Base {

	/**
	 * Platformun okunabilir adı.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Platformun slug'ı (meta key, log vb. için).
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Platform ayarında aktif mi?
	 *
	 * @return bool
	 */
	abstract public function is_enabled();

	/**
	 * API bilgileri doğru çalışıyor mu?
	 *
	 * @return array{success:bool,message:string}
	 */
	abstract public function validate_credentials();

	/**
	 * Gönderiyi ilgili platformda paylaş.
	 *
	 * @param int $post_id Paylaşılacak yazı ID.
	 * @return array{success:bool,message:string,remote_id?:string}
	 */
	abstract public function share( $post_id );

	/**
	 * Seçenek okuma yardımcısı.
	 *
	 * @param string $key     options_ sonrası anahtar.
	 * @param mixed  $default Varsayılan değer.
	 * @return mixed
	 */
	protected function opt( $key, $default = '' ) {
		return get_option( 'options_social_' . $this->get_slug() . '_' . $key, $default );
	}

	/**
	 * Yazıdan paylaşım mesajı oluştur.
	 *
	 * @param int $post_id Yazı ID.
	 * @return string
	 */
	protected function build_message( $post_id ) {
		$template = get_option( 'options_social_message_template', '{title}' . "\n" . '{url}' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$excerpt_source = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		$excerpt        = wp_trim_words( wp_strip_all_tags( $excerpt_source ), 40, '…' );

		$replacements = array(
			'{title}'     => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
			'{excerpt}'   => html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' ),
			'{url}'       => get_permalink( $post_id ),
			'{site_name}' => get_bloginfo( 'name' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Yazının öne çıkarılmış görsel URL'sini döndür.
	 *
	 * @param int    $post_id    Yazı ID.
	 * @param string $image_size Görsel boyutu.
	 * @return string
	 */
	protected function get_featured_image_url( $post_id, $image_size = 'large' ) {
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( ! $thumb_id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $thumb_id, $image_size );
		return $url ? $url : '';
	}

	/**
	 * WordPress HTTP API ile POST isteği gönder.
	 *
	 * @param string $url  Endpoint URL.
	 * @param array  $args wp_remote_post argümanları.
	 * @return array{success:bool,message:string,body?:array}
	 */
	protected function remote_post( $url, $args ) {
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) && ! empty( $data['error']['message'] )
				? $data['error']['message']
				: ( 'HTTP ' . $code . ': ' . $body );
			return array(
				'success' => false,
				'message' => $message,
			);
		}

		return array(
			'success' => true,
			'message' => 'OK',
			'body'    => is_array( $data ) ? $data : array(),
		);
	}
}
