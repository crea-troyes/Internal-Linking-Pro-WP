<?php
if (!defined('ABSPATH')) exit;

require_once CREA3_ILP_PATH . 'includes/class-crea3-ilp-gutenberg.php';

final class Crea3_ILP_Plugin {
    private static ?Crea3_ILP_Plugin $instance = null;

    public static function instance(): Crea3_ILP_Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'load_textdomain']);

        $this->migrate_legacy_data();

        if (is_admin()) {
            require_once CREA3_ILP_PATH . 'includes/class-crea3-ilp-admin.php';
            require_once CREA3_ILP_PATH . 'includes/class-crea3-ilp-scanner.php';
            require_once CREA3_ILP_PATH . 'includes/class-crea3-ilp-analyzer.php';
            new Crea3_ILP_Admin();
        }

        // Le module Gutenberg doit aussi être initialisé pendant les requêtes REST.
        new Crea3_ILP_Gutenberg();
    }

    /**
     * Load a WordPress.org language pack or fall back to the bundled catalog.
     */
    public function load_textdomain(): void {
        $domain = 'crea3-internal-linking-pro';

        if (is_textdomain_loaded($domain)) {
            return;
        }

        $locale = sanitize_file_name(determine_locale());
        $filename = $domain . '-' . $locale . '.mo';
        $language_pack = trailingslashit(WP_LANG_DIR) . 'plugins/' . $filename;
        $bundled_catalog = CREA3_ILP_PATH . 'languages/' . $filename;

        if (is_readable($language_pack)) {
            load_textdomain($domain, $language_pack);
        } elseif (is_readable($bundled_catalog)) {
            load_textdomain($domain, $bundled_catalog);
        }
    }

    /**
     * Copy legacy values without overwriting values already stored under the
     * new prefix. Legacy keys are deliberately retained for rollback safety.
     */
    private function migrate_legacy_data(): void {
        if ((int) get_option(CREA3_ILP_MIGRATION_VERSION_OPTION, 0) >= 1) {
            return;
        }

        $missing = new stdClass();
        $option_map = [
            'cma_scan_data' => CREA3_ILP_OPTION_KEY,
            'cma_excluded_ids' => CREA3_ILP_EXCLUDED_IDS_OPTION,
            'cma_cluster_threshold' => CREA3_ILP_CLUSTER_THRESHOLD_OPTION,
        ];

        foreach ($option_map as $legacy_key => $new_key) {
            if ($missing !== get_option($new_key, $missing)) {
                continue;
            }

            $legacy_value = get_option($legacy_key, $missing);
            if ($missing !== $legacy_value) {
                update_option($new_key, $legacy_value, false);
            }
        }

        $legacy_conflicts = get_transient('cma_conflicts_cache');
        if (false !== $legacy_conflicts && false === get_transient('crea3_ilp_conflicts_cache')) {
            set_transient('crea3_ilp_conflicts_cache', $legacy_conflicts, 12 * HOUR_IN_SECONDS);
        }

        update_option(CREA3_ILP_MIGRATION_VERSION_OPTION, 1, false);
    }
}
