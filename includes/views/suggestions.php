<?php
if (!defined('ABSPATH')) exit;

$crea3_ilp_suggestions = $analyzer->get_link_suggestions();
?>

<p><?php echo esc_html__('Internal linking suggestions', 'crea3-internal-linking-pro'); ?></p>

<div class="suggest">

  <table class="cma-dynamic-table suggest-table">
    <thead>
      <tr>
        <th><?php echo esc_html__('From', 'crea3-internal-linking-pro'); ?></th>
        <th></th>
        <th><?php echo esc_html__('To', 'crea3-internal-linking-pro'); ?></th>
        <th><?php echo esc_html__('Relevance', 'crea3-internal-linking-pro'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($crea3_ilp_suggestions as $s): ?>
        <tr>
          <td>
            <strong><?php echo esc_html(wp_strip_all_tags($s['from'])); ?></strong><br>
            <small><a class="link_post_cma" href="<?php echo esc_url($s['url_from']); ?>" target="_blank" rel="noopener"><?php echo esc_html(ltrim(wp_parse_url($s['url_from'], PHP_URL_PATH), '/')); ?></a></small>
          </td>
          <td>→</td>
          <td>
            <strong><?php echo esc_html(wp_strip_all_tags($s['to'])); ?></strong><br>
            <small><a class="link_post_cma" href="<?php echo esc_url($s['url_to']); ?>" target="_blank" rel="noopener"><?php echo esc_html(ltrim(wp_parse_url($s['url_to'], PHP_URL_PATH), '/')); ?></a></small>
          </td>
          <?php
            $crea3_ilp_score_class = 'score-low';
            if ($s['score'] >= 70) $crea3_ilp_score_class = 'score-high';
            elseif ($s['score'] >= 40) $crea3_ilp_score_class = 'score-medium';
          ?>

          <td>
            <span class="cma-score-badge <?php echo esc_attr($crea3_ilp_score_class); ?>">
              <?php echo (int) $s['score']; ?>%
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
