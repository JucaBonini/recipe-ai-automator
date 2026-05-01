<?php
namespace GeneratorAINow\Core;

class Logger {
    public static function log($message, $level = 'error', $context = 'system') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_logs';

        $wpdb->insert($table_name, [
            'level'   => $level,
            'message' => $message,
            'context' => $context
        ]);

        // Auto-limpeza: Mantém apenas os últimos 200 logs
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 200) {
            $wpdb->query("DELETE FROM $table_name ORDER BY id ASC LIMIT " . ($count - 200));
        }
    }

    public static function get_logs($limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_logs';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY id DESC LIMIT %d", $limit));
    }

    public static function clear_all() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . "gan_logs");
    }
}
