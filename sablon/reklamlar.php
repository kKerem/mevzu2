<?php
if (!class_exists('Mevzu_Ads_Manager')) {
    return;
}

$side_enabled = (int) get_option('options_yan_reklam_alani', 0);
if (!$side_enabled) {
    return;
}

$side_display = get_option('options_yan_reklam_goruntuleme', 'tumu');
if (!Mevzu_Ads_Manager::is_display_context($side_display)) {
    return;
}

$left_data  = Mevzu_Ads_Manager::get('yan_sol');
$right_data = Mevzu_Ads_Manager::get('yan_sag');

if (empty($left_data['active']) && empty($right_data['active'])) {
    return;
}

$left_fixed  = (int) get_option('options_yan_reklam_fixed_sol', 0) === 1 ? 'is-fixed' : '';
$right_fixed = (int) get_option('options_yan_reklam_fixed_sag', 0) === 1 ? 'is-fixed' : '';
?>
<div class="reklamlar d-none d-lg-block" aria-hidden="false">
    <div class="kose-reklam kose-reklam-sol <?php echo esc_attr($left_fixed); ?>">
        <?php mevzu_reklam('yan_sol'); ?>
    </div>
    <div class="kose-reklam kose-reklam-sag <?php echo esc_attr($right_fixed); ?>">
        <?php mevzu_reklam('yan_sag'); ?>
    </div>
</div>