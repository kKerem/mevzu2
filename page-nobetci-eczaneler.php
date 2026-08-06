<?php get_header();
$city_slug = get_query_var('sehir');
$selected_city = $city_slug ? lcfirst($city_slug) : strtolower(get_option('options_varsayilan_sehir'));
?>

<div class="container my-3">
    <div class="single-breadcrumb mb-3">
        <?php custom_breadcrumbs(); ?>
    </div>

    <div class="mb-3">
        <form method="POST" action="" id="sehirForm">
            <div class="row align-items-center">
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
        });
        </script>

        <?php
        // EczaneAPI.com ile nöbetçi eczane verilerini getiren fonksiyon
        function get_nobetci_eczaneler_api($sehir = null) {
            $transient_key = 'nobetci_eczaneler_api_v2_' . md5($sehir);
            $data = get_transient($transient_key);
            
            if (false === $data) {
                $api_keys = (array) mevzu_key('eczane_api_keys', []);
                if (empty($api_keys)) {
                    return false;
                }

                $current_key_index = (int) get_option('eczane_api_key_index', 0);
                if ($current_key_index >= count($api_keys)) {
                    $current_key_index = 0;
                }
                $api_key = $api_keys[$current_key_index];
                
                if ($sehir) {
                    $url = 'https://eczaneapi.com/api/v1/pharmacies/on-duty?city=' . urlencode($sehir);
                } else {
                    $url = 'https://eczaneapi.com/api/v1/pharmacies/on-duty?city=' . strtolower(get_option('options_varsayilan_sehir'));
                }
                
                $curl = curl_init();
                
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "X-API-Key: " . $api_key,
                        "Accept: application/json"
                    ],
                ]);
                
                $response = curl_exec($curl);
                $err = curl_error($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($err) {
                    error_log("EczaneAPI cURL Error using key index $current_key_index: " . $err);
                    return false;
                }
                
                if ($http_code !== 200) {
                    error_log("EczaneAPI HTTP Error ($http_code) using key index $current_key_index");
                    return false;
                }

                // İstek başarılı olduysa, bir dahaki sefere döngüde kullanılmak üzere sıradaki key'in indeksini belleğe al
                $next_key_index = ($current_key_index + 1) % count($api_keys);
                update_option('eczane_api_key_index', $next_key_index);
                
                $data = json_decode($response, true);
                
                if($data && isset($data['success']) && $data['success'] === true && isset($data['data'])) {
                    $mapped_days = [];
                    foreach ($data['data'] as $day_data) {
                        $day_name = $day_data['day']; // 'Dün', 'Bugün', 'Yarın'
                        $pharmacies = $day_data['pharmacies'];
                        
                        $mapped_pharmacies = [];
                        foreach ($pharmacies as $p) {
                            $mapped_pharmacies[] = [
                                'pharmacyName' => isset($p['name']) ? $p['name'] : '',
                                'district'     => isset($p['district']['name']) ? $p['district']['name'] : 'Merkez',
                                'address'      => isset($p['address']) ? $p['address'] : '',
                                'phone'        => isset($p['phone']) ? $p['phone'] : '',
                                'phone2'       => isset($p['phone2']) ? $p['phone2'] : '',
                                'latitude'     => isset($p['location']['latitude']) ? $p['location']['latitude'] : '',
                                'longitude'    => isset($p['location']['longitude']) ? $p['location']['longitude'] : '',
                                'town'         => '',
                                'directions'   => '',
                                'pharmacyDutyStart' => isset($p['duty']['date']) ? $p['duty']['date'] : '',
                                'pharmacyDutyEnd' => ''
                            ];
                        }
                        $mapped_days[$day_name] = $mapped_pharmacies;
                    }
                    
                    // Gece 00:00'a kadar cache
                    $now = current_time('timestamp');
                    $midnight = strtotime('tomorrow midnight', $now);
                    $seconds_until_midnight = $midnight - $now;
                    
                    set_transient($transient_key, $mapped_days, $seconds_until_midnight);
                    return $mapped_days;
                } else {
                    error_log("EczaneAPI Error: " . json_encode($data));
                    return false;
                }
            }
            
            return $data;
        }

        // Tüm eczaneleri al (Dün, Bugün, Yarın olarak içeren listeyi grupla)
        $all_days_data = get_nobetci_eczaneler_api(turkce_karakter($selected_city));
        $grouped_pharmacies_by_day = [];
        $distinct_districts = [];

        if($all_days_data && is_array($all_days_data)) {
            foreach($all_days_data as $day => $pharmacies_for_day) {
                $grouped = [];
                foreach($pharmacies_for_day as $pharmacy) {
                    $district = isset($pharmacy['district']) && !empty($pharmacy['district']) ? $pharmacy['district'] : 'Merkez';
                    if (!isset($grouped[$district])) {
                        $grouped[$district] = [];
                    }
                    $grouped[$district][] = $pharmacy;
                    $distinct_districts[$district] = true;
                }
                ksort($grouped);
                $grouped_pharmacies_by_day[$day] = $grouped;
            }
            ksort($distinct_districts);
        }
        ?>
    </div>

    <?php if(!empty($grouped_pharmacies_by_day)): ?>
        <?php 
        $ordered_days = ['Dün', 'Bugün', 'Yarın']; 
        ?>
        <!-- Gün Tabs -->
        <div class="bg-white p-3 mb-2 rounded">
            <ul class="nav nav-pills fz-13 gap-3" id="nobetciTab" role="tablist">
                <?php foreach ($ordered_days as $day): 
                    if (!isset($grouped_pharmacies_by_day[$day])) continue;
                    $safe_day = sanitize_title($day);
                    $is_active = ($day === 'Bugün') ? 'active' : '';

                    $date_label = $day;
                    if ($day === 'Dün') {
                        $date_label = wp_date('j F, l', strtotime('yesterday'));
                    } elseif ($day === 'Bugün') {
                        $date_label = wp_date('j F, l', strtotime('today'));
                    } elseif ($day === 'Yarın') {
                        $date_label = wp_date('j F, l', strtotime('tomorrow'));
                    }
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold fs-7 ps-2 pe-3 py-2 text-capitalize <?php echo $is_active; ?>" id="<?php echo $safe_day; ?>-tab" data-bs-toggle="pill" data-bs-target="#tab-<?php echo $safe_day; ?>" type="button" role="tab" aria-controls="tab-<?php echo $safe_day; ?>" aria-selected="<?php echo $day === 'Bugün' ? 'true' : 'false'; ?>">
                        <div class="small fw-normal text-start mb-1"><?php echo esc_html($day); ?></div>
                        <div class="fw-semibold"><?php echo esc_html($date_label); ?></div>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Gün Tabs İçerik -->
            <div class="tab-content" id="nobetciTabContent">
                <?php foreach ($ordered_days as $day): 
                    if (!isset($grouped_pharmacies_by_day[$day])) continue;
                    $grouped_pharmacies = $grouped_pharmacies_by_day[$day];
                    $safe_day = sanitize_title($day);
                    $is_active = ($day === 'Bugün') ? 'show active' : '';
                ?>
                <div class="tab-pane fade <?php echo $is_active; ?>" id="tab-<?php echo $safe_day; ?>" role="tabpanel" aria-labelledby="<?php echo $safe_day; ?>-tab">
                    <?php if(!empty($grouped_pharmacies)): ?>
                        
                        <!-- İlçe Tabs Menüsü -->
                        <ul class="ilce-tabs mb-0 p-0 pb-1" id="ilceTab-<?php echo $safe_day; ?>" role="tablist" style="overflow-x: auto; overflow-y: hidden; white-space: nowrap; width: 100%;">
                            <?php
                            $d_idx = 0;
                            foreach($grouped_pharmacies as $district => $pharmacies): 
                                $safe_district = sanitize_title($district);
                                $tab_id = "ilce-{$safe_day}-{$safe_district}";
                                $is_d_active = ($d_idx === 0) ? ' active' : '';
                            ?>
                            <li class="nav-item d-inline-block" role="presentation">
                                <button class="btn btn-secondary px-3 py-1 my-2 mt-md-3 my-md-3 me-2 shadow-none text-dark rounded border-0<?php echo $is_d_active; ?>" id="<?php echo $tab_id; ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo $tab_id; ?>" type="button" role="tab" aria-controls="<?php echo $tab_id; ?>" aria-selected="<?php echo ($d_idx===0)?'true':'false'; ?>">
                                    <?php echo esc_html($district); ?> 
                                    <span class="badge bg-dark text-white ms-1 ps-2 pe-2 text-center"><?php echo count($pharmacies); ?></span>
                                </button>
                            </li>
                            <?php $d_idx++; endforeach; ?>
                        </ul>

                        <!-- İlçe Tab Contentleri -->
                        <div class="tab-content" id="ilceTabContent-<?php echo $safe_day; ?>">
                            <?php 
                            $d_idx = 0;
                            foreach($grouped_pharmacies as $district => $pharmacies): 
                                $safe_district = sanitize_title($district);
                                $tab_id = "ilce-{$safe_day}-{$safe_district}";
                                $is_d_active = ($d_idx === 0) ? 'show active' : '';
                            ?>
                            <div class="tab-pane fade <?php echo $is_d_active; ?> district-group" id="<?php echo $tab_id; ?>" role="tabpanel" aria-labelledby="<?php echo $tab_id; ?>-tab" data-district="<?php echo esc_attr($district); ?>">
                                
                                <div class="swiper swiper-slider2">
                                    <div class="swiper-wrapper">
                                        <?php foreach($pharmacies as $eczane): ?>
                                            <div class="swiper-slide h-100">
                                                <div class="mb-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-auto pe-0">
                                                            <svg class="me-1" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 600 600"> <path d="M0 0 C198 0 396 0 600 0 C600 198 600 396 600 600 C402 600 204 600 0 600 C0 402 0 204 0 0 Z" fill="#FEFDFD" /> <path d="M0 0 C113.85 0 227.7 0 345 0 C345 32.34 345 64.68 345 98 C271.41 98 197.82 98 122 98 C122 120.44 122 142.88 122 166 C195.59 166 269.18 166 345 166 C345 198.34 345 230.68 345 264 C271.41 264 197.82 264 122 264 C122 286.44 122 308.88 122 332 C195.59 332 269.18 332 345 332 C345 364.34 345 396.68 345 430 C231.15 430 117.3 430 0 430 C0 288.1 0 146.2 0 0 Z" fill="#ED091B" transform="translate(129,85)" /> <path d="M0 0 C198 0 396 0 600 0 C600 198 600 396 600 600 C402 600 204 600 0 600 C0 402 0 204 0 0 Z M42 42 C42 212.28 42 382.56 42 558 C212.28 558 382.56 558 558 558 C558 387.72 558 217.44 558 42 C387.72 42 217.44 42 42 42 Z" fill="#ED091A" /> <path d="M0 0 C198 0 396 0 600 0 C600 198 600 396 600 600 C402 600 204 600 0 600 C0 402 0 204 0 0 Z M3 3 C3 199.02 3 395.04 3 597 C199.02 597 395.04 597 597 597 C597 400.98 597 204.96 597 3 C400.98 3 204.96 3 3 3 Z" fill="#FDF2F3" /> </svg>
                                                        </div>
                                                        <div class="col ps-1">
                                                            <h5 class="fw-bolder fz-16 m-0"><?php echo esc_html($eczane['pharmacyName']); ?></h5>
                                                        </div>
                                                        <?php if(!empty($eczane['town'])): ?>
                                                        <div class="col-auto">
                                                            <div class="text-muted">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                                                    <path fill="currentColor" d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.11.89 2 2 2h14c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2m-8 14H9v-2h2v2m0-4H9V9h2v4m4 4h-2v-6h2v6Z"/>
                                                                </svg>
                                                                <?php echo esc_html($eczane['town']); ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="row align-items-center">
                                                        <div class="col d-flex align-items-center">
                                                            <h6 class="m-0 fw-semibold small">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="me-1">
                                                                    <path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7m0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5"/>
                                                                </svg>
                                                                Adres
                                                            </h6>
                                                        </div>
                                                        <?php if(!empty($eczane['directions'])): ?>
                                                        <div class="col-auto">
                                                            <div class="bg-dark text-white rounded small-2 cursor-pointer p-1 px-2"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-html="true"
                                                                    data-bs-title="<?php echo esc_attr(str_replace('"', '&quot;', $eczane['directions'])); ?>">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="0" fill="currentColor"><animate fill="freeze" attributeName="r" begin="0.7s" dur="0.2s" values="0;4"/></circle><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="56" stroke-dashoffset="56" d="M12 4c4.42 0 8 3.58 8 8c0 4.42 -3.58 8 -8 8c-4.42 0 -8 -3.58 -8 -8c0 -4.42 3.58 -8 8 -8Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="56;0"/></path><path stroke-dasharray="4" stroke-dashoffset="4" d="M12 4v0M20 12h0M12 20v0M4 12h0" opacity="0"><animate fill="freeze" attributeName="d" begin="1s" dur="0.2s" values="M12 4v0M20 12h0M12 20v0M4 12h0;M12 4v-2M20 12h2M12 20v2M4 12h-2"/><animate fill="freeze" attributeName="stroke-dashoffset" begin="1s" dur="0.2s" values="4;0"/><set fill="freeze" attributeName="opacity" begin="1s" to="1"/><animateTransform attributeName="transform" dur="30s" repeatCount="indefinite" type="rotate" values="0 12 12;360 12 12"/></path></g></svg>
                                                                Yol Tarifi
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="small pt-2" style="min-height: 52px;"><?php echo esc_html($eczane['address']); ?></p>
                                                </div>

                                                <div class="row">
                                                    <?php if(!empty($eczane['phone'])): ?>
                                                    <div class="col mb-3">
                                                        <div class="row align-items-center">
                                                            <div class="col d-flex align-items-center">
                                                                <h6 class="m-0 fw-semibold small">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="me-1">
                                                                        <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z"/>
                                                                    </svg>
                                                                    Telefon
                                                                </h6>
                                                            </div>
                                                            <div class="col-auto">
                                                                <a class="btn btn-success btn-sm shadow-none rounded small-2 text-normal fw-normal p-1 px-2" href="tel:+9<?php echo str_replace(array(' ', '(', ')', '-'),'', esc_attr($eczane['phone'])); ?>">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="64" stroke-dashoffset="64" d="M8 3c0.5 0 2.5 4.5 2.5 5c0 1 -1.5 2 -2 3c-0.5 1 0.5 2 1.5 3c0.39 0.39 2 2 3 1.5c1 -0.5 2 -2 3 -2c0.5 0 5 2 5 2.5c0 2 -1.5 3.5 -3 4c-1.5 0.5 -2.5 0.5 -4.5 0c-2 -0.5 -3.5 -1 -6 -3.5c-2.5 -2.5 -3 -4 -3.5 -6c-0.5 -2 -0.5 -3 0 -4.5c0.5 -1.5 2 -3 4 -3Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="64;0"/><animateTransform id="lineMdPhoneCallLoop0" fill="freeze" attributeName="transform" begin="0.6s;lineMdPhoneCallLoop0.begin+2.7s" dur="0.5s" type="rotate" values="0 12 12;15 12 12;0 12 12;-12 12 12;0 12 12;12 12 12;0 12 12;-15 12 12;0 12 12"/></path><path stroke-dasharray="4" stroke-dashoffset="4" d="M15.76 8.28c-0.5 -0.51 -1.1 -0.93 -1.76 -1.24M15.76 8.28c0.49 0.49 0.9 1.08 1.2 1.72"><animate fill="freeze" attributeName="stroke-dashoffset" begin="lineMdPhoneCallLoop0.begin+0s" dur="2.7s" keyTimes="0;0.111;0.259;0.37;1" values="4;0;0;4;4"/></path><path stroke-dasharray="6" stroke-dashoffset="6" d="M18.67 5.35c-1 -1 -2.26 -1.73 -3.67 -2.1M18.67 5.35c0.99 1 1.72 2.25 2.08 3.65"><animate fill="freeze" attributeName="stroke-dashoffset" begin="lineMdPhoneCallLoop0.begin+0.2s" dur="2.7s" keyTimes="0;0.074;0.185;0.333;0.444;1" values="6;6;0;0;6;6"/></path></g></svg>
                                                                    Ara
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <p class="small pt-2 m-0"><a href="tel:+9<?php echo str_replace(array(' ', '(', ')', '-'),'', esc_attr($eczane['phone'])); ?>" class="text-primary text-decoration-none"><?php echo str_replace(array('-',')','('),' ', esc_html($eczane['phone'])); ?></a></p>
                                                    </div>
                                                    <?php endif; ?>

                                                    <?php if(!empty($eczane['phone2'])): ?>
                                                    <div class="col mb-3">
                                                        <div class="row align-items-center">
                                                            <div class="col">
                                                                <h6 class="m-0 small">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="me-1">
                                                                        <path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1c-9.39 0-17-7.61-17-17c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.25.2 2.45.57 3.57c.11.35.03.74-.25 1.02z"/>
                                                                    </svg>
                                                                    Telefon 2
                                                                </h6>
                                                            </div>
                                                            <div class="col-auto">
                                                                <a class="btn btn-success btn-sm shadow-none rounded small-2 text-normal fw-normal p-1 px-2" href="tel:+9<?php echo str_replace(array(' ', '(', ')', '-'),'', esc_attr($eczane['phone2'])); ?>">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="64" stroke-dashoffset="64" d="M8 3c0.5 0 2.5 4.5 2.5 5c0 1 -1.5 2 -2 3c-0.5 1 0.5 2 1.5 3c0.39 0.39 2 2 3 1.5c1 -0.5 2 -2 3 -2c0.5 0 5 2 5 2.5c0 2 -1.5 3.5 -3 4c-1.5 0.5 -2.5 0.5 -4.5 0c-2 -0.5 -3.5 -1 -6 -3.5c-2.5 -2.5 -3 -4 -3.5 -6c-0.5 -2 -0.5 -3 0 -4.5c0.5 -1.5 2 -3 4 -3Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="64;0"/><animateTransform id="lineMdPhoneCallLoop0" fill="freeze" attributeName="transform" begin="0.6s;lineMdPhoneCallLoop0.begin+2.7s" dur="0.5s" type="rotate" values="0 12 12;15 12 12;0 12 12;-12 12 12;0 12 12;12 12 12;0 12 12;-15 12 12;0 12 12"/></path><path stroke-dasharray="4" stroke-dashoffset="4" d="M15.76 8.28c-0.5 -0.51 -1.1 -0.93 -1.76 -1.24M15.76 8.28c0.49 0.49 0.9 1.08 1.2 1.72"><animate fill="freeze" attributeName="stroke-dashoffset" begin="lineMdPhoneCallLoop0.begin+0s" dur="2.7s" keyTimes="0;0.111;0.259;0.37;1" values="4;0;0;4;4"/></path><path stroke-dasharray="6" stroke-dashoffset="6" d="M18.67 5.35c-1 -1 -2.26 -1.73 -3.67 -2.1M18.67 5.35c0.99 1 1.72 2.25 2.08 3.65"><animate fill="freeze" attributeName="stroke-dashoffset" begin="lineMdPhoneCallLoop0.begin+0.2s" dur="2.7s" keyTimes="0;0.074;0.185;0.333;0.444;1" values="6;6;0;0;6;6"/></path></g></svg>
                                                                    Ara
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <p class="small pt-2 m-0"><a href="tel:+9<?php echo str_replace(array(' ', '(', ')', '-'),'', esc_attr($eczane['phone2'])); ?>" class="text-primary text-decoration-none"><?php echo str_replace(array('-',')','('),' ', esc_html($eczane['phone2'])); ?></a></p>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if(isset($eczane['latitude']) && isset($eczane['longitude'])): ?>
                                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $eczane['latitude']; ?>,<?php echo $eczane['longitude']; ?>" 
                                                target="_blank" 
                                                class="btn btn-primary btn-sm btn-block py-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="9" r="2.5" fill="currentColor" fill-opacity="0"><animate fill="freeze" attributeName="fill-opacity" begin="0.7s" dur="0.15s" values="0;1"/></circle><path fill="none" stroke="currentColor" stroke-dasharray="48" stroke-dashoffset="48" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20.5c0 0 -6 -7 -6 -11.5c0 -3.31 2.69 -6 6 -6c3.31 0 6 2.69 6 6c0 4.5 -6 11.5 -6 11.5Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="48;0"/><animateTransform attributeName="transform" dur="3s" keyTimes="0;0.3;0.4;0.54;0.6;0.68;0.7;1" repeatCount="indefinite" type="rotate" values="0 12 20.5;0 12 20.5;-8 12 20.5;0 12 20.5;5 12 20.5;-2 12 20.5;0 12 20.5;0 12 20.5"/></path></svg>
                                                    Haritada Göster
                                                </a>
                                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                                    </div>
                                    <div class="swiper-pagination position-relative mt-2"></div>
                                </div>
                            </div> <!-- / district tab-pane -->
                            <?php $d_idx++; endforeach; ?>
                        </div> <!-- / district tab-content -->
                    <?php else: ?>
                        <div class="text-center mt-4 mb-2 small">Bu güne ait nöbetçi eczane listesi bulunmuyor.</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" class="mb-3">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2m-2 15l-5-5l1.41-1.41L10 14.17l7.59-7.59L19 8z"/>
                </svg>
                <h4>Nöbetçi Eczane Bulunamadı</h4>
                <p class="mb-0 small">Şu anda <?php echo ucfirst($selected_city); ?> için nöbetçi eczane bilgisi bulunmuyor.</p>
                <div class="mt-3">
                    <a href="<?php echo get_permalink(); ?>" class="btn btn-primary">Farklı Şehir Seç</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?=mevzu_class()?>">
        <h2 class="mb-0">
            Önemli Bilgiler
        </h2>
        <div class="p-3">
            <ul class="m-0 px-3">
                <li>Nöbetçi eczane listesi günlük olarak güncellenmektedir.</li>
                <li>Eczaneye gitmeden önce telefonla arayarak açık olduğunu teyit etmenizi öneririz.</li>
                <li>Acil durumlarda 112 Acil Servis hattını arayabilirsiniz.</li>
                <li>Eczaneler ilçe bazında gruplandırılmıştır.</li>
                <li>Harita linklerine tıklayarak eczanenin konumunu görebilirsiniz.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Swiper başlatmaları
    document.querySelectorAll('.swiper-slider2').forEach(function(el) {
        new Swiper(el, {
            loop: false, 
            observer: true,
            observeParents: true,
            lazy: {
                loadPrevNext: true,
            },
            spaceBetween: 30,
            slidesPerView: 1,
            speed: 300,
            autoplay: {
                delay: 8000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: el.querySelector('.swiper-pagination'),
                clickable: true,
            },
            breakpoints: {
                0: { slidesPerView: 1 },
                600: { slidesPerView: 2 },
                1000: { slidesPerView: 3 }
            }
        });
    });

    // Bootstrap tooltips'leri başlat
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new mevzu2.Tooltip(tooltipTriggerEl, {
        html: true,
        delay: { show: 500, hide: 100 }
    }));
});
</script>

<?php get_footer(); ?> 