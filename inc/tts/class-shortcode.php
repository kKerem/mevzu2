<?php
/**
 * Shortcode sınıfı
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KKEREM_TTS_Shortcode {

	private $file_manager;

	public function __construct() {
		$this->file_manager = new KKEREM_TTS_File_Manager();
		add_shortcode( 'kkerem_tts', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Shortcode: [kkerem_tts] veya [kkerem_tts variant="wave"]
	 *
	 * variant: full — progress + ses | wave — yalnızca play + dalga çubukları
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'  => get_the_ID(),
				'autoplay' => 'false',
				'label'    => __( 'Yazıyı sesli dinle', 'mevzu2' ),
				'variant'  => 'wave',
			),
			$atts
		);

		$post_id = (int) $atts['post_id'];
		if ( ! $post_id || ! function_exists( 'mevzu_tts_post_can_display' ) || ! mevzu_tts_post_can_display( $post_id ) ) {
			return '';
		}

		$file_info = $this->file_manager->get_audio_file_info( $post_id );
		if ( empty( $file_info['file_url'] ) ) {
			return '';
		}

		$this->enqueue_player_assets();

		$file_url      = esc_url( $file_info['file_url'] );
		$autoplay_attr = ( 'true' === $atts['autoplay'] ) ? ' data-autoplay="1"' : '';
		$uid           = 'mtp-' . $post_id . '-' . wp_unique_id();
		$variant       = sanitize_key( $atts['variant'] );
		if ( ! in_array( $variant, array( 'full', 'wave' ), true ) ) {
			$variant = 'wave';
		}

		ob_start();
		if ( 'full' === $variant ) {
			$this->render_player_full( $uid, $file_url, $autoplay_attr, $atts['label'] );
		} else {
			$this->render_player_wave( $uid, $file_url, $autoplay_attr, $atts['label'] );
		}
		return ob_get_clean();
	}

	/**
	 * Tam kontrollü oynatıcı (progress + ses).
	 */
	private function render_player_full( $uid, $file_url, $autoplay_attr, $label ) {
		$label_html = $label !== '' ? '<span class="mtp-label small">' . esc_html( $label ) . '</span>' : '';
		?>
		<div class="kkerem-tts-player">
			<div class="mtp-wrapper mtp-wrapper--full w-100 p-2" id="<?php echo esc_attr( $uid ); ?>" data-mtp-player data-mtp-variant="full">
				<audio class="mtp-audio" preload="metadata"<?php echo $autoplay_attr; ?>>
					<source src="<?php echo esc_url( $file_url ); ?>" type="audio/mpeg">
				</audio>
				<div class="mtp-controls">
					<?php echo $label_html; ?>
					<button type="button" class="mtp-btn mtp-btn-play" aria-label="<?php esc_attr_e( 'Oynat', 'mevzu2' ); ?>"></button>
					<div class="mtp-progress-wrap">
						<div class="mtp-progress" role="slider" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e( 'İlerleme', 'mevzu2' ); ?>">
							<div class="mtp-progress-buffered"></div>
							<div class="mtp-progress-filled"></div>
						</div>
						<div class="mtp-times">
							<span class="mtp-time-cur">0:00</span>
							<span class="mtp-time-dur">0:00</span>
						</div>
					</div>
					<button type="button" class="mtp-btn mtp-btn-mute" aria-label="<?php esc_attr_e( 'Sesi kapat', 'mevzu2' ); ?>"></button>
					<div class="mtp-volume" role="slider" aria-label="<?php esc_attr_e( 'Ses seviyesi', 'mevzu2' ); ?>">
						<div class="mtp-volume-filled"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Kompakt dalga oynatıcı — yalnızca play / animasyonlu çubuklar.
	 */
	private function render_player_wave( $uid, $file_url, $autoplay_attr, $label ) {
		$label_html = $label !== '' ? '<div class="mtp-wave-label small">' . esc_html( $label ) . '</div>' : '';
		?>
		<div
			class="kkerem-tts-player kkerem-tts-player--wave position-relative" 
			role="button"
			tabindex="0"
			aria-label="<?php esc_attr_e( 'Sesli dinle', 'mevzu2' ); ?>"
			data-label-play="<?php esc_attr_e( 'Sesli dinle', 'mevzu2' ); ?>"
			data-label-pause="<?php esc_attr_e( 'Duraklat', 'mevzu2' ); ?>"
		>
			<div class="mtp-wrapper mtp-wrapper--wave" id="<?php echo esc_attr( $uid ); ?>" data-mtp-player data-mtp-variant="wave">
				<audio class="mtp-audio" preload="metadata"<?php echo $autoplay_attr; ?>>
					<source src="<?php echo esc_url( $file_url ); ?>" type="audio/mpeg">
				</audio>
				<div class="mtp-wave-controls text-dark">
					<span class="mtp-btn-wave" aria-hidden="true">
						<span class="mtp-wave-play" aria-hidden="true">
                            <i class="ri-voice-ai-line fs-6"></i>
						</span>
						<span class="mtp-wave-bars" aria-hidden="true">
							<span class="mtp-wave-bar"></span>
							<span class="mtp-wave-bar"></span>
							<span class="mtp-wave-bar"></span>
							<span class="mtp-wave-bar"></span>
							<span class="mtp-wave-bar"></span>
						</span>
					</span>
                    <span class="badge bg-primary fz-10 position-absolute end-0 rounded-xl" style="top:-10px;border-bottom-right-radius:0">Yapay Zeka</span>
                    <?php echo $label_html; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function enqueue_player_assets() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		wp_enqueue_style( 'mevzu-tts-player', MEVZU_TTS_URL . 'assets/css/tts-player.css', array(), MEVZU_TTS_VERSION );
		wp_enqueue_script( 'mevzu-tts-player', MEVZU_TTS_URL . 'assets/js/tts-player.js', array(), MEVZU_TTS_VERSION, true );
	}

	public function enqueue_frontend_scripts() {
		if ( is_singular( 'post' ) && function_exists( 'mevzu_tts_post_can_display' ) && mevzu_tts_post_can_display( get_queried_object_id() ) ) {
			$this->enqueue_player_assets();
		}
	}
}
