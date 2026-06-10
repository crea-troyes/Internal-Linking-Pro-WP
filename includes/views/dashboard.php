<?php
if (!defined('ABSPATH')) exit;

function cma_dashboard_percentage($total, $value): int {
    if ($total == 0) {
        return 0; // éviter division par zéro
    }

    return (int) round(($value / $total) * 100);
}

$metrics = $analyzer->get_dashboard_metrics();
$rows = $metrics['rows'];

$total = count($rows);
$orphans = (int)($metrics['orphans'] ?? 0);
$isolated = (int)($metrics['isolated_posts'] ?? 0);
$suggestions = $analyzer->get_link_suggestions();

$without_outgoing = (int)($metrics['without_internal_outgoing'] ?? 0);
$internal_links = 0;
$external_links = 0;

foreach ($rows as $r) {

    $internal_links += $r['out_int'];
    $external_links += $r['out_ext'];
}

$clusters = $metrics['clusters'];

$total_clusters = count($clusters);

$total_pages_cluster = 0;

foreach ($clusters as $c) {
    $total_pages_cluster += $c['pages_count'];
}

$avg_cluster_size = $total_clusters
    ? round($total_pages_cluster / $total_clusters)
    : 0;

$value = $metrics['links_per_100_words'];
$class = 'blue';
if ($value < 1) $class = 'red';
elseif ($value < 3) $class = 'orange';
elseif ($value < 5) $class = 'green';

?>

