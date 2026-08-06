<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#000">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="<?php bloginfo('description'); ?>">
	<meta name="application-name" content="<?php bloginfo('description'); ?>">
	<meta name="msapplication-TileImage" content="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>" />
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
	<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
	<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
	<link rel="dns-prefetch" href="https://mc.yandex.ru">
	<link rel="dns-prefetch" href="https://cdn.p.analitik.bik.gov.tr">

	<link rel="shortcut icon" type="image/png" href="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>" />
	<link rel="apple-touch-icon" href="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>" />
	<link rel="apple-touch-startup-image" ref="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>">
	<link rel="icon" href="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>" sizes="32x32" />
	<link rel="icon" href="<?php echo get_opt_img('options_favicon') ?: get_template_directory_uri() . '/img/favicon.png'; ?>" sizes="192x192" />
	<?php if (get_option('options_header_alan')) echo get_option('options_header_alan'); ?>
	<link rel="stylesheet" href="<?php bloginfo('template_url') ?>/css/mevzu2.min.css" />
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php bloginfo('template_url') ?>/css/fonts/remixicon.css" />
	<?php
	$mevzu_select2_css  = mevzu_asset_url('select2_source', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', '/css/vendor/select2.min.css', 'local');
	$mevzu_select2_theme_css = mevzu_asset_url('select2_source', 'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css', '/css/vendor/select2-bootstrap-5-theme.min.css', 'local');
	$mevzu_swiper_css   = mevzu_asset_url('swiper_source', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', '/css/vendor/swiper-bundle.min.css', 'local');
	?>
	<link rel="stylesheet" href="<?php echo esc_url($mevzu_select2_css); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url($mevzu_select2_theme_css); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url($mevzu_swiper_css); ?>" />
	<style type="text/css">
		<?php 
		// Özelleştirici / theme_mod öncelikli; yoksa tema ayarları (options_site_rengi)
		$site_rengi = get_theme_mod('mevzu_primary_color');
		if (empty($site_rengi) || !is_string($site_rengi)) {
			$site_rengi = get_option('options_site_rengi', '#e90808');
		}
		if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $site_rengi)) {
			$hex = str_replace('#', '', $site_rengi);
			if (strlen($hex) == 3) {
				$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
				$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
				$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
			} else {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
			}
		} else {
			$r = 233; $g = 8; $b = 8; // Default red
		}
		$site_rengi_rgb = "$r, $g, $b";
		?>
		:root { --mevzu-primary: <?php echo $site_rengi; ?>; --mevzu-primary-rgb: <?php echo $site_rengi_rgb; ?>; }
		.badge-primary { background: rgba(var(--mevzu-primary-rgb), .2); color: var(--mevzu-primary-rgb) }
		body.dark .bg-primary { background: rgba(var(--mevzu-primary-rgb), .8) }
	</style>
</head>

<body <?php body_class(); ?>>

	<?php wp_body_open(); ?>

	<?php
	if (get_option('options_header_sablon'))
		get_template_part("sablon/header-" . get_option('options_header_sablon'));
	else
		get_template_part("sablon/header-sablon1");
	?>
	<main id="main-content" role="main">