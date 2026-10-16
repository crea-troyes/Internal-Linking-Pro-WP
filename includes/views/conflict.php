<?php
if (!defined('ABSPATH')) {
    exit;
}

$crea3_ilp_analysis = $analyzer->cma_conflicts_get_cached_analysis(false);
$crea3_ilp_summary = isset($crea3_ilp_analysis['summary']) && is_array($crea3_ilp_analysis['summary']) ? $crea3_ilp_analysis['summary'] : array();
$crea3_ilp_conflicts = isset($crea3_ilp_analysis['conflicts']) && is_array($crea3_ilp_analysis['conflicts']) ? $crea3_ilp_analysis['conflicts'] : array();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display filter; no data is modified.
$crea3_ilp_active_filter = isset($_GET['crea3_ilp_conflicts_filter']) ? sanitize_key(wp_unslash($_GET['crea3_ilp_conflicts_filter'])) : 'all';
$crea3_ilp_allowed_filters = array('all', 'critical', 'medium', 'light');

if (!in_array($crea3_ilp_active_filter, $crea3_ilp_allowed_filters, true)) {
    $crea3_ilp_active_filter = 'all';
}

$crea3_ilp_visible_conflicts = array_values(array_filter($crea3_ilp_conflicts, static function ($crea3_ilp_conflict) use ($crea3_ilp_active_filter) {
    return 'all' === $crea3_ilp_active_filter || $crea3_ilp_active_filter === ($crea3_ilp_conflict['severity'] ?? '');
}));

if (!function_exists('crea3_ilp_render_conflicts_severity_label')) {
    function crea3_ilp_render_conflicts_severity_label($crea3_ilp_severity)
    {
        $labels = array(
            'critical' => __('Critical', 'crea3-internal-linking-pro'),
            'medium' => __('Medium', 'crea3-internal-linking-pro'),
            'light' => __('Light', 'crea3-internal-linking-pro'),
        );

        return $labels[$crea3_ilp_severity] ?? __('Light', 'crea3-internal-linking-pro');
    }
}

if (!function_exists('crea3_ilp_render_conflicts_date')) {
    function crea3_ilp_render_conflicts_date($date)
    {
        if (!$date) {
            return __('Not available', 'crea3-internal-linking-pro');
        }

        $timestamp = strtotime($date);
        return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : $date;
    }
}

if (!function_exists('crea3_ilp_render_conflicts_filter_url')) {
    function crea3_ilp_render_conflicts_filter_url($crea3_ilp_filter)
    {
        return add_query_arg(
            array(
                'page' => 'crea3-internal-linking-pro',
                'view' => 'conflict',
                'crea3_ilp_conflicts_filter' => $crea3_ilp_filter,
            ),
            admin_url('tools.php')
        );
    }
}

