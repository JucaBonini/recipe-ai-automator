<?php
/**
 * Fired when the plugin is uninstalled.
 * This file cleans up all plugin-related data from the database.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Remover Tabelas Customizadas
$table_queue = $wpdb->prefix . 'gan_queue';
$table_profiles = $wpdb->prefix . 'gan_profiles';
$table_logs = $wpdb->prefix . 'gan_logs';

$wpdb->query("DROP TABLE IF EXISTS $table_queue");
$wpdb->query("DROP TABLE IF EXISTS $table_profiles");
$wpdb->query("DROP TABLE IF EXISTS $table_logs");

// 2. Remover Opções de Configuração
$options = [
    'gan_api_openai',
    'gan_api_gemini',
    'gan_api_claude',
    'gan_posts_per_day',
    'gan_posting_hours',
    'gan_post_status',
    'gan_target_language',
    'gan_version'
];

foreach ($options as $option) {
    delete_option($option);
}
