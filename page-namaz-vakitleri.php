<?php get_header(); ?>

<div class="container namaz">
    
    <div class="single-breadcrumb">
        <?php custom_breadcrumbs(); ?>
    </div>

    <?php echo reklam('govde_ust_reklam'); ?>

    <!-- Şehir Seçimi Formu -->
    <?php 
        $city_raw = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : mevzu_get_current_city();
        $city = normalizeString(strtoupper($city_raw));
    ?>

    <div class="bg-white rounded shadow-sm">

        <div class="bg p-3 mt-3 rounded-top">
            <h1 class="text-center fs-5 text-white fw-semibold text-uppercase"><?php echo $city; ?></h1>
            <div class="text-center mt-3">
                <h4 id="countdown-text" class="text-light small fw-semibold text-uppercase"></h4>
                <div id="countdown-timer" class="display-4 fw-bold text-white">00:00:00</div>
            </div>
            <hr class="my-2">
            <div class="row text-center" id="prayer-times-blocks">
            </div>
        </div>

        <div class="p-3">

            <form method="GET" action="" class="mb-3">
                <div class="row align-items-center gy-3">
                    <div class="col-auto">
                        <label for="city" class="small fw-semibold">Şehir Seçimi:</label>
                    </div>
                    <div class="col-auto">
                        <select name="city" id="city" class="select2 w-100 shadow-sm" onchange="this.form.submit()">
                            <option value="">-- Şehir Seçin --</option>
                            <?php
                            foreach (diyanet_sehirler() as $id => $name) {
                                $selected = (strtoupper($city) === $name) ? 'selected' : '';
                                echo '<option value="' . esc_attr($name) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto small fw-semibold pe-0">
                        Güncel Saat
                    </div>
                    <div class="col-auto small">
                        <?php echo date('d.m.Y' . ' ' . get_option('time_format'), current_time('timestamp')); ?>
                    </div>
                </div>
            </form>

            <?php
            if (!$city) {
                echo '<div class="alert alert-warning">Lütfen bir şehir seçin.</div>';
            } else {
                $cityId = array_search(normalizeString($city), diyanet_sehirler(), true);
                if (!$cityId) {
                    echo '<div class="alert alert-warning">Geçersiz şehir.</div>';
                } else {
                    $prayerTimes = get_prayer_times_data($cityId);
                    if (isset($prayerTimes['error'])) {
                        echo '<div class="alert alert-danger">' . esc_html($prayerTimes['error']) . '</div>';
                    } else {
                        echo '<script>';
                        echo 'const prayerTimes = ' . json_encode($prayerTimes) . ';';
                        echo '</script>';
                        echo get_prayer_times_table($prayerTimes);
                    }
                }
            }
            ?>
        </div>
        
    </div>

</div>

<?php get_footer(); ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
   $("#city").select2({
    theme: "bootstrap-5",
    selectionCssClass: "select2--small",
    dropdownCssClass: "select2--small",
}).on('change', function() {
    var secilen = $(this).val();
    var d = new Date();
    d.setTime(d.getTime() + (365*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = "mevzu_hava_sehir=" + secilen + ";" + expires + ";path=/";
    this.form.submit();
});

// Namaz vakitleri bloklarını ve geri sayımı güncelle
function updatePrayerTimesBlocks(prayerTimes) {
    const now = new Date();
    const blocksContainer = document.getElementById('prayer-times-blocks');
    const countdownText = document.getElementById('countdown-text');
    const countdownTimer = document.getElementById('countdown-timer');

    let nextPrayer = null;
    let nextPrayerTime = null;

    blocksContainer.innerHTML = '';

    // Bugünkü namaz vakitlerini işle
    const prayerNames = ['İmsak', 'Güneş', 'Öğle', 'İkindi', 'Akşam', 'Yatsı'];
    const prayerKeys = ['imsak', 'gunes', 'ogle', 'ikindi', 'aksam', 'yatsi'];

    prayerNames.forEach((name, index) => {
        const prayerTime = new Date(`${now.toDateString()} ${prayerTimes[0][prayerKeys[index]]}`);
        const block = document.createElement('div');
        block.className = 'col-6 col-md-2 mb-3';

        let blockClass = 'p-3 rounded text-center';
        if (prayerTime < now) {
            blockClass += ' bg-blur text-white'; // Geçmiş namaz
        } else if (!nextPrayer || prayerTime < nextPrayerTime) {
            nextPrayer = name;
            nextPrayerTime = prayerTime;
            blockClass += ' bg-primary text-white'; // Bir sonraki namaz
        } else {
            blockClass += ' bg-blur'; // Diğer namazlar
        }

        block.innerHTML = `
            <div class="${blockClass}">
                <div class="fw-bold">${name}</div>
                <div>${prayerTimes[0][prayerKeys[index]]}</div>
            </div>
        `;
        blocksContainer.appendChild(block);
    });

    // Eğer bugünkü namaz vakitleri tamamlandıysa, yarınki imsak vaktini göster
    if (!nextPrayer) {
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        nextPrayer = 'İmsak';
        nextPrayerTime = new Date(`${tomorrow.toDateString()} ${prayerTimes[0]['imsak']}`);
        countdownText.innerText = `${nextPrayer} için kalan süre`;
    } else {
        countdownText.innerText = `${nextPrayer} için kalan süre`;
    }

    startCountdown(nextPrayerTime, countdownTimer);
}

// Geri sayımı başlat
function startCountdown(targetTime, timerElement) {
    const interval = setInterval(() => {
        const now = new Date();
        const diff = targetTime - now;

        if (diff <= 0) {
            clearInterval(interval);
            timerElement.innerText = '00:00:00';
            location.reload(); // Sayfayı yenile
        } else {
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            timerElement.innerText = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    }, 1000);
}

// Sayfa yüklendiğinde blokları ve geri sayımı güncelle
document.addEventListener('DOMContentLoaded', () => {
    if (Array.isArray(prayerTimes)) {
        updatePrayerTimesBlocks(prayerTimes);
    } else {
        console.error('prayerTimes bir dizi değil:', prayerTimes);
    }
});
</script>