<div class="dashboard">

    <!-- COLONNE GAUCHE -->
    <div class="left-column">

      <div class="card score-card loading">

        <h2><?= esc_html__('Internal linking score', 'internal-linking-pro'); ?></h2>

        <!-- Skeleton -->
        <div class="score-skeleton">
            <div class="sk-gauge"></div>
        </div>

        <!-- Contenu réel -->
        <div class="score-content">

            <div class="site-health" data-value="<?= esc_attr((string)(int)$metrics['global_score']); ?>">
            <svg viewBox="0 0 200 120">
                <path d="M20 100 A80 80 0 0 1 180 100" class="gauge-bg" pathLength="100" />
                <path d="M20 100 A80 80 0 0 1 180 100" class="gauge-value" pathLength="100" />
                <path d="M20 100 A80 80 0 0 1 180 100" class="gauge-separator" pathLength="100" />
            </svg>

            <div class="score">0%</div>
            </div>

        </div>
      </div>

      <div class="card summary-card">
        <h2><?= esc_html__('Analyzed content', 'internal-linking-pro'); ?></h2>
        <div class="summary-number"><?= esc_html((string)(int)$total); ?></div>

        <style>
        .summary-bar span:nth-child(1) {
            width: <?= max(0, cma_dashboard_percentage($total, (int)($metrics['strong_pages'] ?? 0))); ?>%;
        }

        .summary-bar span:nth-child(2) {
            width: <?= cma_dashboard_percentage($total, $isolated); ?>%;
        }

        .summary-bar span:nth-child(3) {
            width: <?= cma_dashboard_percentage($total, $without_outgoing); ?>%;
        }
        </style>

        <div class="summary-bar">
          <span></span>
          <span></span>
          <span></span>
        </div>

        <div class="legend">
          <div class="legend-item">
            <div class="legend-left">
              <span class="dot green"></span>
              <span><?= esc_html__('Correct', 'internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?=  (int)($metrics['strong_pages'] ?? 0); ?></span>
          </div>

          <div class="legend-item">
            <div class="legend-left">
              <span class="dot red"></span>
              <span><?= esc_html__('Isolated', 'internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?= esc_html((string)$isolated); ?></span>
          </div>

          <div class="legend-item">
            <div class="legend-left">
              <span class="dot orange"></span>
              <span><?= esc_html__('Without outgoing internal links', 'internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?= esc_html((string)$without_outgoing); ?></span>
          </div>
        </div>
      </div>

      <div class="card linked-card">
        <h2><?= esc_html__('Most linked', 'internal-linking-pro'); ?></h2>

        <ul class="linked-list">
          <?php

            $i = 1;
            foreach ($metrics['top_pages'] as $p) {

                echo '<li>';
                  echo '<span class="title">'.$i.'. '.esc_html($p['title']).'</span>';
                  echo '<span class="value">'.intval($p['in_int']).'</span>';
                echo '</li>';
                $i++;
            }

          ?>
        </ul>
      </div>

      <div class="card suggestion-card">
        <h2><?= esc_html__('Links suggestions', 'internal-linking-pro'); ?></h2>

        <div class="summary-number">
          <?= esc_html((string)count($suggestions)); ?>
        </div>

        <ul class="linked-list">
          <?php foreach (array_slice($suggestions, 0, 5) as $s): ?>
            <li>
              <span class="title">
                <?= esc_html(strip_tags($s['from'])); ?> → <?= esc_html(strip_tags($s['to'])); ?>
              </span>
              <span class="value"><?= esc_html((string)(int)$s['score']); ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>

        <a href="<?= esc_url(admin_url('tools.php?page=cma-maillage&view=suggestions')); ?>" class="button cma-dashboard-btn">
          <?= esc_html__('View opportunities', 'internal-linking-pro'); ?>
        </a>
      </div>
    </div>

    <!-- COLONNE DROITE -->
    <div class="right-column">

      <div class="top-stats">

        <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Orphans', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Pages or posts without incoming internal links', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value red"><?=  $orphans; ?></div>
            <div class="stat-label"><?= esc_html__('Without incoming internal links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-orange"><span class="dashicons dashicons-external" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Without outgoing links', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Pages or posts without outgoing internal links', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value orange"><?=  $without_outgoing; ?></div>
            <div class="stat-label"><?= esc_html__('Without outgoing internal links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Isolated', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Posts without internal links from other posts', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value red"><?=  $isolated; ?></div>
            <div class="stat-label"><?= esc_html__('Unlinked', 'internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-networking" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Clusters', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html(sprintf(__('Total number of clusters with %d links', 'internal-linking-pro'), (int)get_option('cma_cluster_threshold', 3))); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?= $total_clusters; ?></div>
            <div class="stat-label"><?= esc_html__('Clusters', 'internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-chart-line" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Average links', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Average links per page or post', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?= esc_html((string)$metrics['avg_links']); ?></div>
            <div class="stat-label"><?= esc_html__('Links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Internal links', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Total internal links on pages or posts', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?= esc_html((string)(int)$internal_links); ?></div>
            <div class="stat-label"><?= esc_html__('Links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('External links', 'internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?= esc_html__('Total external links on pages or posts', 'internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?= esc_html((string)(int)$external_links); ?></div>
            <div class="stat-label"><?= esc_html__('Links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-media-document" aria-hidden="true"></span></span>
          <div class="stat-title"><?= esc_html__('Link / 100 words', 'internal-linking-pro'); ?></div>
          <div class="stat-main">
            <div class="stat-value <?= esc_attr($class); ?>"><?= esc_html((string)$value); ?></div>
            <div class="stat-label"><?= esc_html__('Links', 'internal-linking-pro'); ?></div>
          </div>
        </div>

      </div>

  
        

        <div class="cma-table">

<table class="cma-table-clusters">

<thead>
<tr>
    <th><?= esc_html__('Cluster list | Average size', 'internal-linking-pro'); ?> : <?= esc_html((string)$avg_cluster_size); ?> | <?= esc_html__('Minimum cluster size', 'internal-linking-pro'); ?> : <?= esc_html((string)(int)get_option('cma_cluster_threshold', 3)); ?>
      <a class="setting-link" href="<?= esc_url(admin_url('tools.php?page=cma-maillage&view=settings')); ?>"><span class="dashicons dashicons-admin-settings"></span></a>
    </th>
    <th class="cma-center"><?= esc_html__('Pages', 'internal-linking-pro'); ?></th>
    <th class="cma-center"><?= esc_html__('Score', 'internal-linking-pro'); ?></th>
    <th class="cma-center"></th>
</tr>
</thead>

<tbody>

<?php
usort($clusters, function($a, $b) {
    return $b['pages_count'] <=> $a['pages_count'];
});
?>

<?php foreach ($clusters as $cluster): ?>

<tr class="cma-tr-main">

    <td class="cma-td-title">
        <strong><?php echo esc_html($cluster['pillar']['title']); ?></strong>
        <?php
        $pillar_url = (string)($cluster['pillar']['url'] ?? '');
        $pillar_path = $pillar_url ? (string)parse_url($pillar_url, PHP_URL_PATH) : '';
        ?>
        <?php if ($pillar_url): ?>
            <a class="cma-cluster-url" href="<?php echo esc_url($pillar_url); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html($pillar_path ?: $pillar_url); ?>
            </a>
        <?php endif; ?>
    </td>

    <td class="cma-center">
        <?php echo intval($cluster['pages_count']); ?>
    </td>

    <td class="cma-center">
        <span class="cma-score score-<?=
            ($cluster['score'] >= 70 ? 'good' : ($cluster['score'] >= 40 ? 'medium' : 'bad'))
        ?>">
            <?php echo intval($cluster['score']); ?>
        </span>
    </td>

    <td class="cma-center">
        <button class="cma-toggle"><?= esc_html__('Details', 'internal-linking-pro'); ?></button>
    </td>

</tr>

<tr class="cma-tr-details">
    <td colspan="4">

        <ul>
        <?php
        $i = 0;
        foreach ($cluster['pages'] as $p) {
          if ($i >= 30) break;

          echo '<li>';
          echo '<strong>' . esc_html(strip_tags($p['title'])) . '</strong>';

          if (!empty($p['anchors']) && is_array($p['anchors'])) {
              echo '<br><small><em>' . esc_html__('Anchors:', 'internal-linking-pro') . ' ' . esc_html(implode(' | ', $p['anchors'])) . '</em></small>';
          } else {
              echo '<br><small><em>' . esc_html__('Anchors:', 'internal-linking-pro') . ' —</em></small>';
          }

          echo '</li>';

          $i++;
        }
        ?>
        </ul>

    </td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>
 

    </div>
  </div>

<?php

echo "<script>

// TABLE
document.querySelectorAll('.cma-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        const next = tr.nextElementSibling;

        next.style.display =
            next.style.display === 'table-row'
            ? 'none'
            : 'table-row';
    });
});

document.addEventListener('DOMContentLoaded', function () {

    const table = document.querySelector('.cma-table-clusters');
    const headers = table.querySelectorAll('th');
    const tbody = table.querySelector('tbody');

    let sortDirection = {};

    headers.forEach((header, colIndex) => {

        // ❌ on ignore la dernière colonne
        if (colIndex === headers.length - 1) return;

        header.addEventListener('click', () => {

            sortDirection[colIndex] = !sortDirection[colIndex];

            const rows = Array.from(tbody.querySelectorAll('.cma-tr-main'));

            let groups = rows.map(row => ({
                main: row,
                details: row.nextElementSibling
            }));

            groups.sort((a, b) => {

                let valA = getCellValue(a.main, colIndex);
                let valB = getCellValue(b.main, colIndex);

                if (!isNaN(valA) && !isNaN(valB)) {
                    valA = Number(valA);
                    valB = Number(valB);
                } else {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }

                if (valA < valB) return sortDirection[colIndex] ? -1 : 1;
                if (valA > valB) return sortDirection[colIndex] ? 1 : -1;
                return 0;
            });

            tbody.innerHTML = '';

            groups.forEach(group => {
                tbody.appendChild(group.main);
                tbody.appendChild(group.details);
            });

            updateSortIcons(header, colIndex);
        });
    });

    function getCellValue(row, index) {
        return row.children[index].innerText.trim();
    }

    function updateSortIcons(activeHeader, colIndex) {
        headers.forEach((th, i) => {
            th.classList.remove('sort-asc', 'sort-desc');
        });

        activeHeader.classList.add(sortDirection[colIndex] ? 'sort-asc' : 'sort-desc');
    }
});

</script>";
