<?php get_header(); ?>

	<div class="container">
    
		<?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

        <?php while ( have_posts() ) : the_post(); ?>

        <div class="single-breadcrumb">
            <?php custom_breadcrumbs(); ?>
        </div>

        <div <?php post_class('tema-widget bg-white shadow-sm rounded-3 pt-3 mt-3'); ?>>
        <h1 class="m-0"><?php the_title(); ?></h1>

            <?php 
            $kunye_rows = (int) get_post_meta(get_the_ID(), 'default_repeater', true);
            if($kunye_rows > 0) : ?>
                <div id="bik-kunye-main" class="table">
                    <?php for ($i = 0; $i < $kunye_rows; $i++) : ?>
                        <?php 
                        $ilk = get_post_meta(get_the_ID(), 'default_repeater_' . $i . '_ilk', true);
                        $ikinci = get_post_meta(get_the_ID(), 'default_repeater_' . $i . '_ikinci', true);
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

        

        <?php endwhile; ?>
	</div>

<?php get_footer();
