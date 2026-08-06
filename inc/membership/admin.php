<?php
/**
 * Admin settings for the membership module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'mevzu_membership_admin_menu');
function mevzu_membership_admin_menu() {
    add_menu_page(
        'Üyelik Ayarları', 
        'Üyelik Sistemi', 
        'manage_options', 
        'mevzu-membership', 
        'mevzu_membership_settings_page', 
        'dashicons-groups', 
        69 // Reklamlı Haberler (30) menüsünün hemen altı
    );
}

add_action('admin_init', 'mevzu_membership_register_settings');
function mevzu_membership_register_settings() {
    register_setting('mevzu_membership_settings', 'mevzu_membership_enabled');
    register_setting('mevzu_membership_settings', 'mevzu_category_subscription_enabled');
    register_setting('mevzu_membership_settings', 'mevzu_tag_subscription_enabled');
    register_setting('mevzu_membership_settings', 'mevzu_comment_notification_enabled');
    register_setting('mevzu_membership_settings', 'mevzu_notification_auto_delete');
    register_setting('mevzu_membership_settings', 'mevzu_email_notifications_enabled');
    register_setting('mevzu_membership_settings', 'mevzu_like_public_enabled');
}

function mevzu_membership_settings_page() {
    $auto_delete_opt = get_option('mevzu_notification_auto_delete', '0');
    ?>
    <div class="wrap">
        <h2>Mevzu Üyelik & Bildirim Sistemi Ayarları</h2>
        <form method="post" action="options.php" id="mevzu-membership-form" class="mevzu-admin-form">
            <?php settings_fields('mevzu_membership_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Üyelik Sistemini Aktif Et</th>
                    <td>
                        <input type="checkbox" name="mevzu_membership_enabled" value="1" <?php checked('1', get_option('mevzu_membership_enabled', '1')); ?> />
                        <p class="description">İşaretlendiğinde üyelik, profil ve ilişkili bildirim özellikleri açılır.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Kategori Takip Sistemini Aktif Et</th>
                    <td>
                        <input type="checkbox" name="mevzu_category_subscription_enabled" value="1" <?php checked('1', get_option('mevzu_category_subscription_enabled', '1')); ?> />
                        <p class="description">Kullanıcıların kategorileri takip etmasına ve yeni yazılarda bildirim almasına izin verir.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Etiket Takip Sistemini Aktif Et</th>
                    <td>
                        <input type="checkbox" name="mevzu_tag_subscription_enabled" value="1" <?php checked('1', get_option('mevzu_tag_subscription_enabled', '1')); ?> />
                        <p class="description">Kullanıcıların etiketleri takip etmesine ve yeni yazılarda bildirim almasına izin verir.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Yorum Yanıtı Bildirimleri</th>
                    <td>
                        <input type="checkbox" name="mevzu_comment_notification_enabled" value="1" <?php checked('1', get_option('mevzu_comment_notification_enabled', '1')); ?> />
                        <p class="description">Kullanıcıların yaptıkları yorumlara yanıt geldiğinde bildirim almalarını sağlar.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Bildirimleri Otomatik Sil</th>
                    <td>
                        <select name="mevzu_notification_auto_delete">
                            <option value="0" <?php selected($auto_delete_opt, '0'); ?>>Silme</option>
                            <option value="3" <?php selected($auto_delete_opt, '3'); ?>>3 gün sonra</option>
                            <option value="7" <?php selected($auto_delete_opt, '7'); ?>>1 hafta sonra</option>
                            <option value="14" <?php selected($auto_delete_opt, '14'); ?>>2 hafta sonra</option>
                            <option value="30" <?php selected($auto_delete_opt, '30'); ?>>1 ay sonra</option>
                            <option value="60" <?php selected($auto_delete_opt, '60'); ?>>2 ay sonra</option>
                            <option value="90" <?php selected($auto_delete_opt, '90'); ?>>3 ay sonra</option>
                            <option value="180" <?php selected($auto_delete_opt, '180'); ?>>6 ay sonra</option>
                            <option value="365" <?php selected($auto_delete_opt, '365'); ?>>1 yıl sonra</option>
                            <option value="730" <?php selected($auto_delete_opt, '730'); ?>>2 yıl sonra</option>
                            <option value="1095" <?php selected($auto_delete_opt, '1095'); ?>>3 yıl sonra</option>
                            <option value="1825" <?php selected($auto_delete_opt, '1825'); ?>>5 yıl sonra</option>
                            <option value="3650" <?php selected($auto_delete_opt, '3650'); ?>>10 yıl sonra</option>
                        </select>
                        <p class="description">Bu süre zarfından eski olan bildirimler veritabanından otomatik olarak temizlenir.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">E-Posta Bildirim Sistemi Gönderimleri Aktif Et</th>
                    <td>
                        <input type="checkbox" name="mevzu_email_notifications_enabled" value="1" <?php checked('1', get_option('mevzu_email_notifications_enabled', '0')); ?> />
                        <p class="description">Bildirimlerin site bildirimi yanı sıra e-posta olarak da HTML şablon formunda iletilmesini sağlar.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Beğeni Sistemi Erişimi</th>
                    <td>
                        <select name="mevzu_like_public_enabled">
                            <option value="0" <?php selected(get_option('mevzu_like_public_enabled', '0'), '0'); ?>>Sadece Üyelere Açık</option>
                            <option value="1" <?php selected(get_option('mevzu_like_public_enabled', '0'), '1'); ?>>Herkese Açık</option>
                        </select>
                        <p class="description">"Herkese Açık" seçildiğinde giriş yapmamış ziyaretçiler de beğeni yapabilir (çerez tabanlı takip).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
