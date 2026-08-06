<div class="row justify-content-between mt-lg-3">
    <div class="col-12 mb-3">
        <div class="tema-widget bg-white rounded shadow-sm" style="height:600px">
            <!-- TradingView Widget BEGIN -->
            <div class="tradingview-widget-container">
            <div class="tradingview-widget-container__widget"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-symbol-overview.js" async>
            {
            "symbols": [
                <?php 
                $veriler = array_slice(get_doviz_data()['Rates'], 0, 8, true);
                if (is_array($veriler)) {
                    $son_key = array_key_last($veriler); // PHP 7.3+ fonksiyonu
                    foreach ($veriler as $kur_uzun => $kur) {
                        $kur_name = is_array($kur) ? ($kur['Name'] ?? $kur_uzun) : $kur_uzun;
                        echo '[ "' . esc_js($kur_name) . '", "' . esc_js($kur_uzun) . 'TRY|1D" ]';
                        if ($kur_uzun !== $son_key) {
                            echo ',';
                        }
                    }
                }
                ?>
            ],
            "chartOnly": false,
            "width": "100%",
            "height": "100%",
            "locale": "tr",
            "colorTheme": "light",
            "autosize": true,
            "showVolume": false,
            "showMA": false,
            "hideDateRanges": false,
            "hideMarketStatus": false,
            "hideSymbolLogo": false,
            "scalePosition": "right",
            "scaleMode": "Normal",
            "fontFamily": "-apple-system, BlinkMacSystemFont, Trebuchet MS, Roboto, Ubuntu, sans-serif",
            "fontSize": "10",
            "noTimeScale": false,
            "valuesTracking": "1",
            "changeMode": "price-and-percent",
            "chartType": "area",
            "maLineColor": "#2962FF",
            "maLineWidth": 1,
            "maLength": 9,
            "headerFontSize": "medium",
            "backgroundColor": "rgba(255, 255, 255, 0)",
            "lineWidth": 2,
            "lineType": 0,
            "dateRanges": [
                "1d|1",
                "5d|30",
                "1m|30",
                "3m|60",
                "12m|1D",
                "60m|1W",
                "all|1M"
            ],
            "dateFormat": "dd/MM/yyyy"
            }
            </script>
            </div>
            <!-- TradingView Widget END -->
        </div>
    </div>

    <div class="col-12">

        <!-- Doviz çevirici -->
        <?php
        $args = array(
            'before_widget' => '<section class="widget">',
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

    </div>
</div>

<div class="row justify-content-center mt-lg-3">
    <div class="col">
        <?php
        $args = array(
            'before_widget' => '<section class="widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        );

        $instance = array(
            'title' => 'Serbest Piyasa',
            'adet'  => 0,
        );
        $widget = new widget_anlikkur();
        echo $widget->widget($args, $instance);
        ?>
    </div>
    <div class="col">
        <?php
        $args = array(
            'before_widget' => '<section class="widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        );

        $instance = array(
            'title'             => 'Altın Fiyatları',
            'adet'  => 0,
        );
        $widget = new widget_anlikaltin();
        echo $widget->widget($args, $instance);
        ?>
    </div>
</div>

<div class="row justify-content-center mt-lg-3">
    <div class="col">
        <?php
        $args = array(
            'before_widget' => '<section class="widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        );

        $instance = array(
            'title'             => 'Kripto Piyasası',
            'adet'  => 0,
        );
        $widget = new widget_anlikkripto();
        echo $widget->widget($args, $instance);
        ?>
    </div>
    <div class="col-12 col-lg-4">
        <div class="sticky-top">
        <?php
        $args = array(
            'before_widget' => '<section class="widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2>',
            'after_title'   => '</h2>',
        );

        $instance = array(
            'title'             => 'Ekonomi Haberleri',       // Widget Başlığı
            'order_by'          => 'date',                // Sıralama: Tarihe göre
            'post_count'        => 8,                     // Haber sayısı
            'template'          => 'populer-basliklar-3', // Şablon - 2
            'selected_category' => get_post_meta(get_the_ID(), 'ekonomi_kategorisi', true)  
        );
        $widget = new bilesen_haftalik_gundem();
        echo $widget->widget($args, $instance);
        ?>
        </div>
    </div>
</div>

<div class="row justify-content-between">
    <div class="col-12">

        <div class="my-3 my-lg-4 tema-widget bg-white shadow-sm rounded">
            <h2>Çapraz Kurlar</h2>
            <div style="height: 574px;margin:-1px">
                <!-- TradingView Widget BEGIN -->
                <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-forex-cross-rates.js" async>
                {
                "width": "100%",
                "height": "100%",
                "currencies": [
                    "TRY",
                    "USD",
                    "EUR",
                    "GBP",
                    "CHF",
                    "CAD",
                    "RUB"
                ],
                "isTransparent": false,
                "colorTheme": "light",
                "locale": "tr",
                "backgroundColor": "#ffffff"
                }
                </script>
                </div>
                <!-- TradingView Widget END -->
            </div>
        </div>

        <div class="my-3 my-lg-4 tema-widget bg-white shadow-sm rounded">
            <h2>Isı Haritası</h2>
            <div style="height: 574px;margin:-1px">
                <!-- TradingView Widget BEGIN -->
                <div class="tradingview-widget-container">
                <div class="tradingview-widget-container__widget"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-forex-heat-map.js" async>
                {
                "width": "100%",
                "height": "100%",
                "currencies": [
                    "EUR",
                    "USD",
                    "JPY",
                    "GBP",
                    "CHF",
                    "AUD",
                    "CAD",
                    "NZD",
                    "CNY"
                ],
                "isTransparent": false,
                "colorTheme": "light",
                "locale": "tr",
                "backgroundColor": "#ffffff"
                }
                </script>
                </div>
                <!-- TradingView Widget END -->
            </div>
        </div>

    </div>

</div>