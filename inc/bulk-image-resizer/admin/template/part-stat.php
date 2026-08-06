<?php
// gestisco le statistiche
namespace bulk_image_resizer;
?>
<div class="row g-3 stat-info-row-2-cols">
    <div class="col-12 col-xl-6 stat-col">
        <div class="bir-stat-box h-100">
                <h3 class="h5">Toplam optimizasyon</h3>
        <div class="stat-info-box" id="stat_box_filesize_info"></div>
        <div id="stat_box_filesize"></div>
        <div>
            <div class="my-2">
            <span class="bir-stat-legend bir-stat-legend-before"></span> <b>Optimize edilmemiş görseller</b>: Eklenti kullanılmadan önceki toplam boyut
            </div>
            <div class="my-2">
            <span class="bir-stat-legend bir-stat-legend-after"></span> <b>Optimize edilmiş görseller</b>: Eklenti optimizasyonundan sonraki toplam boyut
            </div>
        </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="bir-stat-box h-100">
                <h3 class="h5">Optimizasyon geçmişi</h3>
        <div class="stat-info-box" id="stat_box_filesize_history_info"></div>
        <div id="stat_box_filesize_history">
            <canvas id="filesize_history_info_chart"></canvas>
        </div>
        </div>
    </div>
</div>