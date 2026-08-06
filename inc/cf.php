<?php
// Admin sayfası ve gerekli stiller / scriptler eklemek
function mevzu_admin_page() {
    add_menu_page(
        'Mevzu Ayarları',             // Sayfa başlığı
        'Mevzu',                      // Menü başlığı
        'manage_options',             // Yetki gereksinimi
        'mevzu-ayarlari',             // Sayfa slug
        'mevzu_settings_page',        // Sayfa callback fonksiyonu
        'dashicons-admin-generic',    // Menü ikonu
        80                            // Menü sırası
    );
}
add_action( 'admin_menu', 'mevzu_admin_page' );

// Admin sayfası içeriği
function mevzu_settings_page() {
    ?>
    <div class="wrap">
        <h1>Mevzu Ayarları</h1>

        <form method="post" action="options.php">
            <div class="d-flex align-items-start">
                <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="v-pills-home-tab" data-bs-toggle="pill" data-bs-target="#v-pills-home" type="button" role="tab" aria-controls="v-pills-home" aria-selected="true">Home</button>
                <button class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="false" tabindex="-1">Profile</button>
                <button class="nav-link" id="v-pills-disabled-tab" data-bs-toggle="pill" data-bs-target="#v-pills-disabled" type="button" role="tab" aria-controls="v-pills-disabled" aria-selected="false" disabled="" tabindex="-1">Disabled</button>
                <button class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill" data-bs-target="#v-pills-messages" type="button" role="tab" aria-controls="v-pills-messages" aria-selected="false" tabindex="-1">Messages</button>
                <button class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill" data-bs-target="#v-pills-settings" type="button" role="tab" aria-controls="v-pills-settings" aria-selected="false" tabindex="-1">Settings</button>
                </div>
                <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab" tabindex="0">
                    <p>This is some placeholder content the <strong>Home tab's</strong> associated content. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling. You can use it with tabs, pills, and any other <code>.nav</code>-powered navigation.</p>
                </div>
                <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab" tabindex="0">
                    <p>This is some placeholder content the <strong>Profile tab's</strong> associated content. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling. You can use it with tabs, pills, and any other <code>.nav</code>-powered navigation.</p>
                </div>
                <div class="tab-pane fade" id="v-pills-disabled" role="tabpanel" aria-labelledby="v-pills-disabled-tab" tabindex="0">
                    <p>This is some placeholder content the <strong>Disabled tab's</strong> associated content.</p>
                </div>
                <div class="tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab" tabindex="0">
                    <p>This is some placeholder content the <strong>Messages tab's</strong> associated content. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling. You can use it with tabs, pills, and any other <code>.nav</code>-powered navigation.</p>
                </div>
                <div class="tab-pane fade" id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab" tabindex="0">
                    <p>This is some placeholder content the <strong>Settings tab's</strong> associated content. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling. You can use it with tabs, pills, and any other <code>.nav</code>-powered navigation.</p>
                </div>
                </div>
            </div>
            <?php
            settings_fields( 'mevzu_ayarları_grubu' );
            do_settings_sections( 'mevzu-ayarlari' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Ayarları kaydetmek için fonksiyon
function mevzu_settings_init() {
    // Genel ayarları ekliyoruz
    register_setting( 'mevzu_ayarları_grubu', 'mevzu_ayarları' );

    // Site Rengi
    add_settings_section(
        'mevzu_genel_ayarlar', 
        'Genel Ayarlar', 
        null, 
        'mevzu-ayarlari'
    );
    
    add_settings_field(
        'site_rengi', 
        'Site Rengi', 
        'mevzu_site_rengi', 
        'mevzu-ayarlari', 
        'mevzu_genel_ayarlar'
    );

    // Favicon
    add_settings_field(
        'favicon', 
        'Favicon', 
        'mevzu_favicon', 
        'mevzu-ayarlari', 
        'mevzu_genel_ayarlar'
    );

    // Logo
    add_settings_field(
        'logo', 
        'Logo', 
        'mevzu_logo', 
        'mevzu-ayarlari', 
        'mevzu_genel_ayarlar'
    );

    // Ana Manşet Ayarları
    add_settings_section(
        'mevzu_manset_ayarlar', 
        'Ana Manşet Ayarları', 
        null, 
        'mevzu-ayarlari'
    );

    add_settings_field(
        'ana_kategori', 
        'Ana Kategori', 
        'mevzu_ana_kategori', 
        'mevzu-ayarlari', 
        'mevzu_manset_ayarlar'
    );
}
add_action( 'admin_init', 'mevzu_settings_init' );

// Site Rengi - WordPress renk paletinden seçim
function mevzu_site_rengi() {
    $options = get_option('mevzu_ayarları');
    ?>
    <input type="text" name="mevzu_ayarları[site_rengi]" value="<?php echo isset($options['site_rengi']) ? esc_attr($options['site_rengi']) : ''; ?>" class="my-color-field" data-default-color="#effeff" />
    <?php
}

// Favicon Alanı
function mevzu_favicon() {
    $options = get_option('mevzu_ayarları');
    $favicon = isset($options['favicon']) ? esc_url($options['favicon']) : '';
    ?>
    <input type="url" name="mevzu_ayarları[favicon]" value="<?php echo $favicon; ?>" class="regular-text" />
    <!-- data-upload-type ile 'favicon' olduğunu belirtiyoruz -->
    <button type="button" class="upload_image_button button" data-upload-type="favicon">Favicon Seç</button>
    <?php if ( ! empty( $favicon ) ) : ?>
        <div class="favicon-preview" style="margin-top:10px;">
            <img src="<?php echo $favicon; ?>" alt="Favicon Önizleme" style="max-width:150px; height:auto;" />
            <button type="button" class="remove_image_button button" style="margin-top:5px;">Görseli Kaldır</button>
        </div>
    <?php endif;
}

// Logo Alanı
function mevzu_logo() {
    $options = get_option('mevzu_ayarları');
    $logo = isset($options['logo']) ? esc_url($options['logo']) : '';
    ?>
    <?php if ( ! empty( $logo ) ) : ?>
        <div class="logo-preview mb-3 border-bottom" style="margin-top:10px;">
            <img src="<?php echo $logo; ?>" alt="Logo Önizleme" style="max-width:150px; height:auto;" />
        </div>
    <?php endif; ?>
    <input type="url" name="mevzu_ayarları[logo]" value="<?php echo $logo; ?>" class="regular-text" />
    <!-- data-upload-type ile 'logo' olduğunu belirtiyoruz -->
    <button type="button" class="upload_image_button button" data-upload-type="logo">Logo Seç</button>
    <button type="button" class="remove_image_button button">Görseli Kaldır</button>
    <?php
}


// Ana Kategori - Select2 ile kategori seçimi
function mevzu_ana_kategori() {
    $options = get_option('mevzu_ayarları');
    ?>
    <select name="mevzu_ayarları[ana_kategori][]" class="mevzu_select2" multiple="multiple" style="width: 100%">
        <?php
        $categories = get_categories();
        foreach ($categories as $category) {
            $selected = ( isset($options['ana_kategori']) && in_array($category->term_id, $options['ana_kategori']) ) ? 'selected' : '';
            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
        }
        ?>
    </select>
    <?php
}

function mevzu_admin_scripts($hook) {
    if ($hook != 'toplevel_page_mevzu-ayarlari') {
        return;
    }
    
    // WordPress'in renk paleti stil ve scriptleri
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Medya kütüphanesi
    wp_enqueue_media();

    // Select2 JS ve CSS dosyaları
    wp_enqueue_script('jquery-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js');
    wp_enqueue_script('mevzu2-js', get_template_directory_uri() . '/js/mevzu2.bundle.min.js');
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), '', true);

    // Özel inline script: Color Picker, Select2, Medya Yükleme ve Görsel Kaldırma işlemleri
    wp_add_inline_script('select2-js', "
        jQuery(document).ready(function($){
            $('.my-color-field').wpColorPicker();
            $('.mevzu_select2').select2();
            
            // Medya yükleme işlemi
            $('.upload_image_button').click(function() {
                var input = $(this).prev(); // input alanı
                var uploadType = $(this).data('upload-type'); // 'favicon' veya 'logo'
                
                var frame = wp.media({
                    title: 'Medya Seç',
                    button: { text: 'Seç' },
                    multiple: false,
                    uploader: {
                        // wp.media'ya ek parametre gönderiyoruz
                        params: { upload_type: uploadType }
                    }
                });
                
                frame.open().on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    input.val(attachment.url);
                    
                    // Önizleme alanı belirliyoruz
                    var previewClass = (uploadType === 'favicon') ? 'favicon-preview' : 'logo-preview';
                    var previewContainer = input.siblings('.' + previewClass);
                    if (previewContainer.length > 0) {
                        previewContainer.find('img').attr('src', attachment.url);
                    } else {
                        input.after('<div class=\"' + previewClass + '\" style=\"margin-top:10px;\"><img src=\"' + attachment.url + '\" alt=\"' + (uploadType === 'favicon' ? 'Favicon' : 'Logo') + ' Önizleme\" style=\"max-width:150px; height:auto;\" /></div>');
                    }
                });
            });
            
            // Görsel kaldırma işlemi (sayfa yüklendiğinde de mevcut butonlar için çalışır)
            $(document).on('click', '.remove_image_button', function(e) {
                e.preventDefault();
                var container = $(this).closest('.logo-preview, .favicon-preview');
                var input = container.siblings('input[type=\"url\"]').first();
                input.val('');
                container.remove();
            });
        });
    ");
}
add_action('admin_enqueue_scripts', 'mevzu_admin_scripts');


