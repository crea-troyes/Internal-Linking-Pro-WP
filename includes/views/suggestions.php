<?php
if (!defined('ABSPATH')) exit;

$suggestions = $analyzer->get_link_suggestions();
?>

<p><?= esc_html__('Internal linking suggestions', 'internal-linking-pro'); ?></p>

<div class="suggest">

  <table class="cma-dynamic-table suggest-table">
    <thead>
      <tr>
        <th><?= esc_html__('From', 'internal-linking-pro'); ?></th>
        <th></th>
        <th><?= esc_html__('To', 'internal-linking-pro'); ?></th>
        <th><?= esc_html__('Relevance', 'internal-linking-pro'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($suggestions as $s): ?>
        <tr>
          <td>
            <strong><?= esc_html(strip_tags($s['from'])); ?></strong><br>
            <small><a class="link_post_cma" href="<?= esc_url($s['url_from']); ?>" target="_blank" rel="noopener"><?= esc_html(ltrim(parse_url($s['url_from'], PHP_URL_PATH), '/')); ?></a></small>
          </td>
          <td>→</td>
          <td>
            <strong><?= esc_html(strip_tags($s['to'])); ?></strong><br>
            <small><a class="link_post_cma" href="<?= esc_url($s['url_to']); ?>" target="_blank" rel="noopener"><?= esc_html(ltrim(parse_url($s['url_to'], PHP_URL_PATH), '/')); ?></a></small>
          </td>
          <?php
            $score_class = 'score-low';
            if ($s['score'] >= 70) $score_class = 'score-high';
            elseif ($s['score'] >= 40) $score_class = 'score-medium';
          ?>

          <td>
            <span class="cma-score-badge <?= $score_class; ?>">
              <?= (int) $s['score']; ?>%
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
