<?php
/**
 * Firma Başvuru Formu (ön yüz)
 */
$kategoriler  = get_terms( [ 'taxonomy' => 'firma-kategori', 'hide_empty' => false, 'parent' => 0 ] );
$sehirler     = get_terms( [ 'taxonomy' => 'firma-sehir',    'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
$current_user = wp_get_current_user();
?>

    <div id="firma-basvuru-success" class="alert alert-success d-none" role="alert">
        <strong>Başvurunuz alındı!</strong>
        <p class="mb-0 mt-1">Firmanız incelendikten sonra rehberde yayınlanacak. Onaylandığında e-posta ile bilgilendirileceksiniz.</p>
    </div>

    <p class="text-muted small mb-4">Başvurunuz incelendikten sonra yayınlanacaktır.</p>

    <form id="firma-basvuru-form" enctype="multipart/form-data" novalidate>
        <?php wp_nonce_field( 'firma_submit_nonce', 'nonce' ); ?>

        <div class="mb-3">
            <label for="firma_adi" class="form-label fw-semibold">Firma Adı <span class="text-danger">*</span></label>
            <input type="text" name="firma_adi" id="firma_adi" class="form-control" required placeholder="Firma veya işyeri adı">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="basvuru_kategori" class="form-label fw-semibold">Kategori</label>
                <select name="kategori" id="basvuru_kategori" class="form-select">
                    <option value="">— Seçiniz —</option>
                    <?php if ( $kategoriler && ! is_wp_error($kategoriler) ) :
                        foreach ( $kategoriler as $kat ) :
                            $alt_katlar = get_terms( [ 'taxonomy' => 'firma-kategori', 'parent' => $kat->term_id, 'hide_empty' => false ] );
                            if ( $alt_katlar && ! is_wp_error($alt_katlar) && count($alt_katlar) ) : ?>
                                <optgroup label="<?php echo esc_attr($kat->name); ?>">
                                    <?php foreach ( $alt_katlar as $alt ) : ?>
                                        <option value="<?php echo $alt->term_id; ?>"><?php echo esc_html($alt->name); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php else : ?>
                                <option value="<?php echo $kat->term_id; ?>"><?php echo esc_html($kat->name); ?></option>
                            <?php endif;
                        endforeach;
                    endif; ?>
                </select>
            </div>
            <div class="col-sm-6">
                <label for="basvuru_sehir" class="form-label fw-semibold">Şehir</label>
                <select name="sehir" id="basvuru_sehir" class="form-select">
                    <option value="">— Şehir Seçiniz —</option>
                    <?php if ( $sehirler && ! is_wp_error($sehirler) ) :
                        foreach ( $sehirler as $sehir ) : ?>
                            <option value="<?php echo $sehir->term_id; ?>"><?php echo esc_html($sehir->name); ?></option>
                        <?php endforeach;
                    endif; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="firma_yetkili" class="form-label fw-semibold">Yetkili Kişi</label>
            <input type="text" name="yetkili" id="firma_yetkili" class="form-control" placeholder="Ad Soyad">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="firma_telefon" class="form-label fw-semibold">Cep Telefonu</label>
                <input type="tel" name="telefon" id="firma_telefon" class="form-control" placeholder="0532 000 00 00">
            </div>
            <div class="col-sm-6">
                <label for="firma_eposta" class="form-label fw-semibold">İş E-posta</label>
                <input type="email" name="eposta" id="firma_eposta" class="form-control" placeholder="info@firma.com">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="firma_sabit_tel1" class="form-label fw-semibold">Telefon 1</label>
                <input type="tel" name="sabit_tel1" id="firma_sabit_tel1" class="form-control" placeholder="0212 000 00 00">
            </div>
            <div class="col-sm-6">
                <label for="firma_sabit_tel2" class="form-label fw-semibold">Telefon 2</label>
                <input type="tel" name="sabit_tel2" id="firma_sabit_tel2" class="form-control" placeholder="0212 000 00 00">
            </div>
        </div>

        <div class="mb-3">
            <label for="firma_website" class="form-label fw-semibold">Web Sitesi</label>
            <input type="url" name="website" id="firma_website" class="form-control" placeholder="https://firma.com">
        </div>

        <div class="mb-3">
            <label for="firma_video" class="form-label fw-semibold">Tanıtım Videosu</label>
            <input type="url" name="video" id="firma_video" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
            <div class="form-text">YouTube video bağlantısı</div>
        </div>

        <div class="mb-3">
            <label for="firma_adres" class="form-label fw-semibold">Adres</label>
            <textarea name="adres" id="firma_adres" class="form-control" rows="2" placeholder="Firma açık adresi"></textarea>
        </div>

        <div class="mb-3">
            <label for="firma_aciklama" class="form-label fw-semibold">Hakkında</label>
            <textarea name="aciklama" id="firma_aciklama" class="form-control" rows="4" placeholder="Firmanızı kısaca tanıtın..."></textarea>
        </div>

        <div class="mb-3">
            <label for="firma_logo" class="form-label fw-semibold">Firma Logosu / Görseli</label>
            <input type="file" name="firma_logo" id="firma_logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text">JPG, PNG, WebP veya GIF. Max 2MB.</div>
            <div id="firma-logo-preview" class="mt-2 d-none">
                <img src="" alt="Önizleme" class="rounded border" style="max-height:80px;object-fit:contain">
            </div>
        </div>

        <div class="mb-4">
            <label for="firma_galeri" class="form-label fw-semibold">Galeri Görselleri</label>
            <input type="file" name="firma_galeri[]" id="firma_galeri" class="form-control"
                    accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            <div class="form-text">En fazla 5 görsel ekleyebilirsiniz. JPG, PNG, WebP veya GIF. Her biri max 2MB.</div>
            <div id="firma-galeri-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>

        <hr>

        <h3 class="fs-6 fw-bold text-muted mb-3">Çalışma Saatleri</h3>

        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="saatler_unknown" id="saatler_unknown" value="1">
                <label class="form-check-label fw-semibold" for="saatler_unknown">
                    Çalışma saatleri belli değil
                </label>
            </div>
        </div>

        <div id="firma-saatler-section">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr><th>Gün</th><th>Açılış</th><th>Kapanış</th><th>Kapalı</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $days = [ 'mon'=>'Pazartesi','tue'=>'Salı','wed'=>'Çarşamba','thu'=>'Perşembe','fri'=>'Cuma','sat'=>'Cumartesi','sun'=>'Pazar' ];
                    foreach ( $days as $key => $gun ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($gun); ?></strong></td>
                            <td><input type="time" name="saatler[<?php echo $key; ?>][open]" value="09:00" class="form-control form-control-sm firma-time-input" style="width:120px"></td>
                            <td><input type="time" name="saatler[<?php echo $key; ?>][close]" value="18:00" class="form-control form-control-sm firma-time-input" style="width:120px"></td>
                            <td>
                                <div class="form-check mb-0">
                                    <input class="form-check-input firma-closed-cb" type="checkbox"
                                            name="saatler[<?php echo $key; ?>][closed]" value="1">
                                    <label class="form-check-label small">Kapalı</label>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        document.getElementById('saatler_unknown').addEventListener('change', function(){
            document.getElementById('firma-saatler-section').style.display = this.checked ? 'none' : '';
        });
        document.querySelectorAll('.firma-closed-cb').forEach(function(cb){
            cb.addEventListener('change', function(){
                var row = this.closest('tr');
                row.querySelectorAll('.firma-time-input').forEach(function(inp){ inp.disabled = cb.checked; });
            });
        });
        </script>

        <hr>

        <h3 class="fs-6 fw-bold text-muted mb-3">Başvuran Bilgileri</h3>
        <p class="form-text mb-3">Bu bilgiler sadece admin tarafından görülür. Başvurunuz onaylandığında bilgilendirilirsiniz.</p>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="submitter_name" class="form-label fw-semibold">
                    Adınız Soyadınız <span class="text-danger">*</span>
                </label>
                <input type="text" name="submitter_name" id="submitter_name" class="form-control" required
                        value="<?php echo esc_attr( $current_user->display_name ); ?>" placeholder="Ad Soyad">
            </div>
            <div class="col-sm-6">
                <label for="submitter_email" class="form-label fw-semibold">
                    E-posta Adresiniz <span class="text-danger">*</span>
                </label>
                <input type="email" name="submitter_email" id="submitter_email" class="form-control" required
                        value="<?php echo esc_attr( $current_user->user_email ); ?>" placeholder="eposta@ornek.com">
            </div>
        </div>

        <div id="firma-basvuru-error" class="alert alert-danger d-none"></div>

        <button type="submit" class="btn btn-primary">
            <span class="firma-btn-text">Başvuruyu Gönder</span>
            <span class="firma-btn-loading d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Gönderiliyor...
            </span>
        </button>

        <p class="form-text mt-3">Formu göndererek kişisel verilerinizin gizlilik politikasına uygun işleneceğini kabul edersiniz.</p>

    </form>
