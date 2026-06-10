<?php
if (!defined('ABSPATH')) exit;

$raw_value = (string) get_option(CMA_EXCLUDED_IDS_OPTION, '');
$threshold = (int) get_option('cma_cluster_threshold', 3);

echo '<div class="cma-settings-wrapper">';

echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cma-card">';
wp_nonce_field('cma_save_settings_action', 'cma_settings_nonce');

echo '<input type="hidden" name="action" value="cma_save_settings">';

/* =========================
   SECTION 1 : EXCLUSIONS
========================= */

echo '<div class="cma-section">';
echo '<h2>🚫 ' . esc_html__('Excluded content', 'internal-linking-pro') . '</h2>';

echo '<p class="cma-subtitle">';
echo esc_html__('Define which pages should be ignored during the internal linking analysis.', 'internal-linking-pro');
echo '</p>';

echo '<label for="cma_excluded_ids">' . esc_html__('IDs to exclude', 'internal-linking-pro') . '</label>';

echo '<textarea id="cma_excluded_ids" name="cma_excluded_ids" rows="6" placeholder="12, 45, 78">' 
    . esc_textarea($raw_value) . 
'</textarea>';

echo '<p class="cma-help">';
echo esc_html__('Separate IDs with commas, spaces or line breaks.', 'internal-linking-pro');
echo '</p>';

echo '<div class="cma-tip">';
echo '💡 ' . esc_html__('Example: sponsored articles, legal pages, temporary campaigns.', 'internal-linking-pro');
echo '</div>';

echo '</div>';

/* =========================
   SECTION 2 : CLUSTERS
========================= */

echo '<div class="cma-section">';
echo '<h2>🕸️ ' . esc_html__('Cluster settings', 'internal-linking-pro') . '</h2>';

echo '<p class="cma-subtitle">';
echo esc_html__('Control how clusters are detected based on internal linking strength.', 'internal-linking-pro');
echo '</p>';

echo '<label for="cma_cluster_threshold">';
echo esc_html__('Minimum incoming links', 'internal-linking-pro');
echo '</label>';

echo '<input 
    id="cma_cluster_threshold"
    type="number" 
    name="cma_cluster_threshold" 
    value="' . esc_attr($threshold) . '" 
    min="1" 
    max="100"
>';


echo '<div class="cma-scale">';
echo '<span>'.esc_html__('3 = weak', 'internal-linking-pro').'</span>';
echo '<span>'.esc_html__('8–10 = strong cluster', 'internal-linking-pro').'</span>';
echo '</div>';

echo '</div>';

/* =========================
   SUBMIT
========================= */

echo '<div class="cma-footer">';
submit_button(__('Save settings', 'internal-linking-pro'), 'primary', '', false);
echo '</div>';

echo '</form>';
echo '</div>';


/* =========================
   SCRIPT
========================= */

echo "<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('#cma_cluster_threshold');

    input.addEventListener('input', () => {
        let value = parseInt(input.value);

        if (value <= 3) {
            input.style.borderColor = '#f59e0b'; // orange
        } else if (value <= 7) {
            input.style.borderColor = '#3b82f6'; // bleu
        } else {
            input.style.borderColor = '#10b981'; // vert
        }
    });
});
</script>";
