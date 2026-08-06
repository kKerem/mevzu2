<?php
/**
 * Firma Rehberi — Admin Metabox (Firma Bilgileri + Harita)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Firma_Metabox {

    private $days = [
        'mon' => 'Pazartesi', 'tue' => 'Salı',   'wed' => 'Çarşamba',
        'thu' => 'Perşembe',  'fri' => 'Cuma',    'sat' => 'Cumartesi', 'sun' => 'Pazar',
    ];

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'do_meta_boxes',  [ $this, 'rename_thumbnail_box' ] );
        add_action( 'save_post_firma', [ $this, 'save' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue( $hook ) {
        global $post;
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
        if ( ! $post || $post->post_type !== 'firma' ) return;

        wp_enqueue_media();
        wp_enqueue_style(  'firma-admin-css', FIRMA_REHBERI_URL . 'assets/css/admin.css', [], FIRMA_REHBERI_VER );
        wp_enqueue_script( 'firma-admin-js',  FIRMA_REHBERI_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-sortable' ], FIRMA_REHBERI_VER, true );
        wp_localize_script( 'firma-admin-js', 'firmaMetaData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'firma_admin_nonce' ),
        ] );
    }

    public function add_meta_boxes() {
        add_meta_box( 'firma_bilgileri', 'Firma Bilgileri',  [ $this, 'render_bilgileri' ], 'firma', 'normal', 'high' );
        add_meta_box( 'firma_galeri',   'Galeri',           [ $this, 'render_galeri'    ], 'firma', 'side',   'default' );
        add_meta_box( 'firma_harita',   'Konum & Harita',   [ $this, 'render_harita'    ], 'firma', 'normal', 'high' );
        add_meta_box( 'firma_saatler',  'Çalışma Saatleri', [ $this, 'render_saatler'   ], 'firma', 'normal', 'default' );
        add_meta_box( 'firma_options',  'Firma Seçenekleri',[ $this, 'render_options'   ], 'firma', 'side',   'default' );
    }

    /* ------------------------------------------------------------------ */
    /* Render: İletişim Bilgileri                                           */
    /* ------------------------------------------------------------------ */

    public function render_bilgileri( $post ) {
        wp_nonce_field( 'firma_save_meta', 'firma_meta_nonce' );
        $fields = [
            '_firma_yetkili'    => [ 'label' => 'Yetkili Kişi',   'type' => 'text',     'placeholder' => 'Ad Soyad' ],
            '_firma_telefon'    => [ 'label' => 'Cep Telefonu',   'type' => 'tel',      'placeholder' => '0532 000 00 00' ],
            '_firma_sabit_tel1' => [ 'label' => 'Telefon 1','type' => 'tel',      'placeholder' => '0212 000 00 00' ],
            '_firma_sabit_tel2' => [ 'label' => 'Telefon 2','type' => 'tel',      'placeholder' => '0212 000 00 00' ],
            '_firma_eposta'     => [ 'label' => 'E-posta',         'type' => 'email',    'placeholder' => 'info@firma.com' ],
            '_firma_video'      => [ 'label' => 'Tanıtım Videosu', 'type' => 'url',      'placeholder' => 'https://www.youtube.com/watch?v=...' ],
            '_firma_website'    => [ 'label' => 'Web Sitesi',      'type' => 'url',      'placeholder' => 'https://firma.com' ],
            '_firma_adres'      => [ 'label' => 'Adres',           'type' => 'textarea', 'placeholder' => 'Açık adres' ],
        ];
        echo '<table class="form-table firma-meta-table">';
        foreach ( $fields as $key => $f ) {
            $val = esc_attr( get_post_meta( $post->ID, $key, true ) );
            echo '<tr><th><label>' . esc_html( $f['label'] ) . '</label></th><td>';
            if ( $f['type'] === 'textarea' ) {
                echo '<textarea name="' . esc_attr($key) . '" rows="2" class="large-text" placeholder="' . esc_attr($f['placeholder']) . '">' . esc_textarea( get_post_meta( $post->ID, $key, true ) ) . '</textarea>';
            } else {
                echo '<input type="' . esc_attr($f['type']) . '" name="' . esc_attr($key) . '" value="' . $val . '" class="large-text" placeholder="' . esc_attr($f['placeholder']) . '">';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }

    /* ------------------------------------------------------------------ */
    /* Featured Image → "Firma Logosu" yeniden adlandır                    */
    /* ------------------------------------------------------------------ */

    public function rename_thumbnail_box() {
        remove_meta_box( 'postimagediv', 'firma', 'side' );
        add_meta_box( 'postimagediv', 'Firma Logosu', 'post_thumbnail_meta_box', 'firma', 'side', 'high' );
    }

    /* ------------------------------------------------------------------ */
    /* Render: Galeri                                                       */
    /* ------------------------------------------------------------------ */

    public function render_galeri( $post ) {
        $raw    = get_post_meta( $post->ID, '_firma_galeri', true );
        $galeri = $raw ? json_decode( $raw, true ) : [];
        $galeri = is_array( $galeri ) ? array_filter( array_map( 'absint', $galeri ) ) : [];
        ?>
        <div id="firma-galeri-wrap">
            <div id="firma-galeri-container" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;min-height:40px;">
                <?php foreach ( $galeri as $img_id ) :
                    $src = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                    if ( ! $src ) continue;
                ?>
                    <div class="firma-galeri-item" data-id="<?php echo intval( $img_id ); ?>"
                         style="position:relative;width:80px;height:80px;cursor:grab;">
                        <img src="<?php echo esc_url( $src ); ?>" alt=""
                             style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                        <button type="button" class="firma-galeri-remove"
                                style="position:absolute;top:-6px;right:-6px;background:#d63638;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:14px;line-height:1;cursor:pointer;padding:0;">
                            &times;
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="_firma_galeri" id="firma-galeri-ids"
                   value="<?php echo esc_attr( wp_json_encode( array_values( $galeri ) ) ); ?>">
            <button type="button" id="firma-galeri-ekle" class="button button-secondary">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-top:3px;"></span>
                Görsel Ekle
            </button>
            <p class="description" style="margin-top:6px;">Firma galeri görselleri. Birden fazla görsel seçebilirsiniz.</p>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* Render: Konum & Harita                                               */
    /* ------------------------------------------------------------------ */

    public function render_harita( $post ) {
        $lat = get_post_meta( $post->ID, '_firma_lat', true );
        $lng = get_post_meta( $post->ID, '_firma_lng', true );
        $has = $lat && $lng;
        ?>

        <?php if ( $has ) : ?>
        <div style="height:280px;border:1px solid #ddd;border-radius:4px;margin-bottom:12px;overflow:hidden;">
            <iframe src="https://maps.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>&z=15&output=embed"
                    width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
        </div>
        <?php else : ?>
        <div style="height:60px;border:1px solid #ddd;border-radius:4px;margin-bottom:12px;display:flex;align-items:center;justify-content:center;background:#f9f9f9;color:#999;font-size:13px;">
            Henüz konum belirlenmedi. Adres alanını doldurup kaydedin.
        </div>
        <?php endif; ?>

        <p class="description"><b>DIKKAT!</b> <span class="text-primary">Firmaya ait adres bilgisi kaydedilince enlem ve boylam otomatik doldurulur</span>.<br>Eğer otomatik doldurulmazsa Google tarafından adresle ilgili bir <b>yanıt alınamamış</b> demektir. Bu durumda koordinat bilgileri manuel olarak girilmelidir.</p>
        <table class="form-table firma-meta-table">
            <tr>
                <th><label for="_firma_lat">Enlem (Lat)</label></th>
                <td>
                    <input type="text" name="_firma_lat" id="_firma_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text" placeholder="39.9334">
                </td>
            </tr>
            <tr>
                <th><label for="_firma_lng">Boylam (Lng)</label></th>
                <td><input type="text" name="_firma_lng" id="_firma_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text" placeholder="32.8597"></td>
            </tr>
        </table>
        <p class="description">Firmanın sayfasında Google Maps alanının gözükebilmesi için enlem ve boylam bilgileri gereklidir.</p>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* Render: Çalışma Saatleri                                             */
    /* ------------------------------------------------------------------ */

    public function render_saatler( $post ) {
        $raw     = get_post_meta( $post->ID, '_firma_saatler', true );
        $saatler = $raw ? json_decode( $raw, true ) : [];
        $unknown = get_post_meta( $post->ID, '_firma_saatler_unknown', true );
        ?>
        <p>
            <label>
                <input type="checkbox" name="_firma_saatler_unknown" id="firma-saatler-unknown"
                       value="1" <?php checked( $unknown, '1' ); ?>>
                <strong>Çalışma saatleri belli değil</strong>
            </label>
        </p>
        <div id="firma-saatler-table" <?php echo $unknown ? 'style="display:none;"' : ''; ?>>
        <table class="form-table firma-meta-table firma-hours-table">
            <thead><tr><th>Gün</th><th>Açılış</th><th>Kapanış</th><th>Kapalı</th></tr></thead>
            <tbody>
            <?php foreach ( $this->days as $key => $label ) :
                $h      = $saatler[ $key ] ?? [ 'open' => '09:00', 'close' => '18:00', 'closed' => false ];
                $closed = ! empty( $h['closed'] );
            ?>
            <tr>
                <td><strong><?php echo esc_html( $label ); ?></strong></td>
                <td><input type="time" name="_firma_saatler[<?php echo $key; ?>][open]"
                    value="<?php echo esc_attr( $h['open'] ?? '09:00' ); ?>"
                    class="firma-time-input" <?php echo $closed ? 'disabled' : ''; ?>></td>
                <td><input type="time" name="_firma_saatler[<?php echo $key; ?>][close]"
                    value="<?php echo esc_attr( $h['close'] ?? '18:00' ); ?>"
                    class="firma-time-input" <?php echo $closed ? 'disabled' : ''; ?>></td>
                <td><label>
                    <input type="checkbox" name="_firma_saatler[<?php echo $key; ?>][closed]" value="1"
                        class="firma-closed-cb" <?php checked( $closed ); ?>> Kapalı
                </label></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <script>
        document.getElementById('firma-saatler-unknown').addEventListener('change', function() {
            document.getElementById('firma-saatler-table').style.display = this.checked ? 'none' : '';
        });
        </script>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* Render: Seçenekler (Sidebar)                                         */
    /* ------------------------------------------------------------------ */

    public function render_options( $post ) {
        $featured        = get_post_meta( $post->ID, '_firma_featured',       true );
        $feat_start      = get_post_meta( $post->ID, '_firma_featured_start', true );
        $feat_end        = get_post_meta( $post->ID, '_firma_featured_end',   true );
        $submitter_name  = get_post_meta( $post->ID, '_firma_submitter_name', true );
        $submitter_email = get_post_meta( $post->ID, '_firma_submitter_email',true );
        $view_count      = (int) get_post_meta( $post->ID, '_firma_view_count', true );
        $rating_count    = (int) get_post_meta( $post->ID, '_firma_rating_count', true );
        $rating_total    = (int) get_post_meta( $post->ID, '_firma_rating_total', true );
        $rating_avg      = $rating_count > 0 ? round( $rating_total / $rating_count, 1 ) : 0;
        ?>
        <p>
            <label>
                <input type="checkbox" name="_firma_featured" value="1" id="firma-featured-cb" <?php checked( $featured, '1' ); ?>>
                <strong>Öne Çıkarılan Firma</strong>
            </label>
            <br><small class="description">Öne çıkan firmalar listenin başında gösterilir.</small>
        </p>

        <div id="firma-featured-dates" style="<?php echo $featured ? '' : 'display:none;'; ?>">
            <p class="fw-semibold m-0">Tarih Aralığı <span class="text-muted fw-normal">(opsiyonel)</span></p>
            <label class="small d-lock mt-2">Başlangıç</label>
            <input type="date" name="_firma_featured_start" value="<?php echo esc_attr( $feat_start ); ?>" class="widefat" style="font-size:12px">
            <label class="small d-lock mt-2">Bitiş</label>
            <input type="date" name="_firma_featured_end" value="<?php echo esc_attr( $feat_end ); ?>" class="widefat" style="font-size:12px">
            <div class="description small mt-2 text-muted">Tarih girilmezse süresiz öne çıkar. Bitiş tarihi geçince otomatik kaldırılır.</div>
        </div>

        <script>
        (function(){
            var cb = document.getElementById('firma-featured-cb');
            var box = document.getElementById('firma-featured-dates');
            if(cb && box) {
                cb.addEventListener('change', function(){ box.style.display = this.checked ? '' : 'none'; });
            }
        })();
        </script>

        <?php if ( $view_count || $rating_count ) : ?>
        <hr>
        <p class="fw-semibold m-0">İstatistikler</p>
        <div class="small mt-2 text-body d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center"><span class="dashicons dashicons-visibility text-primary me-1"></span> <?php echo $view_count; ?> tıklanma</div>
            <div class="d-flex align-items-center"><span class="dashicons dashicons-star-filled text-warning me-1"></span> <?php echo $rating_count; ?> değerlendirme<?php echo $rating_avg ? ' (' . $rating_avg . ')' : ''; ?></div>
        </div>
        <?php endif; ?>

        <?php if ( $submitter_name || $submitter_email ) : ?>
        <hr>
        <p class="fw-semibold m-0">Başvuran</p>
        <?php if ( $submitter_name )  : ?><?php echo esc_html( $submitter_name ); ?><br><?php endif; ?>
        <?php if ( $submitter_email ) : ?><a href="mailto:<?php echo esc_attr( $submitter_email ); ?>"><?php echo esc_html( $submitter_email ); ?></a><?php endif; ?>
        <?php endif; ?>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* Kaydet                                                               */
    /* ------------------------------------------------------------------ */

    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['firma_meta_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['firma_meta_nonce'], 'firma_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = [ '_firma_yetkili', '_firma_telefon', '_firma_sabit_tel1', '_firma_sabit_tel2', '_firma_eposta', '_firma_video', '_firma_website', '_firma_adres', '_firma_lat', '_firma_lng' ];
        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // Adres geocoding — adres değiştiyse veya koordinat yoksa otomatik al
        $new_adres = sanitize_text_field( $_POST['_firma_adres'] ?? '' );
        $old_adres = get_post_meta( $post_id, '_firma_adres_geocoded', true );
        $has_coords = get_post_meta( $post_id, '_firma_lat', true ) && get_post_meta( $post_id, '_firma_lng', true );

        if ( $new_adres && ( $new_adres !== $old_adres || ! $has_coords ) ) {
            $coords = $this->geocode_address( $new_adres );
            if ( $coords ) {
                update_post_meta( $post_id, '_firma_lat', $coords['lat'] );
                update_post_meta( $post_id, '_firma_lng', $coords['lng'] );
                update_post_meta( $post_id, '_firma_adres_geocoded', $new_adres );
            }
        }

        // Çalışma saatleri
        if ( isset( $_POST['_firma_saatler'] ) && is_array( $_POST['_firma_saatler'] ) ) {
            $saatler = [];
            $valid_days = array_keys( $this->days );
            foreach ( $valid_days as $day ) {
                $d = $_POST['_firma_saatler'][ $day ] ?? [];
                $saatler[ $day ] = [
                    'open'   => sanitize_text_field( $d['open']   ?? '09:00' ),
                    'close'  => sanitize_text_field( $d['close']  ?? '18:00' ),
                    'closed' => ! empty( $d['closed'] ),
                ];
            }
            update_post_meta( $post_id, '_firma_saatler', wp_json_encode( $saatler ) );
        }

        // Galeri
        if ( isset( $_POST['_firma_galeri'] ) ) {
            $galeri = json_decode( stripslashes( $_POST['_firma_galeri'] ), true );
            if ( is_array( $galeri ) ) {
                $galeri = array_values( array_filter( array_map( 'absint', $galeri ) ) );
                update_post_meta( $post_id, '_firma_galeri', wp_json_encode( $galeri ) );
            }
        }

        // Çalışma saatleri belirsiz
        update_post_meta( $post_id, '_firma_saatler_unknown', isset( $_POST['_firma_saatler_unknown'] ) ? '1' : '0' );

        // Öne çıkar + tarih aralığı
        update_post_meta( $post_id, '_firma_featured', isset( $_POST['_firma_featured'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_firma_featured_start', sanitize_text_field( $_POST['_firma_featured_start'] ?? '' ) );
        update_post_meta( $post_id, '_firma_featured_end',   sanitize_text_field( $_POST['_firma_featured_end']   ?? '' ) );

        // Yeni onaylandıysa başvurana bildirim gönder
        if ( $post->post_status === 'publish' ) {
            $old_status = get_post_meta( $post_id, '_firma_prev_status', true );
            if ( $old_status !== 'publish' ) {
                Firma_Notification::send_approval( $post_id );
                update_post_meta( $post_id, '_firma_prev_status', 'publish' );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Geocoding Helper                                                     */
    /* ------------------------------------------------------------------ */

    private function geocode_address( $address ) {
        if ( ! function_exists( 'curl_init' ) ) return null;

        $nominatim = 'https://nominatim.openstreetmap.org/search?' . http_build_query( [
            'q'      => $address,
            'format' => 'json',
            'limit'  => '1',
        ] );

        $body = $this->do_curl( $nominatim );
        $data = $body ? json_decode( $body, true ) : null;

        if ( ! empty( $data[0]['lat'] ) ) {
            return [
                'lat' => number_format( (float) $data[0]['lat'], 6, '.', '' ),
                'lng' => number_format( (float) $data[0]['lon'], 6, '.', '' ),
            ];
        }

        // Fallback: Photon
        $photon = 'https://photon.komoot.io/api/?' . http_build_query( [ 'q' => $address, 'limit' => 1, 'lang' => 'tr' ] );
        $body2  = $this->do_curl( $photon );
        $geo    = $body2 ? json_decode( $body2, true ) : null;

        if ( ! empty( $geo['features'][0]['geometry']['coordinates'] ) ) {
            $c = $geo['features'][0]['geometry']['coordinates'];
            return [
                'lat' => number_format( (float) $c[1], 6, '.', '' ),
                'lng' => number_format( (float) $c[0], 6, '.', '' ),
            ];
        }

        return null;
    }

    private function do_curl( $url ) {
        $ch = curl_init( $url );
        curl_setopt_array( $ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'MevzuFirmaRehberi/1.0 (contact@example.com)',
            CURLOPT_HTTPHEADER     => [ 'Accept-Language: tr,en' ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ] );
        $result = curl_exec( $ch );
        curl_close( $ch );
        return $result ?: null;
    }
}
