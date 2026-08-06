<?php
/**
 * Sosyal Otomasyon admin ayar sayfası.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin sınıfı.
 */
class Mevzu_Social_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mevzu_social_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Alt menüyü ekle.
	 */
	public function add_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'mevzu-ayarlar',
			__( 'Sosyal Otomasyon', 'mevzu2' ),
			__( 'Sosyal Otomasyon', 'mevzu2' ),
			'manage_options',
			'mevzu-social-automation',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Admin assetlerini yükle.
	 *
	 * @param string $hook Sayfa hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== 'mevzu-ayarlar_page_mevzu-social-automation' && $hook !== 'toplevel_page_mevzu-ayarlar' ) {
			return;
		}

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Ayarları kaydet.
	 */
	public function save_settings() {
		if ( ! isset( $_POST['mevzu_social_save'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['mevzu_social_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_social_nonce'] ) ), 'mevzu_social_settings' ) ) {
			return;
		}

		$auto_share       = isset( $_POST['mevzu_social_auto_share'] ) ? 1 : 0;
		$image_required   = isset( $_POST['mevzu_social_image_required'] ) ? 1 : 0;
		$message_template = isset( $_POST['mevzu_social_message_template'] )
			? sanitize_textarea_field( wp_unslash( $_POST['mevzu_social_message_template'] ) )
			: "{title}\n{url}";

		update_option( 'options_social_auto_share', $auto_share );
		update_option( 'options_social_image_required', $image_required );
		update_option( 'options_social_message_template', $message_template );

		$platforms = Mevzu_Social_Automation::get_platform_slugs();
		foreach ( $platforms as $slug ) {
			$enabled = isset( $_POST[ 'mevzu_social_' . $slug . '_enabled' ] ) ? 1 : 0;
			update_option( 'options_social_' . $slug . '_enabled', $enabled );

			$fields = $this->get_platform_fields( $slug );
			foreach ( $fields as $field_key => $sanitize ) {
				$input_name = 'mevzu_social_' . $slug . '_' . $field_key;
				if ( ! isset( $_POST[ $input_name ] ) ) {
					continue;
				}
				$value = wp_unslash( $_POST[ $input_name ] );
				if ( 'url' === $sanitize ) {
					$value = esc_url_raw( $value );
				} elseif ( 'textarea' === $sanitize ) {
					$value = sanitize_textarea_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
				update_option( 'options_social_' . $slug . '_' . $field_key, $value );
			}
		}

		wp_safe_redirect( add_query_arg( 'saved', '1', admin_url( 'admin.php?page=mevzu-social-automation' ) ) );
		exit;
	}

	/**
	 * Platform başına ayar alanlarını döndür.
	 *
	 * @param string $slug Platform slug.
	 * @return array<string, string>
	 */
	private function get_platform_fields( $slug ) {
		$map = array(
			'facebook'  => array(
				'page_id'      => 'text',
				'access_token' => 'text',
			),
			'twitter'   => array(
				'api_key'             => 'text',
				'api_secret'          => 'text',
				'access_token'        => 'text',
				'access_token_secret' => 'text',
			),
			'telegram'  => array(
				'bot_token' => 'text',
				'chat_id'   => 'text',
			),
			'instagram' => array(
				'ig_user_id'   => 'text',
				'access_token' => 'text',
			),
			'webhook'   => array(
				'url' => 'url',
			),
		);

		return isset( $map[ $slug ] ) ? $map[ $slug ] : array();
	}

	/**
	 * Bağlantı testi AJAX handler.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'mevzu_social_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Yetkisiz işlem.' ) );
		}

		$slug = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';
		if ( ! $slug ) {
			wp_send_json_error( array( 'message' => 'Platform belirtilmedi.' ) );
		}

		$platform = Mevzu_Social_Automation::get_platform( $slug );
		if ( ! $platform ) {
			wp_send_json_error( array( 'message' => 'Platform bulunamadı.' ) );
		}

		// AJAX testi sırasında POST'tan gelen geçici değerleri kullanmak için
		// opsiyonel olarak form verisini işleyebiliriz. Şimdilik kaydedilmiş değerleri kullanıyoruz.
		$result = $platform->validate_credentials();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Ayar sayfasını render et.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$auto_share       = (bool) get_option( 'options_social_auto_share', 1 );
		$image_required   = (bool) get_option( 'options_social_image_required', 0 );
		$message_template = get_option( 'options_social_message_template', "{title}\n{url}" );

		$platforms = Mevzu_Social_Automation::get_platform_slugs();
		?>
		<div class="wrap mevzu-settings-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Sosyal Otomasyon', 'mevzu2' ); ?></h1>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Ayarlar kaydedildi.', 'mevzu2' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'mevzu_social_settings', 'mevzu_social_nonce' ); ?>

				<h2 class="title"><?php esc_html_e( 'Genel Ayarlar', 'mevzu2' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Otomatik Paylaşım', 'mevzu2' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mevzu_social_auto_share" value="1" <?php checked( $auto_share ); ?>>
								<?php esc_html_e( 'Yeni yazılarda aktif platformları varsayılan olarak işaretle', 'mevzu2' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Görsel Zorunluluğu', 'mevzu2' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mevzu_social_image_required" value="1" <?php checked( $image_required ); ?>>
								<?php esc_html_e( 'Öne çıkarılmış görsel olmayan yazılar hiçbir platformda paylaşılmasın (Instagram hariç zaten zorunludur)', 'mevzu2' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="mevzu_social_message_template"><?php esc_html_e( 'Mesaj Şablonu', 'mevzu2' ); ?></label>
						</th>
						<td>
							<textarea name="mevzu_social_message_template" id="mevzu_social_message_template" rows="3" class="large-text"><?php echo esc_textarea( $message_template ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Kullanılabilir değişkenler: {title}, {excerpt}, {url}, {site_name}', 'mevzu2' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<hr class="my-4">

				<h2 class="title"><?php esc_html_e( 'Platform Ayarları', 'mevzu2' ); ?></h2>

				<?php foreach ( $platforms as $slug ) : ?>
					<?php
					$platform = Mevzu_Social_Automation::get_platform( $slug );
					if ( ! $platform ) {
						continue;
					}
					$enabled = (bool) get_option( 'options_social_' . $slug . '_enabled', 0 );
					$fields  = $this->get_platform_fields( $slug );
					?>
					<div class="mevzu-social-platform-card" style="background:#fff;border:1px solid #c3c4c7;padding:16px 20px;margin-bottom:20px;border-radius:4px;">
						<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
							<h3 style="margin:0;font-size:16px;"><?php echo esc_html( $platform->get_name() ); ?></h3>
							<label style="display:flex;align-items:center;gap:6px;font-weight:600;">
								<input type="checkbox" name="mevzu_social_<?php echo esc_attr( $slug ); ?>_enabled" value="1" <?php checked( $enabled ); ?>>
								<?php esc_html_e( 'Aktif', 'mevzu2' ); ?>
							</label>
						</div>

						<table class="form-table" role="presentation">
							<?php foreach ( $fields as $field_key => $sanitize ) : ?>
								<?php
								$option_key = 'options_social_' . $slug . '_' . $field_key;
								$value      = get_option( $option_key, '' );
								$input_id   = 'mevzu_social_' . $slug . '_' . $field_key;
								$input_name = 'mevzu_social_' . $slug . '_' . $field_key;
								$label      = $this->field_label( $slug, $field_key );
								$type       = 'url' === $sanitize ? 'url' : 'text';
								?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
									</th>
									<td>
										<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $input_name ); ?>" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
									</td>
								</tr>
							<?php endforeach; ?>
						</table>

						<p>
							<button type="button" class="button mevzu-social-test-btn" data-platform="<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Bağlantıyı Test Et', 'mevzu2' ); ?>
							</button>
							<span class="mevzu-social-test-result" style="margin-left:8px;"></span>
						</p>
					</div>
				<?php endforeach; ?>

				<?php submit_button( __( 'Ayarları Kaydet', 'mevzu2' ), 'primary', 'mevzu_social_save' ); ?>
			</form>
		</div>

		<script>
		(function($) {
			$('.mevzu-social-test-btn').on('click', function() {
				var $btn = $(this);
				var $result = $btn.next('.mevzu-social-test-result');
				var platform = $btn.data('platform');

				$btn.prop('disabled', true);
				$result.text('<?php echo esc_js( __( 'Kontrol ediliyor...', 'mevzu2' ) ); ?>');

				$.post(ajaxurl, {
					action: 'mevzu_social_test_connection',
					platform: platform,
					nonce: '<?php echo esc_js( wp_create_nonce( 'mevzu_social_settings' ) ); ?>'
				}, function(response) {
					if (response.success) {
						$result.html('<span style="color:#00a32a;">✓ ' + response.data.message + '</span>');
					} else {
						$result.html('<span style="color:#d63638;">✗ ' + (response.data.message || '<?php echo esc_js( __( 'Bilinmeyen hata', 'mevzu2' ) ); ?>') + '</span>');
					}
				}).fail(function() {
					$result.html('<span style="color:#d63638;">✗ <?php echo esc_js( __( 'Sunucu hatası', 'mevzu2' ) ); ?></span>');
				}).always(function() {
					$btn.prop('disabled', false);
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Alan etiketini döndür.
	 *
	 * @param string $slug Platform slug.
	 * @param string $key  Alan anahtarı.
	 * @return string
	 */
	private function field_label( $slug, $key ) {
		$labels = array(
			'page_id'             => 'Page ID',
			'access_token'        => 'Access Token',
			'api_key'             => 'API Key',
			'api_secret'          => 'API Secret',
			'access_token_secret' => 'Access Token Secret',
			'bot_token'           => 'Bot Token',
			'chat_id'             => 'Chat ID',
			'ig_user_id'          => 'Instagram User ID',
			'url'                 => 'Webhook URL',
		);

		$label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
		return $label;
	}
}
