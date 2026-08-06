<?php
    $kur_raw = (string) get_query_var('kur');
    $kur_raw = preg_replace('/[^a-zA-Z]/', '', $kur_raw);

    $doviz_data = get_doviz_data();
    $rates      = isset($doviz_data['Rates']) && is_array($doviz_data['Rates']) ? $doviz_data['Rates'] : [];

    // Kur anahtarını case-insensitive eşleştir (usd -> USD gibi)
    $kur = $kur_raw;
    if ( ! isset($rates[$kur]) ) {
        foreach ($rates as $tmp_key => $tmp_rate) {
            if (strtolower((string) $tmp_key) === strtolower($kur_raw)) {
                $kur = (string) $tmp_key;
                break;
            }
        }
    }

    // Son güvenli fallback
    if (!isset($rates[$kur]) && !empty($rates)) {
        $first_key = array_key_first($rates);
        $kur = is_string($first_key) ? $first_key : $kur;
    }

    $rate = isset($rates[$kur]) && is_array($rates[$kur]) ? $rates[$kur] : [];

    $kur_uzun      = strtoupper((string) $kur) . 'TRY';
    $kur_name      = $rate['Name'] ?? strtoupper((string) $kur);
    $rate_type     = $rate['Type'] ?? 'Currency';
    $rate_change   = isset($rate['Change']) ? (float) $rate['Change'] : 0.0;
    $rate_selling  = isset($rate['Selling']) ? (float) $rate['Selling'] : 0.0;
    $rate_buying   = isset($rate['Buying']) ? (float) $rate['Buying'] : 0.0;
    $rate_usd      = isset($rate['USD_Price']) ? (float) $rate['USD_Price'] : 0.0;
    $rate_try      = isset($rate['TRY_Price']) ? (float) $rate['TRY_Price'] : 0.0;
    $update_date   = $doviz_data['Meta_Data']['Update_Date'] ?? '';
    $update_ts_obj = $update_date ? DateTime::createFromFormat('Y-m-d H:i:s', $update_date) : false;
    $update_ts     = $update_ts_obj ? $update_ts_obj->getTimestamp() : current_time('timestamp');

    if ($rate_change > 0) {
        $degisim_class = ' text-success border-success';
        $degisim = '<svg class="me-1 xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 13V8h-5"/><path d="m20 8l-5 5c-.883.883-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373s-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373l-3 3"/></g></svg>';
    } else {
        $degisim_class = ' text-danger border-danger';
        $degisim = '<svg class="me-1 xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor"><path d="M20 11v5h-5"/><path d="m20 16l-5-5c-.883-.883-1.324-1.324-1.865-1.373a1.5 1.5 0 0 0-.27 0c-.541.05-.982.49-1.865 1.373s-1.324 1.324-1.865 1.373q-.135.012-.27 0c-.541-.05-.982-.49-1.865-1.373L4 8"/></g></svg>';
    }
