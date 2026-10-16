<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('cma_scan_data');
delete_option('cma_excluded_ids');
delete_option('cma_cluster_threshold');
delete_transient('cma_conflicts_cache');
delete_option('crea3_ilp_scan_data');
delete_option('crea3_ilp_excluded_ids');
delete_option('crea3_ilp_cluster_threshold');
delete_option('crea3_ilp_migration_version');
delete_transient('crea3_ilp_conflicts_cache');

// Gutenberg suggestion caches use dynamic suffixes and must be removed explicitly.
global $wpdb;

$crea3_ilp_transient_prefixes = [
    '_transient_cma_editor_',
    '_transient_timeout_cma_editor_',
    '_transient_crea3_ilp_editor_',
    '_transient_timeout_crea3_ilp_editor_',
];

foreach ($crea3_ilp_transient_prefixes as $crea3_ilp_transient_prefix) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup must remove dynamically named transient rows; caching is not applicable.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($crea3_ilp_transient_prefix) . '%'
        )
    );
}
