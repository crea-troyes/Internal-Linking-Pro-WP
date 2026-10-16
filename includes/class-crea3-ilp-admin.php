<?php
if (!defined('ABSPATH')) exit;

final class Crea3_ILP_Admin {

    private string $slug = 'crea3-internal-linking-pro';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('wp_ajax_crea3_ilp_run_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_crea3_ilp_clear_scan', [$this, 'ajax_clear_scan']);

        add_action('admin_post_crea3_ilp_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_crea3_ilp_conflicts_recalculate', [$this, 'cma_conflicts_handle_recalculate']);
    }

    public function register_menu(): void {
        add_submenu_page(
            'tools.php',
            __('Internal Linking Pro', 'crea3-internal-linking-pro'),
            __('Internal Linking', 'crea3-internal-linking-pro'),
            'manage_options',
            $this->slug,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void {

        $expected = 'tools_page_' . $this->slug;
        if ($hook !== $expected) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no data is modified.
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';
        $dependencies = ['jquery'];

        if ($view === 'graph') {
            wp_enqueue_script(
                'vis-network',
                CREA3_ILP_URL . 'assets/vendor/vis-network.min.js',
                [],
                '9.1.2',
                true
            );
            $dependencies[] = 'vis-network';
        }

        wp_enqueue_script(
            'crea3-ilp-admin',
            CREA3_ILP_URL . 'assets/admin.js',
            $dependencies,
            CREA3_ILP_VERSION,
            true
        );

        wp_enqueue_style('crea3-ilp-admin', CREA3_ILP_URL . 'assets/admin.css', [], CREA3_ILP_VERSION);

        wp_localize_script('crea3-ilp-admin', 'crea3IlpAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('crea3_ilp_scan_nonce'),
            'i18n'    => [
                'scanRunning' => __('Scan in progress... Do not close this page.', 'crea3-internal-linking-pro'),
                'unknownError' => __('Unknown error.', 'crea3-internal-linking-pro'),
                'ajaxError' => __('AJAX error. A server timeout may have occurred.', 'crea3-internal-linking-pro'),
                'confirmClearCache' => __('Delete the cached analysis?', 'crea3-internal-linking-pro'),
                'clearingCache' => __('Clearing cache...', 'crea3-internal-linking-pro'),
                'details' => __('Details', 'crea3-internal-linking-pro'),
                'hide' => __('Hide', 'crea3-internal-linking-pro'),
                'mapLoading' => __('Calculating map...', 'crea3-internal-linking-pro'),
                'isolatedContent' => __('Isolated content', 'crea3-internal-linking-pro'),
                'type' => __('Type', 'crea3-internal-linking-pro'),
                'page' => __('Page', 'crea3-internal-linking-pro'),
                'post' => __('Post', 'crea3-internal-linking-pro'),
                'incomingLinks' => __('Incoming links', 'crea3-internal-linking-pro'),
                'outgoingLinks' => __('Outgoing links', 'crea3-internal-linking-pro'),
                'role' => __('Role', 'crea3-internal-linking-pro'),
                'pillarPage' => __('Pillar page', 'crea3-internal-linking-pro'),
                'cluster' => __('Cluster', 'crea3-internal-linking-pro'),
                'clusterScore' => __('Cluster score', 'crea3-internal-linking-pro'),
                'warning' => __('Warning', 'crea3-internal-linking-pro'),
                'openContent' => __('Open content', 'crea3-internal-linking-pro'),
                'untitled' => __('Untitled', 'crea3-internal-linking-pro'),
                'anchor' => __('Anchor', 'crea3-internal-linking-pro'),
                'contentItems' => __('content items', 'crea3-internal-linking-pro'),
                'contentItemsFound' => __('content item(s) found', 'crea3-internal-linking-pro'),
            ],
            'toasts' => [],
        ]);
    }

    private function render_view(string $view, array $vars = []): void {
        $file = CREA3_ILP_PATH . 'includes/views/' . $view . '.php';

        if (file_exists($file)) {
            extract($vars, EXTR_SKIP);
            include $file;
        } else {
            /* translators: %s: requested administration view name. */
            echo '<p>' . esc_html(sprintf(__('View not found: %s', 'crea3-internal-linking-pro'), $view)) . '</p>';
        }
    }

    public function render_page(): void {

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'crea3-internal-linking-pro'));
        }

        $data = $this->cma_apply_current_exclusions_to_scan_data(get_option(CREA3_ILP_OPTION_KEY));
        $has_data = is_array($data) && !empty($data['items']);

        $allowed_views = ['dashboard', 'table', 'isolated_posts', 'orphans', 'suggestions', 'conflict', 'silos', 'graph', 'settings', 'dead_end'];
        $allowed_filters = ['post', 'page', 'both'];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no data is modified.
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display filter; no data is modified.
        $filter = isset($_GET['filter']) ? sanitize_key(wp_unslash((string) $_GET['filter'])) : 'both';
        $view = in_array($view, $allowed_views, true) ? $view : 'dashboard';
        $filter = in_array($filter, $allowed_filters, true) ? $filter : 'both';

        $cma_toasts = [];

        echo '<div class="wrap cma-wrap">';

        echo '<div id="cma-toast-container"></div>';
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status flag set after the nonce-protected settings action.
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === '1') {
            $cma_toasts[] = [
                'message' => __('Settings saved successfully.', 'crea3-internal-linking-pro'),
                'type' => 'success'
            ];
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status flag set after the nonce-protected recalculation action.
        if (isset($_GET['conflicts-recalculated']) && $_GET['conflicts-recalculated'] === '1') {
            $cma_toasts[] = [
                'message' => __('Cannibalization conflicts recalculated.', 'crea3-internal-linking-pro'),
                'type' => 'success'
            ];
        }
        
        echo '<header class="header-cma">';
            echo '<h1><img class="cma-header-logo" src="' . esc_url(CREA3_ILP_URL . 'img/logo.webp') . '" alt="' . esc_attr__('Internal Linking Pro', 'crea3-internal-linking-pro') . '"><span class="cma-header-separator">|</span><span class="cma-header-title">' . esc_html__('Internal Linking Audit', 'crea3-internal-linking-pro') . '</span></h1>';
            echo '<p class="description">';
                echo esc_html__('Manual scan. Results are cached to avoid any front-end performance impact.', 'crea3-internal-linking-pro');
            echo '</p>';
        echo '</header>';

        $base_url = menu_page_url($this->slug, false);

        $tab = static function(string $k, string $label) use ($base_url, $view, $filter) {
            $cls = ($view === $k) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $icons = [
                'dashboard'      => 'dashicons-grid-view',
                'table'          => 'dashicons-list-view',
                'isolated_posts' => 'dashicons-media-document',
                'orphans'        => 'dashicons-admin-links',
                'suggestions'    => 'dashicons-lightbulb',
                'conflict'       => 'dashicons-warning',
                'silos'          => 'dashicons-database',
                'graph'          => 'dashicons-location-alt',
                'settings'       => 'dashicons-admin-settings',
            ];
            $args = ['view' => $k];
            if ($k !== 'settings') {
                $args['filter'] = $filter;
            }
            $url = add_query_arg($args, $base_url);

            echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($url) . '">';
            echo '<span class="dashicons ' . esc_attr($icons[$k] ?? 'dashicons-marker') . '" aria-hidden="true"></span>';
            echo '<span class="cma-tab-label">' . esc_html($label) . '</span>';
            echo '</a>';
        };

        $analyzer = null;
        $table_count = 0;
        $orphans_count = 0;
        $isolated_count = 0;
        $suggestions_count = 0;

        if ($has_data) {
            $analyzer = new Crea3_ILP_Analyzer($data);

            $table_count    = count($analyzer->get_table_rows($filter));
            $orphans_count  = count($analyzer->get_orphans_global($filter));
            $isolated_count = count($analyzer->get_isolated_posts());
            if (in_array($view, ['dashboard', 'suggestions'], true)) {
                $suggestions_count = count($analyzer->get_link_suggestions());
            }
        }

        echo '<h2 class="nav-tab-wrapper cma-tabs">';
        
        $tab('dashboard', __('Dashboard', 'crea3-internal-linking-pro'));
        /* translators: %d: number of content items. */
        $tab('table', sprintf(__('Table (%d)', 'crea3-internal-linking-pro'), $table_count));
        /* translators: %d: number of isolated posts. */
        $tab('isolated_posts', sprintf(__('Isolated posts (%d)', 'crea3-internal-linking-pro'), $isolated_count));
        /* translators: %d: number of orphaned content items. */
        $tab('orphans', sprintf(__('Global orphans (%d)', 'crea3-internal-linking-pro'), $orphans_count));
        $suggestions_label = in_array($view, ['dashboard', 'suggestions'], true)
            /* translators: %d: number of link suggestions. */
            ? sprintf(__('Link suggestions (%d)', 'crea3-internal-linking-pro'), $suggestions_count)
            : __('Link suggestions', 'crea3-internal-linking-pro');

        $tab('suggestions', $suggestions_label);
        $tab('conflict', __('Conflicts', 'crea3-internal-linking-pro'));
        $tab('silos', __('Silos', 'crea3-internal-linking-pro'));
        $tab('graph', __('Graph view', 'crea3-internal-linking-pro'));
        $tab('settings', __('Settings', 'crea3-internal-linking-pro'));
        
        echo '</h2>';

        if ($view === 'settings') {
            $this->render_view('setting', [
                'analyzer' => $analyzer,
                'filter'   => $filter
            ]);
            return;
        }

        echo '<div class="cma-controls">';
        echo '<nav class="cma-block cma-filtre" aria-labelledby="cma-filter-label">';
        echo '<span class="cma-filter-label" id="cma-filter-label">' . esc_html__('Display filter:', 'crea3-internal-linking-pro') . '</span>';
        echo '<span class="cma-filter-options">';

        echo wp_kses_post($this->radio_link('filter', 'post', __('Posts', 'crea3-internal-linking-pro'), $filter));
        echo wp_kses_post($this->radio_link('filter', 'page', __('Pages', 'crea3-internal-linking-pro'), $filter));
        echo wp_kses_post($this->radio_link('filter', 'both', __('Posts + Pages', 'crea3-internal-linking-pro'), $filter));

        echo '</span>';
        echo '</nav>';

        echo '<div class="cma-block cma-actions">';
        echo '<button class="button button-primary cma-action-button cma-action-button-primary" id="cma-run-scan">'
            . '<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>'
            . esc_html__('Run scan', 'crea3-internal-linking-pro')
            . '</button> ';

        echo '<button class="button cma-action-button cma-action-button-secondary" id="cma-clear-scan">'
            . '<span class="dashicons dashicons-database" aria-hidden="true"></span>'
            . esc_html__('Clear cache', 'crea3-internal-linking-pro')
            . '</button> ';

        echo '<span class="cma-status" id="cma-status"></span>';
        echo '</div>';
        echo '</div>';

        if (!$has_data) {

            echo esc_html__('No cached analysis found. Click Run Scan.', 'crea3-internal-linking-pro');
            return;
        }

        $analyzer = new Crea3_ILP_Analyzer($data);

        if ($view === 'dashboard') {

            $this->render_view('dashboard', [
                'analyzer' => $analyzer,
                'filter'   => $filter
            ]);
            return;

        } elseif ($view === 'table') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $analyzer->get_table_rows($filter),
                'intro'    => __('Global overview based on current filter.', 'crea3-internal-linking-pro')
            ]);

        } elseif ($view === 'isolated_posts') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $filter === 'page' ? [] : $analyzer->get_isolated_posts(),
                'intro'    => __('Posts without internal links from other posts.', 'crea3-internal-linking-pro')
            ]);

        } elseif ($view === 'orphans') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $analyzer->get_orphans_global($filter),
                'intro'    => __('Content without internal incoming links based on current filter.', 'crea3-internal-linking-pro')
            ]);

        } elseif ($view === 'suggestions') {

            $this->render_view('suggestions', [
                'analyzer' => $analyzer
            ]);
            
        } elseif ($view === 'conflict') {

            $this->render_view('conflict', [
                'analyzer' => $analyzer,
                'filter'   => $filter
            ]);
            $this->cma_render_toasts($cma_toasts);
            echo '</div>';
            return;
            
        } elseif ($view === 'silos') {

            $this->render_view('silos', [
                'analyzer' => $analyzer
            ]);

        } elseif ($view === 'graph') {

            $graph = $analyzer->get_graph_payload($filter);

            echo "<p>" . esc_html__('You can zoom and drag the graph.', 'crea3-internal-linking-pro') . "</p>";

            echo '<div id="cma-graph-wrapper">';
                $threshold = (int) get_option(CREA3_ILP_CLUSTER_THRESHOLD_OPTION, 5);
                echo '<div id="cma-graph"
                data-threshold="' . esc_attr((string) $threshold) . '"
                data-graph=\'' . esc_attr(wp_json_encode($graph)) . '\'></div>';
                echo '<div id="cma-graph-info" data-empty-label="' . esc_attr__('Click a graph node to display SEO details.', 'crea3-internal-linking-pro') . '"></div>';
            echo '</div>';
            

        } elseif ($view === 'dead_end') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $analyzer->get_dead_end_pages($filter),
                'intro'    => __('Pages or posts without any outgoing internal links.', 'crea3-internal-linking-pro')
            ]);

        }

        if (!empty($data['generated_at'])) {
            echo '<p class="cma-meta">'
                . wp_kses_post(sprintf(
                    /* translators: %s: date and time of the last scan. */
                    __('Last scan: %s', 'crea3-internal-linking-pro'),
                    '<strong>' . esc_html(date_i18n('d/m/Y H:i', (int) $data['generated_at'])) . '</strong>'
                ))
                . '</p>';
        }

        $this->cma_render_toasts($cma_toasts);
        echo '</div>';
    }

    private function cma_render_toasts(array $toasts): void {

        if (empty($toasts)) {
            return;
        }

        $script = 'window.crea3IlpAdmin = window.crea3IlpAdmin || {};'
            . 'window.crea3IlpAdmin.toasts = '
            . wp_json_encode(array_values($toasts))
            . ';';
        wp_add_inline_script('crea3-ilp-admin', $script, 'before');
    }

    public function handle_save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'crea3-internal-linking-pro'));
        }

        check_admin_referer('crea3_ilp_save_settings_action', 'crea3_ilp_settings_nonce');

        $raw_input = isset($_POST['crea3_ilp_excluded_ids']) ? sanitize_textarea_field(wp_unslash((string) $_POST['crea3_ilp_excluded_ids'])) : '';
        $clean_ids = $this->sanitize_ids_list($raw_input);

        update_option(CREA3_ILP_EXCLUDED_IDS_OPTION, implode(',', $clean_ids), false);
        delete_transient('crea3_ilp_conflicts_cache');

        $threshold = isset($_POST['crea3_ilp_cluster_threshold'])
            ? min(100, max(1, absint(wp_unslash($_POST['crea3_ilp_cluster_threshold']))))
            : 3;

        update_option(CREA3_ILP_CLUSTER_THRESHOLD_OPTION, $threshold, false);

        $redirect_url = add_query_arg([
            'page' => $this->slug,
            'view' => 'settings',
            'settings-updated' => '1',
        ], admin_url('tools.php'));

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function cma_conflicts_handle_recalculate(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'crea3-internal-linking-pro'));
        }

        check_admin_referer('crea3_ilp_conflicts_recalculate_action', 'crea3_ilp_conflicts_nonce');

        delete_transient('crea3_ilp_conflicts_cache');

        $data = $this->cma_apply_current_exclusions_to_scan_data(get_option(CREA3_ILP_OPTION_KEY));
        if (is_array($data) && !empty($data['items'])) {
            $analyzer = new Crea3_ILP_Analyzer($data);
            $analyzer->cma_conflicts_get_cached_analysis(true);
        }

        $redirect_url = add_query_arg([
            'page' => $this->slug,
            'view' => 'conflict',
            'conflicts-recalculated' => '1',
        ], admin_url('tools.php'));

        wp_safe_redirect($redirect_url);
        exit;
    }

    private function sanitize_ids_list(string $raw): array {
        $parts = preg_split('/[\s,;]+/', $raw);
        if (!is_array($parts)) {
            return [];
        }

        $ids = array_map('intval', $parts);
        $ids = array_filter($ids, static fn($id) => $id > 0);
        $ids = array_values(array_unique($ids));

        sort($ids);

        return $ids;
    }

    private function cma_apply_current_exclusions_to_scan_data($data) {
        if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
            return $data;
        }

        $excluded_ids = $this->sanitize_ids_list((string) get_option(CREA3_ILP_EXCLUDED_IDS_OPTION, ''));
        if (empty($excluded_ids)) {
            $data['excluded_ids'] = [];
            return $data;
        }

        $excluded_lookup = array_flip(array_map('intval', $excluded_ids));

        foreach (array_keys($data['items']) as $id) {
            if (isset($excluded_lookup[(int)$id])) {
                unset($data['items'][$id]);
            }
        }

        foreach (['out_internal', 'posts_only_out'] as $key) {
            if (empty($data[$key]) || !is_array($data[$key])) {
                continue;
            }

            foreach ($data[$key] as $from_id => $targets) {
                if (isset($excluded_lookup[(int)$from_id])) {
                    unset($data[$key][$from_id]);
                    continue;
                }

                if (is_array($targets)) {
                    $data[$key][$from_id] = array_values(array_filter($targets, static function($target_id) use ($excluded_lookup) {
                        return !isset($excluded_lookup[(int)$target_id]);
                    }));
                }
            }
        }

        foreach (['incoming_global', 'out_external_count', 'posts_only_in'] as $key) {
            if (empty($data[$key]) || !is_array($data[$key])) {
                continue;
            }

            foreach (array_keys($data[$key]) as $id) {
                if (isset($excluded_lookup[(int)$id])) {
                    unset($data[$key][$id]);
                }
            }
        }

        if (!empty($data['edges']) && is_array($data['edges'])) {
            $data['edges'] = array_values(array_filter($data['edges'], static function($edge) use ($excluded_lookup) {
                $from = (int)($edge['from'] ?? 0);
                $to = (int)($edge['to'] ?? 0);

                return !isset($excluded_lookup[$from]) && !isset($excluded_lookup[$to]);
            }));
        }

        $data['incoming_global'] = array_fill_keys(array_keys($data['items']), 0);
        foreach (($data['out_internal'] ?? []) as $targets) {
            foreach ((array)$targets as $target_id) {
                if (isset($data['incoming_global'][$target_id])) {
                    $data['incoming_global'][$target_id]++;
                }
            }
        }

        if (!empty($data['posts_only_in']) && is_array($data['posts_only_in'])) {
            $data['posts_only_in'] = [];

            foreach ($data['items'] as $id => $item) {
                if (($item['type'] ?? '') === 'post') {
                    $data['posts_only_in'][$id] = 0;
                }
            }

            foreach (($data['posts_only_out'] ?? []) as $targets) {
                foreach ((array)$targets as $target_id) {
                    if (isset($data['posts_only_in'][$target_id])) {
                        $data['posts_only_in'][$target_id]++;
                    }
                }
            }
        }

        $data['excluded_ids'] = $excluded_ids;

        return $data;
    }

    private function radio_link(string $key, string $value, string $label, string $current): string {

        $base_url = menu_page_url($this->slug, false);

        $url = add_query_arg([
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no data is modified.
            'view' => isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard',
            $key   => $value,
        ], $base_url);

        $is_active     = ($current === $value);
        $active_class  = $is_active ? ' is-active' : '';
        $aria_current  = $is_active ? ' aria-current="page"' : '';

        return '<a class="cma-radio' . esc_attr($active_class) . '" href="' . esc_url($url) . '"' . $aria_current . '>'
            . esc_html($label)
            . '</a>';
    }

    public function ajax_run_scan(): void {

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Access denied.', 'crea3-internal-linking-pro')
            ], 403);
        }

        check_ajax_referer('crea3_ilp_scan_nonce', 'nonce');

        $scanner = new Crea3_ILP_Scanner();
        $data    = $scanner->run_global_scan();

        update_option(CREA3_ILP_OPTION_KEY, $data, false);
        delete_transient('crea3_ilp_conflicts_cache');

        wp_send_json_success([
            'message' => __('Scan completed successfully.', 'crea3-internal-linking-pro'),
            'counts'  => [
                'items' => count($data['items'] ?? []),
                'edges' => count($data['edges'] ?? []),
            ]
        ]);
    }

    public function ajax_clear_scan(): void {

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Access denied.', 'crea3-internal-linking-pro')
            ], 403);
        }

        check_ajax_referer('crea3_ilp_scan_nonce', 'nonce');

        delete_option(CREA3_ILP_OPTION_KEY);
        delete_transient('crea3_ilp_conflicts_cache');

        wp_send_json_success([
            'message' => __('Cache cleared successfully.', 'crea3-internal-linking-pro')
        ]);
    }

}
