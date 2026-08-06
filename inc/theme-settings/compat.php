<?php
/**
 * ACF Uyumluluk Katmanı
 * 
 * ACF eklentisi kaldırıldıktan sonra mevcut get_field(), the_field(),
 * have_rows(), the_row(), get_sub_field(), the_sub_field() çağrılarının
 * çalışmaya devam etmesini sağlar.
 * 
 * ACF verileri WordPress meta tablolarında saklanır:
 *   - Options: wp_options → options_{field_name}
 *   - Post meta: wp_postmeta → {field_name}
 *   - User meta: wp_usermeta → {field_name}
 *   - Term meta: wp_termmeta → {field_name}
 * 
 * Repeater alanları:
 *   - Satır sayısı: options_{repeater_name} = N
 *   - Alt alanlar: options_{repeater_name}_{index}_{sub_field} = value
 * 
 * Group alanları:
 *   - Alt alanlar: options_{group_name}_{sub_field} = value
 */

if (!defined('ABSPATH')) {
    exit;
}

// ACF zaten aktifse hiçbir şey yapma
if (function_exists('get_field')) {
    return;
}

// ============================================================
//  ROW STACK — have_rows/the_row/get_sub_field traversal state
// ============================================================
global $mevzu_acf_row_stack;
$mevzu_acf_row_stack = array();

// ============================================================
//  CONTEXT PARSER
// ============================================================
function mevzu_acf_parse_context($post_id = null) {
    if ($post_id === 'option' || $post_id === 'options') {
        return array('type' => 'option', 'id' => null);
    }
    if (is_string($post_id) && preg_match('/^user_(\d+)$/', $post_id, $m)) {
        return array('type' => 'user', 'id' => intval($m[1]));
    }
    if (is_string($post_id) && preg_match('/^(category|term)_(\d+)$/', $post_id, $m)) {
        return array('type' => 'term', 'id' => intval($m[2]));
    }
    if (is_object($post_id) && isset($post_id->term_id)) {
        return array('type' => 'term', 'id' => intval($post_id->term_id));
    }
    if (is_string($post_id) && preg_match('/^widget_/', $post_id)) {
        return array('type' => 'option', 'id' => null, 'widget_key' => $post_id);
    }
    if ($post_id === null || $post_id === false || $post_id === 0) {
        return array('type' => 'post', 'id' => get_the_ID());
    }
    return array('type' => 'post', 'id' => intval($post_id));
}

// ============================================================
//  META GETTER / SETTER
// ============================================================
function mevzu_acf_get_meta($field_name, $context) {
    switch ($context['type']) {
        case 'option':
            $value = get_option('options_' . $field_name, null);
            if ($value === null) {
                $value = get_option($field_name, null);
            }
            return $value;
        case 'post':
            if (!$context['id']) return null;
            $value = get_post_meta($context['id'], $field_name, true);
            return ($value !== '' && $value !== false) ? $value : null;
        case 'user':
            if (!$context['id']) return null;
            $value = get_user_meta($context['id'], $field_name, true);
            return ($value !== '' && $value !== false) ? $value : null;
        case 'term':
            if (!$context['id']) return null;
            $value = get_term_meta($context['id'], $field_name, true);
            return ($value !== '' && $value !== false) ? $value : null;
    }
    return null;
}

function mevzu_acf_update_meta($field_name, $value, $context) {
    switch ($context['type']) {
        case 'option':
            update_option('options_' . $field_name, $value);
            break;
        case 'post':
            if ($context['id']) update_post_meta($context['id'], $field_name, $value);
            break;
        case 'user':
            if ($context['id']) update_user_meta($context['id'], $field_name, $value);
            break;
        case 'term':
            if ($context['id']) update_term_meta($context['id'], $field_name, $value);
            break;
    }
}

