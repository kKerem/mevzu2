<?php
/**
 * mevzu_key() için WordPress'siz test.
 * Çalıştırma: php tests/test-keys.php
 */
define( 'ABSPATH', __DIR__ . '/../' );

$gecici = sys_get_temp_dir() . '/mevzu-test-keys-' . getmypid();
mkdir( $gecici );
copy( __DIR__ . '/../inc/keys.php', $gecici . '/keys.php' );
file_put_contents( $gecici . '/config-keys.php', <<<'PHP'
<?php
return [
    'weather_api_key' => 'dosyadan-gelen',
    'eczane_api_keys' => [ 'a', 'b' ],
];
PHP
);

define( 'MEVZU_LICENSE_SHARED_SECRET', 'sabitten-gelen' );
require $gecici . '/keys.php';

$basarisiz = 0;
function kontrol( $ad, $beklenen, $sonuc ) {
    global $basarisiz;
    if ( $sonuc !== $beklenen ) {
        printf( "BASARISIZ: %s -> %s (beklenen %s)\n", $ad,
            var_export( $sonuc, true ), var_export( $beklenen, true ) );
        $basarisiz = 1;
    }
}

// 1. Sabit tanımlıysa sabit kazanır.
kontrol( 'sabit onceligi', 'sabitten-gelen', mevzu_key( 'license_shared_secret' ) );
// 2. Sabit yoksa config-keys.php'den okunur.
kontrol( 'dosyadan okuma', 'dosyadan-gelen', mevzu_key( 'weather_api_key' ) );
// 3. Dizi değerler bozulmadan döner.
kontrol( 'dizi degeri', [ 'a', 'b' ], mevzu_key( 'eczane_api_keys' ) );
// 4. Bilinmeyen anahtar varsayılanı döner.
kontrol( 'varsayilan', 'yok', mevzu_key( 'olmayan_anahtar', 'yok' ) );
// 5. Varsayılan verilmezse boş dize döner.
kontrol( 'bos varsayilan', '', mevzu_key( 'olmayan_anahtar_2' ) );

unlink( $gecici . '/keys.php' );
unlink( $gecici . '/config-keys.php' );
rmdir( $gecici );

echo $basarisiz ? "TESTLER BASARISIZ\n" : "TUM TESTLER GECTI\n";
exit( $basarisiz );
