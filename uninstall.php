<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('cma_scan_data');
delete_option('cma_excluded_ids');
delete_option('cma_cluster_threshold');
delete_transient('cma_conflicts_cache');

// Gutenberg suggestion caches use dynamic suffixes and must be removed explicitly.
global $wpdb;

$transient_prefixes = [
    '_transient_cma_editor_',
    '_transient_timeout_cma_editor_',
];

foreach ($transient_prefixes as $transient_prefix) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($transient_prefix) . '%'
        )
    );
}
