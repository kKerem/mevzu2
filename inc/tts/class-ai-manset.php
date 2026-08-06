<?php
/**
 * Yapay Zeka Manşeti — anasayfa bar + modal oynatıcı.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mevzu_TTS_AI_Manset {

	public function __construct() {
		add_action( 'mevzu_homepage_after_top_promos', array( $this, 'render_homepage_bar' ), 15 );
		add_action( 'wp_footer', array( $this, 'render_modal_footer' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 25 );
		add_action( 'wp_ajax_mevzu_yzm_line_audio', array( $this, 'ajax_line_audio' ) );
		add_action( 'wp_ajax_nopriv_mevzu_yzm_line_audio', array( $this, 'ajax_line_audio' ) );
		add_action( 'wp_ajax_mevzu_yzm_modal_slides', array( $this, 'ajax_modal_slides' ) );
		add_action( 'wp_ajax_nopriv_mevzu_yzm_modal_slides', array( $this, 'ajax_modal_slides' ) );
	}

	/**
	 * Ön yüz varlıkları.
	 */
	public function maybe_enqueue_assets() {
		if ( ! is_front_page() || ! self::is_enabled() ) {
			return;
		}
		if ( empty( mevzu_tts_get_todays_yapay_zeka_playlist() ) ) {
			return;
		}

		wp_enqueue_style( 'mevzu-yzm-manset', MEVZU_TTS_URL . 'assets/css/ai-manset.css', array(), MEVZU_TTS_VERSION );
		wp_enqueue_script( 'mevzu-yzm-manset', MEVZU_TTS_URL . 'assets/js/ai-manset.min.js', array(), MEVZU_TTS_VERSION, true );
		wp_localize_script(
			'mevzu-yzm-manset',
			'mevzuYzm',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'mevzu_yzm_line' ),
				'modalNonce' => wp_create_nonce( 'mevzu_yzm_modal' ),
				'intro'      => mevzu_tts_get_yzm_intro_text(),
				'outro'      => mevzu_tts_get_yzm_outro_text(),
				'i18n'       => array(
					'loading'    => __( 'Haberler yükleniyor…', 'mevzu2' ),
					'loadError'  => __( 'Haberler yüklenemedi. Lütfen tekrar deneyin.', 'mevzu2' ),
					'share'      => __( 'Paylaş', 'mevzu2' ),
					'copied'     => __( 'Bağlantı kopyalandı', 'mevzu2' ),
					'readStory'  => __( 'Habere git', 'mevzu2' ),
					'prev'       => __( 'Önceki Haber', 'mevzu2' ),
					'next'       => __( 'Sonraki Haber', 'mevzu2' ),
					'play'       => __( 'Oynat', 'mevzu2' ),
					'pause'      => __( 'Duraklat', 'mevzu2' ),
				),
			)
		);
	}

	/**
	 * Başlangıç / kısa metin — Mevzu² AI.
	 */
	public function ajax_line_audio() {
		check_ajax_referer( 'mevzu_yzm_line', 'nonce' );

		$text = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : '';
		$text = trim( wp_strip_all_tags( $text ) );
		if ( $text === '' || mb_strlen( $text ) > 500 ) {
			wp_send_json_error( array( 'message' => 'Geçersiz metin.' ), 400 );
		}

		if ( ! Mevzu_AI_Client::is_ready() ) {
			wp_send_json_error( array( 'message' => Mevzu_AI_Client::get_unavailable_message() ), 503 );
		}

		if ( ! Mevzu_TTS_Daily_Limit::can_use() ) {
			wp_send_json_error( array( 'message' => 'Mevzu² AI günlük kotanız doldu.' ), 429 );
		}

		$service = new KKEREM_TTS_Service();
		$url     = $service->get_or_create_cached_line_audio( $text );

		if ( $url ) {
			wp_send_json_success( array( 'url' => $url ) );
		}

		wp_send_json_error( array( 'message' => 'Ses oluşturulamadı.' ), 500 );
	}

	/**
	 * Modal haber slaytları — yalnızca manşet açıldığında (DOM şişmesin).
	 */
	public function ajax_modal_slides() {
		check_ajax_referer( 'mevzu_yzm_modal', 'nonce' );

		if ( ! self::is_enabled() ) {
			wp_send_json_error( array( 'message' => 'Kapalı.' ), 403 );
		}

		$playlist = mevzu_tts_get_todays_yapay_zeka_playlist();
		if ( empty( $playlist ) ) {
			wp_send_json_error( array( 'message' => 'Liste boş.' ), 404 );
		}

		$total    = count( $playlist );
		$slides   = array();
		$minimal  = array();

		foreach ( $playlist as $i => $item ) {
			$lines = mevzu_tts_get_yzm_reading_lines( (int) $item['id'] );
			if ( empty( $lines ) && ! empty( $item['excerpt'] ) ) {
				$lines = mevzu_tts_split_reading_lines( $item['excerpt'] );
			}

			$slides[] = $this->render_news_slide_html( $item, $i, $total, $lines );
			$minimal[] = array(
				'id'    => (int) $item['id'],
				'audio' => $item['audio'],
				'lines' => $lines,
			);
		}

		wp_send_json_success(
			array(
				'playlist' => $minimal,
				'slides'   => $slides,
			)
		);
	}

	public static function is_enabled() {
		return (int) get_opt_g( 'options_yapay_zeka_manseti', 'goster', 0 ) === 1;
	}

	public static function get_bar_title() {
		return function_exists( 'mevzu_tts_get_yzm_bar_title' )
			? mevzu_tts_get_yzm_bar_title()
			: 'Günün Manşetleri';
	}

	/**
	 * Robot + konuşma balonu (çubuk üstü / karşılama slaytı).
	 */
	private function render_robot_bubble( $text, $extra_class = '' ) {
		?>
		<div class="yzm-robot-bubble d-flex align-items-end gap-2 <?php echo esc_attr( $extra_class ); ?>">
			<div class="yzm-robot-avatar rounded-circle bg-dark d-flex align-items-center justify-content-center flex-shrink-0 text-primary shadow-sm" aria-hidden="true">
				<i class="ri-robot-2-line"></i>
			</div>
			<div class="yzm-speech-bubble bg-white text-dark rounded shadow-sm flex-grow-1 min-w-0 p-3">
				<p class="yzm-speech-text mb-0 small fw-medium"><?php echo esc_html( $text ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Lyrics slaytı (haber / karşılama / kapanış — aynı görünüm).
	 *
	 * @param array<string,mixed> $args Slayt alanları.
	 */
	private function render_lyrics_slide_html( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'slide_class'   => 'yzm-slide--news',
				'extra_attrs'   => '',
				'headline'      => '',
				'lines'         => array(),
				'badge'         => '',
				'badge_class'   => 'bg-primary',
				'date'          => '',
				'counter'       => '',
				'thumb'         => '',
				'show_toolbar'  => false,
				'permalink'     => '',
				'share_title'   => '',
				'aria_label'    => '',
			)
		);

		$lines      = is_array( $args['lines'] ) ? $args['lines'] : array();
		$aria_label = $args['aria_label'] !== '' ? $args['aria_label'] : $args['headline'];
		$media_attr = '';
		if ( ! empty( $args['thumb'] ) ) {
			$media_attr = ' style="background-image:url(\'' . esc_url( $args['thumb'] ) . '\')"';
		}

		ob_start();
		?>
		<div class="swiper-slide <?php echo esc_attr( $args['slide_class'] ); ?>" <?php echo $args['extra_attrs']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $args['show_toolbar'] && ! empty( $args['permalink'] ) ) : ?>
			<div class="yzm-slide-toolbar d-flex align-items-center justify-content-between position-absolute start-0 end-0 z-3 px-3 pt-1">
				<button type="button" class="btn btn-dark btn-sm rounded-pill px-2 d-flex align-items-center yzm-slide-share" data-share-url="<?php echo esc_url( $args['permalink'] ); ?>" data-share-title="<?php echo esc_attr( $args['share_title'] ); ?>" aria-label="<?php esc_attr_e( 'Paylaş', 'mevzu2' ); ?>">
					<i class="ri-share-line me-1" aria-hidden="true"></i>
					<small>Haberi Paylaş</small>
				</button>
				<a href="<?php echo esc_url( $args['permalink'] ); ?>" class="btn btn-dark btn-sm rounded-pill px-2 d-flex align-items-center yzm-slide-goto">
					<small><?php esc_html_e( 'Habere git', 'mevzu2' ); ?></small>
					<i class="ri-arrow-right-s-line"></i>
				</a>
			</div>
			<?php endif; ?>
			<div class="yzm-slide-ribbon d-flex flex-wrap align-items-center gap-2 position-absolute start-0 end-0 z-2 px-3">
				<?php if ( ! empty( $args['badge'] ) ) : ?>
					<span class="yzm-slide-cat badge <?php echo esc_attr( $args['badge_class'] ); ?>"><?php echo esc_html( $args['badge'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $args['date'] ) ) : ?>
					<span class="badge rounded-pill bg-dark bg-opacity-50 text-white yzm-slide-date"><i class="ri-time-line" aria-hidden="true"></i> <?php echo esc_html( $args['date'] ); ?></span>
				<?php endif; ?>
				<?php if ( $args['counter'] !== '' ) : ?>
					<span class="badge rounded-pill bg-dark bg-opacity-50 text-white ms-auto yzm-slide-counter"><?php echo esc_html( $args['counter'] ); ?></span>
				<?php endif; ?>
			</div>
			<div class="yzm-slide-media<?php echo empty( $args['thumb'] ) ? ' yzm-slide-media--yzm' : ''; ?>"<?php echo $media_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-hidden="true"></div>
			<div class="yzm-slide-scrim yzm-slide-scrim--lyrics" aria-hidden="true"></div>
			<div class="yzm-slide-lyrics-wrap p-3">
				<?php if ( $args['headline'] !== '' ) : ?>
					<h3 class="yzm-slide-headline small fw-bold mt-3"><?php echo esc_html( $args['headline'] ); ?></h3>
				<?php endif; ?>
				<div class="yzm-lyrics py-3" role="article" aria-label="<?php echo esc_attr( $aria_label ); ?>">
					<?php foreach ( $lines as $line ) : ?>
						<p class="yzm-lyric-line"><?php echo esc_html( $line ); ?></p>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Haber slaytı HTML (AJAX ile enjekte edilir).
	 *
	 * @param array<string,mixed> $item   Playlist öğesi.
	 * @param int                 $index  Sıra (0 tabanlı).
	 * @param int                 $total  Toplam haber.
	 * @param string[]            $lines  Okuma satırları.
	 */
	private function render_news_slide_html( $item, $index, $total, $lines ) {
		$badge_class = 'bg-primary';
		if ( ! empty( $item['category_color'] ) ) {
			$badge_class = 'bg-' . sanitize_html_class( $item['category_color'] );
		}

		return $this->render_lyrics_slide_html(
			array(
				'slide_class'  => 'yzm-slide--news',
				'extra_attrs'  => 'data-post-id="' . esc_attr( (string) $item['id'] ) . '"',
				'headline'     => $item['title'],
				'lines'        => $lines,
				'badge'        => $item['category'] ?? '',
				'badge_class'  => $badge_class,
				'date'         => $item['date'] ?? '',
				'counter'      => ( $index + 1 ) . ' / ' . $total,
				'thumb'        => $item['thumb'] ?? '',
				'show_toolbar' => true,
				'permalink'    => $item['permalink'] ?? '',
				'share_title'  => $item['title'] ?? '',
			)
		);
	}

	public function render_homepage_bar() {
		if ( ! is_front_page() || ! self::is_enabled() ) {
			return;
		}

		$playlist = mevzu_tts_get_todays_yapay_zeka_playlist();
		if ( empty( $playlist ) ) {
			return;
		}

		$title    = self::get_bar_title();
		$intro    = mevzu_tts_get_yzm_intro_text();
		$subtitle = sprintf(
			/* translators: %d: haber sayısı */
			_n( '%d haber — dinlemek için dokunun', '%d haber — dinlemek için dokunun', count( $playlist ), 'mevzu2' ),
			count( $playlist )
		);
		$modal_id = 'yzm-manset-modal';

		$this->modal_rendered = true;
		?>
		<div class="bg-white p-1 rounded-3 shadow-sm h-100 mb-3 yzm-bar-wrap">
			<button type="button" class="btn btn-dark yzm-bar d-flex align-items-center gap-3 w-100 text-start shadow-sm rounded-3" data-yzm-bar data-modal-id="<?php echo esc_attr( $modal_id ); ?>"
				data-count="<?php echo esc_attr( (string) count( $playlist ) ); ?>"
				data-intro="<?php echo esc_attr( $intro ); ?>"
				data-outro="<?php echo esc_attr( mevzu_tts_get_yzm_outro_text() ); ?>"
				aria-haspopup="dialog">
				<span class="yzm-bar-icon rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
					<i class="ri-mic-ai-line fs-5"></i>
				</span>
				<span class="yzm-bar-text flex-grow-1 min-w-0">
					<span class="yzm-bar-title">
						<div class="d-flex align-items-center gap-2 mb-1">
							<span class="bg-primary fz-10 fw-semibold py-1 px-2 rounded">Y.Z</span>
							<span class="fw-bold fs-6"><?php echo esc_html( $title ); ?></span>
						</div>
						<span class="yzm-bar-sub d-block small opacity-75"><?php echo esc_html( $subtitle ); ?></span>
					</span>
					
				</span>
				<span class="yzm-bar-chevron opacity-75 flex-shrink-0" aria-hidden="true"><i class="ri-arrow-right-s-line fs-5"></i></span>
			</button>
		</div>
		<?php
	}

	/** @var bool */
	private $modal_rendered = false;

	public function render_modal_footer() {
		if ( empty( $this->modal_rendered ) || ! is_front_page() ) {
			return;
		}

		$title       = self::get_bar_title();
		$intro       = mevzu_tts_get_yzm_intro_text();
		$outro       = mevzu_tts_get_yzm_outro_text();
		$intro_lines = mevzu_tts_split_reading_lines( $intro );
		$outro_lines = mevzu_tts_split_reading_lines( $outro );
		?>
		<div class="modal fade yzm-modal" id="yzm-manset-modal" tabindex="-1" aria-labelledby="yzm-manset-modal-label" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
				<div class="modal-content border-0 overflow-hidden rounded-4">
					<div class="modal-body p-0 bg-dark">
						<div class="yzm-swiper-wrap position-relative">
							<div class="yzm-modal-header position-absolute top-0 start-0 end-0 d-flex align-items-center justify-content-between gap-2 px-3 py-2">
								<h5 class="yzm-modal-title mb-0 fs-6 fw-bold text-truncate text-white" id="yzm-manset-modal-label"><?php echo esc_html( $title ); ?></h5>
								<button type="button" class="btn-close btn-close-white flex-shrink-0" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Kapat', 'mevzu2' ); ?>"></button>
							</div>
							<div class="yzm-modal-loading position-absolute top-0 start-0 end-0 bottom-0 z-3 d-none flex-column align-items-center justify-content-center gap-3 rounded-4" data-yzm-loading aria-live="polite">
								<div class="spinner-border text-light" role="status"></div>
								<p class="yzm-modal-loading__text mb-0 text-white opacity-75 small"><?php esc_html_e( 'Haberler yükleniyor…', 'mevzu2' ); ?></p>
							</div>
							<div class="swiper yzm-swiper yzm-swiper--creative">
								<div class="swiper-wrapper">
									<?php
									echo $this->render_lyrics_slide_html(
										array(
											'slide_class' => 'yzm-slide--welcome',
											'headline'    => '',
											'lines'       => $intro_lines,
											'badge'       => __( 'Karşılama', 'mevzu2' ),
											'badge_class' => 'bg-primary',
											'aria_label'  => $intro,
										)
									);
									?>
									<?php
									echo $this->render_lyrics_slide_html(
										array(
											'slide_class' => 'yzm-slide--outro',
											'extra_attrs' => 'data-yzm-outro-slide',
											'headline'    => '',
											'lines'       => $outro_lines,
											'badge'       => __( 'Kapanış', 'mevzu2' ),
											'badge_class' => 'bg-secondary',
											'aria_label'  => $outro,
										)
									);
									?>
								</div>
							</div>
							<div class="yzm-controls position-absolute bottom-0 start-0 end-0 pb-3 px-3 pt-4">
								<!-- <div class="yzm-progress-dots d-flex justify-content-center gap-2 mb-2" aria-hidden="true"></div> -->
								<div class="yzm-control-row d-flex align-items-center justify-content-between gap-2">
									<button type="button" class="yzm-btn-nav yzm-btn-prev btn btn-dark" disabled><?php esc_html_e( 'Önceki Haber', 'mevzu2' ); ?></button>
									<div class="yzm-robot-player-wrap">
										<button type="button" class="yzm-robot-player" data-yzm-wave aria-label="<?php esc_attr_e( 'Oynat', 'mevzu2' ); ?>">
											<span class="yzm-robot-player__face" aria-hidden="true">
												<span class="yzm-robot-player__glyph">
													<i class="ri-robot-2-line" aria-hidden="true"></i>
													<span class="yzm-robot-player__mouth">
														<span class="yzm-robot-player__idle">
															<i class="ri-play-fill" aria-hidden="true"></i>
														</span>
														<span class="yzm-robot-player__bars" aria-hidden="true">
															<span class="yzm-robot-bar"></span>
															<span class="yzm-robot-bar"></span>
															<span class="yzm-robot-bar"></span>
															<span class="yzm-robot-bar"></span>
															<span class="yzm-robot-bar"></span>
														</span>
													</span>
												</span>
											</span>
										</button>
									</div>
									<button type="button" class="yzm-btn-nav yzm-btn-next btn btn-dark"><?php esc_html_e( 'Sonraki Haber', 'mevzu2' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
		<?php
	}
}
