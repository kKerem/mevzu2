<?php
get_header();
set_post_view();
?>
<style>#onesignal-bell-container {display:none}.single-content table.table.table-responsive{width: auto !important;}
table>:not(caption)>*>* {border-width: 1px;padding: .5rem;border-width: 0 var(--mevzu-border-width);}
header .container .bg-white.rounded.shadow-sm{display: none !important;}
table tr td, table tr th{font-weight: 400 !important;width: auto !important;padding: 2px !important;}
table tr:not(:first-child) td strong, table tr:not(:first-child) th strong{font-weight: 400 !important;}
.single-resmi-ilanlar .content table:first-child {width: 100% !important;}
.single-resmi-ilanlar .content table:first-child td{border-width: var(--mevzu-border-width) !important;}
table tr {vertical-align: middle;}
</style>


	<div class="container-fluid overflow-hidden mb-3 mb-lg-5">

        <a class="kategori rounded mt-3 d-inline-block" href="<?php bloginfo('url') ?>/resmi-ilanlar">Resmi İlan</a>
                    
        <div class="row tarih mt-3 justify-content-between align-items-center">
            <div class="col-12 col-md">Yayınlanma Tarihi:<b class="ps-2"><?php echo get_the_date('d.m.Y H:i'); ?></b></div>
            <?php if (get_the_date() !== get_the_modified_date()) { echo '<div class="col-auto ms-md-auto">Güncelleme Tarihi:<b class="ps-2">' . get_the_modified_date("d.m.Y H:i") . '</b></div>'; } ?>
        </div>

		<?php while ( have_posts() ) : the_post(); ?>
            <div class="my-3 bik-ilan" id="bik-ilan-<?php if(get_post_meta(get_the_ID(), 'ilan_numarasi', true)) echo esc_html(get_post_meta(get_the_ID(), 'ilan_numarasi', true)); ?>">
                <div class="bg-white p-3 rounded shadow-sm<?php echo ' ilan-'. $post->ID; ?>" property="ilanBody">
                    
                    <h1 class="fs-4 fw-bold text-center mt-3 mb-1"><?php the_title(); ?></h1>
                    <div class="content">
                        <?php
                        $content = get_the_content(); // İçeriği al
                        $title = get_the_title(); // Başlığı al

                        // Başlığı <span class="d-none">...</span> içine al
                        $content = str_replace($title, '<span class="d-none">' . $title . '</span>', $content);

                        // Başlığın hemen ardından gelen <br> etiketini sil
                        $content = str_replace('<span class="d-none">' . $title . '</span><br>', '<span class="d-none">' . $title . '</span>', $content);

                        echo apply_filters('the_content', $content); // İçeriği filtrelerle birlikte ekrana yazdır
                        ?>
                    </div>
                    
                    <div class="row align-items-center border-top mx-0 pt-3 gx-md-0">
                        <div class="col-12 col-md">
                            <div class="single-tags">
                                <a href="https://www.ilan.gov.tr" target="_blank" title="BİK" class="tag">#ilangovtr</a>
                            </div>
                        </div>
                        <div class="col-12 col-md-auto ms-auto pe-lg-0 fw-semibold px-0 ilan_id">
                            <small>Basın No: <?php if(get_post_meta(get_the_ID(), 'ilan_numarasi', true)) echo esc_html(get_post_meta(get_the_ID(), 'ilan_numarasi', true)); ?></small>
                        </div>
                    </div>
                </div>
            </div>
		<?php endwhile;?>

    </div>

    <script>
       window.addEventListener('DOMContentLoaded', function() {
            var tables = document.getElementsByTagName('table');
            for (var i = 0; i < tables.length; i++) {
                tables[i].classList.add('table', 'table-responsive', 'table-bordered');
            }
        });
    </script>
<?php
get_footer();
