<?php
/**
 * Menü item alanları: Menü İkonu
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Menu_Fields {
    
    public function __construct() {
        add_action('wp_nav_menu_item_custom_fields', array($this, 'add_fields'), 10, 2);
        add_action('wp_update_nav_menu_item', array($this, 'save_fields'), 10, 2);
    }
    
    public function add_fields($item_id, $menu_item) {
        $icon = get_post_meta($item_id, 'menu-ikon', true);
        ?>
        <p class="field-mevzu-icon description description-wide">
            <label for="mevzu-menu-icon-<?php echo esc_attr($item_id); ?>">
                İkon (SVG/HTML)
                <textarea id="mevzu-menu-icon-<?php echo esc_attr($item_id); ?>" 
                          name="mevzu_menu_ikon[<?php echo esc_attr($item_id); ?>]" 
                          rows="3" class="widefat"><?php echo esc_textarea($icon); ?></textarea>
            </label>
        </p>
        <?php
    }
    
    public function save_fields($menu_id, $menu_item_id) {
        if (isset($_POST['mevzu_menu_ikon'][$menu_item_id])) {
            $value = wp_kses_post($_POST['mevzu_menu_ikon'][$menu_item_id]);
            update_post_meta($menu_item_id, 'menu-ikon', $value);
        }
    }
}

new Mevzu_Menu_Fields();
