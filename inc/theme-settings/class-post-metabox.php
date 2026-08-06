<?php
/**
 * Post Meta Box'ları
 * - Yazı: gömülü medya (URL embed + native video)
 * - Resmi İlanlar: ilan numarası
 * - Sayfalar: reklamları gizle, sayfa rengi, bilgi tablosu
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Mevzu_Post_Metabox {

    public function __construct() {
        add_action( 'add_meta_boxes',        [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post',             [ $this, 'save_post_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'edit_form_after_title', [ $this, 'render_resmi_ilan_above_editor' ] );
        add_filter( 'wp_insert_post_data',   [ $this, 'validate_yz_manset_featured_image' ], 10, 2 );
        add_action( 'admin_notices',         [ $this, 'admin_notice_yz_manset_thumbnail' ] );
        // inject_ust_manset_into_thumbnail kaldırıldı — Üst Manşet Görseli artık Sayfa Ayarları metabox'unda
    }

    public function admin_scripts( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        wp_enqueue_media(); // native video seçici için
    }

    public function add_meta_boxes() {

        // Yazı ve Resmi İlan — Sayfa Ayarları (Manşet konumları)
        add_meta_box(
            'mevzu-sayfa-ayarlari',
            __( 'Sayfa Ayarları', 'mevzu2' ),
            [ $this, 'render_sayfa_ayarlari' ],
            [ 'post', 'resmi-ilanlar' ],
            'side',
            'high'
        );

        // Yazı — native video
        add_meta_box(
            'mevzu-native-video',
            __( 'Gömülü Video', 'mevzu2' ),
            [ $this, 'render_native_video' ],
            'post',
            'side',
            'default'
        );

        // Yazı — sosyal gönderi / URL embed
        add_meta_box(
            'mevzu-sosyal-gonderi',
            __( 'Sosyal Medya Post Linki (İsteğe bağlı)', 'mevzu2' ),
            [ $this, 'render_social_embed' ],
            'post',
            'normal',
            'high'
        );
        // Sayfa Bilgileri Tablosu — sadece iletisim / kunye slug'larında
        $screen_post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
        if ( $screen_post_id ) {
            $slug = get_post_field( 'post_name', $screen_post_id );
            if ( in_array( $slug, [ 'iletisim', 'kunye' ], true ) ) {
        }
        }
    }

    public function inject_ust_manset_into_thumbnail( $html, $post_id ) {
        $cat    = get_category_by_slug( 'ust-manset' );
        $cat_id = $cat ? (int) $cat->term_id : 0;
        $img_id = (int) get_post_meta( $post_id, 'ust_manset_gorseli_id', true );
        $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '';

        ob_start(); ?>
        <div id="mevzu-ust-manset-gorsel-wrap" class="my-3 pt-3 border-top" style="<?php echo has_category( $cat_id, $post_id ) ? '' : 'display:none'; ?> border-top:1px solid #dcdcde;margin-top:12px;padding-top:12px">
            <strong style="display:block;margin-bottom:6px;font-size:12px">
                Üst Manşet Görseli <span style="font-weight:400;color:#787c82">(1176×330)</span>
            </strong>

            <?php wp_nonce_field( 'mevzu_ust_manset_gorsel', 'mevzu_ust_manset_gorsel_nonce' ); ?>
            <input type="hidden" id="ust_manset_gorseli_id" name="ust_manset_gorseli_id"
                value="<?php echo esc_attr( $img_id ?: '' ); ?>">

            <div id="ust-manset-img-preview" style="margin-bottom:8px;<?php echo $img_url ? '' : 'display:none'; ?>">
                <img src="<?php echo esc_url( $img_url ); ?>"
                    style="max-width:100%;height:auto;border-radius:4px;border:1px solid #dcdcde;display:block">
            </div>

            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <button type="button" class="button" id="ust-manset-img-select" style="font-size:11px">
                    <?php echo $img_id ? 'Değiştir' : 'Görsel Seç'; ?>
                </button>
                <button type="button" class="button button-link-delete" id="ust-manset-img-remove"
                    style="font-size:11px;<?php echo $img_id ? '' : 'display:none'; ?>">
                    Kaldır
                </button>
            </div>

            <div id="ust-manset-img-warning" style="display:none;margin-top:6px;padding:5px 8px;background:#fff8e1;border-left:3px solid #f0b400;border-radius:2px;font-size:11px;color:#6d5200">
                Görsel boyutu 1176×330 px olmalı.
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var catId   = <?php echo (int) $cat_id; ?>;
            var $wrap   = $('#mevzu-ust-manset-gorsel-wrap');

            function syncWrap() {
                var checked = $('input[name="post_category[]"]').filter(function(){
                    return parseInt($(this).val()) === catId && $(this).is(':checked');
                }).length > 0;
                checked ? $wrap.show() : $wrap.hide();
            }
            syncWrap();
            $(document).on('change', 'input[name="post_category[]"]', syncWrap);

            var frame;
            $('#ust-manset-img-select').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({ title:'Üst Manşet Görseli (1176×330)', button:{ text:'Kullan' }, library:{ type:'image' }, multiple:false });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    $('#ust_manset_gorseli_id').val(att.id);
                    $('#ust-manset-img-preview img').attr('src', att.url);
                    $('#ust-manset-img-preview').show();
                    $('#ust-manset-img-select').text('Değiştir');
                    $('#ust-manset-img-remove').show();
                    $('#ust-manset-img-warning').toggle(att.width !== 1176 || att.height !== 330);
                });
                frame.open();
            });

            $('#ust-manset-img-remove').on('click', function() {
                $('#ust_manset_gorseli_id').val('');
                $('#ust-manset-img-preview').hide();
                $('#ust-manset-img-select').text('Görsel Seç');
                $(this).hide();
                $('#ust-manset-img-warning').hide();
            });
        });
        </script>
        <?php
        $extra = ob_get_clean();
        return $html . $extra;
    }
        public function render_sayfa_ayarlari( $post ) {
        wp_nonce_field( 'mevzu_sayfa_ayarlari', 'mevzu_sayfa_ayarlari_nonce' );
        $positions = (array) get_post_meta( $post->ID, 'mevzu_manset_konumlari', true );
        $img_id    = (int) get_post_meta( $post->ID, 'ust_manset_gorseli_id', true );
        $img_url   = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '';
        // Tüm alanlar + hangilerinin aktif olduğu
        $all_fields = [
            'ust_manset'   => 'Üst Manşette Göster',
            'sicak_gundem' => 'Sıcak Gündemde Göster',
            'manset'       => 'Ana Manşette Göster',
            'yan_manset'   => 'Yan Manşette Göster',
            'alt_manset'   => 'Alt Manşette Göster',
        ];
        $active_fields = [];
        if ( get_opt_g( 'options_ust_manset_yeni', 'goster' ) == 1 )       $active_fields[] = 'ust_manset';
        if ( get_opt_g( 'options_manset', 'slider_sayisi' ) )               $active_fields[] = 'manset';
        if ( get_opt_g( 'options_ust_manset', 'ust_manset_ayarlari' ) == 1 ) $active_fields[] = 'sicak_gundem';
        if ( get_opt_g( 'options_alt_manset', 'alt_manseti_goster' ) == 1 ) $active_fields[] = 'alt_manset';
        if ( get_option( 'options_yan_manset_tip' ) === 'yan_manset' )      $active_fields[] = 'yan_manset';
        ?>
        <div class="mevzu-metabox">


            <!-- Sayfa Şablonu Butonu -->
            <button type="button" class="button w-100 mb-2" id="mevzu-sablon-btn">
                Rehber Anasayfa Şablonu
            </button>

            <!-- Modal -->
            <div id="mevzu-sablon-modal" style="display:none;position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
                <div style="background:#fff;border-radius:6px;padding:20px;width:360px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative">
                    <button type="button" id="mevzu-sablon-modal-close"
                        style="position:absolute;top:10px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;color:#646970;line-height:1">&times;</button>
                    <h3 style="margin:0 0 12px;font-size:14px">Anasayfa Şablonu</h3>
                    <div class="row g-1 gap-2 text-center">
                        <div class="col-12 bg-dark bg-opacity-25 text-dark text-opacity-50 d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 3em;">Logo ve Menü Alanı</div>
                        <div class="col-12 bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 4em;">Üst Manşet</div>
                        <div class="col-12 d-flex g-1 gap-2 position-relative">
                            <div class="position-absolute text-dark" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">Sıcak Gündem</div>
                            <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3em;"></div>
                            <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3em;"></div>
                            <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3em;"></div>
                            <div class="bg-warning bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3em;"></div>
                        </div>
                        <div class="col-8 bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 6em;">Manşet</div>
                        <div class="col d-flex flex-column g-1 gap-2 position-relative" style="min-height: 7em;">
                            <div class="position-absolute text-dark" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">Yan Manşet</div>
                            <div class="bg-info bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3.5em;"></div>
                            <div class="bg-info bg-opacity-25 d-flex align-items-center justify-content-center rounded shadow-sm col" style="min-height: 3.5em;"></div>
                        </div>
                        <div class="col-12 bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 4em;">Yazar Köşesi</div>
                        <div class="col-12 bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 7em;">Ana Kategori Haberleri</div>
                    </div>
                    <div class="row g-1 gap-2 text-center mt-2">
                        <div class="col-8 d-flex g-1 gap-2 flex-column">
                            <div class="bg-success bg-opacity-25 text-success d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 5em;">Alt Manşet</div>
                            <div class="bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 3em;">Diğer Kategori Haberleri</div>
                            <div class="bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 3em;">Diğer Kategori Haberleri</div>
                            <div class="bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 3em;">Diğer Kategori Haberleri</div>
                        </div>
                        <div class="col h-100">
                            <div class="bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 16em;">Sağ Sütun</div>
                        </div>
                        <div class="col-12 bg-dark bg-opacity-10 text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 7em;">Video Haberler</div>
                        <div class="col-12 bg-dark bg-opacity-25 text-dark text-opacity-50 d-flex align-items-center justify-content-center rounded shadow-sm" style="min-height: 3em;">Sitenin Alt Alanı</div>
                    </div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                var $modal = $('#mevzu-sablon-modal');
                $('#mevzu-sablon-btn').on('click', function() { $modal.css('display','flex'); });
                $('#mevzu-sablon-modal-close').on('click', function() { $modal.hide(); });
                $modal.on('click', function(e) { if ($(e.target).is($modal)) $modal.hide(); });
            });
            </script>

            <?php foreach ( $all_fields as $key => $label ) :
                $checked  = in_array( $key, $positions, true );
                $active   = in_array( $key, $active_fields, true );
            ?>
            <label style="display:flex;align-items:center;gap:6px;margin-bottom:6px;cursor:<?php echo $active ? 'pointer' : 'not-allowed'; ?>;<?php echo $active ? '' : 'opacity:.5'; ?>">
                <input type="checkbox" name="mevzu_manset_konumlari[]"
                    value="<?php echo esc_attr( $key ); ?>"
                    id="manset_konum_<?php echo esc_attr( $key ); ?>"
                    <?php checked( $checked ); ?>
                    <?php disabled( ! $active ); ?>>
                <?php echo esc_html( $label ); ?>
                <?php if ( ! $active ) : ?>
                <span style="font-size:11px;color:#999">(Kapalı)</span>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>

            <?php
            // Görsel şartı olan konumlar: sorguları _thumbnail_id EXISTS koşuyor.
            $gorsel_gerektiren = [ 'manset', 'sicak_gundem', 'alt_manset' ];
            $thumb_var         = (int) get_post_thumbnail_id( $post->ID ) > 0;
            ?>
            <p id="mevzu-manset-gorsel-uyari"
               class="mb-0 mt-1 text-danger"
               data-konumlar="<?php echo esc_attr( wp_json_encode( $gorsel_gerektiren ) ); ?>"
               style="<?php echo $thumb_var ? 'display:none' : ''; ?>">
                <?php esc_html_e( 'Manşette görünmesi için öne çıkarılmış görsel eklemelisiniz.', 'mevzu2' ); ?>
            </p>
            <script>
            jQuery(function($) {
                var $uyari   = $('#mevzu-manset-gorsel-uyari');
                var konumlar = $uyari.data('konumlar') || [];

                function thumbVar() {
                    var $alan = $('#_thumbnail_id');
                    if ($alan.length) {
                        var v = parseInt($alan.val(), 10);
                        if (v === -1) { return false; }
                        if (v > 0)    { return true; }
                    }
                    return $('#postimagediv .inside').find('img').length > 0;
                }

                function isaretliMi() {
                    return konumlar.some(function(k) {
                        return $('#manset_konum_' + k).is(':checked');
                    });
                }

                function senkronla() {
                    $uyari.toggle(isaretliMi() && !thumbVar());
                }

                $(document).on('change', 'input[name="mevzu_manset_konumlari[]"], #_thumbnail_id', senkronla);

                var $kutu = $('#postimagediv');
                if ($kutu.length && window.MutationObserver) {
                    new MutationObserver(senkronla).observe($kutu[0], { childList: true, subtree: true });
                }

                senkronla();
            });
            </script>

            <?php
            $tts_target = function_exists( 'mevzu_tts_target_category_id' ) ? mevzu_tts_target_category_id() : (int) get_option( 'kkerem_tts_category_id', 0 );
            $in_tts_cat = $tts_target && function_exists( 'mevzu_tts_post_in_target_category' ) && mevzu_tts_post_in_target_category( $post->ID );
            if ( 'resmi-ilanlar' !== $post->post_type && $tts_target && ! $in_tts_cat && function_exists( 'mevzu_yz_module_active' ) && mevzu_yz_module_active() ) :
                $yz_checked    = in_array( 'yapay_zeka_manset', $positions, true );
                $yz_default    = __( "Ses dosyası oluşturularak anasayfadaki 'Günün Özeti' bölümüne eklenir.", 'mevzu2' );
                $yz_thumb_req  = __( 'Öne çıkarılmış görsel eklenmesi zorunludur!', 'mevzu2' );
                $has_thumbnail = (int) get_post_thumbnail_id( $post->ID ) > 0;
                $uyari_class   = ( $yz_checked && ! $has_thumbnail ) ? 'text-danger' : 'text-muted';
                $uyari_text    = ( $yz_checked && ! $has_thumbnail ) ? $yz_thumb_req : $yz_default;
                ?>
            <div class="mevzu-tts-yz-manset-wrap">
                <label class="d-flex align-items-center">
                    <input type="checkbox" name="mevzu_manset_konumlari[]" value="yapay_zeka_manset" id="manset_konum_yapay_zeka_manset" <?php checked( $yz_checked ); ?>>
                    <div class="d-flex align-items-center ms-1">
                        <?php esc_html_e( 'Yapay Zeka Manşetinde Göster', 'mevzu2' ); ?>
                    </div>
                </label>
                <p
                    id="uyari"
                    class="<?php echo esc_attr( $uyari_class ); ?> mb-0 mt-1"
                    data-default-msg="<?php echo esc_attr( $yz_default ); ?>"
                    data-thumb-required-msg="<?php echo esc_attr( $yz_thumb_req ); ?>"
                ><?php echo esc_html( $uyari_text ); ?></p>
            </div>
            <script>
            jQuery(function($) {
                var $yzCb = $('#manset_konum_yapay_zeka_manset');
                var $uyari = $('#uyari');
                if (!$yzCb.length || !$uyari.length) {
                    return;
                }

                var defaultMsg = $uyari.data('default-msg') || '';
                var thumbMsg = $uyari.data('thumb-required-msg') || '';
                var serverThumbId = <?php echo $has_thumbnail ? (int) get_post_thumbnail_id( $post->ID ) : 0; ?>;

                function getThumbId() {
                    var $field = $('#_thumbnail_id');
                    if ($field.length) {
                        var submitted = parseInt($field.val(), 10);
                        if (submitted === -1) {
                            return 0;
                        }
                        if (submitted > 0) {
                            return submitted;
                        }
                    }
                    if ($('#postimagediv .inside').find('img').length) {
                        return serverThumbId > 0 ? serverThumbId : 1;
                    }
                    if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                        try {
                            var featured = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
                            return featured ? parseInt(featured, 10) : 0;
                        } catch (e) {}
                    }
                    return serverThumbId;
                }

                function setPublishEnabled(enabled) {
                    $('#publish, #save-post').prop('disabled', !enabled);
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        try {
                            if (enabled) {
                                wp.data.dispatch('core/editor').unlockPostSaving('mevzu-yz-thumb-required');
                            } else {
                                wp.data.dispatch('core/editor').lockPostSaving('mevzu-yz-thumb-required');
                            }
                        } catch (e) {}
                    }
                }

                function syncYzUyari() {
                    var yzOn = $yzCb.is(':checked');
                    var hasThumb = getThumbId() > 0;
                    var block = yzOn && !hasThumb;

                    if (block) {
                        $uyari.text(thumbMsg).removeClass('text-muted').addClass('text-danger');
                    } else {
                        $uyari.text(defaultMsg).removeClass('text-danger').addClass('text-muted');
                    }
                    setPublishEnabled(!block);
                }

                $yzCb.on('change', syncYzUyari);
                $(document).on('change', '#_thumbnail_id', syncYzUyari);

                var $thumbBox = $('#postimagediv');
                if ($thumbBox.length && window.MutationObserver) {
                    new MutationObserver(function() {
                        syncYzUyari();
                    }).observe($thumbBox[0], { childList: true, subtree: true, attributes: true });
                }

                $(document).on('click', '#postimagediv .remove-post-thumbnail, #postimagediv a.del-link', function() {
                    setTimeout(syncYzUyari, 50);
                });

                if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                    var lastFeatured = null;
                    wp.data.subscribe(function() {
                        try {
                            var featured = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
                            if (featured !== lastFeatured) {
                                lastFeatured = featured;
                                syncYzUyari();
                            }
                        } catch (e) {}
                    });
                }

                $('#post').on('submit', function(e) {
                    if ($yzCb.is(':checked') && getThumbId() <= 0) {
                        e.preventDefault();
                        window.alert(thumbMsg);
                        return false;
                    }
                });

                syncYzUyari();
            });
            </script>
            <?php endif; ?>

            <!-- Üst Manşet Görseli — sadece üst_manset seçiliyse -->
            <div id="mevzu-ust-manset-gorsel-wrap" style="margin-top:10px;padding-top:10px;border-top:1px solid #dcdcde;<?php echo in_array( 'ust_manset', $positions, true ) ? '' : 'display:none'; ?>">
                <p style="margin:0 0 6px;font-size:12px;color:#646970">
                    <strong>Üst Manşet Görseli</strong><br>
                    Zorunlu boyut: <strong>1176×330</strong>
                </p>
                <input type="hidden" id="ust_manset_gorseli_id" name="ust_manset_gorseli_id"
                    value="<?php echo esc_attr( $img_id ?: '' ); ?>">
                <div id="ust-manset-img-preview" style="margin-bottom:8px;<?php echo $img_url ? '' : 'display:none'; ?>">
                    <img src="<?php echo esc_url( $img_url ); ?>" id="ust-manset-img-tag"
                        style="max-width:100%;height:auto;border-radius:4px;border:1px solid #dcdcde">
                </div>
                <div class="d-flex flex-wrap justify-content-between">
                    <button type="button" class="button" id="ust-manset-img-select">
                        <span class="dashicons dashicons-format-image" style="margin-top:3px;margin-right:4px"></span>
                        <?php echo $img_id ? 'Görseli Değiştir' : 'Görsel Seç'; ?>
                    </button>
                    <button type="button" class="button button-link-delete" id="ust-manset-img-remove"
                        <?php echo $img_id ? '' : 'style="display:none"'; ?>>Kaldır</button>
                </div>
                <div id="ust-manset-size-error" style="display:none;margin-top:6px;padding:6px 10px;background:#fcf0f1;border-left:3px solid #d63638;font-size:12px;color:#d63638"></div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var $wrap = $('#mevzu-ust-manset-gorsel-wrap');
            $('#manset_konum_ust_manset').on('change', function() {
                $(this).is(':checked') ? $wrap.slideDown(150) : $wrap.slideUp(150);
            });
            // Görsel seçici
            var frame;
            $('#ust-manset-img-select').on('click', function(e) {
                e.preventDefault();
                if ( frame ) { frame.open(); return; }
                frame = wp.media({ title: 'Üst Manşet Görseli Seç', button: { text: 'Seç' }, multiple: false });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    var W = 1176, H = 330;
                    if ( att.width && att.height && ( att.width !== W || att.height !== H ) ) {
                        frame.open(); // medya kütüphanesini açık tut
                        $('#ust-manset-size-error')
                            .text('Bu görsel kullanılamaz: ' + att.width + '×' + att.height + '. Görsel tam olarak ' + W + '×' + H + ' px olmalıdır.')
                            .show();
                        return;
                    }
                    $('#ust-manset-size-error').hide();
                    $('#ust_manset_gorseli_id').val(att.id);
                    $('#ust-manset-img-tag').attr('src', att.url);
                    $('#ust-manset-img-preview').show();
                    $('#ust-manset-img-remove').show();
                    $('#ust-manset-img-select').text('Görseli Değiştir');
                });
                frame.open();
            });
            $('#ust-manset-img-remove').on('click', function() {
                $('#ust_manset_gorseli_id').val('');
                $('#ust-manset-img-preview').hide();
                $(this).hide();
                $('#ust-manset-img-select').html('<span class="dashicons dashicons-format-image" style="margin-top:3px;margin-right:4px"></span>Görsel Seç');
            });
        });
        </script>
        <?php
    }

    public function render_ust_manset_gorsel( $post ) {
        wp_nonce_field( 'mevzu_ust_manset_gorsel', 'mevzu_ust_manset_gorsel_nonce' );

        $cat        = get_category_by_slug( 'ust-manset' );
        $cat_id     = $cat ? $cat->term_id : 0;
        $in_cat     = $cat_id && has_category( $cat_id, $post );
        $img_id     = (int) get_post_meta( $post->ID, 'ust_manset_gorseli_id', true );
        $img_url    = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '';

        ?>
        <div id="mevzu-ust-manset-gorsel-wrap">
            <p class="description" style="margin-bottom:8px">
                Tam boyut: <strong>1176 × 330 px</strong>.<br>
                Bu görsel anasayfadaki Üst Manşet swiperında kullanılır.
            </p>

            <input type="hidden" id="ust_manset_gorseli_id" name="ust_manset_gorseli_id"
                value="<?php echo esc_attr( $img_id ?: '' ); ?>">

            <?php if ( $img_url ) : ?>
            <div id="ust-manset-img-preview" style="margin-bottom:8px">
                <img src="<?php echo esc_url( $img_url ); ?>" style="max-width:100%;height:auto;border-radius:4px;border:1px solid #dcdcde">
            </div>
            <?php else : ?>
            <div id="ust-manset-img-preview" style="margin-bottom:8px;display:none">
                <img src="" id="ust-manset-img-tag" style="max-width:100%;height:auto;border-radius:4px;border:1px solid #dcdcde">
            </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap justify-content-between">
                <button type="button" class="button" id="ust-manset-img-select">
                    <span class="dashicons dashicons-format-image" style="margin-top:3px;margin-right:4px"></span>
                    <?php echo $img_id ? 'Görseli Değiştir' : 'Görsel Seç'; ?>
                </button>
                <button type="button" class="button button-link-delete" id="ust-manset-img-remove"
                    <?php echo $img_id ? '' : 'style="display:none"'; ?>>
                    Kaldır
                </button>
            </div>
            <div id="ust-manset-img-warning" class="notice notice-warning inline" style="display:none;margin:8px 0 0;padding:6px 10px">
                <p style="margin:0;font-size:12px">Uyarı: Görsel boyutu 1176×330 px olmalı.</p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Kategori seçimi değişince metabox'u göster/gizle
            var catId = <?php echo (int) $cat_id; ?>;
            var $metabox = $('#mevzu-ust-manset-gorsel');
            function syncVisibility() {
                var checked = $('input[name="post_category[]"]').filter(function(){
                    return parseInt($(this).val()) === catId && $(this).is(':checked');
                }).length > 0;
                checked ? $metabox.show() : $metabox.hide();
            }
            syncVisibility(); // sayfa yüklenince hemen uygula
            $(document).on('change', 'input[name="post_category[]"]', syncVisibility);

            // Görsel seçici
            var frame;
            $('#ust-manset-img-select').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Üst Manşet Görseli Seç (1176×330)',
                    button: { text: 'Bu Görseli Kullan' },
                    library: { type: 'image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    var w = att.width, h = att.height;
                    if (w !== 1176 || h !== 330) {
                        $('#ust-manset-img-warning').show();
                    } else {
                        $('#ust-manset-img-warning').hide();
                    }
                    $('#ust_manset_gorseli_id').val(att.id);
                    var preview = $('#ust-manset-img-preview');
                    preview.find('img, #ust-manset-img-tag').attr('src', att.url);
                    preview.show();
                    $('#ust-manset-img-select').html('<span class="dashicons dashicons-format-image" style="margin-top:3px;margin-right:4px"></span>Görseli Değiştir');
                    $('#ust-manset-img-remove').show();
                });
                frame.open();
            });

            $('#ust-manset-img-remove').on('click', function() {
                $('#ust_manset_gorseli_id').val('');
                $('#ust-manset-img-preview').hide();
                $('#ust-manset-img-select').html('<span class="dashicons dashicons-format-image" style="margin-top:3px;margin-right:4px"></span>Görsel Seç');
                $('#ust-manset-img-remove').hide();
                $('#ust-manset-img-warning').hide();
            });
        });
        </script>
        <?php
    }

    public function render_resmi_ilan_above_editor( $post ) {
        // Metot kaldırıldı — hook bağlantısı korunuyor
    }

    /* ============================================================
     *  YAZI — GÖMÜLÜ MEDYA METABOX
     * ============================================================ */

    public function render_native_video( $post ) {
        wp_nonce_field( 'mevzu_post_meta', 'mevzu_post_meta_nonce' );
        $video_id   = (int) get_post_meta( $post->ID, 'mevzu_native_video_id', true );
        $video_url  = $video_id ? wp_get_attachment_url( $video_id ) : '';
        $video_name = $video_url ? basename( $video_url ) : '';
        ?>
        <div class="mevzu-metabox" style="padding:4px 0">
            <input type="hidden" id="mevzu_native_video_id" name="mevzu_native_video_id"
                value="<?php echo esc_attr( $video_id ?: '' ); ?>">

            <div id="mevzu-video-preview" style="<?php echo $video_id ? 'display:inline-block' : 'display:none'; ?>;margin-bottom:10px;border-radius:6px;overflow:hidden;background:#000;border:1px solid #dcdcde;max-width:100%">
                <video id="mevzu-video-player" controls preload="metadata"
                    style="display:block;max-width:100%;max-height:240px;width:auto;height:auto;background:#000"
                    <?php if ( $video_url ) echo 'src="' . esc_url( $video_url ) . '"'; ?>>
                </video>
                <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#f6f7f7;border-top:1px solid #dcdcde">
                    <span class="dashicons dashicons-video-alt3" style="color:#2271b1;font-size:18px;flex-shrink:0"></span>
                    <span id="mevzu-video-name" style="font-size:12px;color:#1d2327;flex:1;word-break:break-all;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $video_name ); ?></span>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap justify-content-between">
                <button type="button" class="button" id="mevzu-video-select-btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php echo $video_id ? 'Videoyu Değiştir' : 'Video Seç / Yükle'; ?>
                </button>
                <button type="button" class="button button-link-delete" id="mevzu-video-remove-btn"
                    <?php echo $video_id ? '' : 'style="display:none"'; ?>>
                    Kaldır
                </button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var frame;
            var $idField   = $('#mevzu_native_video_id');
            var $nameSpan  = $('#mevzu-video-name');
            var $preview   = $('#mevzu-video-preview');
            var $selectBtn = $('#mevzu-video-select-btn');
            var $removeBtn = $('#mevzu-video-remove-btn');

            $selectBtn.on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Video Seç / Yükle',
                    button: { text: 'Bu Videoyu Kullan' },
                    library: { type: 'video' },
                    multiple: false
                });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    $idField.val(att.id);
                    var name = att.filename || att.url.split('/').pop();
                    $nameSpan.text(name);
                    var $player = $('#mevzu-video-player');
                    if ($player.length) { $player.attr('src', att.url); $player[0].load(); }
                    $preview.show();
                    $selectBtn.html('<span class="dashicons dashicons-upload" style="margin-top:3px;margin-right:4px"></span>Videoyu Değiştir');
                    $removeBtn.show();
                });
                frame.open();
            });

            $removeBtn.on('click', function() {
                $idField.val('');
                $nameSpan.text('');
                var $player = $('#mevzu-video-player');
                if ($player.length) { $player[0].pause(); $player.removeAttr('src'); $player[0].load(); }
                $preview.hide();
                $selectBtn.html('<span class="dashicons dashicons-upload" style="margin-top:3px;margin-right:4px"></span>Video Seç / Yükle');
                $removeBtn.hide();
            });
        });
        </script>
        <?php
    }

    public function render_social_embed( $post ) {
        wp_nonce_field( 'mevzu_post_meta', 'mevzu_post_meta_nonce' );
        $embed_url = get_post_meta( $post->ID, 'mevzu_embed_media_url', true );
        ?>
        <div class="mevzu-metabox">
            <label for="mevzu_embed_media_url" class="text-body mb-2">Bağlantı Adresi (URL)</label>
            <input type="url" id="mevzu_embed_media_url" name="mevzu_embed_media_url"
                value="<?php echo esc_url( $embed_url ); ?>"
                class="widefat mb-2"
                placeholder="Örnek: https://www.instagram.com/p/DX1T26SCDYP/ veya https://www.facebook.com/share/v/1GEfSSDFxG/ gibi...">
            <p class="description">
                Haberin sosyal medya linklerini içerik sonunda <span class="fw-semibold text-primary">'Instagram postunu görüntülemek için tıklayın'</span> şeklinde link halinde gösterir.
                <br>
                Desteklenenler: YouTube, Facebook, Instagram, Twitter
            </p>
        </div>
        <?php
    }

        public function save_post_meta( $post_id, $post ) {

        // Autosave ve yetki kontrolü
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Sayfa Ayarları — manşet konumları
        if ( isset( $_POST['mevzu_sayfa_ayarlari_nonce'] ) &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_sayfa_ayarlari_nonce'] ) ), 'mevzu_sayfa_ayarlari' ) ) {
            $allowed = [ 'ust_manset', 'manset', 'sicak_gundem', 'alt_manset', 'yan_manset', 'yapay_zeka_manset' ];
            if ( 'resmi-ilanlar' === $post->post_type ) {
                // İlan metinleri seslendirmeye uygun değil; alan arayüzde de gizli.
                $allowed = array_values( array_diff( $allowed, [ 'yapay_zeka_manset' ] ) );
            }
            $konumlar = isset( $_POST['mevzu_manset_konumlari'] )
                ? array_values( array_intersect( (array) $_POST['mevzu_manset_konumlari'], $allowed ) )
                : [];
            if ( in_array( 'yapay_zeka_manset', $konumlar, true ) && ! $this->post_has_featured_thumbnail_from_request( $post_id ) ) {
                $konumlar = array_values( array_diff( $konumlar, [ 'yapay_zeka_manset' ] ) );
                set_transient( 'mevzu_yz_no_thumb_' . get_current_user_id(), 1, 60 );
            }
            update_post_meta( $post_id, 'mevzu_manset_konumlari', $konumlar );

            // Üst Manşet görseli (yeni metabox'tan)
            $img_id = isset( $_POST['ust_manset_gorseli_id'] ) ? (int) $_POST['ust_manset_gorseli_id'] : 0;
            if ( $img_id > 0 ) {
                update_post_meta( $post_id, 'ust_manset_gorseli_id', $img_id );
                $sized_url = wp_get_attachment_image_url( $img_id, 'ust-manset-gorsel' );
                if ( ! $sized_url ) {
                    $attach_file = get_attached_file( $img_id );
                    if ( $attach_file ) {
                        $meta = wp_generate_attachment_metadata( $img_id, $attach_file );
                        wp_update_attachment_metadata( $img_id, $meta );
                    }
                    $sized_url = wp_get_attachment_image_url( $img_id, 'ust-manset-gorsel' );
                }
                update_post_meta( $post_id, 'ust_manset_gorseli_url', $sized_url ?: wp_get_attachment_url( $img_id ) );
            } elseif ( isset( $_POST['ust_manset_gorseli_id'] ) ) {
                delete_post_meta( $post_id, 'ust_manset_gorseli_id' );
                delete_post_meta( $post_id, 'ust_manset_gorseli_url' );
            }
        }

        if ( ! isset( $_POST['mevzu_post_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mevzu_post_meta_nonce'], 'mevzu_post_meta' ) ) return;

        // Üst Manşet görseli
        if ( isset( $_POST['mevzu_ust_manset_gorsel_nonce'] ) &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_ust_manset_gorsel_nonce'] ) ), 'mevzu_ust_manset_gorsel' ) ) {
            $img_id = isset( $_POST['ust_manset_gorseli_id'] ) ? (int) $_POST['ust_manset_gorseli_id'] : 0;
            if ( $img_id > 0 ) {
                update_post_meta( $post_id, 'ust_manset_gorseli_id', $img_id );

                // 1176x330 kopyasını oluştur (yoksa generate et)
                $sized_url = wp_get_attachment_image_url( $img_id, 'ust-manset-gorsel' );
                if ( ! $sized_url ) {
                    // Boyut henüz üretilmemişse regenerate et
                    $attach_file = get_attached_file( $img_id );
                    if ( $attach_file ) {
                        $meta = wp_generate_attachment_metadata( $img_id, $attach_file );
                        wp_update_attachment_metadata( $img_id, $meta );
                    }
                    $sized_url = wp_get_attachment_image_url( $img_id, 'ust-manset-gorsel' );
                }
                // Boyut hâlâ yoksa (görsel çok küçükse) full URL kullan
                $img_url = $sized_url ?: wp_get_attachment_image_url( $img_id, 'full' );
                update_post_meta( $post_id, 'ust_manset_gorseli_url', $img_url );
            } else {
                delete_post_meta( $post_id, 'ust_manset_gorseli_id' );
                delete_post_meta( $post_id, 'ust_manset_gorseli_url' );
            }
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Yazı ayarları (eski, yorum satırındaki alandan kalma)
                if ( isset( $_POST['mevzu_yazi_ayarlari'] ) ) {
            update_post_meta( $post_id, 'yazi_ayarlari', array_map( 'sanitize_text_field', (array) $_POST['mevzu_yazi_ayarlari'] ) );
        } else {
            update_post_meta( $post_id, 'yazi_ayarlari', [] );
        }

        // Eski YouTube URL (geriye dönük)
        if ( isset( $_POST['mevzu_youtube_url'] ) ) {
            update_post_meta( $post_id, 'youtube_url', esc_url_raw( $_POST['mevzu_youtube_url'] ) );
        }

        // Gömülü medya URL
        if ( isset( $_POST['mevzu_embed_media_url'] ) ) {
            $raw = trim( (string) wp_unslash( $_POST['mevzu_embed_media_url'] ) );
            if ( $raw === '' ) {
                delete_post_meta( $post_id, 'mevzu_embed_media_url' );
            } else {
                update_post_meta( $post_id, 'mevzu_embed_media_url', esc_url_raw( $raw ) );
            }
        }

        // Native video attachment ID + R2 upload
        if ( isset( $_POST['mevzu_native_video_id'] ) ) {
            $vid = (int) $_POST['mevzu_native_video_id'];
            if ( $vid > 0 ) {
                update_post_meta( $post_id, 'mevzu_native_video_id', $vid );

                // R2 aktifse ve daha önce bu video yüklenmemişse R2'ye gönder
                if ( function_exists( 'mevzu_upload_video_to_r2' ) ) {
                    $existing_r2_url = get_post_meta( $post_id, 'mevzu_native_video_url', true );
                    // Sadece yeni video seçildiyse (URL yoksa veya değiştiyse) yükle
                    $old_vid = (int) get_post_meta( $post_id, '_mevzu_last_r2_vid', true );
                    if ( ! $existing_r2_url || $old_vid !== $vid ) {
                        $r2_url = mevzu_upload_video_to_r2( $vid );
                        if ( $r2_url && ! is_wp_error( $r2_url ) ) {
                            update_post_meta( $post_id, 'mevzu_native_video_url', $r2_url );
                            update_post_meta( $post_id, '_mevzu_last_r2_vid', $vid );
                        }
                    }
                } elseif ( get_option( 'options_video_depolama', 'local' ) !== 'r2' ) {
                    // Yerel modda R2 URL'sini temizle
                    delete_post_meta( $post_id, 'mevzu_native_video_url' );
                }
            } else {
                delete_post_meta( $post_id, 'mevzu_native_video_id' );
                delete_post_meta( $post_id, 'mevzu_native_video_url' );
                delete_post_meta( $post_id, '_mevzu_last_r2_vid' );
            }
        }

        // İlan numarası
        if ( isset( $_POST['mevzu_ilan_numarasi'] ) ) {
            update_post_meta( $post_id, 'ilan_numarasi', sanitize_text_field( $_POST['mevzu_ilan_numarasi'] ) );
        }

        // Sayfa ayarları
        if ( isset( $_POST['mevzu_reklamlari_gizle'] ) ) {
            update_post_meta( $post_id, 'reklamlari_gizle', sanitize_text_field( $_POST['mevzu_reklamlari_gizle'] ) );
        }
        if ( isset( $_POST['mevzu_sayfa_renk'] ) ) {
            update_post_meta( $post_id, 'sayfa_renk', sanitize_hex_color( $_POST['mevzu_sayfa_renk'] ) );
        }
        if ( isset( $_POST['mevzu_iletisim_eposta'] ) ) {
            update_post_meta( $post_id, 'iletisim_formu_eposta', sanitize_email( $_POST['mevzu_iletisim_eposta'] ) );
        }
        if ( isset( $_POST['mevzu_iletisim_formu_aktif'] ) ) {
            update_post_meta( $post_id, 'iletisim_formu_aktif', sanitize_text_field( $_POST['mevzu_iletisim_formu_aktif'] ) );
        }

        // Sayfa Tablosu (Repeater)
        if ( isset( $_POST['repeater_ilk'] ) && is_array( $_POST['repeater_ilk'] ) ) {
            $ilks    = $_POST['repeater_ilk'];
            $ikincis = $_POST['repeater_ikinci'] ?? [];
            $count   = 0;
            $old     = (int) get_post_meta( $post_id, 'default_repeater', true );
            for ( $i = 0; $i < ( $old + 10 ); $i++ ) {
                delete_post_meta( $post_id, 'default_repeater_' . $i . '_ilk' );
                delete_post_meta( $post_id, 'default_repeater_' . $i . '_ikinci' );
            }
            foreach ( $ilks as $idx => $ilk ) {
                if ( ! empty( $ilk ) || ! empty( $ikincis[ $idx ] ) ) {
                    update_post_meta( $post_id, 'default_repeater_' . $count . '_ilk',    sanitize_text_field( $ilk ) );
                    update_post_meta( $post_id, 'default_repeater_' . $count . '_ikinci', sanitize_text_field( $ikincis[ $idx ] ?? '' ) );
                    $count++;
                }
            }
            update_post_meta( $post_id, 'default_repeater', $count );
        } elseif ( isset( $_POST['mevzu_post_meta_nonce'] ) ) {
            update_post_meta( $post_id, 'default_repeater', 0 );
        }
    }

    /**
     * İstekten öne çıkarılmış görsel var mı?
     */
    private function post_has_featured_thumbnail_from_request( $post_id ) {
        if ( isset( $_POST['_thumbnail_id'] ) ) {
            $thumb_id = (int) $_POST['_thumbnail_id'];
            if ( $thumb_id > 0 ) {
                return true;
            }
            if ( -1 === $thumb_id ) {
                return false;
            }
        }
        return $post_id > 0 && has_post_thumbnail( $post_id );
    }

    /**
     * YZ manşeti seçiliyken öne çıkarılmış görsel yoksa yayınlamayı engelle.
     */
    public function validate_yz_manset_featured_image( $data, $postarr ) {
        if ( ( $data['post_type'] ?? '' ) !== 'post' ) {
            return $data;
        }
        if ( empty( $_POST['mevzu_sayfa_ayarlari_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mevzu_sayfa_ayarlari_nonce'] ) ), 'mevzu_sayfa_ayarlari' ) ) {
            return $data;
        }
        $konumlar = isset( $_POST['mevzu_manset_konumlari'] ) ? (array) wp_unslash( $_POST['mevzu_manset_konumlari'] ) : [];
        if ( ! in_array( 'yapay_zeka_manset', $konumlar, true ) ) {
            return $data;
        }
        $post_id = (int) ( $postarr['ID'] ?? 0 );
        if ( $this->post_has_featured_thumbnail_from_request( $post_id ) ) {
            return $data;
        }
        set_transient( 'mevzu_yz_no_thumb_' . get_current_user_id(), 1, 60 );
        if ( in_array( $data['post_status'] ?? '', [ 'publish', 'future', 'private' ], true ) ) {
            $data['post_status'] = 'draft';
        }
        return $data;
    }

    public function admin_notice_yz_manset_thumbnail() {
        $key = 'mevzu_yz_no_thumb_' . get_current_user_id();
        if ( ! get_transient( $key ) ) {
            return;
        }
        delete_transient( $key );
        echo '<div class="notice notice-error is-dismissible"><p>';
        esc_html_e( 'Yapay Zeka Manşeti için öne çıkarılmış görsel zorunludur. Yazı yayınlanamadı ve taslak olarak kaydedildi.', 'mevzu2' );
        echo '</p></div>';
    }
}

new Mevzu_Post_Metabox();

