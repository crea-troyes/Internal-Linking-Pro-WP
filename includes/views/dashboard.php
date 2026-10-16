<?php
if (!defined('ABSPATH')) exit;

function crea3_ilp_dashboard_percentage($crea3_ilp_total, $crea3_ilp_value): int {
    if ($crea3_ilp_total == 0) {
        return 0; // éviter division par zéro
    }

    return (int) round(($crea3_ilp_value / $crea3_ilp_total) * 100);
}

$crea3_ilp_metrics = $analyzer->get_dashboard_metrics();
$crea3_ilp_rows = $crea3_ilp_metrics['rows'];

$crea3_ilp_total = count($crea3_ilp_rows);
$crea3_ilp_orphans = (int)($crea3_ilp_metrics['orphans'] ?? 0);
$crea3_ilp_isolated = (int)($crea3_ilp_metrics['isolated_posts'] ?? 0);
$crea3_ilp_suggestions = $analyzer->get_link_suggestions();

$crea3_ilp_without_outgoing = (int)($crea3_ilp_metrics['without_internal_outgoing'] ?? 0);
$crea3_ilp_internal_links = 0;
$crea3_ilp_external_links = 0;

foreach ($crea3_ilp_rows as $crea3_ilp_r) {

    $crea3_ilp_internal_links += $crea3_ilp_r['out_int'];
    $crea3_ilp_external_links += $crea3_ilp_r['out_ext'];
}

$crea3_ilp_clusters = $crea3_ilp_metrics['clusters'];

$crea3_ilp_total_clusters = count($crea3_ilp_clusters);

$crea3_ilp_total_pages_cluster = 0;

foreach ($crea3_ilp_clusters as $crea3_ilp_c) {
    $crea3_ilp_total_pages_cluster += $crea3_ilp_c['pages_count'];
}

$crea3_ilp_avg_cluster_size = $crea3_ilp_total_clusters
    ? round($crea3_ilp_total_pages_cluster / $crea3_ilp_total_clusters)
    : 0;

$crea3_ilp_value = $crea3_ilp_metrics['links_per_100_words'];
$crea3_ilp_class = 'blue';
if ($crea3_ilp_value < 1) $crea3_ilp_class = 'red';
elseif ($crea3_ilp_value < 3) $crea3_ilp_class = 'orange';
elseif ($crea3_ilp_value < 5) $crea3_ilp_class = 'green';

?>

