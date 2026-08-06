<?php
/**
 * Resmi İlan Sistemi Modülü
 * 
 * Bu modül Resmi İlanlar post tipini ve ilgili admin özelliklerini yönetir.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resmi İlanlar Post Tipini Kaydet
 */
function mevzu_resmi_ilanlar_register_post_type() {
    $post_type = 'resmi-ilanlar';

    // Eğer zaten kayıtlıysa (başka bir yerden), önce temizle
    if (post_type_exists($post_type)) {
        unregister_post_type($post_type);
    }

    register_post_type($post_type, array(
        'labels' => array(
            'name' => 'Resmi İlanlar',
            'singular_name' => 'Resmi İlanlar',
            'menu_name' => 'Resmi İlanlar',
            'all_items' => 'Tümünü Görüntüle',
            'edit_item' => 'İlan Düzenle',
            'view_item' => 'İlan Görüntüle',
            'view_items' => 'İlanları Görüntüle',
            'add_new_item' => 'Yeni İlan Ekle',
            'new_item' => 'Yeni İlan',
            'parent_item_colon' => 'Sahip Resmi Ilanlar:',
            'search_items' => 'Resmi Ilanlarda Ara',
            'not_found' => 'Resmi ilan bulunamadı',
            'not_found_in_trash' => 'Çöpte resmi ilan bulunamadı',
            'archives' => 'Resmi ilanlar arşivi',
            'attributes' => 'Resmi ilanlar nitelikleri',
            'insert_into_item' => 'Resmi ilanlara ekle',
            'uploaded_to_this_item' => 'Bu resmi ilanlara yüklendi',
            'filter_items_list' => 'Resmi ilanlar listesini filtrele',
            'filter_by_date' => 'Resmi ilanları tarihe göre filtrele',
            'items_list_navigation' => 'Resmi İlanlar listesinde gezinme',
            'items_list' => 'Resmi İlanlar listesi',
            'item_published' => 'Resmi İlanlar yayınlandı.',
            'item_published_privately' => 'Resmi İlanlar özel olarak yayınlandı.',
            'item_reverted_to_draft' => 'Resmi İlanlar drafta geri döndü.',
            'item_scheduled' => 'Resmi İlanlar planlandı.',
            'item_updated' => 'Resmi İlanlar güncellendi.',
            'item_link' => 'Resmi İlanlar Linki',
            'item_link_description' => 'Resmi ilanların bağlantısı.',
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 7,
        'menu_icon' => 'dashicons-feedback',
        'supports' => array('title', 'editor', 'page-attributes', 'thumbnail'),
        'taxonomies' => array('post_format', 'post_tag'),
        'has_archive' => true,
        'delete_with_user' => false,
    ));
}
add_action('init', 'mevzu_resmi_ilanlar_register_post_type', 9);

/**
 * "At a Glance" Widget'ına Resmi İlan Sayısını Ekle
 */
function mevzu_resmi_ilanlar_add_to_glance() {
    $post_type = 'resmi-ilanlar';
    $post_type_obj = get_post_type_object($post_type);
    if (!$post_type_obj) return;
    
    $count = wp_count_posts($post_type);
    $num_posts = number_format_i18n($count->publish);
    
    if (current_user_can('edit_posts')) {
        $num = sprintf('<a href="edit.php?post_type=%1$s">%2$s %3$s</a>', $post_type, $num_posts, 'Resmi İlan');
    } else {
        $num = sprintf('<span>%1$s %2$s</span>', $num_posts, $post_type_obj->labels->name);
    }

    echo '<li class="post-count ' . $post_type . '-count">' . $num . '</li>';
}
add_filter('dashboard_glance_items', 'mevzu_resmi_ilanlar_add_to_glance');

/**
 * Resmi İlanlar İçin Otomatik Yayın Tarihi (Yarın 00:00)
 */
function mevzu_resmi_ilanlar_set_publish_date($data, $postarr) {
    if (isset($data['post_type']) && $data['post_type'] === 'resmi-ilanlar' && isset($postarr['post_status']) && $postarr['post_status'] === 'auto-draft') {
        $tomorrow = date('Y-m-d 00:00:00', strtotime('+1 day', current_time('timestamp')));
        $data['post_date'] = $tomorrow;
        $data['post_date_gmt'] = get_gmt_from_date($tomorrow);
    }
    return $data;
}
add_filter('wp_insert_post_data', 'mevzu_resmi_ilanlar_set_publish_date', 10, 2);

/**
 * Resmi İlanlar Şablonlarını Yükle
 */
function mevzu_resmi_ilanlar_template_include($template) {
    if (is_singular('resmi-ilanlar')) {
        $new_template = __DIR__ . '/templates/single-resmi-ilanlar.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    } elseif (is_post_type_archive('resmi-ilanlar')) {
        $new_template = __DIR__ . '/templates/archive-resmi-ilanlar.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'mevzu_resmi_ilanlar_template_include');

/**
 * SEO ve Meta Etiketleri İçin Değişken Ayarı
 */
function mevzu_resmi_ilanlar_meta_degisken($degisken) {
    if (get_post_type() === 'resmi-ilanlar') {
        return 'resmiilan';
    }
    return $degisken;
}
add_filter('mevzu_meta_degisken', 'mevzu_resmi_ilanlar_meta_degisken');

/**
 * İlan Numarası Metabox
 */
function mevzu_resmi_ilanlar_metabox() {
    add_meta_box(
        'resmi_ilan_numarasi',
        'İlan Numarası',
        'mevzu_resmi_ilanlar_metabox_render',
        'resmi-ilanlar',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'mevzu_resmi_ilanlar_metabox');

function mevzu_resmi_ilanlar_metabox_render($post) {
    wp_nonce_field('resmi_ilan_numarasi_nonce', 'resmi_ilan_numarasi_nonce_field');
    $value = get_post_meta($post->ID, 'ilan_numarasi', true);
    echo '<label for="ilan_numarasi" style="display:block;margin-bottom:4px">Basın İlan No:</label>';
    echo '<input type="text" id="ilan_numarasi" name="ilan_numarasi" value="' . esc_attr($value) . '" style="width:100%" placeholder="Örn: 123456">';
}

function mevzu_resmi_ilanlar_metabox_save($post_id) {
    if (!isset($_POST['resmi_ilan_numarasi_nonce_field'])) return;
    if (!wp_verify_nonce($_POST['resmi_ilan_numarasi_nonce_field'], 'resmi_ilan_numarasi_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['ilan_numarasi'])) {
        update_post_meta($post_id, 'ilan_numarasi', sanitize_text_field($_POST['ilan_numarasi']));
    }
}
add_action('save_post_resmi-ilanlar', 'mevzu_resmi_ilanlar_metabox_save');

/**
 * Breadcrumb Ayarı
 */
function mevzu_resmi_ilanlar_breadcrumb_show_parents($show, $post_type) {
    if ($post_type === 'resmi-ilanlar') {
        return false;
    }
    return $show;
}
add_filter('mevzu_breadcrumb_show_parents', 'mevzu_resmi_ilanlar_breadcrumb_show_parents', 10, 2);
