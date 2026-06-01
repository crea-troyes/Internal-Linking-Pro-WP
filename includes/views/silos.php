<?php
if (!defined('ABSPATH')) exit;

$metrics = $analyzer->get_dashboard_metrics();
$clusters = $metrics['clusters'];

function cma_get_score_class_coherence($score) {
    if ($score < 50) return 'red0';
    if ($score < 70) return 'ext';
    return 'in';
}

function cma_get_score_class_fuite($score) {
    if ($score > 30) return 'red0';
    if ($score > 15) return 'ext';
    return 'in';
}

?>

<p><?= esc_html__('Check whether your pages are linked and organized into consistent SEO silos. Leakage measures only internal links sent outside the silo; external links are diagnosed separately.', 'crea-maillage-audit'); ?></p>

<div class="cma-silos cma-table-wrap">

<table id="tableau" class="widefat cma-table-silos cma-dynamic-table striped">

<thead>
<tr>
    <th><?= esc_html__('Silo', 'crea-maillage-audit'); ?></th>
    <th class="cma-center"><?= esc_html__('Pages', 'crea-maillage-audit'); ?></th>
    <th class="cma-center"><?= esc_html__('Coherence', 'crea-maillage-audit'); ?></th>
    <th class="cma-center"><?= esc_html__('Leakage', 'crea-maillage-audit'); ?></th>
    <th class="cma-center"><?= esc_html__('Issues', 'crea-maillage-audit'); ?></th>
    <th class="cma-center"><?= esc_html__('Recommendations', 'crea-maillage-audit'); ?></th>
</tr>
</thead>

<tbody>

<?php foreach ($clusters as $cluster): 

    $analysis = $analyzer->analyze_silo($cluster);
    $pillar_url = (string)($cluster['pillar']['url'] ?? '');
    $pillar_path = $pillar_url ? (string)parse_url($pillar_url, PHP_URL_PATH) : '';

?>

<tr>
    <td>
        <?= esc_html($cluster['pillar']['title']); ?>
        <?php if ($pillar_url): ?>
            <br>
            <small>
                <a class="link_post_cma" href="<?= esc_url($pillar_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?= esc_html($pillar_path ?: $pillar_url); ?>
                </a>
            </small>
        <?php endif; ?>
    </td>

    <td class="cma-center">
        <span class="cma-badge inpage">
            <?= intval($cluster['pages_count']); ?>
        </span>
    </td>

    <td class="cma-center">
        <span class="cma-badge <?= cma_get_score_class_coherence($analysis['coherence']); ?>">
            <?= intval($analysis['coherence']); ?>%
        </span>
        <small class="cma-silo-metric-detail">
            <?= esc_html(sprintf(__('%1$d%% retained · %2$d%% lateral', 'crea-maillage-audit'), intval($analysis['retention']), intval($analysis['lateral_coverage']))); ?>
        </small>
    </td>

    <td class="cma-center">
        <span class="cma-badge <?= cma_get_score_class_fuite($analysis['fuite']); ?>">
            <?= intval($analysis['fuite']); ?>%
        </span>
        <small class="cma-silo-metric-detail">
            <?= esc_html(sprintf(_n('%d link outside silo', '%d links outside silo', intval($analysis['inter_silo_links']), 'crea-maillage-audit'), intval($analysis['inter_silo_links']))); ?>
        </small>
    </td>

    <td>
        <?php if (!empty($analysis['issues'])): ?>
            <ul class="cma-issues">
                <?php foreach ($analysis['issues'] as $issue): ?>
                    <li><span class="dashicons dashicons-warning" aria-hidden="true"></span><?= esc_html($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <span class="cma-silo-status-ok"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>OK</span>
        <?php endif; ?>
    </td>

    <td>
        <?php if (!empty($analysis['recommendations'])): ?>
            <ul class="cma-reco">
                <?php foreach ($analysis['recommendations'] as $rec): ?>
                    <li><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><?= esc_html($rec); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const table = document.querySelector('.cma-dynamic-table');
    if (!table) return;

    const headers = table.querySelectorAll('th');
    const tbody = table.querySelector('tbody');

    let currentIndex = null;
    let currentDir = 'asc';

    headers.forEach((th, index) => {

        th.style.cursor = 'pointer';

        th.addEventListener('click', () => {

            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Gestion du sens de tri
            if (currentIndex === index) {
                currentDir = currentDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentIndex = index;
                currentDir = 'asc';
            }

            // Reset des flèches
            headers.forEach(h => h.removeAttribute('data-sort-dir'));
            th.setAttribute('data-sort-dir', currentDir);

            rows.sort((a, b) => {

                let A = a.children[index].innerText.trim();
                let B = b.children[index].innerText.trim();

                // Nettoyage valeurs (% , espaces, etc.)
                A = A.replace('%', '').replace(',', '.').replace(/\s/g, '');
                B = B.replace('%', '').replace(',', '.').replace(/\s/g, '');

                const numA = parseFloat(A);
                const numB = parseFloat(B);

                // Tri numérique si possible
                if (!isNaN(numA) && !isNaN(numB)) {
                    return currentDir === 'asc' ? numA - numB : numB - numA;
                }

                // Sinon tri texte
                return currentDir === 'asc'
                    ? A.localeCompare(B, undefined, { numeric: true, sensitivity: 'base' })
                    : B.localeCompare(A, undefined, { numeric: true, sensitivity: 'base' });
            });

            // Réinjection dans le DOM
            rows.forEach(row => tbody.appendChild(row));
        });
    });

});
</script>