?>


    
        <!-- UST KISIM -->
        <div class="my-3">
            <div class="row align-items-center mb-3">
                <div class="col-auto pe-0">
                    <?php if($rate_type == 'Currency') : ?>
                        <img class="rounded-circle h-48 w-48" loading="lazy" src="https://s3-symbol-logo.tradingview.com/country/<?php echo kur_ulke($kur); ?>.svg">
                    <?php elseif($rate_type == 'CryptoCurrency') : ?>
                        <img class="rounded-circle" loading="lazy" src="https://s3-symbol-logo.tradingview.com/crypto/XTVC<?php echo ($rate_type == 'Currency' ? kur_ulke($kur) : str_replace('SUSDS', 'USDS2', $kur)); ?>--big.svg" width="48" height="48" alt="<?php echo $kur; ?>">
                    <?php endif; ?>
                </div>
                <div class="col">
                <h1 class="fw-semibold mb-0"><?=$kur?></h1>
                </div>
            </div>
            <p><?=$kur_name?> anlık olarak <?=ceil($rate_selling * 100) / 100; ?>TL fiyatından işlem görmektedir. Fiyatı son 24 saatte <?=$rate_change; ?>% değişim göstermiştir.</p>
            
            <div class="fz-24 d-inline-block me-2"><b><?=$rate_selling;?></strong></b> Türk Lirası</div>
            <span class="border border-2 rounded p-2 small fw-semibold<?php echo $degisim_class?>"><?=$degisim?><?=$rate_change; ?>%</span>

            <?php if($rate_type == 'Currency') : ?>
                <div class="row align-items-center mt-3">
                    <div class="col-auto mb-1">
                        <span class="d-block fw-semibold">Alış</span>
                        <?=number_format($rate_buying, 0, ',', '.')?> TRY
                    </div>
                    <div class="col-auto mb-1">
                        <span class="d-block fw-semibold">Satış</span>
                        <?=number_format($rate_selling, 0, ',', '.')?> TRY
                    </div>
                    <div class="col-auto">
                        <span class="d-block fw-semibold">Değişim</span>
                        <?php $degisim2= ($rate_change > 0 ? "+" . $rate_change . "%" : $rate_change . "%"); ?>
                        <div><?=$degisim2?></div>
                    </div>
                    <div class="col-auto">
                        <span class="d-block fw-semibold">Son Güncelleme</span>
                        <?=date_i18n('d.m.Y H:i:s', $update_ts);?>
                    </div>
                </div>
            <?php elseif($rate_type == 'CryptoCurrency') : ?>
                <div class="row align-items-center mt-3">
                    <div class="col-auto mb-1">
                        <span class="d-block fw-semibold">USD</span>
                        <?=number_format($rate_usd, 0, ',', '.')?> USD
                    </div>
                    <div class="col-auto mb-1">
                        <span class="d-block fw-semibold">TRY</span>
                        <?=number_format($rate_try, 0, ',', '.')?> TRY
                    </div>
                    <div class="col-auto mb-1">
                        <span class="d-block fw-semibold">Satış</span>
                        <?=number_format($rate_selling, 0, ',', '.')?> TRY
                    </div>
                    <div class="col-auto">
                        <span class="d-block fw-semibold">Değişim</span>
                        <?php $degisim2= ($rate_change > 0 ? "+" . $rate_change . "%" : $rate_change . "%"); ?>
                        <div><?=$degisim2?></div>
                    </div>
                    <div class="col-auto">
                        <span class="d-block fw-semibold">Son Güncelleme</span>
                        <?=date_i18n('d.m.Y H:i:s', $update_ts);?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- UST KISIM -->
    
