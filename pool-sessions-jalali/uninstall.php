<?php
/**
 * Uninstall script for Pool Sessions Jalali
 * 
 * This file is executed when the plugin is deleted from WordPress
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('pool_sessions_jalali_options');

// Delete all pool session posts
$args = array(
    'post_type' => 'pool_session',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids'
);

$sessions = get_posts($args);

foreach ($sessions as $session_id) {
    wp_delete_post($session_id, true);
}

// Clear any transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pool_sessions_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pool_sessions_%'");

// Flush rewrite rules
flush_rewrite_rules();
