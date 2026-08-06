<?php
/**
 * Kategori alanları: Kategori Rengi
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Category_Fields {
    
    public function __construct() {
        add_action('category_add_form_fields', array($this, 'add_fields'));
        add_action('category_edit_form_fields', array($this, 'edit_fields'));
        add_action('created_category', array($this, 'save_fields'));
        add_action('edited_category', array($this, 'save_fields'));
    }
    
    private function get_colors() {
        return array(
            'primary'       => 'Tema Rengi',
            'secondary'     => 'Gri',
            'success'       => 'Yeşil',
            'danger'        => 'Kırmızı',
            'warning'       => 'Sarı',
            'info'          => 'Açık Mavi',
        );
    }
    
    public function add_fields() {
        ?>
        <div class="form-field">
            <label for="mevzu_cat_renk">Kategori Rengi</label>
            <select name="mevzu_cat_renk" id="mevzu_cat_renk">
                <option value="">— Seçiniz —</option>
                <?php foreach ($this->get_colors() as $val => $text): ?>
                    <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($text); ?></option>
                <?php endforeach; ?>
            </select>
            <p>Bu kategori için kullanılacak renk teması.</p>
        </div>
        <?php
    }
    
    public function edit_fields($term) {
        $value = get_term_meta($term->term_id, 'cat_renk', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="mevzu_cat_renk">Kategori Rengi</label></th>
            <td>
                <select name="mevzu_cat_renk" id="mevzu_cat_renk">
                    <option value="">— Seçiniz —</option>
                    <?php foreach ($this->get_colors() as $val => $text): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($value, $val); ?>><?php echo esc_html($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }
    
    public function save_fields($term_id) {
        if (isset($_POST['mevzu_cat_renk'])) {
            update_term_meta($term_id, 'cat_renk', sanitize_text_field($_POST['mevzu_cat_renk']));
        }
    }
}

new Mevzu_Category_Fields();
