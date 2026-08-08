<?php
/**
 * "Kaynak" Alanı — Haberler İçin Tekil, Yeniden Kullanılabilir Kaynak Bilgisi
 *
 * Etiketler gibi düz bir taksonomiye dayanır (önceden girilen değerler
 * otomatik tamamlama ile önerilir, aynı isim tekrar term oluşturmaz).
 * Etiketlerden farkı: bir yazıya yalnızca TEK bir kaynak atanabilir.
 * Varsayılan çoklu-seçim etiket kutusu bu yüzden meta_box_cb => false ile
 * kapatılır; yerine bu dosyadaki tekli-seçim otomatik tamamlama kutusu kullanılır.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mevzu_kaynak_register_taxonomy() {
    register_taxonomy( 'kaynak', 'post', [
        'labels' => [
            'name'          => 'Kaynaklar',
            'singular_name' => 'Kaynak',
            'search_items'  => 'Kaynak Ara',
            'all_items'     => 'Tüm Kaynaklar',
            'edit_item'     => 'Kaynağı Düzenle',
            'update_item'   => 'Kaynağı Güncelle',
            'add_new_item'  => 'Yeni Kaynak Ekle',
            'new_item_name' => 'Yeni Kaynak Adı',
            'menu_name'     => 'Kaynaklar',
        ],
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'meta_box_cb'       => false,
        'rewrite'           => [ 'slug' => 'kaynak' ],
    ] );

    // Yeni bir kaynak eklenirken (daha önce var olmayan bir isimse) o kaynağın
    // web sitesi adresini de saklayabilmek için term meta.
    register_term_meta( 'kaynak', 'kaynak_url', [
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest'      => false,
    ] );
}
add_action( 'init', 'mevzu_kaynak_register_taxonomy' );

/**
 * Taksonomi ilk kez eklendiğinde /kaynak/ arşiv linklerinin 404 vermemesi için
 * tek seferlik rewrite flush. Her sayfa yüklemesinde flush_rewrite_rules()
 * çalıştırmak pahalı olduğundan bir option ile kilitlenir.
 */
function mevzu_kaynak_maybe_flush_rewrite() {
    if ( get_option( 'mevzu_kaynak_rewrite_flushed' ) !== '1' ) {
        flush_rewrite_rules();
        update_option( 'mevzu_kaynak_rewrite_flushed', '1' );
    }
}
add_action( 'init', 'mevzu_kaynak_maybe_flush_rewrite', 20 );