// ============================================================
//  KNOWN GROUP SUB-FIELDS (bilinen group alanlarının alt alanları)
// ============================================================
function mevzu_acf_known_groups() {
    static $groups = null;
    if ($groups !== null) return $groups;
    
    $groups = array(
        'manset' => array('secili_kategori', 'slider_modeli', 'slider_renk', 'slider_sayisi', 'slider_basliklari', 'baslik_boyutu', 'baslik_hizasi'),
        'archive_manset' => array('goster', 'slider_modeli', 'slider_renk', 'slider_sayisi', 'slider_basliklari', 'baslik_boyutu', 'baslik_hizasi'),
        'yapay_zeka_manseti' => array( 'goster', 'baslik', 'baslangic_cumlesi', 'bitis_cumlesi' ),
        'alt_manset' => array('alt_manseti_goster', 'secili_kategori', 'slider_modeli', 'slider_renk', 'slider_sayisi', 'slider_basliklari', 'baslik_boyutu', 'baslik_hizasi'),
        'ana_kategori_grup' => array('ana_kategori', 'ana_kategori_title', 'ana_kategori_titlecheck'),
        'ust_manset' => array('ust_manset_ayarlari', 'slider_sayisi'),
        'ust_reklam' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'alt_reklam' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'sag_sabit_reklam' => array('reklam_basligi', 'image', 'reklam_url'),
        'sol_sabit_reklam' => array('reklam_basligi', 'image', 'reklam_url'),
        'govde_ust_reklam' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'icerik_oncesi' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'icerik_sonrasi' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
    );
    
    return $groups;
}

/**
 * Group alanının alt alanlarını associative array olarak yükle
 */
function mevzu_acf_load_group($group_name, $context) {
    $groups = mevzu_acf_known_groups();
    if (!isset($groups[$group_name])) return null;
    
    $data = array();
    $found = false;
    
    foreach ($groups[$group_name] as $sub) {
        $key = $group_name . '_' . $sub;
        $val = mevzu_acf_get_meta($key, $context);
        if ($val !== null) {
            $data[$sub] = maybe_unserialize($val);
            $found = true;
        } else {
            $data[$sub] = null;
        }
    }
    
    return $found ? $data : null;
}

// ============================================================
//  get_field()
// ============================================================
function get_field($selector, $post_id = false, $format = true) {
    $context = mevzu_acf_parse_context($post_id);
    
    // Önce group olup olmadığını kontrol et (options context için)
    if ($context['type'] === 'option') {
        $group_data = mevzu_acf_load_group($selector, $context);
        if ($group_data !== null) {
            return $group_data;
        }
    }
    
    $value = mevzu_acf_get_meta($selector, $context);
    
    if (is_string($value) && is_serialized($value)) {
        $value = maybe_unserialize($value);
    }
    
    // Logo ve resim attachment ID'lerini otomatik URL'ye çevirme
    if ($format && is_numeric($value)) {
        $is_image_field = false;
        $image_keywords = array('logo', 'favicon', 'avatar', 'resim', 'image', 'gorsel');
        
        foreach ($image_keywords as $keyword) {
            if (stripos($selector, $keyword) !== false) {
                $is_image_field = true;
                break;
            }
        }
        
        if ($is_image_field) {
            $img_url = wp_get_attachment_url($value);
            if ($img_url) {
                return $img_url;
            }
        }
    }
    
    return $value;
}

// ============================================================
//  the_field()
// ============================================================
function the_field($selector, $post_id = false, $format = true) {
    $value = get_field($selector, $post_id, $format);
    if (!is_array($value)) {
        echo $value;
    }
    return $value;
}

