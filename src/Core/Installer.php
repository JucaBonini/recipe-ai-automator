<?php
namespace RecipeAI\Core;

class Installer {
    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rai_queue';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            post_id bigint(20) DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            processed_at datetime,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
