<?php
/**
 * Kullanıcı profil alanları: Yazar ayarları, sosyal medya hesapları
 */
if (!defined('ABSPATH')) exit;

class Mevzu_User_Fields {

    public function __construct() {
        add_action('show_user_profile', array($this, 'render_fields'));
        add_action('edit_user_profile', array($this, 'render_fields'));
        add_action('personal_options_update', array($this, 'save_fields'));
        add_action('edit_user_profile_update', array($this, 'save_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public static function get_social_fields() {
        return array(
            'facebook' => array(
                'label'       => 'Facebook',
                'type'        => 'url',
                'placeholder' => 'https://facebook.com/...',
                'meta_key'    => 'mevzu_social_facebook',
            ),
            'twitter' => array(
                'label'       => 'X (Twitter)',
                'type'        => 'url',
                'placeholder' => 'https://x.com/...',
                'meta_key'    => 'mevzu_social_twitter',
            ),
            'instagram' => array(
                'label'       => 'Instagram',
                'type'        => 'url',
                'placeholder' => 'https://instagram.com/...',
                'meta_key'    => 'mevzu_social_instagram',
            ),
            'youtube' => array(
                'label'       => 'YouTube',
                'type'        => 'url',
                'placeholder' => 'https://youtube.com/...',
                'meta_key'    => 'mevzu_social_youtube',
            ),
            'whatsapp' => array(
                'label'       => 'WhatsApp',
                'type'        => 'text',
                'placeholder' => '5XX XXX XX XX veya https://wa.me/...',
                'meta_key'    => 'mevzu_social_whatsapp',
                'description' => 'Telefon numarası veya WhatsApp bağlantısı girebilirsiniz.',
            ),
            'linkedin' => array(
                'label'       => 'LinkedIn',
                'type'        => 'url',
                'placeholder' => 'https://linkedin.com/in/...',
                'meta_key'    => 'mevzu_social_linkedin',
            ),
            'tiktok' => array(
                'label'       => 'TikTok',
                'type'        => 'url',
                'placeholder' => 'https://tiktok.com/@...',
                'meta_key'    => 'mevzu_social_tiktok',
            ),
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'user-edit.php' && $hook !== 'profile.php') return;
        wp_enqueue_media();
    }

    public function render_fields($user) {
        $gizle = get_user_meta($user->ID, 'kullaniciyi_gizle', true);
        ?>
        <h3>Mevzu² Yazar Ayarları</h3>
        <table class="form-table">
            <tr>
                <th><label>Yazarlar kısmında gizle</label></th>
                <td>
                    <label>
                        <input type="hidden" name="mevzu_kullaniciyi_gizle" value="0">
                        <input type="checkbox" name="mevzu_kullaniciyi_gizle" value="1" <?php checked($gizle, '1'); ?>>
                        Bu kullanıcıyı yazarlar listesinde gizle
                    </label>
                </td>
            </tr>
        </table>

        <h3>Mevzu² Sosyal Medya Hesapları</h3>
        <p class="description">Bu alanlar yazar profilinde ve köşe yazılarında gösterilir.</p>
        <table class="form-table">
            <?php foreach (self::get_social_fields() as $key => $field) :
                $value = get_user_meta($user->ID, $field['meta_key'], true);
                $input_name = 'mevzu_social_' . $key;
                ?>
            <tr>
                <th><label for="<?php echo esc_attr($input_name); ?>"><?php echo esc_html($field['label']); ?></label></th>
                <td>
                    <input
                        type="<?php echo esc_attr($field['type']); ?>"
                        class="regular-text"
                        id="<?php echo esc_attr($input_name); ?>"
                        name="<?php echo esc_attr($input_name); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                    >
                    <?php if (!empty($field['description'])) : ?>
                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function save_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;

        if (isset($_POST['mevzu_kullaniciyi_gizle'])) {
            update_user_meta($user_id, 'kullaniciyi_gizle', sanitize_text_field($_POST['mevzu_kullaniciyi_gizle']));
        }

        foreach (self::get_social_fields() as $key => $field) {
            $input_name = 'mevzu_social_' . $key;
            if (!isset($_POST[$input_name])) {
                continue;
            }

            $raw = wp_unslash($_POST[$input_name]);
            if ($field['type'] === 'url') {
                $value = esc_url_raw(trim($raw));
            } else {
                $value = sanitize_text_field(trim($raw));
            }

            if ($value === '') {
                delete_user_meta($user_id, $field['meta_key']);
            } else {
                update_user_meta($user_id, $field['meta_key'], $value);
            }
        }
    }
}

new Mevzu_User_Fields();

/**
 * Kullanıcının sosyal medya bağlantılarını döndürür.
 *
 * @return array<string, array{label:string, url:string, icon:string, class:string}>
 */
function mevzu_get_user_social_links($user_id, $black = 0) {
    $user_id = (int) $user_id;
    if (!$user_id) {
        return array();
    }

    $icons = array(
        'facebook'  => array('icon' => 'ri-facebook-circle-fill', 'class' => ($black==1 ? 'text-body text-facebook-hover' : 'text-facebook'), 'label' => 'Facebook'),
        'twitter'   => array('icon' => 'ri-twitter-x-fill', 'class' => ($black==1 ? 'text-body text-twitter-hover' : 'text-twitter'), 'label' => 'X'),
        'instagram' => array('icon' => 'ri-instagram-line', 'class' => ($black==1 ? 'text-body text-instagram-hover' : 'text-instagram'), 'label' => 'Instagram'),
        'youtube'   => array('icon' => 'ri-youtube-fill', 'class' => ($black==1 ? 'text-body text-youtube-hover' : 'text-youtube'), 'label' => 'YouTube'),
        'whatsapp'  => array('icon' => 'ri-whatsapp-line', 'class' => ($black==1 ? 'text-body text-whatsapp-hover' : 'text-whatsapp'), 'label' => 'WhatsApp'),
        'linkedin'  => array('icon' => 'ri-linkedin-box-fill', 'class' => ($black==1 ? 'text-body text-linkedin-hover' : 'text-linkedin'), 'label' => 'LinkedIn'),
        'tiktok'    => array('icon' => 'ri-tiktok-fill', 'class' => ($black==1 ? 'text-body text-tiktok-hover' : 'text-tiktok'), 'label' => 'TikTok'),
    );

    $links = array();

    foreach (Mevzu_User_Fields::get_social_fields() as $key => $field) {
        $raw = get_user_meta($user_id, $field['meta_key'], true);
        if ($raw === '' || $raw === null) {
            continue;
        }

        $url = mevzu_normalize_user_social_url($key, $raw);
        if (!$url) {
            continue;
        }

        $icon_data = isset($icons[$key]) ? $icons[$key] : array(
            'icon'  => 'ri-links-line',
            'class' => 'text-body',
            'label' => $field['label'],
        );

        $links[$key] = array(
            'label' => $icon_data['label'],
            'url'   => $url,
            'icon'  => $icon_data['icon'],
            'class' => $icon_data['class'],
        );
    }

    return $links;
}

/**
 * Sosyal medya değerini tıklanabilir URL'ye çevirir.
 */
function mevzu_normalize_user_social_url($key, $value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if ($key === 'whatsapp') {
        if (preg_match('#^https?://#i', $value)) {
            return esc_url_raw($value);
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '90') !== 0 && strlen($digits) === 10) {
            $digits = '90' . $digits;
        }

        return 'https://wa.me/' . $digits;
    }

    if (!preg_match('#^https?://#i', $value)) {
        return '';
    }

    return esc_url_raw($value);
}

/**
 * Kullanıcı sosyal medya ikonlarını ekrana basar.
 */
function mevzu_render_user_social_links($user_id, $args = array(), $black = 0) {
    $links = mevzu_get_user_social_links($user_id);
    if (empty($links)) {
        return;
    }

    $args = wp_parse_args($args, array(
        'wrapper_class' => 'mevzu-user-social d-flex flex-wrap align-items-center gap-2 mt-2',
        'link_class'    => 'mevzu-user-social__link d-inline-flex align-items-center justify-content-center rounded-circle border text-decoration-none',
        'icon_class'    => 'h6 m-0',
        'size'          => 28,
    ));

    echo '<div class="' . esc_attr($args['wrapper_class']) . '">';

    foreach ($links as $key => $social) {
        $style = $args['size'] ? ' style="width:' . (int) $args['size'] . 'px;height:' . (int) $args['size'] . 'px;"' : '';
        echo '<a href="' . esc_url($social['url']) . '" class="' . esc_attr($args['link_class'] . ' ' . $social['class'] . ' ' . $args['icon_class']) . '" target="_blank" rel="nofollow noopener" aria-label="' . esc_attr($social['label']) . '"' . $style . '>';
        echo '<i class="' . esc_attr($social['icon']) . '"></i>';
        echo '</a>';
    }

    echo '</div>';
}
