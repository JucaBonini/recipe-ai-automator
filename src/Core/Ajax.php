<?php
namespace RecipeAI\Core;

class Ajax {
    public function __construct() {
        add_action('wp_ajax_rai_process_instant', [$this, 'process_instant_item']);
        add_action('wp_ajax_rai_delete_item', [$this, 'delete_item']);
        add_action('wp_ajax_rai_move_item', [$this, 'move_item']);
    }

    public function delete_item() {
        check_ajax_referer('rai_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'rai_queue', ['id' => (int) $_POST['item_id']]);
        wp_send_json_success();
    }

    public function move_item() {
        check_ajax_referer('rai_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        global $wpdb;
        $id = (int) $_POST['item_id'];
        $direction = $_POST['direction']; // 'up' or 'down'
        $table = $wpdb->prefix . 'rai_queue';

        $current = $wpdb->get_row($wpdb->prepare("SELECT id, sort_order FROM $table WHERE id = %d", $id));
        
        if ($direction === 'up') {
            $other = $wpdb->get_row($wpdb->prepare("SELECT id, sort_order FROM $table WHERE sort_order < %d ORDER BY sort_order DESC LIMIT 1", $current->sort_order));
        } else {
            $other = $wpdb->get_row($wpdb->prepare("SELECT id, sort_order FROM $table WHERE sort_order > %d ORDER BY sort_order ASC LIMIT 1", $current->sort_order));
        }

        if ($other) {
            $wpdb->update($table, ['sort_order' => $other->sort_order], ['id' => $current->id]);
            $wpdb->update($table, ['sort_order' => $current->sort_order], ['id' => $other->id]);
        }
        wp_send_json_success();
    }

    public function process_instant_item() {
        check_ajax_referer('rai_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $item_id = (int) $_POST['item_id'];
        global $wpdb;
        $table_name = $wpdb->prefix . 'rai_queue';
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id));

        if ($item && ($item->status === 'pending' || $item->status === 'error')) {
            $wpdb->update($table_name, ['status' => 'processing'], ['id' => $item_id]);
            $generator = new Generator();
            $result = $generator->generate_recipe($item->keyword);

            if (!is_wp_error($result)) {
                $manager = new PostManager();
                $post_id = $manager->create_recipe_post($result);

                if ($post_id) {
                    $wpdb->update($table_name, ['status' => 'completed', 'post_id' => $post_id, 'processed_at' => current_time('mysql')], ['id' => $item_id]);
                    wp_send_json_success();
                }
            }
            $wpdb->update($table_name, ['status' => 'error'], ['id' => $item_id]);
            wp_send_json_error('Erro na IA ou Mapeamento');
        }
        wp_send_json_error('Item já processado');
    }
}