$crea3_ilp_summary = wp_parse_args(
    $crea3_ilp_summary,
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
$crea3_ilp_scan_date = !empty($crea3_ilp_analysis['scan_generated_at']) ? date_i18n('d/m/Y H:i', (int) $crea3_ilp_analysis['scan_generated_at']) : '';
?>

<div class="cma-conflicts-dashboard">
    <section class="cma-conflicts-hero">
        <div class="cma-conflicts-heading-copy">
            <p class="cma-conflicts-eyebrow"><?php esc_html_e('Editorial analysis', 'crea3-internal-linking-pro'); ?></p>
            <div class="cma-conflicts-title-line">
                <span class="dashicons dashicons-warning cma-conflicts-title-icon" aria-hidden="true"></span>
                <h2><?php esc_html_e('Cannibalization conflicts', 'crea3-internal-linking-pro'); ?></h2>
            </div>
            <p><?php esc_html_e('Identify content that may compete in Google and prioritize the most impactful fixes.', 'crea3-internal-linking-pro'); ?></p>
            <div class="cma-conflicts-heading-meta">
                <span><strong><?php echo esc_html((string) $crea3_ilp_summary['affected_posts']); ?></strong> <?php esc_html_e('affected content', 'crea3-internal-linking-pro'); ?></span>
                <span><strong><?php echo esc_html((string) count($crea3_ilp_visible_conflicts)); ?></strong> <?php esc_html_e('displayed results', 'crea3-internal-linking-pro'); ?></span>
                <?php if ($crea3_ilp_scan_date) : ?>
                    <span><?php esc_html_e('Analysis from', 'crea3-internal-linking-pro'); ?> <?php echo esc_html($crea3_ilp_scan_date); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cma-conflicts-recalculate-form">
            <?php wp_nonce_field('crea3_ilp_conflicts_recalculate_action', 'crea3_ilp_conflicts_nonce'); ?>
            <input type="hidden" name="action" value="crea3_ilp_conflicts_recalculate">
            <button type="submit" class="button button-primary cma-conflicts-recalculate">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php esc_html_e('Recalculate conflicts', 'crea3-internal-linking-pro'); ?>
            </button>
        </form>
    </section>

    <?php if (!empty($crea3_ilp_analysis['limited_data'])) : ?>
        <div class="notice notice-info inline cma-conflicts-notice">
            <p><?php esc_html_e('Some data is not yet available to enrich this analysis.', 'crea3-internal-linking-pro'); ?></p>
        </div>
    <?php endif; ?>

    <section class="cma-conflicts-summary" aria-label="<?php esc_attr_e('Conflict summary', 'crea3-internal-linking-pro'); ?>">
        <article class="cma-conflicts-card cma-conflicts-card-total">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Total detected', 'crea3-internal-linking-pro'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $crea3_ilp_summary['total']); ?></strong>
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Conflicts to review', 'crea3-internal-linking-pro'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-critical">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Critical', 'crea3-internal-linking-pro'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $crea3_ilp_summary['critical']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Top priority', 'crea3-internal-linking-pro'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-medium">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Medium', 'crea3-internal-linking-pro'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $crea3_ilp_summary['medium']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Needs review', 'crea3-internal-linking-pro'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-light">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Light', 'crea3-internal-linking-pro'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $crea3_ilp_summary['light']); ?></strong>
                <span class="cma-conflicts-card-dot" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Monitor', 'crea3-internal-linking-pro'); ?></small>
        </article>
        <article class="cma-conflicts-card cma-conflicts-card-posts">
            <span class="cma-conflicts-card-label"><?php esc_html_e('Affected content', 'crea3-internal-linking-pro'); ?></span>
            <div class="cma-conflicts-card-value-row">
                <strong><?php echo esc_html((string) $crea3_ilp_summary['affected_posts']); ?></strong>
                <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
            </div>
            <small><?php esc_html_e('Impacted scope', 'crea3-internal-linking-pro'); ?></small>
        </article>
    </section>

    <section class="cma-conflicts-insights" aria-label="<?php esc_attr_e('Key insights', 'crea3-internal-linking-pro'); ?>">
        <article class="cma-conflicts-insight">
            <span class="dashicons dashicons-category" aria-hidden="true"></span>
            <div>
                <span><?php esc_html_e('Most affected cluster', 'crea3-internal-linking-pro'); ?></span>
                <strong><?php echo esc_html($crea3_ilp_summary['top_cluster'] ?: __('Not available', 'crea3-internal-linking-pro')); ?></strong>
            </div>
        </article>
        <article class="cma-conflicts-insight">
            <span class="dashicons dashicons-tag" aria-hidden="true"></span>
            <div>
                <span><?php esc_html_e('Most involved expression', 'crea3-internal-linking-pro'); ?></span>
                <strong><?php echo esc_html($crea3_ilp_summary['top_topic'] ?: __('Not available', 'crea3-internal-linking-pro')); ?></strong>
            </div>
        </article>
    </section>

    <section class="cma-conflicts-toolbar">
        <div>
            <h3><?php esc_html_e('Detected conflicts', 'crea3-internal-linking-pro'); ?></h3>
            <p>
                <?php
                /* translators: %d: number of conflict results for the active filter. */
                echo esc_html(sprintf(_n('%d result for this filter', '%d results for this filter', count($crea3_ilp_visible_conflicts), 'crea3-internal-linking-pro'), count($crea3_ilp_visible_conflicts)));
                ?>
            </p>
        </div>
        <nav class="cma-conflicts-filters" aria-label="<?php esc_attr_e('Filter conflicts', 'crea3-internal-linking-pro'); ?>">
            <?php
            $crea3_ilp_filters = array(
                'all' => __('All', 'crea3-internal-linking-pro'),
                'critical' => __('Critical', 'crea3-internal-linking-pro'),
                'medium' => __('Medium', 'crea3-internal-linking-pro'),
                'light' => __('Light', 'crea3-internal-linking-pro'),
            );
            foreach ($crea3_ilp_filters as $crea3_ilp_filter => $crea3_ilp_label) :
                ?>
                <a class="<?php echo $crea3_ilp_active_filter === $crea3_ilp_filter ? 'is-active' : ''; ?>" href="<?php echo esc_url(crea3_ilp_render_conflicts_filter_url($crea3_ilp_filter)); ?>">
                    <?php echo esc_html($crea3_ilp_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <div class="cma-conflicts-table-shell">
        <div class="cma-conflicts-table-scroll">
            <table class="widefat cma-conflicts-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Severity', 'crea3-internal-linking-pro'); ?></th>
                        <th><?php esc_html_e('Expression or topic', 'crea3-internal-linking-pro'); ?></th>
                        <th><?php esc_html_e('Affected content', 'crea3-internal-linking-pro'); ?></th>
                        <th><?php esc_html_e('Similarity', 'crea3-internal-linking-pro'); ?></th>
                        <th><?php esc_html_e('Conflict type', 'crea3-internal-linking-pro'); ?></th>
                        <th><?php esc_html_e('Recommendation', 'crea3-internal-linking-pro'); ?></th>
                        <th class="cma-conflicts-action-column"><?php esc_html_e('Action', 'crea3-internal-linking-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$crea3_ilp_visible_conflicts) : ?>
                        <tr>
                            <td colspan="7">
                                <div class="cma-conflicts-empty">
                                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                    <strong><?php esc_html_e('No conflicts found with this filter.', 'crea3-internal-linking-pro'); ?></strong>
                                    <p><?php esc_html_e('Run a recalculation after your next editorial changes.', 'crea3-internal-linking-pro'); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($crea3_ilp_visible_conflicts as $crea3_ilp_index => $crea3_ilp_conflict) : ?>
                        <?php
                        $crea3_ilp_severity = $crea3_ilp_conflict['severity'] ?? 'light';
                        $crea3_ilp_score = max(0, min(100, (int) ($crea3_ilp_conflict['score'] ?? 0)));
                        $crea3_ilp_details_id = 'cma-conflicts-details-' . (int) $crea3_ilp_index;
                        $crea3_ilp_articles = isset($crea3_ilp_conflict['articles']) && is_array($crea3_ilp_conflict['articles']) ? $crea3_ilp_conflict['articles'] : array();
                        $crea3_ilp_types = isset($crea3_ilp_conflict['types']) && is_array($crea3_ilp_conflict['types']) ? $crea3_ilp_conflict['types'] : array();
                        ?>
                        <tr class="cma-conflicts-row">
                            <td>
                                <span class="cma-conflicts-badge cma-conflicts-badge-<?php echo esc_attr($crea3_ilp_severity); ?>">
                                    <?php echo esc_html(crea3_ilp_render_conflicts_severity_label($crea3_ilp_severity)); ?>
                                </span>
                            </td>
                            <td>
                                <strong class="cma-conflicts-topic"><?php echo esc_html($crea3_ilp_conflict['topic'] ?? __('Related topic', 'crea3-internal-linking-pro')); ?></strong>
                                <?php if (!empty($crea3_ilp_conflict['common_expressions'])) : ?>
                                    <span class="cma-conflicts-topic-meta"><?php echo esc_html(implode(' · ', array_slice($crea3_ilp_conflict['common_expressions'], 0, 3))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="cma-conflicts-article-count">
                                    <strong><?php echo esc_html((string) count($crea3_ilp_articles)); ?></strong>
                                    <span><?php esc_html_e('content items', 'crea3-internal-linking-pro'); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="cma-conflicts-score" style="--cma-conflicts-score: <?php echo esc_attr((string) $crea3_ilp_score); ?>%;">
                                    <strong><?php echo esc_html((string) $crea3_ilp_score); ?>%</strong>
                                    <span class="cma-conflicts-score-track"><span class="cma-conflicts-score-fill"></span></span>
                                </div>
                            </td>
                            <td>
                                <div class="cma-conflicts-types">
                                    <?php foreach (array_slice($crea3_ilp_types, 0, 3) as $type) : ?>
                                        <span class="cma-conflicts-type"><?php echo esc_html((string) $type); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><p class="cma-conflicts-table-recommendation"><?php echo esc_html($crea3_ilp_conflict['recommendation'] ?? __('Monitor only', 'crea3-internal-linking-pro')); ?></p></td>
                            <td class="cma-conflicts-action-column">
                                <button type="button" class="button cma-conflicts-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr($crea3_ilp_details_id); ?>">
                                    <span class="cma-conflicts-toggle-label"><?php esc_html_e('Details', 'crea3-internal-linking-pro'); ?></span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </button>
                            </td>
                        </tr>
                        <tr id="<?php echo esc_attr($crea3_ilp_details_id); ?>" class="cma-conflicts-details-row">
                            <td colspan="7">
                                <div class="cma-conflicts-details">
                                    <div class="cma-conflicts-details-head">
                                        <div>
                                            <span class="cma-conflicts-details-eyebrow"><?php esc_html_e('Risk analysis', 'crea3-internal-linking-pro'); ?></span>
                                            <h4><?php echo esc_html($crea3_ilp_conflict['topic'] ?? __('Editorial conflict', 'crea3-internal-linking-pro')); ?></h4>
                                        </div>
                                        <div class="cma-conflicts-details-score">
                                            <span><?php esc_html_e('Global score', 'crea3-internal-linking-pro'); ?></span>
                                            <strong><?php echo esc_html((string) $crea3_ilp_score); ?>/100</strong>
                                        </div>
                                    </div>

                                    <div class="cma-conflicts-articles-grid">
                                        <?php foreach ($crea3_ilp_articles as $crea3_ilp_article) : ?>
                                            <article class="cma-conflicts-article-card <?php echo !empty($crea3_ilp_article['is_primary']) ? 'is-primary' : ''; ?>">
                                                <div class="cma-conflicts-article-card-head">
                                                    <?php if (!empty($crea3_ilp_article['is_primary'])) : ?>
                                                        <span class="cma-conflicts-primary-label"><?php esc_html_e('Suggested primary content', 'crea3-internal-linking-pro'); ?></span>
                                                    <?php else : ?>
                                                        <span class="cma-conflicts-secondary-label"><?php esc_html_e('Competing content', 'crea3-internal-linking-pro'); ?></span>
                                                    <?php endif; ?>
                                                    <h5><?php echo esc_html($crea3_ilp_article['title'] ?? __('Untitled', 'crea3-internal-linking-pro')); ?></h5>
                                                    <?php if (!empty($crea3_ilp_article['url'])) : ?>
                                                        <a href="<?php echo esc_url($crea3_ilp_article['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($crea3_ilp_article['url']); ?></a>
                                                    <?php endif; ?>
                                                </div>
                                                <dl class="cma-conflicts-article-stats">
                                                    <div><dt><?php esc_html_e('Updated', 'crea3-internal-linking-pro'); ?></dt><dd><?php echo esc_html(crea3_ilp_render_conflicts_date($crea3_ilp_article['modified'] ?? '')); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Internal score', 'crea3-internal-linking-pro'); ?></dt><dd><?php echo esc_html((string) ($crea3_ilp_article['score'] ?? __('N/A', 'crea3-internal-linking-pro'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Incoming links', 'crea3-internal-linking-pro'); ?></dt><dd><?php echo esc_html((string) ($crea3_ilp_article['incoming'] ?? __('N/A', 'crea3-internal-linking-pro'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Outgoing links', 'crea3-internal-linking-pro'); ?></dt><dd><?php echo esc_html((string) ($crea3_ilp_article['outgoing'] ?? __('N/A', 'crea3-internal-linking-pro'))); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Cluster', 'crea3-internal-linking-pro'); ?></dt><dd><?php echo esc_html((string) ($crea3_ilp_article['cluster'] ?? __('N/A', 'crea3-internal-linking-pro'))); ?></dd></div>
                                                </dl>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="cma-conflicts-details-grid">
                                        <section class="cma-conflicts-detail-section">
                                            <h5><?php esc_html_e('Shared expressions', 'crea3-internal-linking-pro'); ?></h5>
                                            <div class="cma-conflicts-tags">
                                                <?php foreach (($crea3_ilp_conflict['common_expressions'] ?? array()) as $crea3_ilp_expression) : ?>
                                                    <span><?php echo esc_html((string) $crea3_ilp_expression); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (empty($crea3_ilp_conflict['common_expressions'])) : ?><em><?php esc_html_e('No specific expression detected.', 'crea3-internal-linking-pro'); ?></em><?php endif; ?>
                                            </div>
                                        </section>
                                        <section class="cma-conflicts-detail-section">
                                            <h5><?php esc_html_e('Shared anchors', 'crea3-internal-linking-pro'); ?></h5>
                                            <div class="cma-conflicts-tags">
                                                <?php foreach (($crea3_ilp_conflict['common_anchors'] ?? array()) as $crea3_ilp_anchor) : ?>
                                                    <span><?php echo esc_html((string) $crea3_ilp_anchor); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (empty($crea3_ilp_conflict['common_anchors'])) : ?><em><?php esc_html_e('No shared anchors detected.', 'crea3-internal-linking-pro'); ?></em><?php endif; ?>
                                            </div>
                                        </section>
                                    </div>

                                    <section class="cma-conflicts-network-section">
                                        <div class="cma-conflicts-section-heading">
                                            <h5><?php esc_html_e('Simplified link map', 'crea3-internal-linking-pro'); ?></h5>
                                            <span><?php esc_html_e('The suggested primary content is highlighted.', 'crea3-internal-linking-pro'); ?></span>
                                        </div>
                                        <div class="cma-conflicts-network">
                                            <?php foreach ($crea3_ilp_articles as $crea3_ilp_article) : ?>
                                                <div class="cma-conflicts-network-node <?php echo !empty($crea3_ilp_article['is_primary']) ? 'is-primary' : ''; ?>">
                                                    <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                                                    <strong><?php echo esc_html($crea3_ilp_article['title'] ?? __('Untitled', 'crea3-internal-linking-pro')); ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>

                                    <div class="cma-conflicts-risk">
                                        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
                                        <p><?php echo esc_html($crea3_ilp_conflict['risk_explanation'] ?? __('These contents cover a similar editorial scope and may compete in search results.', 'crea3-internal-linking-pro')); ?></p>
                                    </div>

                                    <div class="cma-conflicts-recommendation">
                                        <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
                                        <div>
                                            <strong><?php esc_html_e('Recommended action', 'crea3-internal-linking-pro'); ?></strong>
                                            <p><?php echo esc_html($crea3_ilp_conflict['recommendation'] ?? __('Monitor only', 'crea3-internal-linking-pro')); ?></p>
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
