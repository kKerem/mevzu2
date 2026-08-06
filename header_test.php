<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package mevzu2
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <meta http-equiv="refresh"
        content="<?php echo (get_option('options_yenileme_suresi')) ? get_option('options_yenileme_suresi') : '600'; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>" />
    <meta name="theme-color" content="#000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="apple-touch-startup-image" href="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>">
    <meta name="apple-mobile-web-app-title" content="<?php bloginfo('description'); ?>">
    <meta name="application-name" content="<?php bloginfo('description'); ?>">
    <link rel="icon" href="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>" sizes="32x32" />
    <link rel="icon" href="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>" sizes="192x192" />
    <link rel="apple-touch-icon" href="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>" />
    <meta name="msapplication-TileImage" content="<?php if (get_opt_img('options_favicon'))
        echo get_opt_img('options_favicon'); ?>" />


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500..700&display=swap" rel="stylesheet">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Oxygen:wght@300;400;700&display=swap" rel="stylesheet"> -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"> -->

    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php bloginfo('template_url') ?>/css/mevzu2.min.css" />
    <script src="<?php bloginfo('template_url') ?>/js/mevzu2.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css" />

    <script>!function () { var t = document.createElement("scrip t"); t.setAttribute(" src", 'https://cdn.p.analitik.bik.gov.tr/tr a cker' + (typ eof  Intl !== " u ndefined" ? (ty peof(Intl || " ").PluralRules !== "undefined" ? '1' : typeof P r omi s e !== "undefined" ? '2' : typeof Muta t ion O bser v er! = = 'unde fined' ? '3' : '4') : '4') + '.js '), t.setAttribute("data-website-id", "8 2633bb2-91c4-4ac7-b95a-8ec0e12e 9ef3"), t.setAttribute("data-host-url", '//82633bb2-91c4-4ac7-b95a-8ec0e12 e9ef3.collector.p.analitik.b ik.gov.tr'), document.head.appendChild(t) }();</script>
    <style>
        <?php if (get_option('options_reklam_slider'))
            echo "#owl-anaslider .owl-dot:nth-child(" . get_opt_g('options_reklam_slider', 'slider_sirasi', 0) . "):before{content: 'R';}"; ?>
    </style>

    <?php /* <script>
//   document.addEventListener("DOMContentLoaded", function() {
//     const images = document.querySelectorAll('img');

//     images.forEach(function(img) {
//       img.onerror = function() {
//         // Görsel yüklenemediğinde yapılacaklar
//         const errorDiv = document.createElement('div');
//         errorDiv.className = 'hata'; // 'hata' sınıfı ekle
//         errorDiv.style.width = img.offsetWidth + 'px'; // Görselin genişliğini al
//         // errorDiv.style.height = img.offsetHeight + 'px';
//         // errorDiv.style.backgroundColor = 'black';

//         // Görselin yerini 'div' ile değiştir
//         img.parentNode.replaceChild(errorDiv, img);
//       };
//     });
//   });
</script> */ ?>

