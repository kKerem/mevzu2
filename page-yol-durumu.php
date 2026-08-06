<?php
/* Template Name: Yol Durumu */
get_header();
?>

<div class="container my-3">
    <div class="single-breadcrumb mb-3">
        <?php custom_breadcrumbs(); ?>
    </div>

    <?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

    <?php while (have_posts()) : the_post(); ?>
        
        <div class="mb-3">
            <?php
            $selected_city = isset($_GET['sehir']) ? urldecode($_GET['sehir']) : get_option('options_varsayilan_sehir');
            $sehirler = ["Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", "Amasya", "Ankara", "Antalya", "Artvin", "Aydın", "Balıkesir", 
                "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur", "Bursa", "Çanakkale", "Çankırı", "Çorum", "Denizli", "Diyarbakır", 
                "Edirne", "Elazığ", "Erzincan", "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane", "Hakkâri", "Hatay", 
                "Isparta", "Mersin", "İstanbul", "İzmir", "Kars", "Kastamonu", "Kayseri", "Kırklareli", "Kırşehir", "Kocaeli", 
                "Konya", "Kütahya", "Malatya", "Manisa", "Kahramanmaraş", "Mardin", "Muğla", "Muş", "Nevşehir", "Niğde", "Ordu", 
                "Rize", "Sakarya", "Samsun", "Siirt", "Sinop", "Sivas", "Tekirdağ", "Tokat", "Trabzon", "Tunceli", "Şanlıurfa", 
                "Uşak", "Van", "Yozgat", "Zonguldak", "Aksaray", "Bayburt", "Karaman", "Kırıkkale", "Batman", "Şırnak", 
                "Bartın", "Ardahan", "Iğdır", "Yalova", "Karabük", "Kilis", "Osmaniye", "Düzce"];
            ?>
            <div class="row align-items-center small">
                <div class="col-auto small-2 text-uppercase fw-semibold text-body pe-0">
                    Şehir Seçimi:
                </div>
                <div class="col col-md-auto pe-0">
                    <select id="sehir" class="form-control select2-bootstrap5">
                        <?php foreach ($sehirler as $sehir): ?>
                            <option value="<?= $sehir ?>" <?= ($sehir == $selected_city) ? 'selected' : '' ?>>
                                <?= $sehir ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div id="map" class="rounded overflow-hidden shadow-sm" style="height: 400px"></div>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>

<!-- Select2 CSS ve JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Yandex Haritalar API -->
<script src="https://api-maps.yandex.ru/2.1/?apikey=9dccf027-6130-4d86-84f0-e28aca33f440&lang=tr_TR"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        jQuery('#sehir').select2();

        const cityCoords = {
            "Adana": [37.0000, 35.3213], "Adıyaman": [37.7648, 38.2786], "Afyonkarahisar": [38.7507, 30.5567],
            "Ağrı": [39.7214, 43.0503], "Amasya": [40.6499, 35.8353], "Ankara": [39.9208, 32.8541],
            "Antalya": [36.8969, 30.7133], "Artvin": [41.1828, 41.8183], "Aydın": [37.8404, 27.8416],
            "Balıkesir": [39.6484, 27.8826], "Bilecik": [40.1432, 29.9793], "Bingöl": [38.8846, 40.4939],
            "Bitlis": [38.4015, 42.1078], "Bolu": [40.5760, 31.5788], "Burdur": [37.7203, 30.2908],
            "Bursa": [40.1826, 29.0601], "Çanakkale": [40.1553, 26.4142], "Çankırı": [40.6013, 33.6134],
            "Çorum": [40.5489, 34.9534], "Denizli": [37.7833, 29.0947], "Diyarbakır": [37.9144, 40.2306],
            "Edirne": [41.6771, 26.5557], "Elazığ": [38.6743, 39.2232], "Erzincan": [39.7506, 39.4925],
            "Erzurum": [39.9058, 41.2658], "Eskişehir": [39.7833, 31.1667], "Gaziantep": [37.0662, 37.3833],
            "Giresun": [40.9175, 38.3927], "Gümüşhane": [40.4603, 39.4812], "Hakkâri": [37.5737, 43.7408],
            "Hatay": [36.4018, 36.3498], "Isparta": [37.7648, 30.5567], "Mersin": [36.8000, 34.6333],
            "İstanbul": [41.0082, 28.9784], "İzmir": [38.4237, 27.1428], "Kars": [40.6013, 43.0975],
            "Kastamonu": [41.3887, 33.7827], "Kayseri": [38.7333, 35.4833], "Kırklareli": [41.7357, 27.2250],
            "Kırşehir": [39.1459, 34.1607], "Kocaeli": [40.8533, 29.8815], "Konya": [37.8714, 32.4827],
            "Kütahya": [39.4176, 29.9855], "Malatya": [38.3552, 38.3095], "Manisa": [38.6120, 27.4265],
            "Kahramanmaraş": [37.5731, 36.9371], "Mardin": [37.3212, 40.7245], "Muğla": [37.2165, 28.3636],
            "Muş": [38.9462, 41.7539], "Nevşehir": [38.6244, 34.7140], "Niğde": [37.9667, 34.6799],
            "Ordu": [40.9833, 37.8833], "Rize": [41.0245, 40.5234], "Sakarya": [40.7569, 30.3782],
            "Samsun": [41.2867, 36.33], "Siirt": [37.9329, 41.9405], "Sinop": [42.0263, 35.1551],
            "Sivas": [39.7477, 37.0179], "Tekirdağ": [40.9780, 27.5110], "Tokat": [40.3167, 36.55],
            "Trabzon": [41.0015, 39.7178], "Tunceli": [39.1081, 39.5471], "Şanlıurfa": [37.1674, 38.7955],
            "Uşak": [38.6823, 29.4082], "Van": [38.5012, 43.3723], "Yalova": [40.6550, 29.2769],
            "Yozgat": [39.8181, 34.8147], "Zonguldak": [41.4564, 31.7987], "Aksaray": [38.3687, 34.0370],
            "Bayburt": [40.2552, 40.2249], "Karaman": [37.1810, 33.2229], "Kırıkkale": [39.8453, 33.5064],
            "Batman": [37.8828, 41.1301], "Şırnak": [37.4187, 42.4918], "Bartın": [41.6359, 32.3375],
            "Ardahan": [41.1105, 42.7022], "Iğdır": [39.8871, 44.0048], "Karabük": [41.2044, 32.6227],
            "Kilis": [37.1167, 37.3833], "Osmaniye": [37.0681, 36.2619], "Düzce": [40.8438, 31.1565]
        };

        const defaultCity = '<?php echo addslashes($selected_city); ?>';

        let map;
        ymaps.ready(function () {
            map = new ymaps.Map("map", {
                center: cityCoords[defaultCity],
                zoom: 13,
                controls: ['zoomControl']
            });

            const trafficControl = new ymaps.control.TrafficControl({
                state: { trafficShown: true }
            });
            map.controls.add(trafficControl);
            trafficControl.getProvider('traffic#actual').state.set('trafficShown', true);
        });

        jQuery('#sehir').on('change', function () {
            const selected = jQuery(this).val();
            if (cityCoords[selected]) {
                map.setCenter(cityCoords[selected], 13, {
                    checkZoomRange: true
                });
            }
        });
    });
</script>
