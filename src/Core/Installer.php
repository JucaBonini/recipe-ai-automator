<?php
namespace GeneratorAINow\Core;

class Installer {
    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_queue';
        $profiles_table = $wpdb->prefix . 'gan_profiles';
        $logs_table = $wpdb->prefix . 'gan_logs';
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de Fila
        $sql_queue = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            profile_id bigint(20) DEFAULT 0,
            post_type varchar(50) DEFAULT 'post' NOT NULL,
            niche varchar(50) DEFAULT 'recipe' NOT NULL,
            category_ids varchar(255) DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            post_id bigint(20) DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            processed_at datetime,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabela de Perfis
        $sql_profiles = "CREATE TABLE $profiles_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            niche varchar(50) DEFAULT 'recipe' NOT NULL,
            post_type varchar(50) DEFAULT 'post' NOT NULL,
            taxonomy varchar(50) DEFAULT 'category' NOT NULL,
            meta_mapping text,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabela de Logs (Nova!)
        $sql_logs = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) DEFAULT 'error' NOT NULL,
            message text NOT NULL,
            context varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_queue);
        dbDelta($sql_profiles);
        dbDelta($sql_logs);
    }
}