function custom_upload_prefilter($file) {
    // Eğer 'upload_type' parametresi yoksa, yani bizim özel yüklemeler dışındaysa hiçbir kontrol uygulamıyoruz
    if (! isset($_REQUEST['upload_type'])) {
        return $file;
    }
    
    $upload_type = $_REQUEST['upload_type'];
    
    if ($upload_type === 'favicon') {
        // Favicon için izin verilen MIME türleri:
        // Not: .ico dosyaları için WordPress'te 'image/x-icon' veya 'image/vnd.microsoft.icon' MIME tipleri kullanılabilir.
        $allowed_mime_types = array(
            'image/x-icon',
            'image/vnd.microsoft.icon',
            'image/jpeg',
            'image/png',
            'image/svg+xml'
        );
        
        if (! in_array($file['type'], $allowed_mime_types)) {
            $file['error'] = 'Favicon için sadece ICO, JPG, JPEG, PNG ve SVG dosya tipleri yüklenebilir.';
            return $file;
        }
        
        // Dosya boyutu kontrolü (1MB = 1048576 byte)
        if ($file['size'] > 1048576) {
            $file['error'] = 'Favicon dosya boyutu 1MB\'ı aşamaz.';
            return $file;
        }
        
        // Görsel boyutları kontrolü (maksimum 512x512 piksel)
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info) {
            $width  = $image_info[0];
            $height = $image_info[1];
            if ($width > 512 || $height > 512) {
                $file['error'] = 'Favicon maksimum boyutları 512x512 piksel olmalıdır.';
                return $file;
            }
        }
        
    } elseif ($upload_type === 'logo') {
        // Logo için izin verilen MIME türleri:
        $allowed_mime_types = array(
            'image/jpeg',
            'image/png',
            'image/svg+xml'
        );
        
        if (! in_array($file['type'], $allowed_mime_types)) {
            $file['error'] = 'Logo için sadece JPG, JPEG, PNG ve SVG dosya tipleri yüklenebilir.';
            return $file;
        }
        
        // Dosya boyutu kontrolü (1MB = 1048576 byte)
        if ($file['size'] > 1048576) {
            $file['error'] = 'Logo dosya boyutu 1MB\'ı aşamaz.';
            return $file;
        }
        
        // Görsel boyutları kontrolü (maksimum 200x56 piksel)
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info) {
            $width  = $image_info[0];
            $height = $image_info[1];
            if ($width > 200 || $height > 56) {
                $file['error'] = 'Logo maksimum boyutları 200x56 piksel olmalıdır.';
                return $file;
            }
        }
    }
    
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'custom_upload_prefilter');