</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'mevzu2'); ?></a>
    sadasd

    <header class="bg-light border-bottom">

        <div class="row align-items-center justify-content-between d-lg-none py-1 w-100">
            <div class="col-2">
                <button data-trigger="navbar_main" class="btn shadow-none link-dark d-block p-2" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 20 20">
                        <path fill="currentColor" fill-rule="evenodd"
                            d="M2 8a1 1 0 0 1 1-1h10.308a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm0-4a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm0 8a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm0 4a1 1 0 0 1 1-1h10.308a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="col text-center">
                <a href="<?php bloginfo('url') ?>"><img src="<?php bloginfo('template_url'); ?>/img/logo.png"
                        alt="<?php bloginfo('title'); ?>" width="268" height="56">
                    <h1 class="d-none"><?php bloginfo('title'); ?></h1>
                </a>
            </div>
            <div class="col-2 text-end pe-0">
                <a href="#" class="link-dark px-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 1664 1664">
                        <path fill="currentColor"
                            d="M1152 704q0-185-131.5-316.5T704 256T387.5 387.5T256 704t131.5 316.5T704 1152t316.5-131.5T1152 704m512 832q0 52-38 90t-90 38q-54 0-90-38l-343-342q-179 124-399 124q-143 0-273.5-55.5t-225-150t-150-225T0 704t55.5-273.5t150-225t225-150T704 0t273.5 55.5t225 150t150 225T1408 704q0 220-124 399l343 343q37 37 37 90" />
                    </svg>
                </a>
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body p-0">
                                <form role="search" method="get" class="search-form"
                                    action="<?php echo esc_url(home_url('/')); ?>">
                                    <div class="input-group">
                                        <span
                                            class="screen-reader-text"><?php echo _x('Arama Sonucu:', 'label'); ?></span>
                                        <input type="search" class="form-control rounded p-4"
                                            placeholder="<?php echo esc_attr_x('Ara...', 'placeholder'); ?>"
                                            aria-label="Ara" aria-describedby="search-addon"
                                            value="<?php echo get_search_query(); ?>" name="s" />
                                        <button type="submit" class="btn btn-primary"><svg
                                                xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                viewBox="0 0 1664 1664">
                                                <path fill="currentColor"
                                                    d="M1152 704q0-185-131.5-316.5T704 256T387.5 387.5T256 704t131.5 316.5T704 1152t316.5-131.5T1152 704m512 832q0 52-38 90t-90 38q-54 0-90-38l-343-342q-179 124-399 124q-143 0-273.5-55.5t-225-150t-150-225T0 704t55.5-273.5t150-225t225-150T704 0t273.5 55.5t225 150t150 225T1408 704q0 220-124 399l343 343q37 37 37 90" />
                                            </svg></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============= MOBILE ============== -->
        <nav id="navbar_main" class="mobile-offcanvas navbar navbar-expand-lg navbar-light pt-2">
            <div class="d-block">

                <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>"
                    class="p-0 mt-minus-5 mobil_nav_aramaform d-lg-none">
                    <input type="search" class="form-control rounded-0 border-0 border-bottom bg-light py-2"
                        placeholder="Arama yap..." aria-label="Ara..." aria-describedby="search-addon"
                        value="<?php echo get_search_query(); ?>" name="s" />
                    <button type="submit" class="d-none"></button>
                </form>

                <div class="row mx-2">
                    <div class="col">
                        <?php wp_nav_menu(array(
                            'theme_location' => 'ust-menu',
                            'container' => false,
                            'menu_id' => 'mobil_menu',
                            'menu_class' => 'mt-0',
                            'fallback_cb' => '__return_false',
                            'items_wrap' => '<ul id="%1$s" class="navbar-nav nav-mobil %2$s">%3$s</ul>',
                            'depth' => 2,
                            'walker' => new bootstrap_5_wp_nav_menu_walker()
                        )); ?>
                    </div>
                    <div class="col-12 ms-auto">
                        <hr class="border-color-primary">
                        <?php wp_nav_menu(array(
                            'theme_location' => 'main-menu',
                            'container' => false,
                            'menu_class' => '',
                            'fallback_cb' => '__return_false',
                            'items_wrap' => '<ul id="%1$s" class="navbar-nav nav-mobil %2$s">%3$s</ul>',
                            'depth' => 2,
                            'walker' => new bootstrap_5_wp_nav_menu_walker()
                        )); ?>
                    </div>

                </div>

            </div> <!-- container-fluid.// -->
        </nav>
        <!-- ============= MOBILE END// ============== -->


        <!-- ============= COMPONENT ============== -->

        <nav id="navbar_main" class="d-none d-lg-block navbar navbar-expand-lg navbar-light p-0 shadow-sm mb-3">
            <?php if (get_post_type() != 'resmi-ilanlar'): ?>
                <div class="header-ust">
                    <div class="container">
                        <div class="row align-items-center justify-content-between text-muted">
                            <div class="col">
                                <?php wp_nav_menu(array(
                                    'theme_location' => 'ust-menu',
                                    'container' => false,
                                    'menu_class' => '',
                                    'fallback_cb' => '__return_false',
                                    'items_wrap' => '<ul id="%1$s" class="navbar-nav %2$s">%3$s</ul>',
                                    'depth' => 2,
                                    'walker' => new bootstrap_5_wp_nav_menu_walker()
                                )); ?>
                            </div>
                            <div class="col-auto position-relative header-ust-sag">
                                <ul class="navbar-nav align-items-center">
                                    <li class="nav-item">

                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="header">
                <div class="container">
                    <div class="row align-items-center">

                        <div class="col-12 col-md-auto">
                            <a class="navbar-brand d-none d-lg-flex me-0" href="<?php bloginfo('url'); ?>">
                                <img src="<?php bloginfo('template_url'); ?>/img/logo.png"
                                    alt="<?php bloginfo('title'); ?>" width="268" height="56">
                            </a>
                        </div>

                        <form action="" class="p-0 mt-minus-5 mobil_nav_aramaform d-lg-none">
                            <input type="text" class="form-control border-0" placeholder="Search" aria-label="Search"
                                aria-describedby="search-addon" />
                            <button type="submit" class="d-none"></button>
                        </form>
                        <div class="col">

                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'main-menu',
                                'container' => false,
                                'menu_class' => '',
                                'fallback_cb' => '__return_false',
                                'items_wrap' => '<ul id="%1$s" class="navbar-nav %2$s">%3$s</ul>',
                                'depth' => 2,
                                'walker' => new bootstrap_5_wp_nav_menu_walker()
                            ));
                            ?>

                        </div>
                        <div class="col-auto col-md-1 ms-auto">
                            <ul class="navbar-nav nav-sag m-0 list-unstyled ms-auto justify-content-end">
                                <li class="nav-item">
                                    <label for="lightSwitch" class="form-check-input-darkmode-label px-2">
                                        <input type="checkbox" class="form-check-input-darkmode" id="lightSwitch">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 256 256">
                                            <path fill="currentColor"
                                                d="M235.54 150.21a104.84 104.84 0 0 1-37 52.91A104 104 0 0 1 32 120a103.09 103.09 0 0 1 20.88-62.52a104.84 104.84 0 0 1 52.91-37a8 8 0 0 1 10 10a88.08 88.08 0 0 0 109.8 109.8a8 8 0 0 1 10 10Z" />
                                        </svg>
                                    </label>
                                </li>
                                <li class="nav-item">
                                    <a href="#" data-trigger="navbar_main" class="nav-link" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 448 512">
                                            <path fill="#222222"
                                                d="M0 96c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32h384c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zm448 160c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32h384c17.7 0 32 14.3 32 32z" />
                                        </svg>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="dropdown-toggle hidden-arrow nav-link" href="#ara"
                                        id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 24 24">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2.5"
                                                d="m21 21l-4.343-4.343m0 0A8 8 0 1 0 5.343 5.343a8 8 0 0 0 11.314 11.314Z">
                                            </path>
                                        </svg>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-left aramabg"
                                        aria-labelledby="navbarDropdownMenuLink">
                                        <li>
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-4 ms-auto">
                                                        <form role="search" method="get" class="search-form"
                                                            action="<?php echo esc_url(home_url('/')); ?>">
                                                            <div class="input-group">
                                                                <span
                                                                    class="screen-reader-text"><?php echo _x('Arama Sonucu:', 'label'); ?></span>
                                                                <input type="search" class="form-control rounded"
                                                                    placeholder="<?php echo esc_attr_x('Aramak istediğiniz kelimeyi yazın...', 'placeholder'); ?>"
                                                                    aria-label="Ara" aria-describedby="search-addon"
                                                                    value="<?php echo get_search_query(); ?>"
                                                                    name="s" />
                                                                <button type="submit"
                                                                    class="btn btn-primary">Ara</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                            </ul>

                        </div>

                    </div>

                </div>
            </div>
            <?php if (get_post_type() != 'resmi-ilanlar'): ?>
                <div class="header-alt bg-sondakika">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 col-lg-8 py-2">
                                <div class="row">
                                    <div class="col-auto sondakika-icon">
                                        <a href="<?php bloginfo('url') ?>/sondakika">
                                            <svg class="text-darkmod" xmlns="http://www.w3.org/2000/svg"
                                                xmlns:xlink="http://www.w3.org/1999/xlink" width="34" height="36"
                                                version="1.1" id="saat" x="0px" y="0px" viewBox="0 0 315.377 315.377"
                                                style="enable-background:new 0 0 315.377 315.377;" xml:space="preserve">
                                                <g class="fsd">
                                                    <path
                                                        d="M107.712,181.769l-7.938,7.705c-1.121,1.089-1.753,2.584-1.753,4.146v3.288c0,3.191,2.588,5.779,5.78,5.779h47.4    c3.196,0,5.782-2.588,5.782-5.779v-4.256c0-3.191-2.586-5.78-5.782-5.78h-26.19l0.722-0.664    c17.117-16.491,29.232-29.471,29.232-46.372c0-13.513-8.782-27.148-28.409-27.148c-8.568,0-16.959,2.75-23.629,7.74    c-2.166,1.625-2.918,4.537-1.803,7.007l1.458,3.224c0.708,1.568,2.074,2.739,3.735,3.195c1.651,0.456,3.433,0.148,4.842-0.836    c4.289-2.995,8.704-4.515,13.127-4.515c8.608,0,12.971,4.28,12.971,12.662C137.142,152.524,127.72,162.721,107.712,181.769z">
                                                    </path>
                                                </g>
                                                <g class="fsd">
                                                    <path
                                                        d="M194.107,114.096c-0.154-0.014-0.31-0.02-0.464-0.02h-1.765c-1.89,0-3.658,0.923-4.738,2.469l-35.4,50.66    c-0.678,0.971-1.041,2.127-1.041,3.311v4.061c0,3.192,2.586,5.78,5.778,5.78h32.322v16.551c0,3.191,2.586,5.779,5.778,5.779h5.519    c3.19,0,5.781-2.588,5.781-5.779v-16.551h5.698c3.192,0,5.781-2.588,5.781-5.78v-3.753c0-3.19-2.589-5.779-5.781-5.779h-5.698    v-45.189c0-3.19-2.591-5.779-5.781-5.779h-5.519C194.419,114.077,194.261,114.083,194.107,114.096z M188.799,165.045h-17.453    c4.434-6.438,12.015-17.487,17.453-25.653V165.045z">
                                                    </path>
                                                </g>
                                                <g class="ffb">
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M157.906,290.377c-68.023,0-123.365-55.342-123.365-123.365c0-64.412,49.625-117.443,112.647-122.895v19.665    c0,1.397,0.771,2.681,2.003,3.337c0.558,0.298,1.169,0.444,1.778,0.444c0.737,0,1.474-0.216,2.108-0.643l44.652-30    c1.046-0.702,1.673-1.879,1.673-3.139c0-1.259-0.627-2.437-1.673-3.139l-44.652-30c-1.159-0.779-2.654-0.857-3.887-0.198    c-1.232,0.657-2.003,1.941-2.003,3.337v15.254C70.364,24.547,9.54,88.806,9.54,167.011c0,81.809,66.558,148.365,148.365,148.365    c37.876,0,73.934-14.271,101.532-40.183l-17.111-18.226C219.38,278.512,189.4,290.377,157.906,290.377z">
                                                        </path>
                                                    </g>
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M284.552,89.689c-5.111-8.359-11.088-16.252-17.759-23.456l-18.344,16.985c5.552,5.995,10.522,12.562,14.776,19.515    L284.552,89.689z">
                                                        </path>
                                                    </g>
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M280.146,150.258l24.773-3.363c-1.322-9.74-3.625-19.373-6.846-28.632l-23.612,8.211    C277.135,134.163,279.047,142.165,280.146,150.258z">
                                                        </path>
                                                    </g>
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M242.999,45.459c-8.045-5.643-16.678-10.496-25.66-14.427l-10.022,22.903c7.464,3.267,14.64,7.301,21.327,11.991    L242.999,45.459z">
                                                        </path>
                                                    </g>
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M253.208,245.353l19.303,15.887c6.244-7.587,11.75-15.817,16.363-24.462l-22.055-11.771    C262.983,232.195,258.404,239.041,253.208,245.353z">
                                                        </path>
                                                    </g>
                                                    <g>
                                                        <path style="fill:#222222 !important"
                                                            d="M280.908,176.552c-0.622,8.157-2.061,16.264-4.273,24.093l24.057,6.802c2.666-9.426,4.396-19.18,5.146-28.99    L280.908,176.552z">
                                                        </path>
                                                    </g>
                                                </g>
                                            </svg>
                                            <span class="text-darkmod" style="color: #222222; font-weight: 700;">Son
                                                dakika</span>
                                        </a>
                                    </div>
                                    <div class="col position-relative">
                                        <?php
                                        $transient_key = 'son_dakika_haberleri';
                                        $query = get_transient($transient_key);

                                        if (false === $query) {
                                            // Transient yoksa veya süresi dolmuşsa yeni sorgu yap
                                            $args = array(
                                                'posts_per_page' => get_option('options_son_dakika_haber_sayisi'), // Number of posts to retrieve
                                                'post_status' => 'publish', // Retrieve only published posts
                                                'orderby' => 'date', // Order posts by date (newest to oldest)
                                            );
                                            $query = new WP_Query($args);

                                            // Transient'ı süresiz oluştur
                                            set_transient($transient_key, $query, 0);
                                        }

                                        if ($query->have_posts()): ?>
                                            <div class="overflow-hidden h-100 w-100">
                                                <div id="sondakika-carousel"
                                                    class="owl-carousel sondakika-carousel position-absolute h-100">
                                                    <?php while ($query->have_posts()):
                                                        $query->the_post(); ?>
                                                        <div class="item sa"><span><?php echo get_the_date("H:i"); ?></span>  /  <a
                                                                href="<?php echo get_the_permalink() ?>"><?php the_title() ?></a>
                                                        </div>
                                                    <?php endwhile;
                                                    wp_reset_postdata(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 ps-lg-3">
                                <div class="row">
                                    <div class="col-6 ps-lg-0 justify-self-end align-self-center">
                                        <div class="overflow-hidden position-relative">
                                            <div id="owl-borsa" class="owl-carousel ">

                                                <?php
                                                global $wpdb;
                                                $table_name = $wpdb->prefix . 'doviz';

                                                // Transient anahtarı
                                                $transient_key = 'currency_data';
                                                $currency_data = get_transient($transient_key);

                                                if ($currency_data === false) {
                                                    // Transient mevcut değilse veya süresi dolmuşsa veritabanı sorgusunu yap
                                                    $currency_codes = array('USD', 'EUR', 'BTC', 'GA');
                                                    $currency_data = array();

                                                    foreach ($currency_codes as $currency_code) {
                                                        $result = $wpdb->get_row("SELECT * FROM $table_name WHERE currency_code = '$currency_code'");
                                                        if ($result) {
                                                            $currency_data[$currency_code] = $result;
                                                        }
                                                    }

                                                    // Veriyi transient olarak 1 saat süreyle sakla
                                                    set_transient($transient_key, $currency_data, HOUR_IN_SECONDS);
                                                }

                                                // Verileri ekrana bastır
                                                if (!empty($currency_data)) {
                                                    foreach ($currency_data as $currency_code => $result): ?>
                                                        <div class="item py-2">
                                                            <div class="row align-items-center">
                                                                <div class="col-auto">
                                                                    <?php
                                                                    if ($currency_code == 'USD') {
                                                                        $curr = "Dolar";
                                                                        echo '<svg class="text-darkmod" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15c0-1.09 1.01-1.85 2.7-1.85c1.42 0 2.13.54 2.39 1.4c.12.4.45.7.87.7h.3c.66 0 1.13-.65.9-1.27c-.42-1.18-1.4-2.16-2.96-2.54V4.5c0-.83-.67-1.5-1.5-1.5S10 3.67 10 4.5v.66c-1.94.42-3.5 1.68-3.5 3.61c0 2.31 1.91 3.46 4.7 4.13c2.5.6 3 1.48 3 2.41c0 .69-.49 1.79-2.7 1.79c-1.65 0-2.5-.59-2.83-1.43c-.15-.39-.49-.67-.9-.67h-.28c-.67 0-1.14.68-.89 1.3c.57 1.39 1.9 2.21 3.4 2.53v.67c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-.65c1.95-.37 3.5-1.5 3.5-3.55c0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
                                                                    } elseif ($currency_code == 'EUR') {
                                                                        $curr = "Euro";
                                                                        echo '<svg class="text-darkmod" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 20 20"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M12.489 4C9.43 4 7 6.213 7 9.5c0 3.386 2.527 6 5.489 6c.743 0 1.451-.161 2.098-.454a1 1 0 1 1 .826 1.821a7.061 7.061 0 0 1-2.924.633C8.283 17.5 5 13.845 5 9.5C5 5.055 8.38 2 12.489 2c1.237 0 2.428.393 3.574 1.174a1 1 0 1 1-1.126 1.652C14.08 4.242 13.274 4 12.489 4Z"/><path d="M3 8a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm0 3.5a1 1 0 0 1 1-1h8a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Z"/></g></svg>';
                                                                    } elseif ($currency_code == 'BTC') {
                                                                        $curr = "BTC";
                                                                        echo '<svg class="text-darkmod" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="currentColor" d="M5 21q-.825 0-1.413-.588T3 19V4q0-.425.288-.713T4 3q.425 0 .713.288T5 4v15h15q.425 0 .713.288T21 20q0 .425-.288.713T20 21H5Zm2-3q-.425 0-.713-.288T6 17v-7q0-.425.288-.713T7 9h2q.425 0 .713.288T10 10v7q0 .425-.288.713T9 18H7Zm5 0q-.425 0-.713-.288T11 17V5q0-.425.288-.713T12 4h2q.425 0 .713.288T15 5v12q0 .425-.288.713T14 18h-2Zm5 0q-.425 0-.713-.288T16 17v-3q0-.425.288-.713T17 13h2q.425 0 .713.288T20 14v3q0 .425-.288.713T19 18h-2Z"/></svg>';
                                                                    } elseif ($currency_code == 'GA') {
                                                                        $curr = "Altın";
                                                                        echo '<svg class="text-darkmod" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="currentColor" d="m1 22l1.5-5h7l1.5 5H1m12 0l1.5-5h7l1.5 5H13m-7-7l1.5-5h7l1.5 5H6m17-8.95l-3.86 1.09L18.05 11l-1.09-3.86l-3.86-1.09l3.86-1.09l1.09-3.86l1.09 3.86L23 6.05Z"/></svg>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <div class="col px-0 borsa-text">
                                                                    <span class="title"><?php echo $curr; ?></span>
                                                                    <span class="context">
                                                                        <span
                                                                            class="value"><?php echo number_format($result->satis, 2, ',', '.'); ?></span>

                                                                        <?php if (isset($result->d_yon) && $result->d_yon === 'caret-up') {
                                                                            echo '<svg style="transform:rotate(0deg)" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 20 22"><g id="evaArrowUpFill0"><g id="evaArrowUpFill1"><path id="evaArrowUpFill2" fill="#09c922" d="M16.21 16H7.79a1.76 1.76 0 0 1-1.59-1a2.1 2.1 0 0 1 .26-2.21l4.21-5.1a1.76 1.76 0 0 1 2.66 0l4.21 5.1A2.1 2.1 0 0 1 17.8 15a1.76 1.76 0 0 1-1.59 1Z"></path></g></g></svg>';
                                                                        } else {
                                                                            echo '<svg style="transform:rotate(180deg)" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 20 22"><g id="evaArrowUpFill0"><g id="evaArrowUpFill1"><path id="evaArrowUpFill2" fill="#c90914" d="M16.21 16H7.79a1.76 1.76 0 0 1-1.59-1a2.1 2.1 0 0 1 .26-2.21l4.21-5.1a1.76 1.76 0 0 1 2.66 0l4.21 5.1A2.1 2.1 0 0 1 17.8 15a1.76 1.76 0 0 1-1.59 1Z"></path></g></g></svg>';
                                                                        } ?>

                                                                        <small
                                                                            class="text-<?php echo (isset($result->d_yon) && $result->d_yon === 'caret-up' ? 'green' : 'red'); ?>">%<?php echo isset($result->degisim) ? $result->degisim : ''; ?></small>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach;
                                                }
                                                wp_reset_postdata();
                                                ?>



                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6 px-lg-0 align-self-center">

                                        <a href="<?php bloginfo('url') ?>/havadurumu" alt="Hava Durumu" title="Hava Durumu">
                                            <div id="header-havadurumu" class="row align-items-center citySelector m-0">
                                                <?php display_havadurumu_temperature(); ?>
                                            </div>
                                        </a>


                                    </div>
                                </div>

                            </div>

                        </div>



                    </div>
                </div>
            <?php endif; ?>
        </nav>
        <!-- ============= COMPONENT END// ============== -->

    </header>



    <?php /*
<div id="page" class="site">

<header id="masthead" class="site-header">
<div class="site-branding">
<?php
the_custom_logo();
if ( is_front_page() && is_home() ) :
 ?>
 <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
 <?php
else :
 ?>
 <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
 <?php
endif;
$mevzu2_description = get_bloginfo( 'description', 'display' );
if ( $mevzu2_description || is_customize_preview() ) :
 ?>
 <p class="site-description"><?php echo $mevzu2_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php endif; ?>
</div><!-- .site-branding -->

<nav id="site-navigation" class="main-navigation">
<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Primary Menu', 'mevzu2' ); ?></button>
<?php
wp_nav_menu(
 array(
     'theme_location' => 'menu-1',
     'menu_id'        => 'primary-menu',
 )
);
?>
</nav><!-- #site-navigation -->
</header><!-- #masthead -->
*/ ?>