function mevzu_kaynak_add_meta_box() {
    add_meta_box(
        'mevzu-kaynak',
        __( 'Kaynak', 'mevzu2' ),
        'mevzu_kaynak_render_meta_box',
        'post',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'mevzu_kaynak_add_meta_box' );

function mevzu_kaynak_render_meta_box( $post ) {
    wp_nonce_field( 'mevzu_kaynak_save', 'mevzu_kaynak_nonce' );

    $terimler = wp_get_post_terms( $post->ID, 'kaynak', [ 'fields' => 'names' ] );
    $mevcut   = ( ! is_wp_error( $terimler ) && ! empty( $terimler ) ) ? $terimler[0] : '';
    ?>
    <input type="text" id="mevzu_kaynak_input" name="mevzu_kaynak"
        value="<?php echo esc_attr( $mevcut ); ?>"
        class="widefat" autocomplete="off"
        placeholder="<?php esc_attr_e( 'Ör. Karabük Belediyesi', 'mevzu2' ); ?>">
    <p class="description" style="margin-top:6px">
        <?php esc_html_e( 'Yazının kaynağı. Yazmaya başlayınca daha önce girilmiş kaynaklar önerilir; yalnızca tek bir kaynak seçilebilir.', 'mevzu2' ); ?>
    </p>

    <div id="mevzu_kaynak_url_wrap" style="display:none;margin-top:10px">
        <label for="mevzu_kaynak_url" style="display:block;margin-bottom:4px;font-weight:600;font-size:12px">
            <?php esc_html_e( 'Kaynak URL\'si (opsiyonel)', 'mevzu2' ); ?>
        </label>
        <input type="url" id="mevzu_kaynak_url" name="mevzu_kaynak_url"
            value="" class="widefat" placeholder="https://...">
        <p class="description" style="margin-top:4px">
            <?php esc_html_e( 'Bu kaynak daha önce hiç kullanılmamış; isterseniz web sitesini ekleyin. Ön yüzdeki rozet buraya linklenir. Daha sonra Yazılar → Kaynaklar sayfasından değiştirebilirsiniz.', 'mevzu2' ); ?>
        </p>
    </div>

    <script>
    jQuery(function($) {
        var $input   = $('#mevzu_kaynak_input');
        var $urlWrap = $('#mevzu_kaynak_url_wrap');
        var nonce    = '<?php echo esc_js( wp_create_nonce( 'mevzu_kaynak_search' ) ); ?>';

        $input.autocomplete({
            minLength: 1,
            source: function(request, response) {
                $.get(ajaxurl, {
                    action: 'mevzu_kaynak_search',
                    nonce: nonce,
                    term: request.term
                }, function(data) {
                    response(data || []);
                });
            },
            select: function() {
                // Listeden seçildiyse kesin var olan bir kaynak — kısa gecikmeyle kontrol et.
                setTimeout(kontrolEt, 10);
            }
        });

        function kontrolEt() {
            var deger = $.trim($input.val());
            if (deger === '') { $urlWrap.hide(); return; }

            $.get(ajaxurl, {
                action: 'mevzu_kaynak_search',
                nonce: nonce,
                term: deger
            }, function(data) {
                var varOlan = Array.isArray(data) && data.indexOf(deger) !== -1;
                varOlan ? $urlWrap.hide() : $urlWrap.show();
            });
        }

        $input.on('blur', kontrolEt);
    });
    </script>
    <?php
}

function mevzu_kaynak_admin_scripts( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
    $post_type = isset( $_GET['post'] ) ? get_post_type( (int) $_GET['post'] ) : ( $_GET['post_type'] ?? 'post' );
    if ( $post_type !== 'post' ) return;
    wp_enqueue_script( 'jquery-ui-autocomplete' );
}
add_action( 'admin_enqueue_scripts', 'mevzu_kaynak_admin_scripts' );

function mevzu_kaynak_ajax_search() {
    check_ajax_referer( 'mevzu_kaynak_search', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json( [] );
    }

    $q = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    if ( $q === '' ) {
        wp_send_json( [] );
    }

    $terimler = get_terms( [
        'taxonomy'   => 'kaynak',
        'name__like' => $q,
        'hide_empty' => false,
        'number'     => 15,
        'orderby'    => 'name',
    ] );

    $sonuc = [];
    if ( ! is_wp_error( $terimler ) ) {
        foreach ( $terimler as $t ) {
            $sonuc[] = $t->name;
        }
    }
    wp_send_json( $sonuc );
}
add_action( 'wp_ajax_mevzu_kaynak_search', 'mevzu_kaynak_ajax_search' );

function mevzu_kaynak_save_meta_box( $post_id ) {
    if ( ! isset( $_POST['mevzu_kaynak_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_kaynak_nonce'] ) ), 'mevzu_kaynak_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $deger = isset( $_POST['mevzu_kaynak'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['mevzu_kaynak'] ) ) ) : '';

    if ( $deger !== '' ) {
        // Term daha önce var mıydı? URL yalnızca YENİ bir kaynak için kaydedilir —
        // wp_set_object_terms çağrısından ÖNCE bakılmalı, çünkü o çağrı term'i oluşturur.
        $onceden_vardi = (bool) term_exists( $deger, 'kaynak' );

        // false = mevcut term'lerin yerine geçer, tek kaynak garantisi burada sağlanır.
        wp_set_object_terms( $post_id, $deger, 'kaynak', false );

        if ( ! $onceden_vardi ) {
            $url = isset( $_POST['mevzu_kaynak_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['mevzu_kaynak_url'] ) ) ) : '';
            if ( $url !== '' ) {
                $term = get_term_by( 'name', $deger, 'kaynak' );
                if ( $term ) {
                    update_term_meta( $term->term_id, 'kaynak_url', $url );
                }
            }
        }
    } else {
        wp_delete_object_term_relationships( $post_id, 'kaynak' );
    }
}
add_action( 'save_post_post', 'mevzu_kaynak_save_meta_box' );

/**
 * Ön yüzde "Kaynak: X" rozetini basar. Kaynak atanmamışsa hiçbir şey yazmaz.
 * Tüm tekil haber şablonlarında içerikten hemen sonra çağrılır.
 */
function mevzu_kaynak_the_badge( $post_id = null ) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if ( ! $post_id || get_post_type( $post_id ) !== 'post' ) {
        return;
    }

    $terimler = get_the_terms( $post_id, 'kaynak' );
    if ( empty( $terimler ) || is_wp_error( $terimler ) ) {
        return;
    }

    $terim    = $terimler[0];
    $dis_url  = get_term_meta( $terim->term_id, 'kaynak_url', true );
    $dis_link = is_string( $dis_url ) && $dis_url !== '';
    $href     = $dis_link ? $dis_url : get_term_link( $terim );
    ?>
    <div class="px-2 px-md-0 mb-3">
        <div class="d-inline-flex align-items-center gap-2 border bg-light rounded-pill py-1 px-3">
            <i class="ri-links-line opacity-50"></i>
            <span class="text-body small fw-normal"><?php esc_html_e( 'Kaynak:', 'mevzu2' ); ?></span>
            <a href="<?php echo esc_url( $href ); ?>" class="text-link small fw-semibold text-decoration-none"
                <?php if ( $dis_link ) : ?>target="_blank" rel="nofollow noopener"<?php endif; ?>><?php echo esc_html( $terim->name ); ?></a>
        </div>
    </div>
    <?php
}

/**
 * "Yazılar → Kaynaklar" taksonomi yönetim ekranına URL alanı ekler.
 * Yazı düzenleme kutusu URL'yi yalnızca YENİ kaynak eklenirken sorar;
 * burası ise sonradan eklemek/düzeltmek için merkezi bir yer sağlar.
 */
function mevzu_kaynak_add_form_url_field( $taxonomy ) {
    wp_nonce_field( 'mevzu_kaynak_term_url', 'mevzu_kaynak_term_url_nonce' );
    ?>
    <div class="form-field">
        <label for="mevzu_kaynak_term_url"><?php esc_html_e( 'Kaynak URL\'si', 'mevzu2' ); ?></label>
        <input type="url" name="mevzu_kaynak_term_url" id="mevzu_kaynak_term_url" value="" placeholder="https://...">
        <p><?php esc_html_e( 'Opsiyonel. Ön yüzdeki kaynak rozeti buraya linklenir.', 'mevzu2' ); ?></p>
    </div>
    <?php
}
add_action( 'kaynak_add_form_fields', 'mevzu_kaynak_add_form_url_field' );

function mevzu_kaynak_edit_form_url_field( $term ) {
    wp_nonce_field( 'mevzu_kaynak_term_url', 'mevzu_kaynak_term_url_nonce' );
    $url = get_term_meta( $term->term_id, 'kaynak_url', true );
    ?>
    <tr class="form-field">
        <th scope="row"><label for="mevzu_kaynak_term_url"><?php esc_html_e( 'Kaynak URL\'si', 'mevzu2' ); ?></label></th>
        <td>
            <input type="url" name="mevzu_kaynak_term_url" id="mevzu_kaynak_term_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://...">
            <p class="description"><?php esc_html_e( 'Opsiyonel. Ön yüzdeki kaynak rozeti buraya linklenir.', 'mevzu2' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'kaynak_edit_form_fields', 'mevzu_kaynak_edit_form_url_field' );

function mevzu_kaynak_save_term_url( $term_id ) {
    if ( ! isset( $_POST['mevzu_kaynak_term_url_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_kaynak_term_url_nonce'] ) ), 'mevzu_kaynak_term_url' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_categories' ) ) return;

    $url = isset( $_POST['mevzu_kaynak_term_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['mevzu_kaynak_term_url'] ) ) ) : '';
    if ( $url !== '' ) {
        update_term_meta( $term_id, 'kaynak_url', $url );
    } else {
        delete_term_meta( $term_id, 'kaynak_url' );
    }
}
add_action( 'created_kaynak', 'mevzu_kaynak_save_term_url' );
add_action( 'edited_kaynak', 'mevzu_kaynak_save_term_url' );
