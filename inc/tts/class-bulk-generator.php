<?php
/**
 * Toplu Ses Dosyası Oluşturma Sayfası
 * Menü 'Metin Okuma' altına taşındı, günlük limit eklendi.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KKEREM_TTS_Bulk_Generator {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_bulk_generator_menu'));
        add_action( 'wp_ajax_mevzu_yz_generate_bulk', array( $this, 'ajax_generate_bulk' ) );
        add_action( 'wp_ajax_mevzu_yz_get_posts_without_audio', array( $this, 'ajax_get_posts_without_audio' ) );
        add_action( 'wp_ajax_kkerem_tts_generate_bulk', array( $this, 'ajax_generate_bulk' ) );
        add_action( 'wp_ajax_kkerem_tts_get_posts_without_audio', array( $this, 'ajax_get_posts_without_audio' ) );
    }

    public function add_bulk_generator_menu() {
        add_submenu_page(
            MEVZU_YZ_ADMIN_PAGE,
            'Toplu Ses Dosyası Oluşturucu',
            'Toplu Ses Oluşturucu',
            'manage_options',
            MEVZU_YZ_ADMIN_PAGE_BULK,
            array( $this, 'render_bulk_generator_page' )
        );
    }
    
    public function render_bulk_generator_page() {
        $target_category = get_option('kkerem_tts_category_id');
        $category_name = '';
        
        if ($target_category) {
            $category = get_category($target_category);
            $category_name = $category ? $category->name : 'Bilinmeyen Kategori';
        }
        
        $daily_usage = Mevzu_TTS_Daily_Limit::get_usage();
        $daily_remaining = Mevzu_TTS_Daily_Limit::remaining();
        
        ?>
        <div class="wrap">
            <h1>Toplu Ses Dosyası Oluşturucu</h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>Günlük Kullanım:</strong> 
                    <?php echo $daily_usage; ?> / <?php echo Mevzu_TTS_Daily_Limit::get_limit(); ?> 
                    (Kalan: <?php echo $daily_remaining; ?>)
                </p>
            </div>
            
            <?php if (!$target_category): ?>
                <div class="notice notice-error">
                    <p><strong>Hata:</strong> Önce <a href="<?php echo esc_url( mevzu_yz_admin_url() ); ?>"><?php echo esc_html( MEVZU_YZ_MODULE_LABEL ); ?> ayarları</a> sayfasından hedef kategori ID'sini belirleyin.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><strong>Hedef Kategori:</strong> <?php echo esc_html($category_name); ?> (ID: <?php echo esc_html($target_category); ?>)
                        <br>
                        <small>Sadece <b>Köşe Yazıları kategorisi</b> için burayı düzenleyebilirsiniz. <a href="<?php echo admin_url('admin.php?page=mevzu-ayarlar'); ?>#mevzu_kose_yazilari_kategorisi" class="text-link-hover">Hedef kategoriyi değiştirmek için tıklayın</a></small>
                    </p>
                </div>
                
                <div class="card">
                    <h2 class="mb-0">Kontrol Paneli</h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="posts-per-batch">Toplu İşlem Boyutu:</label></th>
                            <td>
                                <select id="posts-per-batch">
                                    <option value="1">1 post</option>
                                    <option value="5">5 post</option>
                                    <option value="10" selected>10 post</option>
                                    <option value="15">15 post (Günlük Limit)</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="delay-between-posts">Postlar Arası Gecikme (saniye):</label></th>
                            <td>
                                <select id="delay-between-posts">
                                    <option value="0">Gecikme yok</option>
                                    <option value="1" selected>1 saniye</option>
                                    <option value="2">2 saniye</option>
                                    <option value="5">5 saniye</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th class="py-0">
                                <button type="button" id="scan-posts" class="button button-primary">Ses Dosyası Olmayan Postları Tara</button>
                            </th>
                            <td class="py-0">
                                <div>
                                    <button type="button" id="generate-bulk" class="button button-secondary" disabled>Toplu Ses Dosyası Oluştur</button>
                                    <button type="button" id="stop-generation" class="button button-secondary" style="display: none;">Durdur</button>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2>Sonuçlar</h2>
                    <div id="results-container">
                        <p>Ses dosyası olmayan postları taramak için yukarıdaki "Tara" butonuna tıklayın.</p>
                    </div>
                </div>
                
                <div class="card">
                    <h2>İşlem Durumu</h2>
                    <div id="progress-container" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-text">Hazırlanıyor...</div>
                    </div>
                    <div id="status-log"></div>
                </div>
                
                <style>
                .progress-bar { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 10px 0; }
                .progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s ease; }
                .progress-text { text-align: center; font-weight: bold; margin: 10px 0; }
                .post-item { padding: 10px; border: 1px solid #ddd; margin: 5px 0; border-radius: 4px; background: #f9f9f9; }
                .post-item.success { background: #d4edda; border-color: #c3e6cb; }
                .post-item.error { background: #f8d7da; border-color: #f5c6cb; }
                .post-item.processing { background: #fff3cd; border-color: #ffeaa7; }
                #status-log { max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }
                .log-entry { margin: 2px 0; padding: 2px 0; }
                .log-entry.success { color: #28a745; }
                .log-entry.error { color: #dc3545; }
                .log-entry.info { color: #17a2b8; }
                </style>
                
                <script>
                jQuery(document).ready(function($) {
                    let postsToProcess = [];
                    let currentIndex = 0;
                    let isGenerating = false;
                    const dailyRemaining = <?php echo $daily_remaining; ?>;
                    
                    $('#scan-posts').on('click', function() {
                        const button = $(this);
                        const batchSize = Math.min(parseInt($('#posts-per-batch').val()), dailyRemaining);
                        button.prop('disabled', true).text('Taranıyor...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mevzu_yz_get_posts_without_audio',
                                nonce: '<?php echo wp_create_nonce( 'mevzu_yz_nonce' ); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    const allPosts = response.data.posts;
                                    postsToProcess = allPosts.slice(0, batchSize);
                                    
                                    $('#results-container').html('<h3>Ses Dosyası Olmayan Postlar (' + postsToProcess.length + ' / ' + allPosts.length + ' adet)</h3>');
                                    
                                    if (postsToProcess.length > 0) {
                                        postsToProcess.forEach(function(post) {
                                            $('#results-container').append(
                                                '<div class="post-item" id="post-' + post.ID + '">' +
                                                '<strong>' + post.post_title + '</strong> (ID: ' + post.ID + ')' +
                                                '<br><small>Yayın Tarihi: ' + post.post_date + '</small>' +
                                                '</div>'
                                            );
                                        });
                                        
                                        $('#generate-bulk').prop('disabled', false);
                                    } else {
                                        $('#results-container').append('<p>Tüm postlar için ses dosyası mevcut!</p>');
                                    }
                                }
                            },
                            complete: function() {
                                button.prop('disabled', false).text('Ses Dosyası Olmayan Postları Tara');
                            }
                        });
                    });
                    
                    $('#generate-bulk').on('click', function() {
                        if (postsToProcess.length === 0) return;
                        
                        isGenerating = true;
                        currentIndex = 0;
                        
                        $('#generate-bulk').hide();
                        $('#stop-generation').show();
                        $('#progress-container').show();
                        
                        processNext();
                    });
                    
                    $('#stop-generation').on('click', function() {
                        isGenerating = false;
                        $('#generate-bulk').show();
                        $('#stop-generation').hide();
                        addLogEntry('İşlem kullanıcı tarafından durduruldu', 'info');
                    });
                    
                    function processNext() {
                        if (!isGenerating || currentIndex >= postsToProcess.length) {
                            finishGeneration();
                            return;
                        }
                        
                        const delay = parseInt($('#delay-between-posts').val()) * 1000;
                        const post = postsToProcess[currentIndex];
                        const postElement = $('#post-' + post.ID);
                        
                        postElement.removeClass('success error').addClass('processing');
                        addLogEntry('İşleniyor: ' + post.post_title + ' (ID: ' + post.ID + ')', 'info');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mevzu_yz_generate_bulk',
                                post_id: post.ID,
                                nonce: '<?php echo wp_create_nonce( 'mevzu_yz_nonce' ); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    postElement.removeClass('processing').addClass('success');
                                    addLogEntry('✓ Başarılı: ' + post.post_title, 'success');
                                } else {
                                    postElement.removeClass('processing').addClass('error');
                                    addLogEntry('✗ Hata: ' + post.post_title + ' - ' + response.data, 'error');
                                }
                            },
                            error: function() {
                                postElement.removeClass('processing').addClass('error');
                                addLogEntry('✗ AJAX Hatası: ' + post.post_title, 'error');
                            },
                            complete: function() {
                                currentIndex++;
                                updateProgress();
                                setTimeout(processNext, delay);
                            }
                        });
                    }
                    
                    function updateProgress() {
                        const progress = (currentIndex / postsToProcess.length) * 100;
                        $('.progress-fill').css('width', progress + '%');
                        $('.progress-text').text('İşlenen: ' + currentIndex + ' / ' + postsToProcess.length);
                    }
                    
                    function finishGeneration() {
                        isGenerating = false;
                        $('#generate-bulk').show();
                        $('#stop-generation').hide();
                        addLogEntry('Toplu işlem tamamlandı!', 'success');
                    }
                    
                    function addLogEntry(message, type) {
                        const timestamp = new Date().toLocaleTimeString();
                        $('#status-log').append('<div class="log-entry ' + type + '">[' + timestamp + '] ' + message + '</div>');
                        $('#status-log').scrollTop($('#status-log')[0].scrollHeight);
                    }
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function ajax_get_posts_without_audio() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_bulk_nonce' );
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }
        
        $target_category = get_option('kkerem_tts_category_id');
        if (!$target_category) {
            wp_send_json_error('Hedef kategori ID belirlenmemiş');
        }
        
        $posts = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => 'mevzu_manset_konumlari',
                    'value'   => 'yapay_zeka_manset',
                    'compare' => 'LIKE',
                ),
            ),
        ));

        $cat_posts = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'category'       => $target_category,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        $posts = array_unique( array_merge( $posts, $cat_posts ) );
        
        $posts_without_audio = array();
        $file_manager = new KKEREM_TTS_File_Manager();
        
        foreach ($posts as $post_id) {
            if ( ! function_exists( 'mevzu_tts_post_should_process' ) || ! mevzu_tts_post_should_process( $post_id ) ) {
                continue;
            }
            if (!$file_manager->audio_file_exists($post_id)) {
                $post = get_post($post_id);
                $posts_without_audio[] = array(
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_date' => $post->post_date
                );
            }
        }
        
        wp_send_json_success(array(
            'posts' => $posts_without_audio,
            'total' => count($posts_without_audio)
        ));
    }
    
    public function ajax_generate_bulk() {
        mevzu_yz_verify_ajax_nonce( 'kkerem_tts_bulk_nonce' );
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }
        
        // Günlük limit kontrolü
        if (!Mevzu_TTS_Daily_Limit::can_use()) {
            wp_send_json_error('Mevzu² AI günlük kotanız doldu (' . Mevzu_TTS_Daily_Limit::get_usage() . '/' . Mevzu_TTS_Daily_Limit::get_limit() . ').');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('Geçersiz post ID');
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post bulunamadı');
        }
        
        $tts_service = new KKEREM_TTS_Service();
        $file_manager = new KKEREM_TTS_File_Manager();
        
        $file_manager->delete_audio_file($post_id);
        
        $result = $tts_service->synthesize_text($post->post_content, $post_id);
        
        if ($result) {
            wp_send_json_success('Ses dosyası başarıyla oluşturuldu: ' . $result['file_url']);
        } else {
            if (!Mevzu_AI_Client::is_ready()) {
                wp_send_json_error(Mevzu_AI_Client::get_unavailable_message());
            }
            wp_send_json_error('Mevzu² AI ses oluşturulamadı.');
        }
    }
}

new KKEREM_TTS_Bulk_Generator();