<div class="dashboard">

    <!-- COLONNE GAUCHE -->
    <div class="left-column">

      <div class="card score-card loading">

        <h2><?php echo esc_html__('Internal linking score', 'crea3-internal-linking-pro'); ?></h2>

        <!-- Skeleton -->
        <div class="score-skeleton">
            <div class="sk-gauge"></div>
        </div>

        <!-- Contenu réel -->
        <div class="score-content">

            <div class="site-health" data-value="<?php echo esc_attr((string)(int)$crea3_ilp_metrics['global_score']); ?>">
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
        <h2><?php echo esc_html__('Analyzed content', 'crea3-internal-linking-pro'); ?></h2>
        <div class="summary-number"><?php echo esc_html((string)(int)$crea3_ilp_total); ?></div>

        <div class="summary-bar">
          <span data-width="<?php echo esc_attr((string) max(0, crea3_ilp_dashboard_percentage($crea3_ilp_total, (int)($crea3_ilp_metrics['strong_pages'] ?? 0)))); ?>"></span>
          <span data-width="<?php echo esc_attr((string) crea3_ilp_dashboard_percentage($crea3_ilp_total, $crea3_ilp_isolated)); ?>"></span>
          <span data-width="<?php echo esc_attr((string) crea3_ilp_dashboard_percentage($crea3_ilp_total, $crea3_ilp_without_outgoing)); ?>"></span>
        </div>

        <div class="legend">
          <div class="legend-item">
            <div class="legend-left">
              <span class="dot green"></span>
              <span><?php echo esc_html__('Correct', 'crea3-internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?php echo  (int)($crea3_ilp_metrics['strong_pages'] ?? 0); ?></span>
          </div>

          <div class="legend-item">
            <div class="legend-left">
              <span class="dot red"></span>
              <span><?php echo esc_html__('Isolated', 'crea3-internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?php echo esc_html((string)$crea3_ilp_isolated); ?></span>
          </div>

          <div class="legend-item">
            <div class="legend-left">
              <span class="dot orange"></span>
              <span><?php echo esc_html__('Without outgoing internal links', 'crea3-internal-linking-pro'); ?></span>
            </div>
            <span class="legend-value"><?php echo esc_html((string)$crea3_ilp_without_outgoing); ?></span>
          </div>
        </div>
      </div>

      <div class="card linked-card">
        <h2><?php echo esc_html__('Most linked', 'crea3-internal-linking-pro'); ?></h2>

        <ul class="linked-list">
          <?php

            $crea3_ilp_i = 1;
            foreach ($crea3_ilp_metrics['top_pages'] as $crea3_ilp_p) {

                echo '<li>';
                  echo '<span class="title">' . esc_html((string) $crea3_ilp_i) . '. ' . esc_html($crea3_ilp_p['title']) . '</span>';
                  echo '<span class="value">'.intval($crea3_ilp_p['in_int']).'</span>';
                echo '</li>';
                $crea3_ilp_i++;
            }

          ?>
        </ul>
      </div>

      <div class="card suggestion-card">
        <h2><?php echo esc_html__('Links suggestions', 'crea3-internal-linking-pro'); ?></h2>

        <div class="summary-number">
          <?php echo esc_html((string)count($crea3_ilp_suggestions)); ?>
        </div>

        <ul class="linked-list">
          <?php foreach (array_slice($crea3_ilp_suggestions, 0, 5) as $s): ?>
            <li>
              <span class="title">
                <?php echo esc_html(wp_strip_all_tags($s['from'])); ?> → <?php echo esc_html(wp_strip_all_tags($s['to'])); ?>
              </span>
              <span class="value"><?php echo esc_html((string)(int)$s['score']); ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>

        <a href="<?php echo esc_url(admin_url('tools.php?page=crea3-internal-linking-pro&view=suggestions')); ?>" class="button cma-dashboard-btn">
          <?php echo esc_html__('View opportunities', 'crea3-internal-linking-pro'); ?>
        </a>
      </div>
    </div>

    <!-- COLONNE DROITE -->
    <div class="right-column">

      <div class="top-stats">

        <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Orphans', 'crea3-internal-linking-pro'); ?> 
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Pages or posts without incoming internal links', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value red"><?php echo esc_html((string) $crea3_ilp_orphans); ?></div>
            <div class="stat-label"><?php echo esc_html__('Without incoming internal links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-orange"><span class="dashicons dashicons-external" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Without outgoing links', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Pages or posts without outgoing internal links', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value orange"><?php echo esc_html((string) $crea3_ilp_without_outgoing); ?></div>
            <div class="stat-label"><?php echo esc_html__('Without outgoing internal links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Isolated', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Posts without internal links from other posts', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value red"><?php echo esc_html((string) $crea3_ilp_isolated); ?></div>
            <div class="stat-label"><?php echo esc_html__('Unlinked', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-networking" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Clusters', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php /* translators: %d: minimum number of links in a cluster. */ echo esc_html(sprintf(__('Total number of clusters with %d links', 'crea3-internal-linking-pro'), (int)get_option(CREA3_ILP_CLUSTER_THRESHOLD_OPTION, 3))); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?php echo esc_html((string) $crea3_ilp_total_clusters); ?></div>
            <div class="stat-label"><?php echo esc_html__('Clusters', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

        <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-chart-line" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Average links', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Average links per page or post', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?php echo esc_html((string)$crea3_ilp_metrics['avg_links']); ?></div>
            <div class="stat-label"><?php echo esc_html__('Links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Internal links', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Total internal links on pages or posts', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?php echo esc_html((string)(int)$crea3_ilp_internal_links); ?></div>
            <div class="stat-label"><?php echo esc_html__('Links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-blue"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('External links', 'crea3-internal-linking-pro'); ?>
            <span class="tooltip">
              <span class="tooltip-icon">Ⓘ</span>
              <span class="tooltip-text"><?php echo esc_html__('Total external links on pages or posts', 'crea3-internal-linking-pro'); ?></span>
            </span>
          </div>
          <div class="stat-main">
            <div class="stat-value blue"><?php echo esc_html((string)(int)$crea3_ilp_external_links); ?></div>
            <div class="stat-label"><?php echo esc_html__('Links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

         <div class="stat-col">
          <span class="stat-icon stat-icon-red"><span class="dashicons dashicons-media-document" aria-hidden="true"></span></span>
          <div class="stat-title"><?php echo esc_html__('Link / 100 words', 'crea3-internal-linking-pro'); ?></div>
          <div class="stat-main">
            <div class="stat-value <?php echo esc_attr($crea3_ilp_class); ?>"><?php echo esc_html((string)$crea3_ilp_value); ?></div>
            <div class="stat-label"><?php echo esc_html__('Links', 'crea3-internal-linking-pro'); ?></div>
          </div>
        </div>

      </div>

  
        

        <div class="cma-table">

<table class="cma-table-clusters">

<thead>
<tr>
    <th><?php echo esc_html__('Cluster list | Average size', 'crea3-internal-linking-pro'); ?> : <?php echo esc_html((string)$crea3_ilp_avg_cluster_size); ?> | <?php echo esc_html__('Minimum cluster size', 'crea3-internal-linking-pro'); ?> : <?php echo esc_html((string)(int)get_option(CREA3_ILP_CLUSTER_THRESHOLD_OPTION, 3)); ?> 
      <a class="setting-link" href="<?php echo esc_url(admin_url('tools.php?page=crea3-internal-linking-pro&view=settings')); ?>"><span class="dashicons dashicons-admin-settings"></span></a>
    </th>
    <th class="cma-center"><?php echo esc_html__('Pages', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"><?php echo esc_html__('Score', 'crea3-internal-linking-pro'); ?></th>
    <th class="cma-center"></th>
</tr>
</thead>

<tbody>

<?php
usort($crea3_ilp_clusters, function($a, $b) {
    return $b['pages_count'] <=> $a['pages_count'];
});
?>

<?php foreach ($crea3_ilp_clusters as $crea3_ilp_cluster): ?>

<tr class="cma-tr-main">

    <td class="cma-td-title">
        <strong><?php echo esc_html($crea3_ilp_cluster['pillar']['title']); ?></strong>
        <?php
        $crea3_ilp_pillar_url = (string)($crea3_ilp_cluster['pillar']['url'] ?? '');
        $crea3_ilp_pillar_path = $crea3_ilp_pillar_url ? (string)wp_parse_url($crea3_ilp_pillar_url, PHP_URL_PATH) : '';
        ?>
        <?php if ($crea3_ilp_pillar_url): ?>
            <a class="cma-cluster-url" href="<?php echo esc_url($crea3_ilp_pillar_url); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html($crea3_ilp_pillar_path ?: $crea3_ilp_pillar_url); ?>
            </a>
        <?php endif; ?>
    </td>

    <td class="cma-center">
        <?php echo intval($crea3_ilp_cluster['pages_count']); ?>
    </td>

    <td class="cma-center">
        <span class="cma-score score-<?php echo
            ($crea3_ilp_cluster['score'] >= 70 ? 'good' : ($crea3_ilp_cluster['score'] >= 40 ? 'medium' : 'bad'))
        ?>">
            <?php echo intval($crea3_ilp_cluster['score']); ?>
        </span>
    </td>

    <td class="cma-center">
        <button class="cma-toggle"><?php echo esc_html__('Details', 'crea3-internal-linking-pro'); ?></button>
    </td>

</tr>

<tr class="cma-tr-details">
    <td colspan="4">

        <ul>
        <?php
        $crea3_ilp_i = 0;
        foreach ($crea3_ilp_cluster['pages'] as $crea3_ilp_p) {
          if ($crea3_ilp_i >= 30) break;

          echo '<li>';
          echo '<strong>' . esc_html(wp_strip_all_tags($crea3_ilp_p['title'])) . '</strong>';

          if (!empty($crea3_ilp_p['anchors']) && is_array($crea3_ilp_p['anchors'])) {
              echo '<br><small><em>' . esc_html__('Anchors:', 'crea3-internal-linking-pro') . ' ' . esc_html(implode(' | ', $crea3_ilp_p['anchors'])) . '</em></small>';
          } else {
              echo '<br><small><em>' . esc_html__('Anchors:', 'crea3-internal-linking-pro') . ' —</em></small>';
          }

          echo '</li>';

          $crea3_ilp_i++;
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
