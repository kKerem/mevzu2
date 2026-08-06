<?php
get_header();

$city_slug = get_query_var('sehir');
$city_raw = $city_slug ? $city_slug : mevzu_get_current_city();
$city = turkce_karakter($city_raw);

$data = get_weather_data($city);

if (!$data || !isset($data['list'])) {
    echo '<p>Hava durumu verisi alınamadı.</p>';
}

$daily_data = $data['list'];
$today = $daily_data[0];
?>

<div class="container">

    <div class="single-breadcrumb mb-3">
        <?php custom_breadcrumbs(); ?>
    </div>

    <?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

    <form method="POST" action="" id="sehirForm">
        <div class="row align-items-center mb-3">
            <div class="col-auto small-2 text-uppercase fw-semibold text-body pe-0">Şehir Seçimi:</div>
            <div class="col col-md-auto pe-0">
                    <select name="sehir" id="sehir" class="form-control select2-bootstrap5">
                        <?php
                        $varsayilan_sehir = strtolower(get_option('options_varsayilan_sehir'));
                        $varsayilan_sehir_normalized = turkce_karakter($varsayilan_sehir);
                        
                        foreach(sehirler() as $key => $value) {
                            $sehir_name = strtolower($value['name']);
                            $sehir_name_tr = strtolower($value['name_tr']);
                            $sehir_name_normalized = turkce_karakter($sehir_name);
                            $sehir_name_tr_normalized = turkce_karakter($sehir_name_tr);
                            $is_selected = '';
                            
                            // Eğer URL'den şehir geliyorsa onu seç
                            if ($city_slug) {
                                $city_slug_normalized = turkce_karakter(strtolower($city_slug));
                                $is_selected = ($sehir_name == strtolower($city_slug) || 
                                            $sehir_name_tr == strtolower($city_slug) ||
                                            $sehir_name_normalized == $city_slug_normalized ||
                                            $sehir_name_tr_normalized == $city_slug_normalized) ? ' selected' : '';
                            } else {
                                // Varsayılan şehir ile karşılaştır - tüm varyasyonları kontrol et
                                $is_selected = ($sehir_name == $varsayilan_sehir || 
                                            $sehir_name_tr == $varsayilan_sehir ||
                                            $sehir_name_normalized == $varsayilan_sehir_normalized ||
                                            $sehir_name_tr_normalized == $varsayilan_sehir_normalized) ? ' selected' : '';
                            }
                            
                            echo '<option value="' . turkce_karakter($value['name']) . '"' . $is_selected . '>' . ucfirst($value['name_tr']) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
            <div class="col col-md-auto ps-0">
                <button type="submit" class="btn btn-primary fz-12 px-3 rounded-0 rounded-end shadow-sm h-35"><i class="ri-corner-down-left-line"></i></button>
            </div>
        </div>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function loadSelect2() {
            if (typeof jQuery !== 'undefined') {
                jQuery.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {
                    jQuery('#sehir').select2({
                        theme: 'bootstrap-5',
                        placeholder: "Şehir seçiniz"
                    });

                    jQuery('#sehir').on('change', function() {
                        var sehir = jQuery(this).val();
                        if(sehir) {
                            window.location.href = '<?=get_permalink()?>/' + encodeURIComponent(sehir.toLowerCase());
                        }
                    });
                });
            } else {
                setTimeout(loadSelect2, 100);
            }
        }
        loadSelect2();

        // Değişiklikte formu otomatik gönder
        $('#sehir').on('change', function() {
            var sehir = $(this).val();
            if(sehir) {
                var d = new Date();
                d.setTime(d.getTime() + (365*24*60*60*1000));
                var expires = "expires="+ d.toUTCString();
                document.cookie = "mevzu_hava_sehir=" + sehir + ";" + expires + ";path=/";
                window.location.href = '<?=get_permalink()?>/' + encodeURIComponent(sehir.toLowerCase());
            }
        });
    });
    </script>
    

  <div class="swiper HavaDurumu mt-3">
    <div class="swiper-wrapper">
        <?php $i=1; foreach ($daily_data as $forecast): if($i <= 7) : ?>
            <div class="swiper-slide tema-widget rounded shadow-sm mb-3 pt-3 px-3<?php echo ($i>1 ? ' bg-light' : ' bg-white'); ?>">
                <div class="row justify-content-between align-items-center">
                    <div class="col">
                        <small class="d-block text-uppercase fw-bold">
                            <?php echo date_i18n('l', $forecast['dt']); ?>
                        </small>
                        <small class="fz-12 text-body"><?php echo date_i18n('d M Y', $forecast['dt']); ?></small>
                        <div class="derece fw-bold fz-36"><?php echo round($forecast['temp']['max'] ?? 0); ?>°</div>
                    </div>
                    <div class="col-auto text-center">
                        <?php
                        $icon = $forecast['weather'][0]['icon'] ?? '01d';
                        $icon_url = get_template_directory_uri() . '/img/assets/havalar/' . $icon . '.svg';
                        ?>
                        <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo ucfirst($forecast['weather'][0]['description'] ?? '-'); ?>" width="82">
                        <div class="small fw-semibold satir-1"><?php echo ucfirst($forecast['weather'][0]['description'] ?? '-'); ?></div>
                    </div>
                </div>
                
                <div class="row small align-items-center">
                    <div class="col-12 small">Hissedilen</div>
                    <div class="col-auto ps-2" title="Gündüz">
                        <img loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/clear-day.svg" width="20" alt="Gündüz">
                        <?php echo round($forecast['feels_like']['day'] ?? 0); ?>°C
                    </div>
                    <div class="col-auto ps-0" title="Gece">
                        <img loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/clear-night.svg" width="20" alt="Gece">
                        <?php echo round($forecast['feels_like']['night'] ?? 0); ?>°C
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top small">

                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/windsock.svg" width="40" alt="Rüzgar">
                            Rüzgar:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold">
                            <?php if (isset($today['deg']) && $i==1 ): 
                                $deg = (int)$today['deg'];
                            ?>
                            <img loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/compass.svg" width="40" style="transform: rotate(<?= $deg; ?>deg)" alt="Rüzgar Yönü">
                            <?php endif; ?>
                            <?= $today['speed'] ?? '-'; ?> km/s
                        </div>
                    </div>

                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/barometer.svg" width="40" alt="Basınç">
                            Basınç:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold"><?= $forecast['pressure'] ?? '-'; ?> hPa</div>
                    </div>

                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/humidity.svg" width="40" alt="Nem">
                            Nem:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold"><?= $forecast['humidity'] ?? '-'; ?>%</div>
                    </div>

                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/haze.svg" width="40" alt="Sis">
                            Sis:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold"><?= $forecast['clouds'] ?? '-'; ?>%</div>
                    </div>

                    <?php if (isset($forecast['pop'])): ?>
                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/umbrella.svg" width="40" alt="Yağış Olasılığı">
                            Yağış Olasılığı:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold"><?= ($forecast['pop'] * 100); ?>%</div>
                    </div>
                    <?php endif; ?>

                    <div class="row justify-content-center align-items-center gx-0">
                        <div class="col">
                            <img src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/rain.svg" width="40" alt="Yağmur">
                            Yağmur:
                        </div>
                        <div class="col-auto text-end pe-md-2 fw-semibold"><?=(isset($forecast['rain']) ? $forecast['rain'] : '0')?> mm</div>
                    </div>
                </div>

                <div class="mt-3 border-top small">
                    <?php
                    $sunrise = $forecast['sunrise'];
                    $sunset  = $forecast['sunset'];
                    $now     = time();

                    // Güneşin ilerleme oranı (0 ile 1 arası)
                    if ($now < $sunrise) {
                        $progress = 0;
                    } elseif ($now > $sunset) {
                        $progress = 1;
                    } else {
                        $progress = ($now - $sunrise) / ($sunset - $sunrise);
                    }

                    $x = 10 + ($progress * 180);

                    $radius = 90;
                    $centerX = 100;
                    $centerY = 100;

                    $dx = $x - $centerX;
                    $y = $centerY - sqrt($radius * $radius - $dx * $dx);
                    ?>

                    <!-- Saat bilgileri -->
                    <div class="row justify-content-between align-items-center gx-0 text-center py-3">
                        <div class="col-auto">
                            <img loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/sunrise.svg" width="40" alt="Gün doğumu">
                            <div class="small">Gün doğumu</div>
                            <div class="fw-semibold"><?php echo date('H:i', $sunrise); ?></div>
                        </div>
                        <?php if($i==1) : ?>
                        <div class="col">
                            <div class="gundogumu">
                                <svg width="100%" height="82px" viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10,100 A90,90 0 0,1 190,100" fill="none" stroke-width="1" />
                                    <foreignObject x="<?php echo $x - 15; ?>" y="<?php echo $y - 15; ?>" width="30" height="30">
                                        <img loading="lazy" src="<?= get_template_directory_uri() ?>/img/assets/havalar/standart/clear-day.svg" width="30" height="30" class="bg-white rounded-circle" alt="Güneşin konumu" />
                                    </foreignObject>
                                </svg>
                            </div>
                        </div>
                        <?php else : ?>
                        <div class="col">
                            <div class="border-top mx-3">
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-auto">
                            <img loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/sunset.svg" width="40" alt="Gün batımı">
                            <div class="small">Gün batımı</div>
                            <div class="fw-semibold"><?php echo date('H:i', $sunset); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php $i++; endif; endforeach; ?>
    </div>
  </div>

    <div class="table-responsive tema-widget rounded shadow-sm bg-white">
        <h2 class="mb-0">16 günlük hava tahmini</h2>
        <table class="table table-striped table-hover m-0 align-middle">
            <thead>
                <tr class="text-center small fw-normal">
                    <th class="px-5 pt-0 pb-1" scope="col"></th>
                    <th class="px-5 pt-0 pb-1" scope="col"></th>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/thermometer-celsius.svg" width="40" alt="Hissedilen">
                        Sıcaklık
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/thermometer-warmer.svg" width="40" alt="Hissedilen">
                        Hissedilen
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/windsock.svg" width="40" alt="Rüzgar">
                        Rüzgar
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/barometer.svg" width="40" alt="Basınç">
                        Basınç
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/humidity.svg" width="40" alt="Nem">
                        Nem
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/haze.svg" width="40" alt="Sis">
                        Sis
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/umbrella.svg" width="40" alt="Yağış Olasılığı">
                        Yağış
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/rain.svg" width="40" alt="Yağmur">
                        Yağmur
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/sunrise.svg" width="40" alt="Gün doğumu">
                        Doğuş
                    </td>
                    <th class="px-2 pt-0 pb-1" scope="col">
                        <img class="d-block mx-auto mb-1" loading="lazy" src="<?=get_template_directory_uri()?>/img/assets/havalar/standart/sunset.svg" width="40" alt="Gün batımı">
                        Batış
                    </td>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php foreach ($daily_data as $forecast): ?>
                    <tr>
                        <td class="p-2 ps-3 text-start fw-semibold small">
                            <span class="d-block fw-normal text-body"><?php echo date_i18n('d M Y', $forecast['dt']); ?></span>
                            <?php echo date_i18n('l', $forecast['dt']); ?>
                        </td>
                        <td class="p-2 text-md-start small">
                            <?php
                            $icon = $forecast['weather'][0]['icon'] ?? '01d';
                            $icon_url = get_template_directory_uri() . '/img/assets/havalar/' . $icon . '.svg';
                            ?>
                            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo ucfirst($forecast['weather'][0]['description'] ?? '-'); ?>" width="45" class="me-2">
                            <?php echo ucfirst($forecast['weather'][0]['description'] ?? '-'); ?>
                        </td>
                        <td class="p-2">
                            <span class="fw-semibold"><?php echo round($forecast['temp']['max'] ?? 0); ?>°</span><small><span class="text-muted"> / </span><?php echo round($forecast['temp']['min'] ?? 0); ?>°</small>
                        </td>
                        <td class="p-2">
                            <span class="fw-semibold"><?php echo round($forecast['feels_like']['day'] ?? 0); ?>°</span><small><span class="text-muted"> / </span><?php echo round($forecast['feels_like']['night'] ?? 0); ?>°C</small>
                        </td>
                        <td class="p-2"><?= ($forecast['pop'] * 100); ?>%</td>
                        <td class="p-2"><?= $forecast['speed'] ?? '-'; ?>km/s</td>
                        <td class="p-2"><?= $forecast['pressure'] ?? '-'; ?>hPa</td>
                        <td class="p-2"><?= $forecast['humidity'] ?? '-'; ?>%</td>
                        <td class="p-2"><?= $forecast['clouds'] ?? '-'; ?>%</td>
                        <td class="p-2"><?=(isset($forecast['rain']) ? $forecast['rain'] : '0')?>mm</td>
                        <td class="p-2"><?php echo date('H:i', $forecast['sunrise']); ?></td>
                        <td class="p-2"><?php echo date('H:i', $forecast['sunset']); ?></td>
                    </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>


<?php get_footer(); ?>
