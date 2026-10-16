<?php
if (!defined('ABSPATH')) exit;

$crea3_ilp_raw_value = (string) get_option(CREA3_ILP_EXCLUDED_IDS_OPTION, '');
$crea3_ilp_threshold = (int) get_option(CREA3_ILP_CLUSTER_THRESHOLD_OPTION, 3);

echo '<div class="cma-settings-wrapper">';

echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cma-card">';
wp_nonce_field('crea3_ilp_save_settings_action', 'crea3_ilp_settings_nonce');

echo '<input type="hidden" name="action" value="crea3_ilp_save_settings">';

/* =========================
   SECTION 1 : EXCLUSIONS
========================= */

echo '<div class="cma-section">';
echo '<h2>🚫 ' . esc_html__('Excluded content', 'crea3-internal-linking-pro') . '</h2>';

echo '<p class="cma-subtitle">';
echo esc_html__('Define which pages should be ignored during the internal linking analysis.', 'crea3-internal-linking-pro');
echo '</p>';

echo '<label for="crea3_ilp_excluded_ids">' . esc_html__('IDs to exclude', 'crea3-internal-linking-pro') . '</label>';

echo '<textarea id="crea3_ilp_excluded_ids" name="crea3_ilp_excluded_ids" rows="6" placeholder="12, 45, 78">' 
    . esc_textarea($crea3_ilp_raw_value) . 
'</textarea>';

echo '<p class="cma-help">';
echo esc_html__('Separate IDs with commas, spaces or line breaks.', 'crea3-internal-linking-pro');
echo '</p>';

echo '<div class="cma-tip">';
echo '💡 ' . esc_html__('Example: sponsored articles, legal pages, temporary campaigns.', 'crea3-internal-linking-pro');
echo '</div>';

echo '</div>';

/* =========================
   SECTION 2 : CLUSTERS
========================= */

echo '<div class="cma-section">';
echo '<h2>🕸️ ' . esc_html__('Cluster settings', 'crea3-internal-linking-pro') . '</h2>';

echo '<p class="cma-subtitle">';
echo esc_html__('Control how clusters are detected based on internal linking strength.', 'crea3-internal-linking-pro');
echo '</p>';

echo '<label for="crea3_ilp_cluster_threshold">';
echo esc_html__('Minimum incoming links', 'crea3-internal-linking-pro');
echo '</label>';

echo '<input 
    id="crea3_ilp_cluster_threshold"
    type="number" 
    name="crea3_ilp_cluster_threshold" 
    value="' . esc_attr($crea3_ilp_threshold) . '" 
    min="1" 
    max="100"
>';


echo '<div class="cma-scale">';
echo '<span>'.esc_html__('3 = weak', 'crea3-internal-linking-pro').'</span>';
echo '<span>'.esc_html__('8–10 = strong cluster', 'crea3-internal-linking-pro').'</span>';
echo '</div>';

echo '</div>';

/* =========================
   SUBMIT
========================= */

echo '<div class="cma-footer">';
submit_button(__('Save settings', 'crea3-internal-linking-pro'), 'primary', '', false);
echo '</div>';

echo '</form>';
echo '</div>';