// ============================================================
//  have_rows()
// ============================================================
function have_rows($field_name, $post_id = false) {
    global $mevzu_acf_row_stack;
    
    $context = mevzu_acf_parse_context($post_id);
    
    // Stack key: parent prefix + field name
    $parent_prefix = '';
    if (!empty($mevzu_acf_row_stack)) {
        // İç içe repeater: parent'ın prefix'ini al
        end($mevzu_acf_row_stack);
        $parent_key = key($mevzu_acf_row_stack);
        $parent = $mevzu_acf_row_stack[$parent_key];
        
        // Eğer aynı repeater çağrılıyorsa, bu bir alt repeater değildir
        if ($parent['field_name'] !== $field_name && $parent['context']['type'] === $context['type']) {
            $parent_prefix = $parent['meta_prefix'] . $parent['index'] . '_';
        }
    }
    
    $stack_key = $parent_prefix . $field_name . '|' . $context['type'] . '|' . ($context['id'] ?? '');
    
    if (!isset($mevzu_acf_row_stack[$stack_key])) {
        // Repeater satır sayısını bul
        $meta_key = $parent_prefix . $field_name;
        $count_val = mevzu_acf_get_meta($meta_key, $context);
        $count = intval($count_val);
        
        if ($count <= 0) {
            return false;
        }
        
        $mevzu_acf_row_stack[$stack_key] = array(
            'field_name' => $field_name,
            'context'    => $context,
            'count'      => $count,
            'index'      => -1,
            'meta_prefix' => $parent_prefix . $field_name . '_',
        );
    }
    
    $stack = &$mevzu_acf_row_stack[$stack_key];
    
    if ($stack['index'] + 1 < $stack['count']) {
        return true;
    }
    
    // Bitti, temizle
    unset($mevzu_acf_row_stack[$stack_key]);
    return false;
}

// ============================================================
//  the_row()
// ============================================================
function the_row() {
    global $mevzu_acf_row_stack;
    if (empty($mevzu_acf_row_stack)) return false;
    
    end($mevzu_acf_row_stack);
    $key = key($mevzu_acf_row_stack);
    if ($key === null) return false;
    
    $mevzu_acf_row_stack[$key]['index']++;
    return true;
}

// ============================================================
//  get_row() — mevcut satırın tüm alt alanlarını döndürür
//  page-iletisim.php'deki get_row('field', postId)['ilk'] kullanımını destekler
// ============================================================
function get_row($field_name = false, $post_id = false) {
    global $mevzu_acf_row_stack;
    if (empty($mevzu_acf_row_stack)) return false;
    
    end($mevzu_acf_row_stack);
    $key = key($mevzu_acf_row_stack);
    $stack = $mevzu_acf_row_stack[$key];
    
    $context = $stack['context'];
    $index = $stack['index'];
    $prefix = $stack['meta_prefix'];
    
    // Alt alanları yükle — bilinen repeater sub_field'ları veya DB'den otomatik keşif
    // page-iletisim.php 'ilk' ve 'ikinci' kullanıyor → default_repeater
    $known_repeater_subfields = array(
        'default_repeater' => array('ilk', 'ikinci'),
        'bloklar' => array('goruntuleme_sablonu', 'tekli_blok', 'haber_sayisi', 'sablon2__populer_etiketler'),
        'footer_menu' => array('title', 'menu_block'),
        'sablon2__populer_etiketler' => array('text', 'link'),
        'anasayfa_reklam_1' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_2' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_3' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_4' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_5' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_6' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_7' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'anasayfa_reklam_8' => array('reklam_aktif', 'reklam_basligi', 'reklam_url', 'image', 'gorunurluk', 'start_date', 'end_date', 'type', 'html_code', 'placeholder'),
        'menu_block' => array('secim', 'url', 'url2'),
    );
    
    $field = $stack['field_name'];
    $data = array();
    
    if (isset($known_repeater_subfields[$field])) {
        foreach ($known_repeater_subfields[$field] as $sub) {
            $meta_key = $prefix . $index . '_' . $sub;
            $val = mevzu_acf_get_meta($meta_key, $context);
            $data[$sub] = ($val !== null) ? maybe_unserialize($val) : null;
        }
    }
    
    return $data;
}

