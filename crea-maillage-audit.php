<?php
/**
 * Plugin Name: ILP - Internal Linking Pro
 * Description: Advanced internal linking audit tool (orphan pages, link graph, PageRank, clusters). Admin-only with manual scan.
 * Version: 2.0.2
 * Author: GUILLIER Alban
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crea-maillage-audit
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('CMA_VERSION', '2.0.2');
define('CMA_PATH', plugin_dir_path(__FILE__));
define('CMA_URL', plugin_dir_url(__FILE__));
define('CMA_OPTION_KEY', 'cma_scan_data');
define('CMA_EXCLUDED_IDS_OPTION', 'cma_excluded_ids');

/**
 * Load plugin textdomain
 */
function cma_load_textdomain() {
    load_plugin_textdomain(
        'crea-maillage-audit',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'cma_load_textdomain');

require_once CMA_PATH . 'includes/class-cma-plugin.php';

add_action('plugins_loaded', static function () {
    CMA_Plugin::instance();
});
