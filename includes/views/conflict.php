<?php
if (!defined('ABSPATH')) {
    exit;
}

$analysis = $analyzer->cma_conflicts_get_cached_analysis(false);
$summary = isset($analysis['summary']) && is_array($analysis['summary']) ? $analysis['summary'] : array();
$conflicts = isset($analysis['conflicts']) && is_array($analysis['conflicts']) ? $analysis['conflicts'] : array();
$active_filter = isset($_GET['cma_conflicts_filter']) ? sanitize_key(wp_unslash($_GET['cma_conflicts_filter'])) : 'all';
$allowed_filters = array('all', 'critical', 'medium', 'light');

if (!in_array($active_filter, $allowed_filters, true)) {
    $active_filter = 'all';
}

$visible_conflicts = array_values(array_filter($conflicts, static function ($conflict) use ($active_filter) {
    return 'all' === $active_filter || $active_filter === ($conflict['severity'] ?? '');
}));

if (!function_exists('render_conflicts_severity_label')) {
    function render_conflicts_severity_label($severity)
    {
        $labels = array(
            'critical' => __('Critical', 'crea-maillage-audit'),
            'medium' => __('Medium', 'crea-maillage-audit'),
            'light' => __('Light', 'crea-maillage-audit'),
        );

        return $labels[$severity] ?? __('Light', 'crea-maillage-audit');
    }
}

if (!function_exists('render_conflicts_date')) {
    function render_conflicts_date($date)
    {
        if (!$date) {
            return __('Not available', 'crea-maillage-audit');
        }

        $timestamp = strtotime($date);
        return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : $date;
    }
}

if (!function_exists('render_conflicts_filter_url')) {
    function render_conflicts_filter_url($filter)
    {
        return add_query_arg(
            array(
                'page' => 'cma-maillage',
                'view' => 'conflict',
                'cma_conflicts_filter' => $filter,
            ),
            admin_url('tools.php')
        );
    }
}

$summary = wp_parse_args(
    $summary,
    array(
        'total' => 0,
        'critical' => 0,
        'medium' => 0,
        'light' => 0,
        'affected_posts' => 0,
        'top_cluster' => '',
        'top_topic' => '',
    )
);
$scan_date = !empty($analysis['scan_generated_at']) ? date_i18n('d/m/Y H:i', (int) $analysis['scan_generated_at']) : '';
?>