// ============================================================
//  get_row_index()
// ============================================================
function get_row_index() {
    global $mevzu_acf_row_stack;
    if (empty($mevzu_acf_row_stack)) return 0;
    
    end($mevzu_acf_row_stack);
    $key = key($mevzu_acf_row_stack);
    return $mevzu_acf_row_stack[$key]['index'];
}

// ============================================================
//  get_sub_field()
//  İkinci parametre ACF'de format_value'dur ama temada bazen
//  'option' olarak geçiriliyor — bunu ignore ederiz, parent context kullanırız
// ============================================================
function get_sub_field($selector, $format = true) {
    global $mevzu_acf_row_stack;
    if (empty($mevzu_acf_row_stack)) return null;
    
    end($mevzu_acf_row_stack);
    $key = key($mevzu_acf_row_stack);
    $stack = $mevzu_acf_row_stack[$key];
    
    $context = $stack['context'];
    $index = $stack['index'];
    $prefix = $stack['meta_prefix'];
    
    // meta key: {prefix}{index}_{sub_field}
    $meta_key = $prefix . $index . '_' . $selector;
    
    $value = mevzu_acf_get_meta($meta_key, $context);
    
    if (is_string($value) && is_serialized($value)) {
        $value = maybe_unserialize($value);
    }
    
    // Alt alanlar (sub_field) için logo ve resim attachment ID'lerini otomatik URL'ye çevirme
    if ($format && is_numeric($value)) {
        $is_image_field = false;
        $image_keywords = array('logo', 'favicon', 'avatar', 'resim', 'image', 'gorsel');
        
        foreach ($image_keywords as $keyword) {
            if (stripos($selector, $keyword) !== false) {
                $is_image_field = true;
                break;
            }
        }
        
        if ($is_image_field) {
            $img_url = wp_get_attachment_url($value);
            if ($img_url) {
                return $img_url;
            }
        }
    }
    
    return $value;
}

// ============================================================
//  the_sub_field()
// ============================================================
function the_sub_field($selector, $format = true) {
    $value = get_sub_field($selector, $format);
    if (!is_array($value)) {
        echo $value;
    }
    return $value;
}

// ============================================================
//  update_field() / delete_field()
// ============================================================
function update_field($selector, $value, $post_id = false) {
    $context = mevzu_acf_parse_context($post_id);
    mevzu_acf_update_meta($selector, $value, $context);
}

function delete_field($selector, $post_id = false) {
    $context = mevzu_acf_parse_context($post_id);
    switch ($context['type']) {
        case 'option': delete_option('options_' . $selector); break;
        case 'post':   if ($context['id']) delete_post_meta($context['id'], $selector); break;
        case 'user':   if ($context['id']) delete_user_meta($context['id'], $selector); break;
        case 'term':   if ($context['id']) delete_term_meta($context['id'], $selector); break;
    }
}

// ============================================================
//  get_field_object() / get_fields() — basitleştirilmiş stub'lar
// ============================================================
function get_field_object($selector, $post_id = false, $format = true) {
    return array(
        'key' => $selector, 'name' => $selector,
        'value' => get_field($selector, $post_id, $format),
        'type' => 'text',
    );
}

function get_fields($post_id = false) {
    $context = mevzu_acf_parse_context($post_id);
    if ($context['type'] === 'post' && $context['id']) {
        $all = get_post_meta($context['id']);
        $fields = array();
        foreach ($all as $k => $v) {
            if (substr($k, 0, 1) !== '_') {
                $fields[$k] = maybe_unserialize($v[0]);
            }
        }
        return $fields;
    }
    return array();
}

// ============================================================
//  ACF Options Page stubs — artık theme-settings paneli bunu yapıyor
// ============================================================
function acf_add_options_page($args = array())     { return true; }
function acf_add_options_sub_page($args = array()) { return true; }

// ============================================================
//  ACF'nin kullandığı diğer fonksiyon stub'ları (ihtiyaç olursa)
// ============================================================
if (!function_exists('acf_register_block_type')) {
    function acf_register_block_type($args = array()) { return true; }
}
