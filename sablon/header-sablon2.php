<!-- <button data-trigger="navbar_main" class="btn shadow-none link-dark d-inline-block px-0" type="button">
    <i class="fas fa-bars fa-2x"></i>
</button> -->
<header class="header2">
    <div class="shadow-sm bg-white rounded-bottom">
        <div class="container">
            <div class="row align-items-center d-lg-none py-2 mobil-header">
                <div class="col-auto border-end">
                    <a href="#" data-trigger="navbar_main" class="nav-link text-hover" type="button">
                        <i class="ri-menu-unfold-4-line fz-20"></i>
                    </a>
                </div>
                <div class="col">
                    <a href="<?php bloginfo('url'); ?>">
                        <?php render_mevzu_logo('mobil', '40px'); ?>
                        <h1 class="d-none"><?php bloginfo('title'); ?></h1>
                    </a>
                </div>
                <div class="col-auto">
                    <button id="darkModeToggleButton" class="dark-mode-toggle" aria-label="Karanlık modu aç veya kapat"></button>
                </div>
                <div class="col-auto ps-0">
                    <a href="#ara" type="button" class="btn btn-dark p-2 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#aramaYap" style="line-height: 23px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2.5"
                                d="m21 21l-4.343-4.343m0 0A8 8 0 1 0 5.343 5.343a8 8 0 0 0 11.314 11.314Z"></path>
                        </svg>
                        <span class="fw-semibold small ps-1">Haber Ara</span>
                    </a>
                </div>
            </div>
        </div>
        <?php wp_nav_menu(array(
            'theme_location' => 'mobil-menu',
            'container' => false,
            'menu_class' => '',
            'fallback_cb' => '__return_false',
            'items_wrap' => '<ul id="%1$s" class="%2$s d-lg-none mobil-menu m-0">%3$s</ul>',
            'depth' => 0,
            'walker' => new bootstrap_5_wp_nav_menu_walker()
        )); ?>
    </div>
    <!-- ============= MOBILE ============== -->
    <nav id="navbar_main" class="mobile-offcanvas navbar navbar-expand-lg bg-white pt-2" <?= (is_user_logged_in() ? ' style="top:var(--mevzu-offcanvas)"' : '') ?>>
        <div class="d-block">

            <a href="<?php bloginfo('url'); ?>" class="d-block pt-1 text-center">
                <?php render_mevzu_logo('mobil', '40px'); ?>
                <h1 class="d-none"><?php bloginfo('title'); ?></h1>
            </a>

            <div class="row align-items-center justify-content-center text-center mt-3">
                <div class="col-auto">
                    <?php if (get_option('options_facebook')): ?>
                        <a href="<?php echo get_option('options_facebook'); ?>" title="Facebook" target="_blank"
                            class="d-block text-link">
                            <svg class="m-0" xmlns="http://www.w3.org/2000/svg" width="30" height="20"
                                viewBox="0 0 512 512">
                                <path fill="currentColor"
                                    d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48c27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <?php if (get_option('options_twitter')): ?>
                        <a href="<?php echo get_option('options_twitter'); ?>" title="Twitter" target="_blank"
                            class="d-block text-link">
                            <svg class="m-0" xmlns="http://www.w3.org/2000/svg" width="30" height="20" viewBox="0 0 16 16">
                                <path fill="currentColor"
                                    d="M9.294 6.928L14.357 1h-1.2L8.762 6.147L5.25 1H1.2l5.31 7.784L1.2 15h1.2l4.642-5.436L10.751 15h4.05zM7.651 8.852l-.538-.775L2.832 1.91h1.843l3.454 4.977l.538.775l4.491 6.47h-1.843z" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <?php if (get_option('options_instagram')): ?>
                        <a href="<?php echo get_option('options_instagram'); ?>" title="Instagram" target="_blank"
                            class="d-block text-link">
                            <svg class="m-0" xmlns="http://www.w3.org/2000/svg" width="27.5" height="20"
                                viewBox="0 0 448 512">
                                <path fill="currentColor"
                                    d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9S287.7 141 224.1 141m0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7s74.7 33.5 74.7 74.7s-33.6 74.7-74.7 74.7m146.4-194.3c0 14.9-12 26.8-26.8 26.8c-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8s26.8 12 26.8 26.8m76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9c-26.2-26.2-58-34.4-93.9-36.2c-37-2.1-147.9-2.1-184.9 0c-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9c1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0c35.9-1.7 67.7-9.9 93.9-36.2c26.2-26.2 34.4-58 36.2-93.9c2.1-37 2.1-147.8 0-184.8M398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6c-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6c-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6c29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6c11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <?php if (get_option('options_youtube')): ?>
                        <a href="<?php echo get_option('options_youtube'); ?>" title="Youtube" target="_blank"
                            class="d-block text-link">
                            <svg class="m-0" xmlns="http://www.w3.org/2000/svg" width="32.5" height="20"
                                viewBox="0 0 576 512">
                                <path fill="currentColor"
                                    d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597c-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821c11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305m-317.51 213.508V175.185l142.739 81.205z" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mx-2 border-top pt-3 mt-3">
                <div class="col-12 ms-auto">
                    <?php wp_nav_menu(array(
                        'theme_location' => 'ust-menu',
                        'container' => false,
                        'menu_class' => '',
                        'fallback_cb' => '__return_false',
                        'items_wrap' => '<ul id="%1$s" class="navbar-nav nav-mobil %2$s">%3$s</ul>',
                        'depth' => 2,
                        'walker' => new bootstrap_5_wp_nav_menu_walker()
                    )); ?>
                </div>
                <div class="col">
                    <hr class="border-color-primary">
                    <?php wp_nav_menu(array(
                        'theme_location' => 'main-menu',
                        'container' => false,
                        'menu_id' => 'mobil_menu',
                        'menu_class' => 'mt-0',
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
            <div class="header-ust header-bg">
                <div class="container">
                    <div class="row justify-content-between text-muted align-items-center my-2 fz-13">
                        <div class="col-auto">
                            <ul class="navbar-nav align-items-center fw-semibold gap-3">
                                <?php wp_nav_menu(array(
                                    'theme_location' => 'ust-menu',
                                    'container' => false,
                                    'menu_class' => '',
                                    'fallback_cb' => '__return_false',
                                    'items_wrap' => '%3$s',
                                    'depth' => 2,
                                    'walker' => new bootstrap_5_wp_nav_menu_walker()
                                )); ?>
                            </ul>
                        </div>
                        <div class="col header-ust-sag">
                            <?php
                            $veri = get_doviz_data();
                            $kurlar = get_option('options_kur_secimi', ['USD', 'EUR', 'GA']);
                            $secili_kurlar = [];

                            if (is_array($kurlar)) {
                                foreach ($kurlar as $kur) {
                                    if (isset($veri['Rates'][$kur])) {
                                        $secili_kurlar[$kur] = $veri['Rates'][$kur];
                                    }
                                }
                            }

                            if (!empty($secili_kurlar)) {
                                $chunks = array_chunk($secili_kurlar, 3, true); // 3'erli gruplara ayır
                                ?>
                                <div class="swiper" id="swiper-dovizler">
                                    <div class="swiper-wrapper">
                                        <?php foreach ($chunks as $group) { ?>
                                            <div class="swiper-slide">
                                                <div class="row align-items-center justify-content-end gx-2">
                                                    <?php foreach ($group as $kur => $bilgi) { ?>
                                                        <div class="col-auto borsa-text">
                                                            <a href="<?= bloginfo('url') ?>/finans/<?php echo mb_strtoupper($kur); ?>">
                                                                <span class="title text-secondary"><?php echo $kur; ?></span>
                                                                <span class="value text-dark fw-semibold">
                                                                    <?php echo number_format($bilgi['Selling'], ($bilgi['Type'] == 'Currency' ? 2 : 0), ',', '.'); ?>
                                                                    TL
                                                                </span>
                                                                <?php if ($bilgi['Change'] > 0) {
                                                                    echo '<svg style="transform:rotate(0deg)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 22"><path fill="#09c922" d="M16.21 16H7.79a1.76 1.76 0 0 1-1.59-1a2.1 2.1 0 0 1 .26-2.21l4.21-5.1a1.76 1.76 0 0 1 2.66 0l4.21 5.1A2.1 2.1 0 0 1 17.8 15a1.76 1.76 0 0 1-1.59 1Z"/></svg>';
                                                                } else {
                                                                    echo '<svg style="transform:rotate(180deg)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 22"><path fill="#c90914" d="M16.21 16H7.79a1.76 1.76 0 0 1-1.59-1a2.1 2.1 0 0 1 .26-2.21l4.21-5.1a1.76 1.76 0 0 1 2.66 0l4.21 5.1A2.1 2.1 0 0 1 17.8 15a1.76 1.76 0 0 1-1.59 1Z"/></svg>';
                                                                } ?>
                                                            </a>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="header-bg py-2">
            <div class="container">

                <form action="" class="p-0 mt-minus-5 mobil_nav_aramaform d-lg-none">
                    <input type="text" class="form-control border-0" placeholder="Search" aria-label="Search"
                        aria-describedby="search-addon" />
                    <button type="submit" class="d-none"></button>
                </form>

                <div class="row align-items-center">

                    
                    <div class="col-12 col-md-auto">
                        <a class="navbar-brand d-none d-lg-flex" href="<?php bloginfo('url'); ?>">
                            <?php render_mevzu_logo('desktop', '48px'); ?>
                        </a>
                    </div>


                    <div class="col-auto d-none d-lg-flex text-center site-header-top-center ms-auto">
                        <div class="site-header-top-center">
                            <form role="search" method="get" class="site-header-search-form"
                                action="<?php echo esc_url(home_url('/')); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2.5"
                                        d="m21 21l-4.343-4.343m0 0A8 8 0 1 0 5.343 5.343a8 8 0 0 0 11.314 11.314Z">
                                    </path>
                                </svg>
                                <input data-style="row" type="text" placeholder="Haberlerde ara..." value="" name="s"
                                    autocomplete="off">
                                <button type="submit">Ara</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-auto justify-self-end">
                        <div class="row align-items-center mb-0 g-2">
                            <div class="col-auto">
                                <div class="dugme rounded-2 d-flex align-items-center justify-content-center" style="width: 41.59px;height: 41.59px;">
                                    <label for="darkModeToggleInput1" class="nav-link p-2 h-100 form-check-input-darkmode-label text-dark text-center position-relative d-block w-100">
                                        <input type="checkbox" class="form-check-input-darkmode d-none" id="darkModeToggleInput1">
                                    </label>
                                </div>
                            </div>
                            <div class="col-auto dropdown">
                                <div class="dugme rounded-2 d-flex align-items-center justify-content-center" style="width: 41.59px; height: 41.59px;" data-bs-toggle="dropdown" aria-expanded="false" role="button" aria-label="Kullanıcı menüsünü aç">
                                    <a class="nav-link p-0 h-100 text-dark text-hover text-center d-flex align-items-center justify-content-center w-100" aria-label="Kullanıcı menüsü">
                                        <i class="ri-user-line fz-16"></i>
                                    </a>
                                </div>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="--mevzu-position: end;" data-popper-placement="null" data-bs-popper="static">
                                    <?php if ( is_user_logged_in() ) : ?>
                                        <li><h6 class="dropdown-header text-uppercase fz-11 fw-bold tracking-wide">Genel</h6></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/pano') ); ?>"><i class="ri-dashboard-line me-2 fs-6"></i> <span class="small">Pano</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/akis') ); ?>"><i class="ri-flashlight-fill me-2 fs-6"></i> <span class="small">Akış</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/bildirimlerim') ); ?>"><i class="ri-notification-3-line me-2 fs-6"></i><span class="small">Bildirimlerim</span></a></li>
                                        
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><h6 class="dropdown-header text-uppercase fz-11 fw-bold tracking-wide">İçerikler</h6></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/kaydedilenler') ); ?>"><i class="ri-bookmark-line me-2 fs-6"></i> <span class="small">Kaydedilen Haberler</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/begenilenler') ); ?>"><i class="ri-heart-3-line me-2 fs-6"></i> <span class="small">Beğenilen Haberler</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/yorumlarim') ); ?>"><i class="ri-chat-3-line me-2 fs-6"></i> <span class="small">Yorumlarım</span></a></li>

                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><h6 class="dropdown-header text-uppercase fz-11 fw-bold tracking-wide">Aboneliklerim</h6></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/takip') ); ?>"><i class="ri-folder-open-line me-2 fs-6"></i> <span class="small">Takip Ettiğim Kategoriler</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/etkilesimler') ); ?>"><i class="ri-hashtag me-2 fs-6"></i> <span class="small">Takip Ettiğim Etiketler</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/takipyazar') ); ?>"><i class="ri-quill-pen-line me-2 fs-6"></i> <span class="small">Takip Ettiğim Yazarlar</span></a></li>

                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><h6 class="dropdown-header text-uppercase fz-11 fw-bold tracking-wide">Hesap</h6></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/profil') ); ?>"><i class="ri-user-settings-line me-2 fs-6"></i> <span class="small">Profil Ayarları</span></a></li>
                                        <li><a class="dropdown-item px-3 text-danger py-2 d-flex align-items-center" href="<?php echo wp_logout_url(home_url()); ?>"><i class="ri-logout-box-line me-2 fs-6"></i> <span class="small">Çıkış Yap</span></a></li>
                                    <?php else : ?>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/giris') ); ?>"><i class="ri-login-box-line me-2 fs-6"></i> <span class="small">Giriş Yap</span></a></li>
                                        <li><a class="dropdown-item px-3 py-2 d-flex align-items-center" href="<?php echo esc_url( home_url('/hesabim/kayit') ); ?>"><i class="ri-user-add-line me-2 fs-6"></i> <span class="small">Kayıt Ol</span></a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-auto dropdown">
                                <?php 
                                $unread_count = 0;
                                if ( is_user_logged_in() && function_exists('mevzu_get_unread_notifications_count') ) {
                                    $unread_count = mevzu_get_unread_notifications_count( get_current_user_id() );
                                }
                                ?>
                                <div class="dugme rounded-6 d-flex align-items-center justify-content-center" style="width: 41.59px;" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" role="button" aria-label="Bildirim menüsünü aç">
                                    <a class="nav-link p-2 h-100 text-dark text-hover text-center position-relative" aria-label="Bildirimler">
                                        <i class="ri-notification-2-line fz-16"></i>
                                        <span class="position-absolute top-1 start-1 translate-middle badge rounded-pill bg-danger mevzu-notif-badge fz-8 w-12 h-10<?php echo $unread_count === 0 ? ' d-none' : ''; ?>">
                                            <?php echo $unread_count; ?>
                                        </span>
                                    </a>
                                </div>
                                <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-0" style="width: 350px; max-height: 480px; overflow-y: auto;" data-popper-placement="null" data-bs-popper="static">
                                    <?php if ( is_user_logged_in() ) : ?>
                                        <div class="px-1 py-2">
                                            <ul class="nav nav-pills nav-fill px-2 gap-2" id="notif-pills" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link mx-1 p-2 d-block m-0 w-100 small-2 fw-semibold active" id="cat-notif-tab" data-bs-toggle="pill" data-bs-target="#cat-notif" type="button" role="tab" aria-selected="true">Takip Ettiklerim</button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link mx-1 p-2 d-block m-0 w-100 small-2 fw-semibold" id="com-notif-tab" data-bs-toggle="pill" data-bs-target="#com-notif" type="button" role="tab" aria-selected="false">Yanıtlar</button>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content" id="notif-pills-content">
                                            <div class="tab-pane fade show active" id="cat-notif" role="tabpanel">
                                                <div class="list-group list-group-flush">
                                                    <?php 
                                                    if( function_exists('mevzu_get_user_notifications') ) {
                                                        $cat_notifs = mevzu_get_user_notifications( get_current_user_id(), 'category_post', 15 );
                                                        if ( empty($cat_notifs) ) {
                                                            echo '<div class="p-4 text-muted text-center small"><i class="ri-notification-off-line fz-18 d-block mb-2"></i>Bildiriminiz bulunmuyor.</div>';
                                                        } else {
                                                            foreach ( $cat_notifs as $n ) {
                                                                $is_unread = ($n->is_read == 0) ? 'unread bg-info bg-opacity-10' : '';
                                                                echo '<div class="list-group-item list-group-item-action py-3 mevzu-notification-item ' . $is_unread . '" data-notif-id="'.$n->id.'">';
                                                                echo '<div class="small fw-medium mb-1">' . wp_kses_post($n->message) . '</div>';
                                                                echo '<small class="text-muted"><i class="ri-time-line align-middle"></i> ' . human_time_diff(strtotime($n->date_created), current_time('timestamp')) . ' önce</small>';
                                                                echo '</div>';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="com-notif" role="tabpanel">
                                                <div class="list-group list-group-flush">
                                                    <?php 
                                                    if( function_exists('mevzu_get_user_notifications') ) {
                                                        $com_notifs = mevzu_get_user_notifications( get_current_user_id(), 'comment_reply', 15 );
                                                        if ( empty($com_notifs) ) {
                                                            echo '<div class="p-4 text-muted text-center small"><i class="ri-notification-off-line fz-18 d-block mb-2"></i>Bildiriminiz bulunmuyor.</div>';
                                                        } else {
                                                            foreach ( $com_notifs as $n ) {
                                                                $is_unread = ($n->is_read == 0) ? 'unread bg-info bg-opacity-10' : '';
                                                                echo '<div class="list-group-item list-group-item-action py-3 mevzu-notification-item ' . $is_unread . '" data-notif-id="'.$n->id.'">';
                                                                echo '<div class="small fw-medium mb-1">' . wp_kses_post($n->message) . '</div>';
                                                                echo '<small class="text-muted"><i class="ri-time-line align-middle"></i> ' . human_time_diff(strtotime($n->date_created), current_time('timestamp')) . ' önce</small>';
                                                                echo '</div>';
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border-top p-2 text-center">
                                            <a href="<?php echo esc_url( home_url('/hesabim/bildirimlerim') ); ?>" class="btn btn-sm btn-outline-primary w-100 fw-semibold small"><i class="ri-arrow-right-line me-1"></i>Tümünü Göster</a>
                                        </div>
                                    <?php else : ?>
                                        <div class="p-4 text-center">
                                            <i class="ri-shield-user-line fz-18 text-muted d-block mb-2"></i>
                                            <p class="mb-3 small fw-semibold">Bildirimleri görebilmek için giriş yapın.</p>
                                            <a href="<?php echo esc_url( home_url('/hesabim/giris') ); ?>" class="btn btn-primary btn-sm w-100 mb-2">Giriş Yap</a>
                                            <a href="<?php echo esc_url( home_url('/hesabim/kayit') ); ?>" class="text-muted small">Hesabınız yok mu? Kayıt olun</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-auto dropdown">
                                <?php display_havadurumu_temperature($sablon = 'sablon-1'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if (get_post_type() != 'resmi-ilanlar'): ?>
            <div class="header-alt header">
                <div class="container">
                    <div class="row g-0 position-relative">
                        <div class="col-auto px-0 position-relative">
                            <ul id="menu-mevzu2-ana-menu" class="navbar-nav d-flex justify-content-between align-items-center me-lg-1">
                                <li class="nav-item">
                                    <a href="<?php bloginfo('url'); ?>" class="link-home nav-link d-flex align-items-center p-1 ps-0" aria-label="Ana sayfaya git">
                                        <i class="ri-home-3-line fz-20"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-auto ps-0 <?php echo (get_option('options_header_menu')  ?: 'me-auto'); ?>">
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'main-menu',
                                'container' => false,
                                'menu_class' => '',
                                'fallback_cb' => '__return_false',
                                'items_wrap' => '<ul id="%1$s" class="navbar-nav d-flex justify-content-between align-items-center %2$s">%3$s</ul>',
                                'depth' => 2,
                                'walker' => new bootstrap_5_wp_nav_menu_walker()
                            ));
                            ?>
                        </div>
                        <div class="col-auto px-0">
                            <ul id="menu-ana-menu-1" class="navbar-nav d-flex justify-content-between align-items-center gap-2">
                                <li
                                    class="menu-item nav-item">
                                    <a href="<?php echo esc_url( home_url('/akis') ); ?>"
                                        class="nav-link sagMenu d-flex align-items-center">
                                        <i class="ri-flashlight-fill me-1 text-primary fz-15"></i>
                                        Akış
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="nav-link d-flex align-items-center dropdown-toggle ps-1 pe-0" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false" aria-label="Diğer sayfalar menüsünü aç">
                                        <i class="ri-apps-fill fz-18"></i>
                                        <!-- Diğer Sayfalar -->
                                    </a>
                                    <div class="dropdown-menu p-3"
                                        style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                                        <div class="row header-section1">
                                            <?php
                                            $varsayilan_sehir = get_option('options_varsayilan_sehir');
                                            $sayfa_basliklari = array('Son Dakika', 'Hava Durumu', 'Namaz Vakitleri', 'Yol Durumu', 'Nöbetçi Eczaneler');

                                            $bulunan_sayfalar = array();
                                            foreach ($sayfa_basliklari as $baslik) {
                                                $sayfa = get_page_by_title($baslik);
                                                if ($sayfa) {
                                                    $bulunan_sayfalar[] = array('sayfa' => $sayfa, 'baslik' => $baslik);
                                                }
                                            }

                                            if (!empty($bulunan_sayfalar)) {
                                                echo '<div class="col-12 col-md-auto">
                                                    <h5 class="fw-semibold text-dark fz-14 m-0">Hızlı Aramalar</h5>
                                                    <div class="d-flex justify-content-evenly flex-column h-100">';
                                                        foreach ($bulunan_sayfalar as $item) {
                                                            echo '<a href="' . get_permalink($item['sayfa']->ID) . '" class="dropdown-item rounded py-2 pe-3"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76"/></svg>';
                                                            echo $varsayilan_sehir . ' ' . $item['baslik'];
                                                            echo '</a>';
                                                        }
                                                echo '</div>
                                                </div>';
                                            }
                                            ?>
                                            <?php
                                            if (!empty($bulunan_sayfalar)) {
                                                echo '<div class="col border-start border-end mx-lg-3 px-lg-4 d-flex flex-column">';
                                                $sayfa_sayisi = count($bulunan_sayfalar);

                                                $menu_name = 'ust-menu';
                                                $locations = get_nav_menu_locations();
                                                if (isset($locations[$menu_name])) {
                                                    $menu = wp_get_nav_menu_object($locations[$menu_name]);
                                                    if ($menu) {
                                                        $menu_items = wp_get_nav_menu_items($menu->term_id);
                                                        if ($menu_items) {
                                                            echo '<h5 class="fw-semibold text-dark fz-14 col-12">Diğer Kategoriler</h5>';
                                                            echo '<div class="row">';

                                                            // Her 5 öğede bir yeni col açılmalı
                                                            $chunks = array_chunk($menu_items, 5);

                                                            foreach ($chunks as $chunk) {
                                                                echo '<div class="col">';
                                                                foreach ($chunk as $item) {
                                                                    echo '<a href="' . $item->url . '" class="text-link fw-normal d-block">';
                                                                    echo $item->title;
                                                                    echo '</a>';
                                                                }
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        }
                                                    }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <?php
                                            echo '<div class="col-12 col-md-4 d-flex flex-column justify-content-between">';

                                            // Ayın en çok okunan haberi - Transient ile cache
                                            echo '<h5 class="fw-semibold text-dark fz-14">
                                        <svg class="text-primary me-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11 8l1.5-1.5v4"/><path d="m19 5l.949.316c.99.33 1.485.495 1.768.888S22 7.12 22 8.162v.073c0 .86 0 1.291-.207 1.643s-.584.561-1.336.98L17.5 12.5M5 5l-.949.316c-.99.33-1.485.495-1.768.888S2 7.12 2 8.162v.073c0 .86 0 1.291.207 1.643s.584.561 1.336.98L6.5 12.5"/><path stroke-linecap="round" d="M12 16v3"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 22h-7l.34-1.696a1 1 0 0 1 .98-.804h4.36a1 1 0 0 1 .98.804z"/><path stroke-linecap="round" d="M18 22H6M17 2.456c.741.141 1.181.297 1.56.765c.477.586.452 1.219.401 2.485c-.18 4.553-1.2 10.294-6.96 10.294S5.22 10.26 5.038 5.706c-.05-1.266-.075-1.9.4-2.485c.476-.586 1.045-.682 2.184-.874A26.4 26.4 0 0 1 12 2q1.078.002 2 .068"/></g></svg>
                                        Ayın En Çok Okunanı</h5>';

                                            $monthly_transient_key = 'ayin_en_cok_okunan_haberi_v2';
                                            $monthly_post_ids = get_transient($monthly_transient_key);

                                            if (false === $monthly_post_ids || !is_array($monthly_post_ids)) {
                                                $monthly_args = array(
                                                    'post_type'              => 'post',
                                                    'post_status'            => 'publish',
                                                    'posts_per_page'         => 1,
                                                    'ignore_sticky_posts'    => true,
                                                    'no_found_rows'          => true,
                                                    'fields'                 => 'ids',
                                                    'update_post_term_cache' => false,
                                                    'update_post_meta_cache' => false,
                                                    'date_query'             => array(
                                                        array(
                                                            'after' => '1 month ago',
                                                        ),
                                                    ),
                                                    'meta_key'               => 'views_count',
                                                    'orderby'                => 'meta_value_num',
                                                    'order'                  => 'DESC',
                                                );

                                                $monthly_post_ids = get_posts($monthly_args);
                                                if (!is_array($monthly_post_ids)) {
                                                    $monthly_post_ids = array();
                                                }
                                                set_transient($monthly_transient_key, $monthly_post_ids, 1 * HOUR_IN_SECONDS);
                                            }

                                            if (!empty($monthly_post_ids)) {
                                                foreach ($monthly_post_ids as $monthly_post_id) {
                                                    $monthly_post_id = absint($monthly_post_id);
                                                    if (!$monthly_post_id) {
                                                        continue;
                                                    }
                                                    $monthly_title = get_the_title($monthly_post_id);
                                                    $monthly_link  = get_permalink($monthly_post_id);
                                                    if (!$monthly_title || !$monthly_link) {
                                                        continue;
                                                    }
                                                    echo '<a href="' . esc_url($monthly_link) . '" class="text-link fw-normal d-block satir-2">';
                                                    echo esc_html($monthly_title);
                                                    echo '</a>';
                                                }
                                            }

                                            echo '<hr class="my-3">';

                                            // Haftanın okunan 3 postu - Transient ile cache
                                            echo '<h5 class="fw-semibold text-dark fz-14">
                                        <svg class="text-primary me-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M16.5 8c0 1.5-.5 3.5-2.9 4.3c.7-1.7.8-3.4.3-5c-.7-2.1-3-3.7-4.6-4.6c-.4-.3-1.1.1-1 .7c0 1.1-.3 2.7-2 4.4C4.1 10 3 12.3 3 14.5C3 17.4 5 21 9 21c-4-4-1-7.5-1-7.5c.8 5.9 5 7.5 7 7.5c1.7 0 5-1.2 5-6.4c0-3.1-1.3-5.5-2.4-6.9c-.3-.5-1-.2-1.1.3"/></svg>
                                        Haftanın En Çok Okunanları</h5>';

                                            $weekly_transient_key = 'haftanin_en_cok_okunan_haberleri_v2';
                                            $weekly_post_ids = get_transient($weekly_transient_key);

                                            if (false === $weekly_post_ids || !is_array($weekly_post_ids)) {
                                                $args = array(
                                                    'post_type'              => 'post',
                                                    'post_status'            => 'publish',
                                                    'posts_per_page'         => 2,
                                                    'ignore_sticky_posts'    => true,
                                                    'no_found_rows'          => true,
                                                    'fields'                 => 'ids',
                                                    'update_post_term_cache' => false,
                                                    'update_post_meta_cache' => false,
                                                    'date_query'             => array(
                                                        array(
                                                            'after' => '1 week ago',
                                                        ),
                                                    ),
                                                    'meta_key'               => 'views_count',
                                                    'orderby'                => 'meta_value_num',
                                                    'order'                  => 'DESC',
                                                );

                                                $weekly_post_ids = get_posts($args);
                                                if (!is_array($weekly_post_ids)) {
                                                    $weekly_post_ids = array();
                                                }
                                                set_transient($weekly_transient_key, $weekly_post_ids, 1 * HOUR_IN_SECONDS);
                                            }

                                            if (!empty($weekly_post_ids)) {
                                                foreach ($weekly_post_ids as $weekly_post_id) {
                                                    $weekly_post_id = absint($weekly_post_id);
                                                    if (!$weekly_post_id) {
                                                        continue;
                                                    }
                                                    $weekly_title = get_the_title($weekly_post_id);
                                                    $weekly_link  = get_permalink($weekly_post_id);
                                                    if (!$weekly_title || !$weekly_link) {
                                                        continue;
                                                    }
                                                    echo '<a href="' . esc_url($weekly_link) . '" class="text-link fw-normal d-block satir-2">';
                                                    echo esc_html($weekly_title);
                                                    echo '</a>';
                                                }
                                            }

                                            echo '</div>';
                                            ?>
                                        </div>
                                        <div class="row align-items-center border-top mt-3 pt-3 small">
                                            <div class="col d-flex align-items-center">
                                                <?php $array = array(get_option('options_gizlilik_politikasi_sayfasi'), get_option('options_kunye_sayfasi'), get_option('options_iletisim_sayfasi'));
                                                $count = 0;
                                                foreach ($array as $yazdir) {
                                                    echo ($count != 0 ? '<span class="text-muted opacity-50 cursor-default px-3">•</span>' : '') . '<a class="ripple text-link d-block" data-bs-ripple-color="light"  href="' . get_permalink($yazdir) . '" title="' . get_the_title($yazdir) . '">' . get_the_title($yazdir) . '</a>';
                                                    $count++;
                                                }
                                                ?>
                                            </div>
                                            <div class="col-auto"><?php echo do_shortcode('[header_takipedin]'); ?></div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <div id="stickyNavbar" class="bg-white header-alt header shadow-sm" <?php if (is_user_logged_in())
        echo ' style="margin-top: var(--mevzu-offcanvas-sticky);"' ?>>
            <div class="container">
            <?php if (!is_single()): ?>
                <nav class="navbar navbar-expand-lg navbar-light py-0 shadow-none d-none d-md-block">
                    <div id="scrollProgress"></div>
                    <div class="row align-items-center mx-0">
                        <div class="col-auto position-relative">
                            <a href="<?php bloginfo('url'); ?>" class="link-home nav-link d-flex align-items-center py-1">
                                <i class="ri-home-3-line fz-20"></i>
                            </a>
                        </div>
                        <div class="col-auto <?php echo (get_option('options_header_menu')  ?: 'me-auto'); ?>">
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'main-menu',
                                'container' => false,
                                'menu_class' => '',
                                'fallback_cb' => '__return_false',
                                'items_wrap' => '<ul id="%1$s" class="navbar-nav d-flex justify-content-between align-items-center %2$s">%3$s</ul>',
                                'depth' => 2,
                                'walker' => new bootstrap_5_wp_nav_menu_walker()
                            ));
                            ?>
                        </div>
                        <div class="col-auto px-0">
                        <ul id="menu-ana-menu-1" class="navbar-nav d-flex justify-content-between align-items-center gap-2">
                                <li
                                    class="menu-item nav-item">
                                    <a href="<?php echo esc_url( home_url('/akis') ); ?>"
                                        class="nav-link sagMenu d-flex align-items-center">
                                        <i class="ri-flashlight-fill me-1 text-primary fz-15"></i>
                                        Akış
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="nav-link d-flex align-items-center dropdown-toggle ps-1 pe-0" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false" aria-label="Diğer sayfalar menüsünü aç">
                                        <i class="ri-apps-fill fz-18"></i>
                                        <!-- Diğer Sayfalar -->
                                    </a>
                                    <div class="dropdown-menu p-3 border-0"
                                        style="box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                                        <div class="row header-section1 small">
                                            <?php
                                            $varsayilan_sehir = get_option('options_varsayilan_sehir');
                                            $sayfa_basliklari = array('Son Dakika', 'Hava Durumu', 'Namaz Vakitleri', 'Yol Durumu', 'Nöbetçi Eczaneler');

                                            $bulunan_sayfalar = array();
                                            foreach ($sayfa_basliklari as $baslik) {
                                                $sayfa = get_page_by_title($baslik);
                                                if ($sayfa) {
                                                    $bulunan_sayfalar[] = array('sayfa' => $sayfa, 'baslik' => $baslik);
                                                }
                                            }

                                            if (!empty($bulunan_sayfalar)) {
                                                echo '<div class="col-12 col-md-auto">
                                            <h5 class="fw-semibold text-dark fz-14">Hızlı Aramalar</h5>
                                            <ul class="list-unstyled">';
                                                foreach ($bulunan_sayfalar as $item) {
                                                    echo '<li>';
                                                    echo '<a href="' . get_permalink($item['sayfa']->ID) . '" class="dropdown-item rounded p-0 pe-2"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76"/></svg>';
                                                    echo $varsayilan_sehir . ' ' . $item['baslik'];
                                                    echo '</a>';
                                                    echo '</li>';
                                                }
                                                echo '</ul></div>';
                                            }
                                            ?>
                                            <?php
                                            if (!empty($bulunan_sayfalar)) {
                                                echo '<div class="col border-start border-end mx-lg-3 px-lg-4 d-flex flex-column">';
                                                $sayfa_sayisi = count($bulunan_sayfalar);

                                                $menu_name = 'ust-menu';
                                                $locations = get_nav_menu_locations();
                                                if (isset($locations[$menu_name])) {
                                                    $menu = wp_get_nav_menu_object($locations[$menu_name]);
                                                    if ($menu) {
                                                        $menu_items = wp_get_nav_menu_items($menu->term_id);
                                                        if ($menu_items) {
                                                            echo '<h5 class="fw-semibold text-dark fz-14 col-12">Diğer Kategoriler</h5>';
                                                            echo '<div class="row">';

                                                            // Her 5 öğede bir yeni col açılmalı
                                                            $chunks = array_chunk($menu_items, 5);

                                                            foreach ($chunks as $chunk) {
                                                                echo '<div class="col">';
                                                                foreach ($chunk as $item) {
                                                                    echo '<a href="' . $item->url . '" class="text-link fw-normal d-block">';
                                                                    echo $item->title;
                                                                    echo '</a>';
                                                                }
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        }
                                                    }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                            <?php
                                            echo '<div class="col-12 col-md-4 d-flex flex-column justify-content-between">';

                                            // Ayın en çok okunan haberi - Transient ile cache
                                            echo '<h5 class="fw-semibold text-dark fz-14">
                                        <svg class="text-primary me-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11 8l1.5-1.5v4"/><path d="m19 5l.949.316c.99.33 1.485.495 1.768.888S22 7.12 22 8.162v.073c0 .86 0 1.291-.207 1.643s-.584.561-1.336.98L17.5 12.5M5 5l-.949.316c-.99.33-1.485.495-1.768.888S2 7.12 2 8.162v.073c0 .86 0 1.291.207 1.643s.584.561 1.336.98L6.5 12.5"/><path stroke-linecap="round" d="M12 16v3"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 22h-7l.34-1.696a1 1 0 0 1 .98-.804h4.36a1 1 0 0 1 .98.804z"/><path stroke-linecap="round" d="M18 22H6M17 2.456c.741.141 1.181.297 1.56.765c.477.586.452 1.219.401 2.485c-.18 4.553-1.2 10.294-6.96 10.294S5.22 10.26 5.038 5.706c-.05-1.266-.075-1.9.4-2.485c.476-.586 1.045-.682 2.184-.874A26.4 26.4 0 0 1 12 2q1.078.002 2 .068"/></g></svg>
                                        Ayın En Çok Okunanı</h5>';

                                            $monthly_transient_key = 'ayin_en_cok_okunan_haberi_v2';
                                            $monthly_post_ids = get_transient($monthly_transient_key);

                                            if (false === $monthly_post_ids || !is_array($monthly_post_ids)) {
                                                $monthly_args = array(
                                                    'post_type'              => 'post',
                                                    'post_status'            => 'publish',
                                                    'posts_per_page'         => 1,
                                                    'ignore_sticky_posts'    => true,
                                                    'no_found_rows'          => true,
                                                    'fields'                 => 'ids',
                                                    'update_post_term_cache' => false,
                                                    'update_post_meta_cache' => false,
                                                    'date_query'             => array(
                                                        array(
                                                            'after' => '1 month ago',
                                                        ),
                                                    ),
                                                    'meta_key'               => 'views_count',
                                                    'orderby'                => 'meta_value_num',
                                                    'order'                  => 'DESC',
                                                );

                                                $monthly_post_ids = get_posts($monthly_args);
                                                if (!is_array($monthly_post_ids)) {
                                                    $monthly_post_ids = array();
                                                }
                                                set_transient($monthly_transient_key, $monthly_post_ids, 1 * HOUR_IN_SECONDS);
                                            }

                                            if (!empty($monthly_post_ids)) {
                                                foreach ($monthly_post_ids as $monthly_post_id) {
                                                    $monthly_post_id = absint($monthly_post_id);
                                                    if (!$monthly_post_id) {
                                                        continue;
                                                    }
                                                    $monthly_title = get_the_title($monthly_post_id);
                                                    $monthly_link  = get_permalink($monthly_post_id);
                                                    if (!$monthly_title || !$monthly_link) {
                                                        continue;
                                                    }
                                                    echo '<a href="' . esc_url($monthly_link) . '" class="text-link fw-normal d-block satir-2">';
                                                    echo esc_html($monthly_title);
                                                    echo '</a>';
                                                }
                                            }

                                            echo '<hr class="my-3">';

                                            // Haftanın okunan 3 postu - Transient ile cache
                                            echo '<h5 class="fw-semibold text-dark fz-14">
                                        <svg class="text-primary me-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M16.5 8c0 1.5-.5 3.5-2.9 4.3c.7-1.7.8-3.4.3-5c-.7-2.1-3-3.7-4.6-4.6c-.4-.3-1.1.1-1 .7c0 1.1-.3 2.7-2 4.4C4.1 10 3 12.3 3 14.5C3 17.4 5 21 9 21c-4-4-1-7.5-1-7.5c.8 5.9 5 7.5 7 7.5c1.7 0 5-1.2 5-6.4c0-3.1-1.3-5.5-2.4-6.9c-.3-.5-1-.2-1.1.3"/></svg>
                                        Haftanın En Çok Okunanları</h5>';

                                            $weekly_transient_key = 'haftanin_en_cok_okunan_haberleri_v2';
                                            $weekly_post_ids = get_transient($weekly_transient_key);

                                            if (false === $weekly_post_ids || !is_array($weekly_post_ids)) {
                                                $args = array(
                                                    'post_type'              => 'post',
                                                    'post_status'            => 'publish',
                                                    'posts_per_page'         => 2,
                                                    'ignore_sticky_posts'    => true,
                                                    'no_found_rows'          => true,
                                                    'fields'                 => 'ids',
                                                    'update_post_term_cache' => false,
                                                    'update_post_meta_cache' => false,
                                                    'date_query'             => array(
                                                        array(
                                                            'after' => '1 week ago',
                                                        ),
                                                    ),
                                                    'meta_key'               => 'views_count',
                                                    'orderby'                => 'meta_value_num',
                                                    'order'                  => 'DESC',
                                                );

                                                $weekly_post_ids = get_posts($args);
                                                if (!is_array($weekly_post_ids)) {
                                                    $weekly_post_ids = array();
                                                }
                                                set_transient($weekly_transient_key, $weekly_post_ids, 1 * HOUR_IN_SECONDS);
                                            }

                                            if (!empty($weekly_post_ids)) {
                                                foreach ($weekly_post_ids as $weekly_post_id) {
                                                    $weekly_post_id = absint($weekly_post_id);
                                                    if (!$weekly_post_id) {
                                                        continue;
                                                    }
                                                    $weekly_title = get_the_title($weekly_post_id);
                                                    $weekly_link  = get_permalink($weekly_post_id);
                                                    if (!$weekly_title || !$weekly_link) {
                                                        continue;
                                                    }
                                                    echo '<a href="' . esc_url($weekly_link) . '" class="text-link fw-normal d-block satir-2">';
                                                    echo esc_html($weekly_title);
                                                    echo '</a>';
                                                }
                                            }

                                            echo '</div>';
                                            ?>
                                        </div>
                                        <div class="row align-items-center border-top mt-3 pt-3 small">
                                            <div class="col d-flex align-items-center">
                                                <?php $array = array(get_option('options_gizlilik_politikasi_sayfasi'), get_option('options_kunye_sayfasi'), get_option('options_iletisim_sayfasi'));
                                                $count = 0;
                                                foreach ($array as $yazdir) {
                                                    echo ($count != 0 ? '<span class="text-muted opacity-50 cursor-default px-3">•</span>' : '') . '<a class="ripple text-link d-block" data-bs-ripple-color="light"  href="' . get_permalink($yazdir) . '" title="' . get_the_title($yazdir) . '">' . get_the_title($yazdir) . '</a>';
                                                    $count++;
                                                }
                                                ?>
                                            </div>
                                            <div class="col-auto"><?php echo do_shortcode('[header_takipedin]'); ?></div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            <?php else: ?>
                <div id="scrollProgress"></div>
                <div class="row align-items-center p-3 px-0 px-md-3">
                    <div class="col-auto border-end text-danger d-none d-md-inline-flex">
                        <svg class="text-primary" xmlns="http://www.w3.org/2000/svg" width="30" height="30"
                            viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M19.875 3H4.125C2.953 3 2 3.897 2 5v14c0 1.103.953 2 2.125 2h15.75C21.047 21 22 20.103 22 19V5c0-1.103-.953-2-2.125-2m0 16H4.125c-.057 0-.096-.016-.012.008L3.988 5.046c.007-.01.052-.046.137-.046h15.75c.079.001.122.028.125.008l.012 13.946c-.007.01-.052.046-.137.046" />
                            <path fill="currentColor" d="M6 7h6v6H6zm7 8H6v2h12v-2h-4zm1-4h4v2h-4zm0-4h4v2h-4z" />
                        </svg>
                    </div>
                    <div class="col">
                        <h1 id="fixed-post-title" class="single-title m-0 fz-18 satir-1"><?php the_title() ?></h1>
                    </div>
                    <div class="col-auto border-start d-md-none text-danger">
                        <button class="btn btn-outline-primary p-0 rounded-circle" id="scrollToTopBtn" style="display:none">
                            <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" style="transform: rotate(270deg);">
                                <g fill="none" fill-rule="evenodd">
                                    <path
                                        d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z">
                                    </path>
                                    <path fill="currentColor"
                                        d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z">
                                    </path>
                                </g>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
    <!-- ============= COMPONENT END// ============== -->
</header>

<?php if ( function_exists( 'mevzu_should_show_son_dakika' ) ? mevzu_should_show_son_dakika() : ( get_option( 'options_son_dakika_goster', '1' ) == '1' ) ) : ?>
<div class="container">
    <div class="bg-white rounded shadow-sm mt-2 mb-3 my-md-3 small d-none d-md-block">
        <div class="row g-0 align-items-center">
            <div class="col-auto">
                <a href="<?php bloginfo('url') ?>/sondakika"
                    class="d-none d-md-block rounded position-relative bg-primary text-white py-2 px-3 bg-dark-hover shadow-sm m-1">
                    <div class="sondakika d-inline-flex align-items-center justify-content-center me-2">
                        <span></span>
                    </div>
                    <span class="small text-uppercase fw-bold">Son dakika</span>
                </a>
            </div>
            <?php
            $transient_key = 'son_dakika_haberleri_v2';
            $breaking_news_post_ids = get_transient($transient_key);

            if (false === $breaking_news_post_ids || !is_array($breaking_news_post_ids)) {
                $breaking_count = absint(get_option('options_son_dakika_haber_sayisi'));
                if ($breaking_count <= 0) {
                    $breaking_count = 8;
                }
                $args = array(
                    'post_type'              => 'post',
                    'post_status'            => 'publish',
                    'posts_per_page'         => $breaking_count,
                    'ignore_sticky_posts'    => true,
                    'no_found_rows'          => true,
                    'fields'                 => 'ids',
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                );
                $breaking_news_post_ids = get_posts($args);
                if (!is_array($breaking_news_post_ids)) {
                    $breaking_news_post_ids = array();
                }
                set_transient($transient_key, $breaking_news_post_ids, 15 * MINUTE_IN_SECONDS);
            }

            if (!empty($breaking_news_post_ids)): ?>
                <div class="col align-items-center position-relative overflow-hidden rounded-start rounded-md-none">
                    <div class="sondakika-gradient px-2 position-absolute start-0 top-0 h-100 transform-180"></div>
                    <div class="breaking-news swiper px-2 px-md-3 mt-2">
                        <div class="swiper-wrapper">
                            <?php foreach ($breaking_news_post_ids as $breaking_post_id):
                                $breaking_post_id = absint($breaking_post_id);
                                if (!$breaking_post_id) {
                                    continue;
                                }
                                $breaking_title = get_the_title($breaking_post_id);
                                $breaking_link  = get_permalink($breaking_post_id);
                                if (!$breaking_title || !$breaking_link) {
                                    continue;
                                } ?>
                                <div class="swiper-slide swiper-slide swiper-slide-rv">
                                    <span class="text-dark pe-1 fw-semibold"><?php echo esc_html(get_the_date("H:i", $breaking_post_id)); ?></span>
                                    <a href="<?php echo esc_url($breaking_link); ?>"
                                        class="text-link fw-normal"><?php echo esc_html($breaking_title); ?></a>
                                    <svg class="ms-3" xmlns="http://www.w3.org/2000/svg" width="8" height="8"
                                        viewBox="0 0 24 24">
                                        <path class="secondary opacity-50" fill="currentColor"
                                            d="M12 22q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22" />
                                    </svg>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="sondakika-gradient px-2 position-absolute end-0 top-0 h-100"></div>
                </div>
                <div class="col-auto align-self-center">
                    <div class="row g-0 p-1">
                        <div class="col-auto pe-0">
                            <div
                                class="bg-secondary text-secondary p-1 m-1 rounded bg-opacity-25 text-primary-hover h-swiper-button-prev">
                                <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    viewBox="0 0 24 24">
                                    <g fill="none" fill-rule="evenodd">
                                        <path
                                            d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z">
                                        </path>
                                        <path fill="currentColor"
                                            d="M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122l-5.66-5.658Z">
                                        </path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div
                                class="bg-secondary text-secondary p-1 m-1 rounded bg-opacity-25 text-primary-hover h-swiper-button-next">
                                <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    viewBox="0 0 24 24">
                                    <g fill="none" fill-rule="evenodd">
                                        <path
                                            d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z">
                                        </path>
                                        <path fill="currentColor"
                                            d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z">
                                        </path>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>