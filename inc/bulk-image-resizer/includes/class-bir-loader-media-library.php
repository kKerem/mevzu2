<?php
/**
* Medya kütüphanesi ile ilgili hook yönetimi.
* @since 2.0.0
*/
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_loader_media_library extends Bir_loader {

	/**
	 * Medya kütüphanesi için gerekli loader'ları başlatır.
	 */
	public function __construct() {
		// Medya kütüphanesi toplu işlem listesine seçenek ekler.
		add_filter('bulk_actions-upload',  [$this, 'bulk_action_upload'] );
		// Seçilen toplu işlemi uygular.
		add_filter('handle_bulk_actions-upload',  [$this, 'handle_bulk_actions_upload'] , 10, 3);
		// Medya satır aksiyonlarına "Optimize Et" ekler.
		add_filter('media_row_actions', [$this, 'add_optimize_row_action'], 10, 2);
		// Medya listesinde Bulk Actions'tan bağımsız optimize alanı ekler.
		add_action('restrict_manage_posts', [$this, 'render_optimize_selector']);
		// Toplu optimize seçimini upload ekranında işler.
		add_action('load-upload.php', [$this, 'handle_optimize_selector_submit']);
		// Tekli optimize işlemi (satır aksiyonu).
		add_action('admin_post_bir_optimize_attachment', [$this, 'handle_optimize_single_action']);
		// Medya düzenleme ekranı (post.php?action=edit) için optimize butonu.
		add_action('post_submitbox_misc_actions', [$this, 'render_attachment_edit_optimize_button']);
		add_action('attachment_submitbox_misc_actions', [$this, 'render_attachment_edit_optimize_button']);
		// Yükleme ekranında optimizasyon durumu bilgisini gösterir.
		add_action('post-plupload-upload-ui', [$this, 'info_upload']);
		// Medya ekranında optimize/geri yükle sonuçlarını bildirir.
		add_action('admin_notices', [$this, 'media_action_notice']);
		// WordPress'in sildiği original_image meta bilgisini gerektiğinde geri ekler.
		add_filter('wp_update_attachment_metadata',  [$this, 'generate_attachment_metadata'], 10, 2);
		// JPEG kalite filtresini uygular.
		add_filter( 'jpeg_quality',  [$this, 'jpeg_quality'] );
	}

	/**
	 * Toplu işlem listesinden gelen aksiyonu uygular.
	 */
	public function handle_bulk_actions_upload  ($redirect_url, $action, $post_ids) {
		if ($action == 'op-resize-original-images') {
			foreach ($post_ids as $post_id) {
				Bir_facade::process_image($post_id);
			}
		}

		if ($action == 'op-revert-original-images') {
			foreach ($post_ids as $post_id) {
				Bir_facade::restore($post_id);
			}
		}

		return $redirect_url;
	}

	/**
	 * Medya satır aksiyonlarına "Optimize Et" bağlantısı ekler.
	 */
	public function add_optimize_row_action($actions, $post) {
		if (!$post || $post->post_type !== 'attachment' || !current_user_can('upload_files')) {
			return $actions;
		}
		if (!$this->is_image_attachment($post->ID)) {
			return $actions;
		}

		$url = $this->build_optimize_action_url((int) $post->ID, admin_url('upload.php'));

		$actions['bir_optimize'] = '<a href="' . esc_url($url) . '">' . esc_html__('Optimize Et', 'bulk-image-resizer') . '</a>';
		return $actions;
	}

	/**
	 * Medya düzenleme ekranında (attachment edit) optimize butonunu gösterir.
	 */
	public function render_attachment_edit_optimize_button() {
		global $post;
		if (!$post || $post->post_type !== 'attachment' || !current_user_can('upload_files')) {
			return;
		}
		if (!$this->is_image_attachment($post->ID)) {
			return;
		}

		$redirect_to = admin_url('post.php?post=' . (int) $post->ID . '&action=edit');
		$url = $this->build_optimize_action_url((int) $post->ID, $redirect_to);
		?>
		<div class="misc-pub-section">
			<a class="button button-secondary" href="<?php echo esc_url($url); ?>">
				<?php esc_html_e('Optimize Et', 'bulk-image-resizer'); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Medya listesinde toplu optimize alanı (Bulk Actions dışında).
	 */
	public function render_optimize_selector() {
		global $typenow;
		if ($typenow !== 'attachment' || !current_user_can('upload_files')) {
			return;
		}
		?>
		<select name="bir_optimize_action" id="bir_optimize_action">
			<option value=""><?php esc_html_e('Optimize Et', 'bulk-image-resizer'); ?></option>
			<option value="optimize-selected"><?php esc_html_e('Seçili görselleri optimize et', 'bulk-image-resizer'); ?></option>
			<option value="restore-selected"><?php esc_html_e('Seçili görselleri geri yükle', 'bulk-image-resizer'); ?></option>
		</select>
		<?php submit_button(__('Uygula', 'bulk-image-resizer'), 'secondary', 'bir_optimize_apply', false); ?>
		<?php
	}

	/**
	 * Medya ekranında gönderilen "Optimize Et" seçimini işler.
	 */
	public function handle_optimize_selector_submit() {
		if (!is_admin() || !current_user_can('upload_files')) {
			return;
		}
		if (!isset($_REQUEST['bir_optimize_apply'], $_REQUEST['bir_optimize_action'])) {
			return;
		}

		$opt_action = sanitize_text_field(wp_unslash($_REQUEST['bir_optimize_action']));
		if (!in_array($opt_action, ['optimize-selected', 'restore-selected'], true)) {
			return;
		}

		$post_ids_raw = $_REQUEST['media'] ?? [];
		$post_ids = array_filter(array_map('absint', (array) $post_ids_raw));

		if (empty($post_ids)) {
			$redirect = add_query_arg(['bir_optimized' => 0, 'bir_restored' => 0], admin_url('upload.php'));
			wp_safe_redirect($redirect);
			exit;
		}

		$optimized = 0;
		$restored = 0;
		foreach ($post_ids as $post_id) {
			if (!$this->is_image_attachment($post_id)) {
				continue;
			}
			if ($opt_action === 'optimize-selected') {
				Bir_facade::process_image($post_id);
				if (!Bir_facade::is_error()) {
					$optimized++;
				}
			} elseif ($opt_action === 'restore-selected') {
				Bir_facade::restore($post_id);
				if (!Bir_facade::is_error()) {
					$restored++;
				}
			}
		}

		$redirect = add_query_arg(
			[
				'bir_optimized' => $optimized,
				'bir_restored'  => $restored,
			],
			admin_url('upload.php')
		);
		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * Tekli medya satırı aksiyonundaki optimize işlemini çalıştırır.
	 */
	public function handle_optimize_single_action() {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('Bu işlem için yetkiniz yok.', 'bulk-image-resizer'));
		}

		$attachment_id = isset($_GET['attachment_id']) ? absint($_GET['attachment_id']) : 0;
		if (!$attachment_id) {
			wp_safe_redirect(admin_url('upload.php'));
			exit;
		}

		check_admin_referer('bir_optimize_attachment_' . $attachment_id);

		$optimized = 0;
		if ($this->is_image_attachment($attachment_id)) {
			Bir_facade::process_image($attachment_id);
			if (!Bir_facade::is_error()) {
				$optimized = 1;
			}
		}

		$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : admin_url('upload.php');
		if (!$redirect_to) {
			$redirect_to = admin_url('upload.php');
		}
		$redirect = add_query_arg(['bir_optimized' => $optimized], $redirect_to);
		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * Attachment ID'nin optimize edilebilir görsel olup olmadığını kontrol eder.
	 */
	private function is_image_attachment($attachment_id) {
		$mime = (string) get_post_mime_type($attachment_id);
		return $mime !== '' && strpos($mime, 'image/') === 0;
	}

	/**
	 * Tekli optimize işlemi için güvenli admin-post URL'i üretir.
	 */
	private function build_optimize_action_url($attachment_id, $redirect_to) {
		$attachment_id = absint($attachment_id);
		$base_url = admin_url('admin-post.php');
		$query = 'action=bir_optimize_attachment&attachment_id=' . $attachment_id . '&redirect_to=' . rawurlencode($redirect_to);
		$url = $base_url . '?' . $query;
		return wp_nonce_url($url, 'bir_optimize_attachment_' . $attachment_id);
	}

	/**
	 * Medya kütüphanesi toplu işlem listesine eklenti seçeneklerini ekler.
	 */
	public function bulk_action_upload ($bulk_actions) {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		if ($bir_options->plugin_active()) {
			$bulk_actions['op-resize-original-images'] = __('Optimize et (Görsel Optimizasyonu eklentisi)', 'bulk-image-resizer');
		}
		$bulk_actions['op-revert-original-images'] = __('Geri yükle (Görsel Optimizasyonu eklentisi)', 'bulk-image-resizer');
		return $bulk_actions;
	}

	/**
	 * Medya yükleme ekranında bilgilendirme metni gösterir.
	 */
	public function info_upload() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		echo '<div class="notice notice-info inline"><p>';
		if ($bir_options->optimize_active == 1 || $bir_options->webp_active == 1) {
			echo 'Görseller <strong><a href="'.admin_url('admin.php?page=hiz-guvenlik').'" >Hız &amp; Güvenlik</a></strong> ile optimize ediliyor.';
		} else if ($bir_options->resize_active == 1) {
			echo '<strong><a href="'.admin_url('admin.php?page=hiz-guvenlik').'" >Hız &amp; Güvenlik</a></strong>: '.$bir_options->max_width.' x '.$bir_options->max_height.' px üzerindeki görseller yeniden boyutlandırılacak.';
		} else {
			echo 'Görselleri optimize etmek için <strong><a href="'.admin_url('admin.php?page=hiz-guvenlik').'" >Hız &amp; Güvenlik</a></strong> ayarlarını yapılandırın.';
		}
		echo '</p></div>';
	}

	/**
	 * Medya sayfasında optimize/geri yükle işlem sonucu bildirimi.
	 */
	public function media_action_notice() {
		if (!is_admin() || !isset($_GET['bir_optimized']) && !isset($_GET['bir_restored'])) {
			return;
		}
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || !in_array($screen->id, ['upload', 'attachment'], true)) {
			return;
		}

		$optimized = isset($_GET['bir_optimized']) ? absint($_GET['bir_optimized']) : 0;
		$restored = isset($_GET['bir_restored']) ? absint($_GET['bir_restored']) : 0;
		if ($optimized === 0 && $restored === 0) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('İşlenecek uygun görsel bulunamadı ya da seçim yapılmadı.', 'bulk-image-resizer') . '</p></div>';
			return;
		}

		$parts = [];
		if ($optimized > 0) {
			$parts[] = sprintf(esc_html__('%d görsel optimize edildi.', 'bulk-image-resizer'), $optimized);
		}
		if ($restored > 0) {
			$parts[] = sprintf(esc_html__('%d görsel geri yüklendi.', 'bulk-image-resizer'), $restored);
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(implode(' ', $parts)) . '</p></div>';
	}

	/**
	 * WordPress'in sildiği original_image meta bilgisini geri yükler.
	 * original_image kaldırılacaksa değer boş string olarak gelir.
	 */
	public function generate_attachment_metadata($metadata, $attachment_id) {
		$meta_old = wp_get_attachment_metadata($attachment_id);
		
		if (array_key_exists('original_image', $metadata) && $metadata['original_image'] === '') {
			unset($metadata['original_image']);
		} else if (isset($meta_old['original_image']) && !isset($metadata['original_image'])) {
			// Dosyanın gerçekten var olduğunu doğrula.
			$upload_dir = wp_upload_dir();
			$original_image = $upload_dir['basedir']. "/" .dirname($metadata['file']).'/'.$meta_old['original_image'];
			if (is_file($original_image)) {
				$metadata['original_image'] = $meta_old['original_image'];
			}
		}
		
		return $metadata;
	}

	/**
	 * JPEG kalite filtresini döndürür.
	 */
	public function jpeg_quality() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		return $bir_options->quality;
	}
	
}

new Bir_loader_media_library();