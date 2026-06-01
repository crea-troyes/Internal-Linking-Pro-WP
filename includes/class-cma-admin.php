<?php
if (!defined('ABSPATH')) exit;

final class CMA_Admin {

    private string $slug = 'cma-maillage';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('wp_ajax_cma_run_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_cma_clear_scan', [$this, 'ajax_clear_scan']);

        add_action('admin_post_cma_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_cma_conflicts_recalculate', [$this, 'cma_conflicts_handle_recalculate']);
    }

    public function register_menu(): void {
        add_submenu_page(
            'tools.php',
            __('Internal Linking Pro', 'crea-maillage-audit'),
            __('Internal Linking', 'crea-maillage-audit'),
            'manage_options',
            $this->slug,
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void {

        $expected = 'tools_page_' . $this->slug;
        if ($hook !== $expected) return;

        $view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';
        $dependencies = ['jquery'];

        if ($view === 'graph') {
            wp_enqueue_script(
                'vis-network',
                CMA_URL . 'assets/vendor/vis-network.min.js',
                [],
                '9.1.2',
                true
            );
            $dependencies[] = 'vis-network';
        }

        wp_enqueue_script(
            'cma-admin',
            CMA_URL . 'assets/admin.js',
            $dependencies,
            CMA_VERSION,
            true
        );

        wp_enqueue_style('cma-admin', CMA_URL . 'assets/admin.css', [], CMA_VERSION);

        wp_localize_script('cma-admin', 'CMA', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cma_scan_nonce'),
            'i18n'    => [
                'scanRunning' => __('Scan in progress... Do not close this page.', 'crea-maillage-audit'),
                'unknownError' => __('Unknown error.', 'crea-maillage-audit'),
                'ajaxError' => __('AJAX error. A server timeout may have occurred.', 'crea-maillage-audit'),
                'confirmClearCache' => __('Delete the cached analysis?', 'crea-maillage-audit'),
                'clearingCache' => __('Clearing cache...', 'crea-maillage-audit'),
                'details' => __('Details', 'crea-maillage-audit'),
                'hide' => __('Hide', 'crea-maillage-audit'),
                'mapLoading' => __('Calculating map...', 'crea-maillage-audit'),
                'isolatedContent' => __('Isolated content', 'crea-maillage-audit'),
                'type' => __('Type', 'crea-maillage-audit'),
                'page' => __('Page', 'crea-maillage-audit'),
                'post' => __('Post', 'crea-maillage-audit'),
                'incomingLinks' => __('Incoming links', 'crea-maillage-audit'),
                'outgoingLinks' => __('Outgoing links', 'crea-maillage-audit'),
                'role' => __('Role', 'crea-maillage-audit'),
                'pillarPage' => __('Pillar page', 'crea-maillage-audit'),
                'cluster' => __('Cluster', 'crea-maillage-audit'),
                'clusterScore' => __('Cluster score', 'crea-maillage-audit'),
                'warning' => __('Warning', 'crea-maillage-audit'),
                'openContent' => __('Open content', 'crea-maillage-audit'),
                'untitled' => __('Untitled', 'crea-maillage-audit'),
                'anchor' => __('Anchor', 'crea-maillage-audit'),
                'contentItems' => __('content items', 'crea-maillage-audit'),
            ],
        ]);
    }

    private function render_view(string $view, array $vars = []): void {
        $file = CMA_PATH . 'includes/views/' . $view . '.php';

        if (file_exists($file)) {
            extract($vars, EXTR_SKIP);
            include $file;
        } else {
            echo '<p>' . esc_html(sprintf(__('View not found: %s', 'crea-maillage-audit'), $view)) . '</p>';
        }
    }

    public function render_page(): void {

        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'crea-maillage-audit'));
        }

        $data = $this->cma_apply_current_exclusions_to_scan_data(get_option(CMA_OPTION_KEY));
        $has_data = is_array($data) && !empty($data['items']);

        $allowed_views = ['dashboard', 'table', 'isolated_posts', 'orphans', 'suggestions', 'conflict', 'silos', 'graph', 'settings', 'dead_end'];
        $allowed_filters = ['post', 'page', 'both'];
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';
        $filter = isset($_GET['filter']) ? sanitize_key(wp_unslash((string) $_GET['filter'])) : 'both';
        $view = in_array($view, $allowed_views, true) ? $view : 'dashboard';
        $filter = in_array($filter, $allowed_filters, true) ? $filter : 'both';

        $cma_toasts = [];

        echo '<div class="wrap cma-wrap">';

        echo '<div id="cma-toast-container"></div>';
        
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === '1') {
            $cma_toasts[] = [
                'message' => __('Settings saved successfully.', 'crea-maillage-audit'),
                'type' => 'success'
            ];
        }

        if (isset($_GET['conflicts-recalculated']) && $_GET['conflicts-recalculated'] === '1') {
            $cma_toasts[] = [
                'message' => __('Cannibalization conflicts recalculated.', 'crea-maillage-audit'),
                'type' => 'success'
            ];
        }
        
        echo '<header class="header-cma">';
            echo '<h1><img class="cma-header-logo" src="' . esc_url(CMA_URL . 'img/logo.webp') . '" alt="' . esc_attr__('Internal Linking Pro', 'crea-maillage-audit') . '"><span class="cma-header-separator">|</span><span class="cma-header-title">' . esc_html__('Internal Linking Audit', 'crea-maillage-audit') . '</span></h1>';
            echo '<p class="description">';
                echo esc_html__('Manual scan. Results are cached to avoid any front-end performance impact.', 'crea-maillage-audit');
            echo '</p>';
        echo '</header>';

        $base_url = menu_page_url($this->slug, false);

        $tab = static function(string $k, string $label) use ($base_url, $view, $filter) {
            $cls = ($view === $k) ? 'nav-tab nav-tab-active' : 'nav-tab';
            $args = ['view' => $k];
            if ($k !== 'settings') {
                $args['filter'] = $filter;
            }
            $url = add_query_arg($args, $base_url);

            echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        };

        $analyzer = null;
        $table_count = 0;
        $orphans_count = 0;
        $isolated_count = 0;
        $suggestions_count = 0;

        if ($has_data) {
            $analyzer = new CMA_Analyzer($data);

            $table_count    = count($analyzer->get_table_rows($filter));
            $orphans_count  = count($analyzer->get_orphans_global($filter));
            $isolated_count = count($analyzer->get_isolated_posts());
            if (in_array($view, ['dashboard', 'suggestions'], true)) {
                $suggestions_count = count($analyzer->get_link_suggestions());
            }
        }

        echo '<h2 class="nav-tab-wrapper">';
        
        $tab('dashboard', __('Dashboard', 'crea-maillage-audit'));
        $tab('table', sprintf(__('Table (%d)', 'crea-maillage-audit'), $table_count));
        $tab('isolated_posts', sprintf(__('Isolated posts (%d)', 'crea-maillage-audit'), $isolated_count));
        $tab('orphans', sprintf(__('Global orphans (%d)', 'crea-maillage-audit'), $orphans_count));
        $suggestions_label = in_array($view, ['dashboard', 'suggestions'], true)
            ? sprintf(__('Link suggestions (%d)', 'crea-maillage-audit'), $suggestions_count)
            : __('Link suggestions', 'crea-maillage-audit');

        $tab('suggestions', $suggestions_label);
        $tab('conflict', __('Conflicts', 'crea-maillage-audit'));
        $tab('silos', __('Silos', 'crea-maillage-audit'));
        $tab('graph', __('Graph view', 'crea-maillage-audit'));
        $tab('settings', __('Settings', 'crea-maillage-audit'));
        
        echo '</h2>';

        if ($view === 'settings') {
            $this->render_view('setting', [
                'analyzer' => $analyzer,
                'filter'   => $filter
            ]);
            return;
        }

        echo '<div class="cma-controls">';
        echo '<div class="cma-block cma-filtre">';
        echo '<strong>' . esc_html__('Display filter:', 'crea-maillage-audit') . '</strong> ';

        echo $this->radio_link('filter', 'post', __('Posts', 'crea-maillage-audit'), $filter);
        echo $this->radio_link('filter', 'page', __('Pages', 'crea-maillage-audit'), $filter);
        echo $this->radio_link('filter', 'both', __('Posts + Pages', 'crea-maillage-audit'), $filter);

        echo '</div>';

        echo '<div class="cma-block">';
        echo '<button class="button button-primary" id="cma-run-scan">'
            . esc_html__('Run scan', 'crea-maillage-audit')
            . '</button> ';

        echo '<button class="button" id="cma-clear-scan">'
            . esc_html__('Clear cache', 'crea-maillage-audit')
            . '</button> ';

        echo '<span class="cma-status" id="cma-status"></span>';
        echo '</div>';
        echo '</div>';

        if (!$has_data) {

            echo esc_html__('No cached analysis found. Click Run Scan.', 'crea-maillage-audit');
            return;
        }

        $analyzer = new CMA_Analyzer($data);

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
                'intro'    => __('Global overview based on current filter.', 'crea-maillage-audit')
            ]);

        } elseif ($view === 'isolated_posts') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $filter === 'page' ? [] : $analyzer->get_isolated_posts(),
                'intro'    => __('Posts without internal links from other posts.', 'crea-maillage-audit')
            ]);

        } elseif ($view === 'orphans') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $analyzer->get_orphans_global($filter),
                'intro'    => __('Content without internal incoming links based on current filter.', 'crea-maillage-audit')
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
            echo $this->cma_render_toasts($cma_toasts);
            echo '</div>';
            return;
            
        } elseif ($view === 'silos') {

            $this->render_view('silos', [
                'analyzer' => $analyzer
            ]);

        } elseif ($view === 'graph') {

            $graph = $analyzer->get_graph_payload($filter);

            echo "<p>" . esc_html__('You can zoom and drag the graph.', 'crea-maillage-audit') . "</p>";

            echo '<div id="cma-graph-wrapper">';
                $threshold = (int) get_option('cma_cluster_threshold', 5);
                echo '<div id="cma-graph"
                data-threshold="' . $threshold . '"
                data-graph=\'' . esc_attr(wp_json_encode($graph)) . '\'></div>';
                echo '<div id="cma-graph-info" data-empty-label="' . esc_attr__('Click a graph node to display SEO details.', 'crea-maillage-audit') . '"></div>';
            echo '</div>';
            

        } elseif ($view === 'dead_end') {

            $this->render_view('render_table', [
                'analyzer' => $analyzer,
                'filter'   => $filter,
                'rows'     => $analyzer->get_dead_end_pages($filter),
                'intro'    => __('Pages or posts without any outgoing internal links.', 'crea-maillage-audit')
            ]);

        }

        if (!empty($data['generated_at'])) {
            echo '<p class="cma-meta">'
                . sprintf(
                    __('Last scan: %s', 'crea-maillage-audit'),
                    '<strong>' . esc_html(date_i18n('d/m/Y H:i', (int) $data['generated_at'])) . '</strong>'
                )
                . '</p>';
        }

        echo $this->cma_render_toasts($cma_toasts);
        echo '</div>';
    }

    function cma_render_toasts($toasts) {

        if (empty($toasts)) return '';

        $output = '<script>
            document.addEventListener("DOMContentLoaded", function() {';

        foreach ($toasts as $toast) {
            $message = esc_js($toast['message']);
            $type = esc_js($toast['type']);

            $output .= "cmaToast(\"{$message}\", \"{$type}\");";
        }

        $output .= '});
        </script>';

        return $output;
    }

    public function handle_save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'crea-maillage-audit'));
        }

        check_admin_referer('cma_save_settings_action', 'cma_settings_nonce');

        $raw_input = isset($_POST['cma_excluded_ids']) ? wp_unslash((string) $_POST['cma_excluded_ids']) : '';
        $clean_ids = $this->sanitize_ids_list($raw_input);

        update_option(CMA_EXCLUDED_IDS_OPTION, implode(',', $clean_ids), false);
        delete_transient('cma_conflicts_cache');

        $threshold = isset($_POST['cma_cluster_threshold'])
            ? min(100, max(1, absint(wp_unslash($_POST['cma_cluster_threshold']))))
            : 3;

        update_option('cma_cluster_threshold', $threshold, false);

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
            wp_die(__('Access denied.', 'crea-maillage-audit'));
        }

        check_admin_referer('cma_conflicts_recalculate_action', 'cma_conflicts_nonce');

        delete_transient('cma_conflicts_cache');

        $data = $this->cma_apply_current_exclusions_to_scan_data(get_option(CMA_OPTION_KEY));
        if (is_array($data) && !empty($data['items'])) {
            $analyzer = new CMA_Analyzer($data);
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

        $excluded_ids = $this->sanitize_ids_list((string) get_option(CMA_EXCLUDED_IDS_OPTION, ''));
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
            'view' => isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard',
            $key   => $value,
        ], $base_url);

        $checked = ($current === $value) ? 'checked' : '';

        return '<label class="cma-radio">
            <input type="radio" name="' . esc_attr($key) . '" ' . $checked . '
            onclick="window.location.href=\'' . esc_url($url) . '\'">
            ' . esc_html($label) . '
        </label> ';
    }

    public function ajax_run_scan(): void {

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Access denied.', 'crea-maillage-audit')
            ], 403);
        }

        check_ajax_referer('cma_scan_nonce', 'nonce');

        @set_time_limit(600);

        $scanner = new CMA_Scanner();
        $data    = $scanner->run_global_scan();

        update_option(CMA_OPTION_KEY, $data, false);
        delete_transient('cma_conflicts_cache');

        wp_send_json_success([
            'message' => __('Scan completed successfully.', 'crea-maillage-audit'),
            'counts'  => [
                'items' => count($data['items'] ?? []),
                'edges' => count($data['edges'] ?? []),
            ]
        ]);
    }

    public function ajax_clear_scan(): void {

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Access denied.', 'crea-maillage-audit')
            ], 403);
        }

        check_ajax_referer('cma_scan_nonce', 'nonce');

        delete_option(CMA_OPTION_KEY);
        delete_transient('cma_conflicts_cache');

        wp_send_json_success([
            'message' => __('Cache cleared successfully.', 'crea-maillage-audit')
        ]);
    }

}
