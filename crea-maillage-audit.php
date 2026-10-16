<?php
/**
 * Plugin Name: Crea3 Internal Linking Pro
 * Description: Advanced internal linking audit tool (orphan pages, link graph, PageRank, clusters). Admin-only with manual scan.
 * Version: 2.7.0
 * Plugin URI: https://wp.crea-troyes.fr/
 * Author: GUILLIER Alban
 * Author URI: https://portfolio.crea-troyes.fr/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crea3-internal-linking-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('CREA3_ILP_VERSION', '2.7.0');
define('CREA3_ILP_PATH', plugin_dir_path(__FILE__));
define('CREA3_ILP_URL', plugin_dir_url(__FILE__));
define('CREA3_ILP_OPTION_KEY', 'crea3_ilp_scan_data');
define('CREA3_ILP_EXCLUDED_IDS_OPTION', 'crea3_ilp_excluded_ids');
define('CREA3_ILP_CLUSTER_THRESHOLD_OPTION', 'crea3_ilp_cluster_threshold');
define('CREA3_ILP_MIGRATION_VERSION_OPTION', 'crea3_ilp_migration_version');

require_once CREA3_ILP_PATH . 'includes/class-crea3-ilp-plugin.php';

add_action('plugins_loaded', static function () {
    Crea3_ILP_Plugin::instance();
});
