<?php
if (!defined('ABSPATH')) exit;

$crea3_ilp_metrics = $analyzer->get_dashboard_metrics();
$crea3_ilp_clusters = $crea3_ilp_metrics['clusters'];

function crea3_ilp_get_score_class_coherence($score) {
    if ($score < 50) return 'red0';
    if ($score < 70) return 'ext';
    return 'in';
}

function crea3_ilp_get_score_class_fuite($score) {
    if ($score > 30) return 'red0';
    if ($score > 15) return 'ext';
    return 'in';
}

?>

<p><?php echo esc_html__('Check whether your pages are linked and organized into consistent SEO silos. Leakage measures only internal links sent outside the silo; external links are diagnosed separately.', 'crea3-internal-linking-pro'); ?></p>

<div class="cma-silos cma-table-wrap">

<table id="tableau" class="widefat cma-table-silos cma-dynamic-table striped">

<thead>
<tr>
    <th><?php echo esc_html__('Silo', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Pages', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Coherence', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Leakage', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Issues', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Recommendations', 'crea3-internal-linking-pro'); ?></th>
</tr>
</thead>

<tbody>

<?php foreach ($crea3_ilp_clusters as $crea3_ilp_cluster): 

    $crea3_ilp_analysis = $analyzer->analyze_silo($crea3_ilp_cluster);
    $crea3_ilp_pillar_url = (string)($crea3_ilp_cluster['pillar']['url'] ?? '');
    $crea3_ilp_pillar_path = $crea3_ilp_pillar_url ? (string)wp_parse_url($crea3_ilp_pillar_url, PHP_URL_PATH) : '';

?>

<tr>
    <td>
        <?php echo esc_html($crea3_ilp_cluster['pillar']['title']); ?>
        <?php if ($crea3_ilp_pillar_url): ?>
            <br>
            <small>
                <a class="link_post_cma" href="<?php echo esc_url($crea3_ilp_pillar_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($crea3_ilp_pillar_path ?: $crea3_ilp_pillar_url); ?>
                </a>
            </small>
        <?php endif; ?>
    </td>

    <td class="cma-center">
        <span class="cma-badge inpage">
            <?php echo intval($crea3_ilp_cluster['pages_count']); ?>
        </span>
    </td>

    <td class="cma-center">
        <span class="cma-badge <?php echo esc_attr(crea3_ilp_get_score_class_coherence($crea3_ilp_analysis['coherence'])); ?>">
            <?php echo intval($crea3_ilp_analysis['coherence']); ?>%
        </span>
        <small class="cma-silo-metric-detail">
            <?php /* translators: 1: retained links percentage, 2: lateral coverage percentage. */ echo esc_html(sprintf(__('%1$d%% retained · %2$d%% lateral', 'crea3-internal-linking-pro'), intval($crea3_ilp_analysis['retention']), intval($crea3_ilp_analysis['lateral_coverage']))); ?>
        </small>
    </td>

    <td class="cma-center">
        <span class="cma-badge <?php echo esc_attr(crea3_ilp_get_score_class_fuite($crea3_ilp_analysis['fuite'])); ?>">
            <?php echo intval($crea3_ilp_analysis['fuite']); ?>%
        </span>
        <small class="cma-silo-metric-detail">
            <?php /* translators: %d: number of links pointing outside the silo. */ echo esc_html(sprintf(_n('%d link outside silo', '%d links outside silo', intval($crea3_ilp_analysis['inter_silo_links']), 'crea3-internal-linking-pro'), intval($crea3_ilp_analysis['inter_silo_links']))); ?>
        </small>
    </td>

    <td>
        <?php if (!empty($crea3_ilp_analysis['issues'])): ?>
            <ul class="cma-issues">
                <?php foreach ($crea3_ilp_analysis['issues'] as $crea3_ilp_issue): ?>
                    <li><span class="dashicons dashicons-warning" aria-hidden="true"></span><?php echo esc_html($crea3_ilp_issue); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <span class="cma-silo-status-ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>OK</span>
        <?php endif; ?>
    </td>

    <td>
        <?php if (!empty($crea3_ilp_analysis['recommendations'])): ?>
            <ul class="cma-reco">
                <?php foreach ($crea3_ilp_analysis['recommendations'] as $crea3_ilp_rec): ?>
                    <li><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><?php echo esc_html($crea3_ilp_rec); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>
