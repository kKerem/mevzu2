<?php
/**
 * Firma Rehberi — Bootstrap
 *
 * Mevzu² temasına özel Firma Rehberi modülü.
 * CPT, taxonomy, admin ayarları, shortcode ve ön yüz formunu yükler.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FIRMA_REHBERI_PATH', __DIR__ . '/' );
define( 'FIRMA_REHBERI_URL',  get_template_directory_uri() . '/inc/firma-rehberi/' );
define( 'FIRMA_REHBERI_VER',  '1.0.0' );

require_once FIRMA_REHBERI_PATH . 'class-firma-cpt.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-metabox.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-admin.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-notification.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-form.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-shortcode.php';
require_once FIRMA_REHBERI_PATH . 'class-firma-widgets.php';

new Firma_CPT();
new Firma_Metabox();
new Firma_Admin();
new Firma_Form();
new Firma_Shortcode();
