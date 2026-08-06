<?php
/**
 * Debug ve Log görüntüleme sayfası
 */

if (!defined('ABSPATH')) {
    exit;
}

class KKEREM_TTS_Debug {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
        add_action( 'wp_ajax_mevzu_yz_test_api', array( $this, 'ajax_test_api' ) );
        add_action( 'wp_ajax_mevzu_yz_clear_logs', array( $this, 'ajax_clear_logs' ) );
        add_action( 'wp_ajax_kkerem_tts_test_api', array( $this, 'ajax_test_api' ) );
        add_action( 'wp_ajax_kkerem_tts_clear_logs', array( $this, 'ajax_clear_logs' ) );
    }
    
    public function add_debug_menu() {
        add_submenu_page(
            MEVZU_YZ_ADMIN_PAGE,
            MEVZU_YZ_MODULE_LABEL . ' Debug',
            'Debug',
            'manage_options',
            MEVZU_YZ_ADMIN_PAGE_DEBUG,
            array( $this, 'debug_page' )
        );
    }
    
    public function debug_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( MEVZU_YZ_MODULE_LABEL . ' — Debug ve Log' ); ?></h1>
            
            <div class="notice notice-info">
                <p><strong>WordPress Error Log Konumları:</strong></p>
                <ul>
                    <li><code><?php echo ini_get('error_log'); ?></code></li>
                    <li><code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code></li>
                </ul>
            </div>
            
            <h2>Son 50 Log Kaydı</h2>
            <div style="background: #f1f1f1; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                <pre><?php echo esc_html($this->get_recent_logs()); ?></pre>
            </div>
            
            <h2>Sistem Bilgileri</h2>
            <table class="widefat">
                <tr><td><strong>WordPress Version:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                <tr><td><strong>PHP Version:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                <tr><td><strong>WP Debug:</strong></td><td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></td></tr>
                <tr><td><strong>Mevzu² AI Kota:</strong></td><td><?php echo esc_html( (string) Mevzu_TTS_Daily_Limit::get_usage() ); ?> / <?php echo esc_html( (string) Mevzu_TTS_Daily_Limit::get_limit() ); ?></td></tr>
                <tr><td><strong>Mevzu² AI Hazır:</strong></td><td><?php echo class_exists( 'Mevzu_AI_Client' ) && Mevzu_AI_Client::is_ready() ? 'Evet' : 'Hayır'; ?></td></tr>
            </table>
            
            <h2>Mevzu² AI Test</h2>
            <button id="test-api-connection" class="button button-primary">Mevzu² AI Bağlantısını Test Et</button>
            <div id="api-test-result" style="margin-top: 10px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api-connection').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Test Ediliyor...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mevzu_yz_test_api',
                        nonce: '<?php echo wp_create_nonce( 'mevzu_yz_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#api-test-result').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        } else {
                            $('#api-test-result').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#api-test-result').html('<div class="notice notice-error"><p>API test hatası oluştu</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Mevzu² AI Bağlantısını Test Et');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function get_recent_logs() {
        $log_files = array(
            ini_get('error_log'),
            WP_CONTENT_DIR . '/debug.log'
        );
        
        $all_logs = '';
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file) && is_readable($log_file)) {
                $logs = file_get_contents($log_file);
                $lines = explode("\n", $logs);
                $recent_lines = array_slice($lines, -50);
                
                $all_logs .= "=== " . $log_file . " ===\n";
                $all_logs .= implode("\n", $recent_lines) . "\n\n";
            }
        }
        
        if (empty($all_logs)) {
            return "Log dosyası bulunamadı veya okunamıyor.";
        }
        
        return $all_logs;
    }
    
    public function ajax_test_api() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_debug_nonce' );
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok');
        }

        if (!Mevzu_AI_Client::is_ready()) {
            wp_send_json_error(Mevzu_AI_Client::get_unavailable_message());
        }

        $quota = Mevzu_AI_Client::fetch_quota(true);
        $quota_line = '';
        if ($quota) {
            $quota_line = sprintf(
                'Kota: %d / %d (kalan %d). ',
                (int) $quota['used'],
                (int) $quota['limit'],
                (int) $quota['remaining']
            );
        }

        $voice_name = get_option('kkerem_tts_voice_name', 'tr-TR-Standard-A');
        $result = Mevzu_AI_Client::synthesize_text('Mevzu² AI bağlantı testi.');

        if (is_wp_error($result)) {
            wp_send_json_error($quota_line . 'Sentez hatası: ' . $result->get_error_message());
        }

        if (!empty($result['audio_content'])) {
            wp_send_json_success($quota_line . 'Mevzu² AI sentez testi başarılı. Ses: ' . $voice_name . '.');
        }

        wp_send_json_error($quota_line . 'Mevzu² AI test yanıtı beklenmedik.');
    }
    
    public function ajax_clear_logs() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_debug_nonce' );
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok');
        }
        
        wp_send_json_success('Loglar temizlendi.');
    }
}

new KKEREM_TTS_Debug();
