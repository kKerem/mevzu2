<?php
/**
 * Mevzu² AI — yapılandırma
 *
 * Ses sentezi lisans.kkerem.com üzerinden (Mevzu² AI) yapılır;
 * Google API anahtarı temada tutulmaz.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/constants.php';

/** Mevzu² AI API kökü */
if ( ! defined( 'MEVZU_AI_API_BASE' ) ) {
	define( 'MEVZU_AI_API_BASE', 'https://lisans.kkerem.com/api/v1/mevzu-ai/' );
}

/** Yerel gösterim / yedek üst sınır (asıl kota sunucuda) */
if ( ! defined( 'MEVZU_TTS_DAILY_LIMIT' ) ) {
	define( 'MEVZU_TTS_DAILY_LIMIT', 40 );
}

define( 'MEVZU_TTS_VERSION', '2.6.1' );
