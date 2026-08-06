<?php
/**
 * Mevzu² AI (TTS) - Tema Özelliği Başlatıcı
 * 
 * Eski eklenti: kkerem-text-to-speech
 * Şimdi tema özelliği olarak çalışır.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Yapılandırma
require_once __DIR__ . '/config.php';

// Tema yolları sabitleri
define('MEVZU_TTS_PATH', __DIR__ . '/');
define('MEVZU_TTS_URL', get_stylesheet_directory_uri() . '/inc/tts/');

// Sınıfları yükle
require_once MEVZU_TTS_PATH . 'class-mevzu-ai-client.php';
require_once MEVZU_TTS_PATH . 'class-tts-service.php';
require_once MEVZU_TTS_PATH . 'class-file-manager.php';
require_once MEVZU_TTS_PATH . 'class-shortcode.php';
require_once MEVZU_TTS_PATH . 'class-gutenberg.php';
require_once MEVZU_TTS_PATH . 'class-admin.php';
require_once MEVZU_TTS_PATH . 'class-debug.php';
require_once MEVZU_TTS_PATH . 'class-bulk-generator.php';
require_once MEVZU_TTS_PATH . 'class-daily-limit.php';
require_once MEVZU_TTS_PATH . 'class-tts-helpers.php';
require_once MEVZU_TTS_PATH . 'class-audio-retention.php';
require_once MEVZU_TTS_PATH . 'class-ai-manset.php';
require_once MEVZU_TTS_PATH . 'class-tts-queue.php';

// Ana sınıf
class Mevzu_TTS {
    
    private $admin;
    private $tts_service;
    private $file_manager;
    private $shortcode;
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Meta kaydından sonra kuyruk (manşet / kategori — save_post önceliği 10)
        add_action( 'save_post', array( $this, 'handle_save_post_for_tts' ), 99, 2 );
        add_action( 'before_delete_post', array( $this, 'handle_post_delete' ) );
        
        // Cron job'ları temizle
        add_action('wp_ajax_mevzu_tts_cleanup_cron', array($this, 'cleanup_cron_jobs'));
    }
    
    public function init() {
        $this->tts_service = new KKEREM_TTS_Service();
        $this->file_manager = new KKEREM_TTS_File_Manager();
        $this->shortcode = new KKEREM_TTS_Shortcode();
        $this->admin = new KKEREM_TTS_Admin();
        
        // Gutenberg entegrasyonu
        if (function_exists('register_block_type')) {
            new KKEREM_TTS_Gutenberg();
        }

        new Mevzu_TTS_AI_Manset();
        new Mevzu_TTS_Audio_Retention();
    }
    
    public function admin_init() {
        // Admin panel başlatma
    }
    
    /**
     * Yayın / güncelleme sonrası (manşet meta kaydedildikten sonra).
     */
    public function handle_save_post_for_tts( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        if ( ! $post instanceof WP_Post || $post->post_type !== 'post' ) {
            return;
        }
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        $update_mode = get_option( 'kkerem_tts_update_mode', 'publish_only' );

        if ( $update_mode === 'always' ) {
            $this->process_post_for_tts( $post_id, $post, 'save_post' );
            return;
        }

        if ( $update_mode === 'publish_only' ) {
            // İlk yayın dışındaki düzenlemelerde otomatik kuyruk oluşturma.
            if ( get_post_meta( $post_id, '_mevzu_tts_auto_queued_on_publish', true ) ) {
                return;
            }
            update_post_meta( $post_id, '_mevzu_tts_auto_queued_on_publish', '1' );
            $this->process_post_for_tts( $post_id, $post, 'publish' );
        }
    }
    
    public function handle_post_delete($post_id) {
        $this->file_manager->handle_post_deletion($post_id);
    }
    
    private function process_post_for_tts($post_id, $post, $action) {
        KKEREM_TTS_Admin::debug_log('process_post_for_tts başlatıldı (Post ID: ' . $post_id . ', Action: ' . $action . ')');
        
        // Günlük limit kontrolü
        if (!Mevzu_TTS_Daily_Limit::can_use()) {
            KKEREM_TTS_Admin::debug_log('Mevzu² AI günlük kota doldu: ' . Mevzu_TTS_Daily_Limit::get_usage() . '/' . Mevzu_TTS_Daily_Limit::get_limit());
            return;
        }
        
        if ( ! mevzu_tts_post_should_process( $post_id ) ) {
            KKEREM_TTS_Admin::debug_log( 'Post TTS kapsamında değil (hedef kategori veya YZ manşeti), işlem durduruluyor (Post ID: ' . $post_id . ')' );
            return;
        }
        
        if ( ! Mevzu_AI_Client::is_ready() ) {
            KKEREM_TTS_Admin::debug_log( 'Mevzu² AI kullanılamıyor: ' . Mevzu_AI_Client::get_unavailable_message() );
            return;
        }

        $queued = Mevzu_TTS_Queue::enqueue( $post_id, $action );
        if ( is_wp_error( $queued ) ) {
            KKEREM_TTS_Admin::debug_log( 'TTS kuyruk: ' . $queued->get_error_message() . ' (#' . $post_id . ')' );
            return;
        }

        KKEREM_TTS_Admin::debug_log( 'TTS arka plan kuyruğuna alındı (#' . $post_id . ', ' . $action . ')' );
    }
    
    public function cleanup_cron_jobs() {
        $cron_jobs = _get_cron_array();
        $cleaned = 0;
        
        foreach ($cron_jobs as $timestamp => $cron) {
            if (isset($cron['mevzu_tts_generate_audio'])) {
                unset($cron_jobs[$timestamp]['mevzu_tts_generate_audio']);
                $cleaned++;
            }
        }
        
        _set_cron_array($cron_jobs);
        wp_send_json_success(array('cleaned' => $cleaned));
    }
}

// TTS başlat
add_action('after_setup_theme', function() {
    new Mevzu_TTS();
});
