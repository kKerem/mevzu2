<?php
/**
 * Harici servis anahtarları — ŞABLON.
 *
 * Bu dosyayı `config-keys.php` olarak kopyalayıp değerleri doldurun.
 * `config-keys.php` git'e DAHİL DEĞİLDİR ve yalnızca yayın zip'ine enjekte edilir.
 *
 * Alternatif: değerleri wp-config.php içinde sabit olarak tanımlayabilirsiniz
 * (MEVZU_LICENSE_SHARED_SECRET, MEVZU_WEATHER_API_KEY, MEVZU_DIYANET_API_KEY,
 * MEVZU_ECZANE_API_KEYS). Sabit tanımlıysa o kazanır.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'license_shared_secret' => '',
    'weather_api_key'       => '',
    'diyanet_api_key'       => '',
    'eczane_api_keys'       => [],
];
