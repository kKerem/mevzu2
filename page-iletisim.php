<?php get_header(); ?>

	<div class="container">
        <div class="single-breadcrumb">
            <?php custom_breadcrumbs(); ?>
        </div>

		<?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

        <?php while ( have_posts() ) : the_post(); ?>

        <div id="post-<?php the_ID(); ?>" <?php post_class('tema-widget bg-white shadow-sm rounded-3 mt-3'); ?>>
                
            <h1 class="mb-0"><?php the_title(); ?></h1>

            <?php 
            $iletisim_rows = (int) get_post_meta(get_the_ID(), 'default_repeater', true);
            if($iletisim_rows > 0) : ?>
                <div id="bik-iletisim-main" class="table">
                    <?php for ($i = 0; $i < $iletisim_rows; $i++) : ?>
                        <div class="row border-bottom m-0 p-2 py-3">
                            <div class="col-12 col-md-4"><?php echo esc_html(get_post_meta(get_the_ID(), 'default_repeater_' . $i . '_ilk', true)); ?></div>
                            <div class="col fw-semibold"><?php echo esc_html(get_post_meta(get_the_ID(), 'default_repeater_' . $i . '_ikinci', true)); ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </div>

        <div id="bik-kunye-main" <?php post_class('tema-widget bg-white shadow-sm rounded-3 mt-4'); ?>>
                
            <?php $kunye_id = (int) get_option('options_kunye_sayfasi', 1); ?>
            <h1 class="mb-0"><?php echo get_the_title($kunye_id); ?></h1>

            <?php 
            $kunye_rows = (int) get_post_meta($kunye_id, 'default_repeater', true);
            if($kunye_rows > 0) : ?>
                    <div id="bik-kunye-main" class="table">
                        <?php for ($i = 0; $i < $kunye_rows; $i++) : ?>
                            <?php 
                            $ilk = get_post_meta($kunye_id, 'default_repeater_' . $i . '_ilk', true);
                            $ikinci = get_post_meta($kunye_id, 'default_repeater_' . $i . '_ikinci', true);
                            switch ($ilk) {
                                case 'Ticaret Unvanı':$id="bik-kunye-ticaret-unvani";break;
                                case 'Tüzel Kişi Temsilcisi':$id="bik-kunye-tuzel-kisi-temsilcisi";break;
                                case 'Yayıncı':$id="bik-kunye-yayinci";break;
                                case 'Sorumlu Yazı İşleri Müdürü':$id="bik-kunye-sorumlu-yim";break;
                                case 'Yönetim Yeri':$id="bik-kunye-yonetim-yeri";break;
                                case 'İletişim Telefonu':$id="bik-kunye-telefon";break;
                                case 'Kurumsal E-Posta':$id="bik-kunye-eposta";break;
                                case 'Ulusal Elektronik Tebligat Sistemi Adresi':$id="bik-kunye-uets";break;
                                case 'Yer Sağlayıcı Ticaret Unvanı':$id="bik-kunye-yer-saglayici-unvan";break;
                                case 'Yer Sağlayıcı Adresi':$id="bik-kunye-yer-saglayici-adres";break;
                                case 'Mali Müşavir':$id="bik-kunye-yer-mali-musavir";break;
                                case 'İletişim':$id="iletisim";break;
                                
                                default:
                                    $id="";
                                    break;
                            } ?>
                            <div class="row border-bottom m-0 p-2 py-3">
                                <div class="col-12 col-md-4"><?php echo esc_html($ilk); ?></div>
                                <div class="col fw-semibold" id="<?php echo esc_attr($id); ?>"><?php echo esc_html($ikinci); ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (get_post_meta(get_the_ID(), 'iletisim_formu_aktif', true) == '1') : ?>
            <div class="tema-widget bg-white shadow-sm rounded-3 mt-3">
                <h3 class="tema-baslik mt-3">Bize Mesaj Gönderin</h3>
                <div class="p-3">
                    <form id="mevzu-contact-form" class="row g-3 g-lg-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label small text-body fw-normal">Adınız Soyadınız</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small text-body fw-normal">E-Posta Adresiniz</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-12">
                            <label for="subject" class="form-label small text-body fw-normal">Konu</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label small text-body fw-normal">Mesajınız</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">Gönder</button>
                            <span id="form-status" class="ms-3"></span>
                        </div>
                        <input type="hidden" name="action" value="mevzu_contact_form">
                        <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
                        <?php wp_nonce_field('mevzu_contact_nonce', 'security'); ?>
                    </form>
                </div>

                <script>
                    jQuery(document).ready(function($) {
                        $('#mevzu-contact-form').on('submit', function(e) {
                            e.preventDefault();
                            var $form = $(this);
                            var $btn = $form.find('button');
                            var $status = $('#form-status');

                            $btn.prop('disabled', true).text('Gönderiliyor...');
                            $status.text('').removeClass('text-success text-danger');

                            $.post('<?php echo admin_url('admin-ajax.php'); ?>', $form.serialize(), function(response) {
                                if (response.success) {
                                    $status.css('color', 'green').addClass('fw-bold').text('✓ ' + response.data);
                                    $form[0].reset();
                                } else {
                                    $status.css('color', 'red').addClass('fw-bold').text('✗ ' + response.data);
                                }
                                $btn.prop('disabled', false).text('Gönder');
                            });
                        });
                    });
                </script>
            </div>
            <?php endif; ?>

        </div>


        <?php endwhile; ?>
	</div>

<?php get_footer();
