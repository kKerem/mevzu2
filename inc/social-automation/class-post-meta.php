<?php
/**
 * Yazı düzenleme ekranındaki "Sosyal Medyada Paylaş" meta box.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta sınıfı.
 */
class Mevzu_Social_Post_Meta {

	const META_KEY = 'mevzu_social_share_to';
	const NONCE    = 'mevzu_social_post_meta_nonce';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 15, 2 );
	}

	/**
	 * Meta box ekle.
	 */
	public function add_meta_box() {
		add_meta_box(
			'mevzu-social-share',
			__( 'Sosyal Medyada Paylaş', 'mevzu2' ),
			array( $this, 'render' ),
			'post',
			'side',
			'default'
		);
	}

	/**
	 * Meta box içeriğini render et.
	 *
	 * @param WP_Post $post Yazı nesnesi.
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );

		$selected = get_post_meta( $post->ID, self::META_KEY, true );
		if ( ! is_array( $selected ) ) {
			$selected = array();
		}

		$auto_share = (bool) get_option( 'options_social_auto_share', 1 );
		// Hiç kaydedilmemişse varsayılan olarak otomatik paylaşım ayarını yansıt.
		if ( ! metadata_exists( 'post', $post->ID, self::META_KEY ) ) {
			$selected = $auto_share ? Mevzu_Social_Automation::get_platform_slugs() : array();
		}

		$active = Mevzu_Social_Automation::get_active_platforms();

		if ( empty( $active ) ) :
			?>
			<p class="description">
				<?php esc_html_e( 'Aktif platform bulunmuyor. Ayarlar → Sosyal Otomasyon bölümünden en az bir platform açın.', 'mevzu2' ); ?>
			</p>
			<?php
			return;
		endif;
		?>

		<p class="description">
			<?php esc_html_e( 'Bu yazı yayına alındığında işaretli platformlarda otomatik paylaşılacak.', 'mevzu2' ); ?>
		</p>

		<div class="mevzu-social-platforms">
			<?php foreach ( $active as $slug => $platform ) : ?>
				<label style="display:flex;align-items:center;gap:6px;margin-bottom:6px;cursor:pointer;">
					<input type="checkbox" name="mevzu_social_share_to[]" value="<?php echo esc_attr( $slug ); ?>"
						<?php checked( in_array( $slug, $selected, true ) ); ?>>
					<?php echo esc_html( $platform->get_name() ); ?>
				</label>
			<?php endforeach; ?>
		</div>

		<?php
		$logs = Mevzu_Social_Logger::get( $post->ID );
		if ( ! empty( $logs ) ) :
			?>
			<hr style="margin:12px 0;border-color:#dcdcde;">
			<h4 style="margin:0 0 6px;font-size:12px;"><?php esc_html_e( 'Paylaşım Geçmişi', 'mevzu2' ); ?></h4>
			<ul style="margin:0;padding-left:16px;font-size:12px;">
				<?php foreach ( array_slice( $logs, -5 ) as $log ) : ?>
					<li style="margin-bottom:4px;">
						<span class="dashicons dashicons-<?php echo ! empty( $log['success'] ) ? 'yes' : 'no-alt'; ?>"
							style="color:<?php echo ! empty( $log['success'] ) ? '#00a32a' : '#d63638'; ?>;font-size:14px;width:14px;height:14px;vertical-align:middle;"></span>
						<strong><?php echo esc_html( ucfirst( $log['platform'] ) ); ?>:</strong>
						<?php echo esc_html( $log['message'] ); ?>
						<em>(<?php echo esc_html( $log['time'] ); ?>)</em>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php
	}

	/**
	 * Meta box verilerini kaydet.
	 *
	 * @param int     $post_id Yazı ID.
	 * @param WP_Post $post    Yazı nesnesi.
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( $post->post_type !== 'post' ) {
			return;
		}

		$selected = isset( $_POST['mevzu_social_share_to'] ) && is_array( $_POST['mevzu_social_share_to'] )
			? array_map( 'sanitize_key', $_POST['mevzu_social_share_to'] )
			: array();

		$allowed = Mevzu_Social_Automation::get_platform_slugs();
		$selected = array_values( array_intersect( $selected, $allowed ) );

		update_post_meta( $post_id, self::META_KEY, $selected );
	}
}
