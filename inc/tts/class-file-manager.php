<?php
/**
 * Dosya yönetimi sınıfı
 */

if (!defined('ABSPATH')) {
    exit;
}

class KKEREM_TTS_File_Manager {
    
    private $upload_dir;
    private $tts_dir;
    
    public function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->tts_dir = $this->upload_dir['basedir'] . '/kkerem-tts';
        
        $this->ensure_directory_exists();
    }
    
    /**
     * TTS klasörünün var olduğundan emin ol
     */
    private function ensure_directory_exists() {
        if (!file_exists($this->tts_dir)) {
            wp_mkdir_p($this->tts_dir);
            
            // .htaccess dosyası oluştur
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.mp3>\n";
            $htaccess_content .= "    Order allow,deny\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($this->tts_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Post için ses dosyası yolunu al
     */
    public function get_audio_file_path($post_id) {
        $filename = 'post-' . $post_id . '.mp3';
        return $this->tts_dir . '/' . $filename;
    }
    
    /**
     * Post için ses dosyası URL'sini al
     */
    public function get_audio_file_url($post_id) {
        $filename = 'post-' . $post_id . '.mp3';
        return $this->upload_dir['baseurl'] . '/kkerem-tts/' . $filename;
    }
    
    /**
     * Ses dosyasının var olup olmadığını kontrol et
     */
    public function audio_file_exists($post_id) {
        $file_path = $this->get_audio_file_path($post_id);
        return file_exists($file_path);
    }
    
    /**
     * Ses dosyasını sil
     */
    public function delete_audio_file($post_id) {
        $file_path = $this->get_audio_file_path($post_id);
        
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        
        return true; // Dosya zaten yoksa başarılı say
    }
    
    /**
     * Post silindiğinde ses dosyasını da sil
     */
    public function handle_post_deletion($post_id) {
        $this->delete_audio_file($post_id);
    }
    
    /**
     * Ses dosyası bilgilerini al
     */
    public function get_audio_file_info($post_id) {
        $file_path = $this->get_audio_file_path($post_id);
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_info = array(
            'file_path' => $file_path,
            'file_url' => $this->get_audio_file_url($post_id),
            'file_size' => filesize($file_path),
            'file_size_formatted' => size_format(filesize($file_path)),
            'created_time' => filemtime($file_path),
            'created_date' => date('Y-m-d H:i:s', filemtime($file_path))
        );
        
        return $file_info;
    }
    
    /**
     * Tüm ses dosyalarını listele
     */
    public function list_all_audio_files() {
        $files = array();
        
        if (!is_dir($this->tts_dir)) {
            return $files;
        }
        
        $dir_files = scandir($this->tts_dir);
        
        foreach ($dir_files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
                $file_path = $this->tts_dir . '/' . $file;
                $file_info = array(
                    'filename' => $file,
                    'file_path' => $file_path,
                    'file_url' => $this->upload_dir['baseurl'] . '/kkerem-tts/' . $file,
                    'file_size' => filesize($file_path),
                    'file_size_formatted' => size_format(filesize($file_path)),
                    'created_time' => filemtime($file_path),
                    'created_date' => date('Y-m-d H:i:s', filemtime($file_path))
                );
                
                // Post ID'sini dosya adından çıkar
                if (preg_match('/post-(\d+)\.mp3/', $file, $matches)) {
                    $file_info['post_id'] = intval($matches[1]);
                }
                
                $files[] = $file_info;
            }
        }
        
        return $files;
    }
    
    /**
     * Kullanılmayan ses dosyalarını temizle
     */
    public function cleanup_orphaned_files() {
        $audio_files = $this->list_all_audio_files();
        $deleted_count = 0;
        
        foreach ($audio_files as $file) {
            if (isset($file['post_id'])) {
                $post = get_post($file['post_id']);
                if (!$post || $post->post_status === 'trash') {
                    if ($this->delete_audio_file($file['post_id'])) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Disk kullanımını hesapla
     */
    public function get_disk_usage() {
        $total_size = 0;
        $file_count = 0;
        
        if (is_dir($this->tts_dir)) {
            $files = $this->list_all_audio_files();
            foreach ($files as $file) {
                $total_size += $file['file_size'];
                $file_count++;
            }
        }
        
        return array(
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'file_count' => $file_count
        );
    }
}
