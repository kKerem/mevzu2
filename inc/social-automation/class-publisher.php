<?php
/**
 * Yayınlanma anında otomatik paylaşım yapan sınıf.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publisher sınıfı.
 */
class Mevzu_Social_Publisher {

	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'on_publish' ), 20, 3 );
	}

	/**
	 * Yazı yayınlandığında otomatik paylaşım yap.
	 *
	 * @param string   $new_status Yeni durum.
	 * @param string   $old_status Eski durum.
	 * @param WP_Post  $post       Yazı nesnesi.
	 */
	public function on_publish( $new_status, $old_status, $post ) {
		// Sadece post türü için ve yayına geçişlerde çalış.
		if ( $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}
		if ( ! $post instanceof WP_Post || $post->post_type !== 'post' ) {
			return;
		}

		$post_id = (int) $post->ID;

		$selected   = get_post_meta( $post_id, 'mevzu_social_share_to', true );
		$auto_share = (bool) get_option( 'options_social_auto_share', 1 );

		// İlk yayınlama anında save_post henüz çalışmadığı için meta boş olabilir.
		// Bu durumda form verisinden seçimleri oku (nonce doğrulayarak).
		if ( ( ! is_array( $selected ) || empty( $selected ) ) &&
			isset( $_POST['mevzu_social_post_meta_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_social_post_meta_nonce'] ) ), 'mevzu_social_post_meta_nonce' ) &&
			isset( $_POST['mevzu_social_share_to'] ) && is_array( $_POST['mevzu_social_share_to'] ) ) {
			$selected = array_map( 'sanitize_key', wp_unslash( $_POST['mevzu_social_share_to'] ) );
		}

		// Kullanıcı hiç seçim yapmamışsa ve otomatik paylaşım açıksa
		// tüm aktif platformlara paylaş.
		if ( ! is_array( $selected ) || empty( $selected ) ) {
			if ( ! $auto_share ) {
				return;
			}
			$selected = Mevzu_Social_Automation::get_platform_slugs();
		}

		// Görsel zorunluluğu varsa kontrol et (Instagram hariç kendi kontrolünü yapar).
		$image_required = (bool) get_option( 'options_social_image_required', 0 );
		$has_image      = (bool) get_post_thumbnail_id( $post_id );
		if ( $image_required && ! $has_image ) {
			return;
		}

		$active = Mevzu_Social_Automation::get_active_platforms();

		foreach ( $selected as $slug ) {
			$slug = sanitize_key( $slug );
			if ( ! isset( $active[ $slug ] ) ) {
				continue;
			}

			$platform = $active[ $slug ];
			$result   = $platform->share( $post_id );

			Mevzu_Social_Logger::add(
				$post_id,
				$slug,
				$result['success'],
				$result['message'],
				$result['remote_id'] ?? ''
			);
		}
	}
}