<div class="cma-conflicts-dashboard">
    <section class="cma-conflicts-hero">
        <div class="cma-conflicts-heading-copy">
            <p class="cma-conflicts-eyebrow"><?php esc_html_e('Editorial analysis', 'crea-maillage-audit'); ?></p>
            <div class="cma-conflicts-title-line">
                <span class="dashicons dashicons-warning cma-conflicts-title-icon" aria-hidden="true"></span>
                <h2><?php esc_html_e('Cannibalization conflicts', 'crea-maillage-audit'); ?></h2>
            </div>
            <p><?php esc_html_e('Identify content that may compete in Google and prioritize the most impactful fixes.', 'crea-maillage-audit'); ?></p>
            <div class="cma-conflicts-heading-meta">
                <span><strong><?php echo esc_html((string) $summary['affected_posts']); ?></strong> <?php esc_html_e('affected content', 'crea-maillage-audit'); ?></span>
                <span><strong><?php echo esc_html((string) count($visible_conflicts)); ?></strong> <?php esc_html_e('displayed results', 'crea-maillage-audit'); ?></span>
                <?php if ($scan_date) : ?>
                    <span><?php esc_html_e('Analysis from', 'crea-maillage-audit'); ?> <?php echo esc_html($scan_date); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cma-conflicts-recalculate-form">
            <?php wp_nonce_field('cma_conflicts_recalculate_action', 'cma_conflicts_nonce'); ?>
            <input type="hidden" name="action" value="cma_conflicts_recalculate">
            <button type="submit" class="button button-primary cma-conflicts-recalculate">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php esc_html_e('Recalculate conflicts', 'crea-maillage-audit'); ?>
            </button>
        </form>
    </section>

    <?php if (!empty($analysis['limited_data'])) : ?>
        <div class="notice notice-info inline cma-conflicts-notice">
            <p><?php esc_html_e('Some data is not yet available to enrich this analysis.', 'crea-maillage-audit'); ?></p>
        </div>
    <?php endif; ?>

    <section class="cma-conflicts-summary" aria-label="<?php esc_attr_e('Conflict summary', 'crea-maillage-audit'); ?>">
        <article class="cma-conflicts-card cma-conflicts-card-total">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Total detected', 'crea-maillage-audit'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $summary['total']); ?></strong>
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Conflicts to review', 'crea-maillage-audit'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-critical">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Critical', 'crea-maillage-audit'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $summary['critical']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Top priority', 'crea-maillage-audit'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-medium">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Medium', 'crea-maillage-audit'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $summary['medium']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Needs review', 'crea-maillage-audit'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-light">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Light', 'crea-maillage-audit'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $summary['light']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Monitor', 'crea-maillage-audit'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-posts">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Affected content', 'crea-maillage-audit'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $summary['affected_posts']); ?></strong>
                <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Impacted scope', 'crea-maillage-audit'); ?></small>
        </article>
    </section>

    <section class="cma-conflicts-insights" aria-label="<?php esc_attr_e('Key insights', 'crea-maillage-audit'); ?>">
        <article class="cma-conflicts-insight">
            <span class="dashicons dashicons-category" aria-hidden="true"></span>
            <div>
                <span><?php esc_html_e('Most affected cluster', 'crea-maillage-audit'); ?></span>
                <strong><?php echo esc_html($summary['top_cluster'] ?: __('Not available', 'crea-maillage-audit')); ?></strong>
            </div>
        </article>
        <article class="cma-conflicts-insight">
            <span class="dashicons dashicons-tag" aria-hidden="true"></span>
            <div>
                <span><?php esc_html_e('Most involved expression', 'crea-maillage-audit'); ?></span>
                <strong><?php echo esc_html($summary['top_topic'] ?: __('Not available', 'crea-maillage-audit')); ?></strong>
            </div>
        </article>
    </section>

    <section class="cma-conflicts-toolbar">
        <div>
            <h3><?php esc_html_e('Detected conflicts', 'crea-maillage-audit'); ?></h3>
            <p><?php echo esc_html(sprintf(_n('%d result for this filter', '%d results for this filter', count($visible_conflicts), 'crea-maillage-audit'), count($visible_conflicts))); ?></p>
        </div>
        <nav class="cma-conflicts-filters" aria-label="<?php esc_attr_e('Filter conflicts', 'crea-maillage-audit'); ?>">
            <?php
            $filters = array(
                'all' => __('All', 'crea-maillage-audit'),
                'critical' => __('Critical', 'crea-maillage-audit'),
                'medium' => __('Medium', 'crea-maillage-audit'),
                'light' => __('Light', 'crea-maillage-audit'),
            );
            foreach ($filters as $filter => $label) :
                ?>
                <a class="<?php echo $active_filter === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url(render_conflicts_filter_url($filter)); ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <div class="cma-conflicts-table-shell">
        <div class="cma-conflicts-table-scroll">
            <table class="widefat cma-conflicts-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Severity', 'crea-maillage-audit'); ?></th>
                        <th><?php esc_html_e('Expression or topic', 'crea-maillage-audit'); ?></th>
                        <th><?php esc_html_e('Affected content', 'crea-maillage-audit'); ?></th>
                        <th><?php esc_html_e('Similarity', 'crea-maillage-audit'); ?></th>
                        <th><?php esc_html_e('Conflict type', 'crea-maillage-audit'); ?></th>
                        <th><?php esc_html_e('Recommendation', 'crea-maillage-audit'); ?></th>
                        <th class="cma-conflicts-action-column"><?php esc_html_e('Action', 'crea-maillage-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$visible_conflicts) : ?>
                        <tr>
                            <td colspan="7">
                                <div class="cma-conflicts-empty">
                                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                    <strong><?php esc_html_e('No conflicts found with this filter.', 'crea-maillage-audit'); ?></strong>
                                    <p><?php esc_html_e('Run a recalculation after your next editorial changes.', 'crea-maillage-audit'); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($visible_conflicts as $index => $conflict) : ?>
                        <?php
                        $severity = $conflict['severity'] ?? 'light';
                        $score = max(0, min(100, (int) ($conflict['score'] ?? 0)));
                        $details_id = 'cma-conflicts-details-' . (int) $index;
                        $articles = isset($conflict['articles']) && is_array($conflict['articles']) ? $conflict['articles'] : array();
                        $types = isset($conflict['types']) && is_array($conflict['types']) ? $conflict['types'] : array();
                        ?>
                        <tr class="cma-conflicts-row">
                            <td>
                                <span class="cma-conflicts-badge cma-conflicts-badge-<?php echo esc_attr($severity); ?>">
                                    <?php echo esc_html(render_conflicts_severity_label($severity)); ?>
                                </span>
                            </td>
                            <td>
                                <strong class="cma-conflicts-topic"><?php echo esc_html($conflict['topic'] ?? __('Related topic', 'crea-maillage-audit')); ?></strong>
                                <?php if (!empty($conflict['common_expressions'])) : ?>
                                    <span class="cma-conflicts-topic-meta"><?php echo esc_html(implode(' · ', array_slice($conflict['common_expressions'], 0, 3))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="cma-conflicts-article-count">
                                    <strong><?php echo esc_html((string) count($articles)); ?></strong>
                                    <span><?php esc_html_e('content items', 'crea-maillage-audit'); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="cma-conflicts-score" style="--cma-conflicts-score: <?php echo esc_attr((string) $score); ?>%;">
                                    <strong><?php echo esc_html((string) $score); ?>%</strong>
                                    <span class="cma-conflicts-score-track"><span class="cma-conflicts-score-fill"></span></span>
                                </div>
                            </td>
                            <td>
                                <div class="cma-conflicts-types">
                                    <?php foreach (array_slice($types, 0, 3) as $type) : ?>
                                        <span class="cma-conflicts-type"><?php echo esc_html((string) $type); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><p class="cma-conflicts-table-recommendation"><?php echo esc_html($conflict['recommendation'] ?? __('Monitor only', 'crea-maillage-audit')); ?></p></td>
                            <td class="cma-conflicts-action-column">
                                <button type="button" class="button cma-conflicts-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr($details_id); ?>">
                                    <span class="cma-conflicts-toggle-label"><?php esc_html_e('Details', 'crea-maillage-audit'); ?></span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </button>
                            </td>
                        </tr>
                        <tr id="<?php echo esc_attr($details_id); ?>" class="cma-conflicts-details-row">
                            <td colspan="7">
                                <div class="cma-conflicts-details">
                                    <div class="cma-conflicts-details-head">
                                        <div>
                                            <span class="cma-conflicts-details-eyebrow"><?php esc_html_e('Risk analysis', 'crea-maillage-audit'); ?></span>
                                            <h4><?php echo esc_html($conflict['topic'] ?? __('Editorial conflict', 'crea-maillage-audit')); ?></h4>
                                        </div>
                                        <div class="cma-conflicts-details-score">
                                            <span><?php esc_html_e('Global score', 'crea-maillage-audit'); ?></span>
                                            <strong><?php echo esc_html((string) $score); ?>/100</strong>
                                        </div>
                                    </div>

                                    <div class="cma-conflicts-articles-grid">
                                        <?php foreach ($articles as $article) : ?>
                                            <article class="cma-conflicts-article-card <?php echo !empty($article['is_primary']) ? 'is-primary' : ''; ?>">
                                                <div class="cma-conflicts-article-card-head">
                                                    <?php if (!empty($article['is_primary'])) : ?>
                                                        <span class="cma-conflicts-primary-label"><?php esc_html_e('Suggested primary content', 'crea-maillage-audit'); ?></span>
                                                    <?php else : ?>
                                                        <span class="cma-conflicts-secondary-label"><?php esc_html_e('Competing content', 'crea-maillage-audit'); ?></span>
                                                    <?php endif; ?>
                                                    <h5><?php echo esc_html($article['title'] ?? __('Untitled', 'crea-maillage-audit')); ?></h5>
                                                    <?php if (!empty($article['url'])) : ?>
                                                        <a href="<?php echo esc_url($article['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($article['url']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                                <dl class="cma-conflicts-article-stats">
                                                    <div><dt><?php esc_html_e('Updated', 'crea-maillage-audit'); ?></dt><dd><?php echo esc_html(render_conflicts_date($article['modified'] ?? '')); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Internal score', 'crea-maillage-audit'); ?></dt><dd><?php echo esc_html((string) ($article['score'] ?? __('N/A', 'crea-maillage-audit'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Incoming links', 'crea-maillage-audit'); ?></dt><dd><?php echo esc_html((string) ($article['incoming'] ?? __('N/A', 'crea-maillage-audit'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Outgoing links', 'crea-maillage-audit'); ?></dt><dd><?php echo esc_html((string) ($article['outgoing'] ?? __('N/A', 'crea-maillage-audit'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Cluster', 'crea-maillage-audit'); ?></dt><dd><?php echo esc_html((string) ($article['cluster'] ?? __('N/A', 'crea-maillage-audit'))); ?></dd></div>
                                                </dl>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="cma-conflicts-details-grid">
                                        <section class="cma-conflicts-detail-section">
                                            <h5><?php esc_html_e('Shared expressions', 'crea-maillage-audit'); ?></h5>
                                            <div class="cma-conflicts-tags">
                                                <?php foreach (($conflict['common_expressions'] ?? array()) as $expression) : ?>
                                                    <span><?php echo esc_html((string) $expression); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (empty($conflict['common_expressions'])) : ?><em><?php esc_html_e('No specific expression detected.', 'crea-maillage-audit'); ?></em><?php endif; ?>
                                            </div>
                                        </section>
                                        <section class="cma-conflicts-detail-section">
                                            <h5><?php esc_html_e('Shared anchors', 'crea-maillage-audit'); ?></h5>
                                            <div class="cma-conflicts-tags">
                                                <?php foreach (($conflict['common_anchors'] ?? array()) as $anchor) : ?>
                                                    <span><?php echo esc_html((string) $anchor); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (empty($conflict['common_anchors'])) : ?><em><?php esc_html_e('No shared anchors detected.', 'crea-maillage-audit'); ?></em><?php endif; ?>
                                            </div>
                                        </section>
                                    </div>

                                    <section class="cma-conflicts-network-section">
                                        <div class="cma-conflicts-section-heading">
                                            <h5><?php esc_html_e('Simplified link map', 'crea-maillage-audit'); ?></h5>
                                            <span><?php esc_html_e('The suggested primary content is highlighted.', 'crea-maillage-audit'); ?></span>
                                        </div>
                                        <div class="cma-conflicts-network">
                                            <?php foreach ($articles as $article) : ?>
                                                <div class="cma-conflicts-network-node <?php echo !empty($article['is_primary']) ? 'is-primary' : ''; ?>">
                                                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                                    <strong><?php echo esc_html($article['title'] ?? __('Untitled', 'crea-maillage-audit')); ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>

                                    <div class="cma-conflicts-risk">
                                        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                                        <p><?php echo esc_html($conflict['risk_explanation'] ?? __('These contents cover a similar editorial scope and may compete in search results.', 'crea-maillage-audit')); ?></p>
                                    </div>

                                    <div class="cma-conflicts-recommendation">
                                        <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
                                        <div>
                                            <strong><?php esc_html_e('Recommended action', 'crea-maillage-audit'); ?></strong>
                                            <p><?php echo esc_html($conflict['recommendation'] ?? __('Monitor only', 'crea-maillage-audit')); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
