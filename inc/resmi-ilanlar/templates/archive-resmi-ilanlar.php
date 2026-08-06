<?php
get_header();
set_post_view();
if (!empty($_GET['tarih'])) {
    $tarih = $_GET['tarih'];
}
else {
    $tarih = date('Y-m-d');
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

<div class="container">

    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>

    <div class="tema-widget bg-white mt-3 mt-lg-4 rounded shadow-sm">

        <h1 class="mx-0"><?php the_archive_title(); ?></h1>
        <p><?php the_archive_description(); ?></p>

        <div class="p-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <h5 class="resmiilan-tarih m-0"><span>Gösterilen Tarih:</span> <?php echo date('d.m.Y', strtotime($tarih)); ?></h5>
                </div>
                <div class="col col-lg-auto ms-lg-auto mt-3 mt-md-0">
                    <form action="<?php echo esc_url(home_url('/resmi-ilanlar')); ?>" method="get">
                        <div class="form-group">
                            <div class="input-group">
                                <input class="form-control fz-14" type="date" id="datepicker" name="tarih" value="<?php if(isset($_GET['tarih'])) echo $_GET['tarih']; ?>">
                                <button class="btn btn-primary text-capitalize" type="submit" id="inputGroupFileAddon04">
                                    Listele
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $args = array(
                'post_type' => 'resmi-ilanlar',
                'post_status' => 'publish',
                'date_query' => array(
                    array(
                        'year' => substr($tarih, 0, 4),
                        'month' => substr($tarih, 5, 2),
                        'day' => substr($tarih, 8, 2),
                    ),
                ),
            );
            $custom_query = new WP_Query($args); ?>
            
            <?php if ($custom_query->have_posts()) : ?>
                <div class="row mt-3 tema-widget">
                    <?php while ($custom_query->have_posts()) : $custom_query->the_post(); ?>
                    <div class="col-12 col-md-4 my-3 my-md-0 bik-ilan" id="bik-ilan-<?php if(get_post_meta(get_the_ID(), 'ilan_numarasi', true)) echo esc_html(get_post_meta(get_the_ID(), 'ilan_numarasi', true)); ?>">
                        <div class="card card-kucuk sablon-1 tema-widget bg-white rounded">
                            <a href="<?php the_permalink() ?>" class="ripple" data-bs-ripple-color="light">
                                <div class="img-hover">
                                    <?php the_post_thumbnail('gorsel-thumbnail-col-3', ['title' => get_the_title(), 'loading'=>'lazy']); ?>
                                </div>
                                <div class="card-body p-0">
                                    <h3 class="card-title satir-3 fw-semibold my-2"><?php the_title(); ?></h3>
                                    <div class="text-secondary small fw-normal">Yayınlanma Tarihi: <?php echo get_the_date('d.m.Y H:i'); ?></div>
                                </div>
                            </a>
                        </div>

                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <small class="mt-2">Bu tarihte ilan bulunmamaktadır.</small>
            <?php endif; ?>
        </div>
    </div>


</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<?php get_footer(); ?>