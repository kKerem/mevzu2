<?php
/**
 * Harici servis anahtarlarının tek okuma noktası.
 *
 * Öncelik: wp-config.php sabiti → inc/config-keys.php → varsayılan.
 * Gerçek değerler git'e DAHİL DEĞİLDİR (bkz. .gitignore).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'mevzu_key' ) ) {
    /**
     * @param string $ad         license_shared_secret|weather_api_key|diyanet_api_key|eczane_api_keys
     * @param mixed  $varsayilan Anahtar hiçbir kaynakta yoksa dönecek değer.
     * @return mixed
     */
    function mevzu_key( string $ad, $varsayilan = '' ) {
        static $dosya_degerleri = null;

        $sabitler = [
            'license_shared_secret' => 'MEVZU_LICENSE_SHARED_SECRET',
            'weather_api_key'       => 'MEVZU_WEATHER_API_KEY',
            'diyanet_api_key'       => 'MEVZU_DIYANET_API_KEY',
            'eczane_api_keys'       => 'MEVZU_ECZANE_API_KEYS',
        ];

        if ( isset( $sabitler[ $ad ] ) && defined( $sabitler[ $ad ] ) ) {
            $deger = constant( $sabitler[ $ad ] );
            if ( '' !== $deger && [] !== $deger && null !== $deger ) {
                return $deger;
            }
        }

        if ( null === $dosya_degerleri ) {
            $yol = __DIR__ . '/config-keys.php';
            $dosya_degerleri = file_exists( $yol ) ? (array) require $yol : [];
        }

        return array_key_exists( $ad, $dosya_degerleri )
            ? $dosya_degerleri[ $ad ]
            : $varsayilan;
    }
}
