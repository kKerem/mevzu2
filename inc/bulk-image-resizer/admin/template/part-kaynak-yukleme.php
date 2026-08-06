<?php
/**
 * Hız & Güvenlik — Kaynak Yükleme Ayarları
 *
 * Swiper, Select2 ve jQuery'nin local/CDN yüklenme tercihleri.
 */
namespace bulk_image_resizer;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$defaults = array(
	'swiper_source'  => 'local',
	'select2_source' => 'local',
	'jquery_source'  => 'wordpress',
);

$opts = get_option( 'dsxmlrpc-settings', array() );
if ( ! is_array( $opts ) ) {
	$opts = array();
}
$opts = wp_parse_args( $opts, $defaults );
?>

<p class="description">
	<?php _e( 'Swiper, Select2 ve jQuery kütüphanelerinin local tema dosyalarından mı yoksa harici CDN üzerinden mi yükleneceğini seçin. Varsayılan olarak local dosyalar kullanılır.', 'bulk-image-resizer' ); ?>
</p>

<form id="hg-kaynak-yukleme-form" class="hg-form-table mt-3">
	<?php wp_nonce_field( 'hiz_guvenlik_nonce', 'nonce' ); ?>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="swiper_source"><?php _e( 'Swiper', 'bulk-image-resizer' ); ?></label>
				</th>
				<td>
					<select id="swiper_source" name="swiper_source" class="regular-text">
						<option value="local" <?php selected( $opts['swiper_source'], 'local' ); ?>><?php _e( 'Local tema dosyaları', 'bulk-image-resizer' ); ?></option>
						<option value="cdn" <?php selected( $opts['swiper_source'], 'cdn' ); ?>><?php _e( 'CDN (jsdelivr)', 'bulk-image-resizer' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="select2_source"><?php _e( 'Select2', 'bulk-image-resizer' ); ?></label>
				</th>
				<td>
					<select id="select2_source" name="select2_source" class="regular-text">
						<option value="local" <?php selected( $opts['select2_source'], 'local' ); ?>><?php _e( 'Local tema dosyaları', 'bulk-image-resizer' ); ?></option>
						<option value="cdn" <?php selected( $opts['select2_source'], 'cdn' ); ?>><?php _e( 'CDN (jsdelivr)', 'bulk-image-resizer' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jquery_source"><?php _e( 'jQuery', 'bulk-image-resizer' ); ?></label>
				</th>
				<td>
					<select id="jquery_source" name="jquery_source" class="regular-text">
						<option value="wordpress" <?php selected( $opts['jquery_source'], 'wordpress' ); ?>><?php _e( 'WordPress kendi jQuery\'si', 'bulk-image-resizer' ); ?></option>
						<option value="theme" <?php selected( $opts['jquery_source'], 'theme' ); ?>><?php _e( 'Tema jQuery CDN (Cloudflare)', 'bulk-image-resizer' ); ?></option>
					</select>
					<p class="description">
						<?php _e( 'WordPress kendi jQuery\'si seçildiğinde tema footer\'ındaki ikinci jQuery kaldırılır.', 'bulk-image-resizer' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<p class="submit">
		<button type="submit" class="button button-primary">
			<?php _e( 'Kaydet', 'bulk-image-resizer' ); ?>
		</button>
		<span id="hg-kaynak-status" class="ms-2"></span>
	</p>
</form>
