<?php
/**
 * Gutenberg entegrasyonu sınıfı
 * Günlük limit kontrolü ve tema yolları eklendi.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KKEREM_TTS_Gutenberg {
    
    private $file_manager;
    
    public function __construct() {
        $this->file_manager = new KKEREM_TTS_File_Manager();
        
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_post_editor_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action( 'wp_ajax_mevzu_yz_queue_audio', array( $this, 'ajax_queue_audio' ) );
        add_action( 'wp_ajax_mevzu_yz_get_tts_state', array( $this, 'ajax_get_tts_state' ) );
        add_action( 'wp_ajax_mevzu_yz_generate_audio', array( $this, 'ajax_queue_audio' ) );
        add_action( 'wp_ajax_mevzu_yz_get_audio_info', array( $this, 'ajax_get_tts_state' ) );
        add_action( 'wp_ajax_kkerem_tts_generate_audio', array( $this, 'ajax_queue_audio' ) );
        add_action( 'wp_ajax_kkerem_tts_get_audio_info', array( $this, 'ajax_get_tts_state' ) );
    }

    /**
     * Klasik + blok editör yazı ekranı.
     */
    public function enqueue_post_editor_assets( $hook ) {
        if ( ! mevzu_yz_module_active() ) {
            return;
        }
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'post' ) {
            return;
        }

        global $post;
        $post_id = ( $post && isset( $post->ID ) ) ? (int) $post->ID : 0;

        wp_enqueue_script(
            'mevzu-tts-post-editor',
            MEVZU_TTS_URL . 'assets/js/tts-post-editor.js',
            array( 'jquery' ),
            MEVZU_TTS_VERSION,
            true
        );

        wp_localize_script(
            'mevzu-tts-post-editor',
            'mevzuTtsEditor',
            array(
                'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'mevzu_yz_nonce' ),
                'postId'          => $post_id,
                'targetCategory'  => mevzu_tts_target_category_id(),
                'strings'         => array(
                    'queued'     => __( 'Ses dosyası arka planda oluşturuluyor. Sayfayı kapatabilirsiniz.', 'mevzu2' ),
                    'processing' => __( 'Ses dosyası oluşturuluyor…', 'mevzu2' ),
                    'ready'      => __( 'Ses dosyası hazır.', 'mevzu2' ),
                    'error'      => __( 'Ses oluşturulamadı.', 'mevzu2' ),
                    'regenerate' => __( 'Ses Dosyasını Yeniden Oluştur', 'mevzu2' ),
                ),
            )
        );
    }
    
    public function enqueue_block_editor_assets() {
        $target_category = get_option('kkerem_tts_category_id');
        if (empty($target_category)) {
            return;
        }

        wp_enqueue_script(
            'mevzu-yapay-zeka-gutenberg',
            MEVZU_TTS_URL . 'assets/js/gutenberg.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            MEVZU_TTS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'mevzu-yapay-zeka-gutenberg',
            MEVZU_TTS_URL . 'assets/css/gutenberg.css',
            array('wp-edit-blocks'),
            MEVZU_TTS_VERSION
        );
        
        wp_localize_script( 'mevzu-yapay-zeka-gutenberg', 'mevzuYZ', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mevzu_yz_nonce' ),
            'strings' => array(
                'generateAudio' => 'Ses Dosyası Oluştur',
                'audioGenerated' => 'Ses dosyası oluşturuldu',
                'audioGenerating' => 'Ses dosyası oluşturuluyor...',
                'audioError' => 'Ses dosyası oluşturulurken hata oluştu',
                'noAudioFile' => 'Bu post için ses dosyası bulunmuyor',
                'playAudio' => 'Ses Dosyasını Dinle',
                'fileSize' => 'Dosya Boyutu',
                'createdDate' => 'Oluşturulma Tarihi'
            )
        ));
    }
    
    public function add_meta_box() {
        if ( ! mevzu_yz_module_active() ) {
            return;
        }

        add_meta_box(
            'kkerem-tts-audio',
            MEVZU_YZ_MODULE_LABEL,
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        $post_id = $post->ID;
        $target_category = get_option('kkerem_tts_category_id');
        $post_categories = wp_get_post_categories($post_id);
        
        $panel_visible = mevzu_tts_post_should_process( $post_id );
        $job_status    = Mevzu_TTS_Queue::get_status( $post_id );
        $job_message   = Mevzu_TTS_Queue::get_message( $post_id );
        $is_busy       = in_array( $job_status, array( Mevzu_TTS_Queue::STATUS_QUEUED, Mevzu_TTS_Queue::STATUS_PROCESSING ), true );

        $daily_usage = Mevzu_TTS_Daily_Limit::get_usage();
        $daily_remaining = Mevzu_TTS_Daily_Limit::remaining();

        $audio_exists = $this->file_manager->audio_file_exists( $post_id );
        $file_info    = $audio_exists ? $this->file_manager->get_audio_file_info( $post_id ) : null;

        ?>
        <?php if ( ! $panel_visible && ! $is_busy ) : ?>
        <style>#kkerem-tts-audio { display: none; }</style>
        <?php endif; ?>
        <div id="kkerem-tts-meta-box-wrapper"
             data-target-cat="<?php echo esc_attr( (string) mevzu_tts_target_category_id() ); ?>"
             data-initially-visible="<?php echo $panel_visible ? '1' : '0'; ?>">
            <div id="kkerem-tts-main-content">
                <div class="notice notice-info inline py-1 px-2 bg-info bg-opacity-10">
                    <p><small>Mevzu² AI kota: <?php echo $daily_usage; ?>/<?php echo Mevzu_TTS_Daily_Limit::get_limit(); ?> (Kalan: <?php echo $daily_remaining; ?>)</small></p>
                </div>
                
                <div id="kkerem-tts-status-area">
                    <?php if ( $is_busy ) : ?>
                        <p class="description"><small><?php echo esc_html( $job_message ? $job_message : __( 'Ses dosyası arka planda oluşturuluyor.', 'mevzu2' ) ); ?></small></p>
                    <?php elseif ( $audio_exists && $file_info ) : ?>
                        <div class="kkerem-tts-audio-info">
                            <style>
                            audio::-webkit-media-controls-current-time-display,
                            audio::-webkit-media-controls-time-remaining-display {
                                display: none !important;
                            }
                            #kkerem-tts-meta-box-wrapper audio { width: 100%; margin: 10px 0; }
                            </style>
                            <audio class="m-0" controls controlslist="nodownload noplaybackrate">
                                <source src="<?php echo esc_url($file_info['file_url']); ?>" type="audio/mpeg">
                                Tarayıcınız ses dosyasını desteklemiyor.
                            </audio>
                            
                            <p class="fz-10 mt-0 text-end text-muted">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($file_info['created_date']))); ?>, ≈<?php echo esc_html($file_info['file_size_formatted']); ?>
                            </p>

                            <div class="notice notice-danger inline py-1 px-2 bg-danger bg-opacity-10" style="border-left-color: #d63638;">
                                <p><span class="d-flex align-items-center m-0"><i class="ri-error-warning-line fs-6 text-danger me-2"></i><small>Dikkat!</small></span>
                                <small>Yazı içeriği değiştirildiyse ses dosyasının yeniden oluşturulması gerekir. İşlem arka planda yapılır; beklemeniz gerekmez.</small></p>
                            </div>

                            <button type="button" class="button button-secondary w-100 my-2" id="kkerem-tts-regenerate" <?php echo ! Mevzu_TTS_Daily_Limit::can_use() ? 'disabled' : ''; ?>>
                                Ses Dosyasını Yeniden Oluştur
                            </button>

                        </div>
                    <?php else : ?>
                        <div class="kkerem-tts-no-audio">
                            <div class="text-muted mb-2">
                                <p><small><?php echo esc_html( $panel_visible ? __( 'Yayınlandığında ses dosyası arka planda oluşturulur.', 'mevzu2' ) : __( 'Hedef kategori veya Yapay Zeka Manşeti seçildiğinde bu bölüm açılır.', 'mevzu2' ) ); ?></small></p>
                            </div>
                            <?php if ( $panel_visible ) : ?>
                            <button type="button" class="button button-primary" style="width:100%" id="kkerem-tts-generate" <?php echo ! Mevzu_TTS_Daily_Limit::can_use() ? 'disabled' : ''; ?>>
                                Ses Dosyasını Oluştur
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="kkerem-tts-loading" style="display: <?php echo $is_busy ? 'block' : 'none'; ?>; padding: 10px 0;">
                    <span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span>
                    <span><?php echo esc_html( $is_busy ? $job_message : __( 'Ses dosyası oluşturuluyor…', 'mevzu2' ) ); ?></span>
                </div>
                
                <div id="kkerem-tts-error" style="display: none;">
                    <div class="notice notice-error inline py-1 px-2 bg-danger bg-opacity-10 text-danger"><p></p></div>
                </div>
                
                <div id="kkerem-tts-success" style="display: none;">
                    <div class="notice notice-success inline py-1 px-2 bg-success bg-opacity-10 text-success"><p class="d-flex align-items-center m-0"><i class="ri-checkbox-circle-line fs-6 text-success me-2"></i><small>Ses dosyası başarıyla oluşturuldu!</small></p></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Manuel / yeniden oluştur — arka plan kuyruğu.
     */
    public function ajax_queue_audio() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( $post_id < 1 ) {
            wp_send_json_error( array( 'message' => 'Geçersiz yazı ID.' ) );
        }

        $queued = Mevzu_TTS_Queue::enqueue( $post_id, 'manual' );
        if ( is_wp_error( $queued ) ) {
            wp_send_json_error( array( 'message' => $queued->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'message'    => __( 'Ses dosyası arka planda oluşturuluyor.', 'mevzu2' ),
                'job_status' => Mevzu_TTS_Queue::get_status( $post_id ),
            )
        );
    }

    /**
     * Editör durumu (ses + kuyruk).
     */
    public function ajax_get_tts_state() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( $post_id < 1 ) {
            wp_send_json_error( array( 'message' => 'Geçersiz yazı.' ) );
        }

        wp_send_json_success( Mevzu_TTS_Queue::get_editor_state( $post_id ) );
    }
}