<div class="row justify-content-between mt-lg-3">

    <div class="col-12 mb-3">
        <div class="tema-widget bg-white rounded shadow-sm overflow-hidden" style="height:600px">
        <!-- TradingView Widget BEGIN -->
        <div class="tradingview-widget-container">
            <div class="tradingview-widget-container__widget"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
            {
                "autosize": true,
                "symbol": "<?=($rate_type == 'Currency' ? $kur_uzun : strtoupper((string) $kur).'TRY')?>",
                "interval": "D",
                "timezone": "Europe/Istanbul",
                "theme": "light",
                "style": "1",
                "locale": "tr",
                "withdateranges": true,
                "hide_side_toolbar": false,
                "allow_symbol_change": true,
                "details": true,
                "hotlist": true,
                "support_host": "https://www.tradingview.com"
            }
            </script>
        </div>
        <!-- TradingView Widget END -->
        </div>
    </div>

    <div class="col-12 col-lg-8">
        
        <!-- SINGLE CONTENT -->
        <div class="single">
            <div class="bg-white rounded shadow-sm p-3">
                <?php
                $url = 'https://api.unsplash.com/search/photos/?client_id=YBlw_Ao-8XEWS0MSFdftEKmYGoypbbn_ePqFN0VSXtQ&query='.$kur.' banknotes&per_page=1&order_by=relevant';
                $response = wp_remote_get($url);
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (!empty($data['results'][0]['urls']['regular'])) {
                        $image_url = esc_url($data['results'][0]['urls']['regular']);
                        echo '<img src="' . $image_url . '" alt="'.$kur.'" class="w-100 wp-post-image mb-3" />';
                    } else {
                    }
                } else {
                    echo 'API isteği başarısız.';
                }
                ?>

                <h1 class="single-title mt-0 mb-2 fz-24">1 <?=$kur?> kaç TL oldu? <?=$kur_name?> kuru bugün ne kadar?</h1>
                <div class="single-content content mt-2 p-0">
                    <p>Son verilere göre <?=$kur_name?> satış fiyatı
                    <span class="fw-semibold"><?php
                    if($rate_type == "Currency") { 
                        echo $rate_selling . ' TL'; 
                    }
                    else {
                        echo number_format($rate_usd, 0, ',', '.') . '$ (' . number_format($rate_try, 0, ',', '.') . 'TL)'; 
                    }
                    ?></span>
                    seviyesinde işlem görüyor. <?=date_i18n('d.m.Y H:i:s', $update_ts);?> itibarıyla güncellenen bu veriler anlık olarak yenilenmektedir.</p>
                    <h3><?=$kur_name?> kuru <?=($rate_change>0 ? 'yükselişte' : 'düşüşte' )?></h3>
                    <p>Şu anda <?=$kur_name?> <?=number_format($rate_selling, 0, ',', '.')?> TL üzerinden işlem görüyor. <?=$kur_name?> fiyatı son 24 saatte <?=$rate_change?>% oranında değer <?=($rate_change>0 ? 'kazandı' : 'kaybetti' )?>.</p>
                    <p><?=$kur_name?> hesaplama işlemleriniz için, sayfanın alt bölümünde yer alan döviz çevirici aracımızı kullanabilirsiniz. Güncel <?=$kur_name?> kuru ve piyasadaki en doğru, anlık gelişmeler için bizi takip etmeye devam edin.</p>
                    <ul class="m-0">
                        <?php
                        $count = 0;
                        // print_r(get_doviz_data()['Rates']);
                        $current_type = $rate['Type'] ?? null;
                        foreach ( $rates as $kur_array => $kur_value ) {
                            if ( $current_type !== null && is_array($kur_value) && ($kur_value['Type'] ?? null) === $current_type && $kur_value != 0 ) {
                                if ( $kur_array == get_query_var('kur') ) {
                                    continue;
                                }
                                if ( $count < 6 ) {
                                ?>
                                <li>
                                    <a class="text-link fw-semibold" href="./<?=$kur_array?>">
                                    1 <?=$kur_array?> <span class="opacity-75">(<?=($kur_value['Name'] ?? $kur_array)?>)</span> Kaç TL?
                                    </a>
                                </li>
                                <?php
                                $count++;
                                } else {
                                break;
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- SINGLE CONTENT -->

    </div>
    <div class="col-12 col-lg-4">
        <div class="sticky-top">

            <!-- Doviz çevirici -->
            <?php
            $args = array(
                'before_widget' => '<section class="widget mt-3">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2>',
                'after_title'   => '</h2>',
            );

            $instance = array(
                'title'        => 'Döviz Çevirici',
                'template'     => 'sablon-1'
            );
            $widget = new DovizCeviriciWidget();
            echo $widget->widget($args, $instance);
            ?>
            <!-- Doviz çevirici -->
             
            <?php
            $args = array(
                'before_widget' => '<section class="widget mt-3">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2>',
                'after_title'   => '</h2>',
            );

            $instance = array(
                'title'             => 'Ekonomi Haberleri',       // Widget Başlığı
                'order_by'          => 'date',                // Sıralama: Tarihe göre
                'post_count'        => 5,                     // Haber sayısı
                'template'          => 'populer-basliklar-3', // Şablon - 2
                'selected_category' => get_post_meta(get_the_ID(), 'ekonomi_kategorisi', true) 
            );
            $widget = new bilesen_haftalik_gundem();
            echo $widget->widget($args, $instance);
            ?>
        </div>
    </div>

    <div class="col-12">
        
        <div class="row">
            <div class="col-12 col-md-6 mt-3">
                <?php
                    $args = array(
                        'before_widget' => '<section class="widget mt-3">',
                        'after_widget'  => '</section>',
                        'before_title'  => '<h2>',
                        'after_title'   => '</h2>',
                    );

                    $instance = array(
                        'adet'  => 16,
                        'gizle' => 1
                    );
                    $widget = new widget_anlikkur();
                    echo $widget->widget($args, $instance);
                ?>
            </div>
            <div class="col-12 col-md-6 mt-3">
                <?php
                    $args = array(
                        'before_widget' => '<section class="widget mt-3">',
                        'after_widget'  => '</section>',
                        'before_title'  => '<h2>',
                        'after_title'   => '</h2>',
                    );

                    $instance = array(
                        'adet'  => 0,
                        'gizle' => 1
                    );
                    $widget = new widget_anlikaltin();
                    echo $widget->widget($args, $instance);
                ?>
            </div>
            <div class="col-12 col-md-12 mt-3">
                <?php
                    $args = array(
                        'before_widget' => '<section class="widget">',
                        'after_widget'  => '</section>',
                        'before_title'  => '<h2>',
                        'after_title'   => '</h2>',
                    );

                    $instance = array(
                        'adet'  => 0,
                        'gizle' => 1
                    );
                    $widget = new widget_anlikkripto();
                    echo $widget->widget($args, $instance);
                ?>
            </div>
        </div>

    </div>
</div>