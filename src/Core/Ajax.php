<?php
namespace GeneratorAINow\Core;

class Ajax {
    public function __construct() {
        add_action('wp_ajax_gan_process_instant', [$this, 'process_instant_item']);
        add_action('wp_ajax_gan_delete_item', [$this, 'delete_item']);
        add_action('wp_ajax_gan_move_item', [$this, 'move_item']);
        add_action('wp_ajax_gan_get_taxonomies', [$this, 'get_taxonomies_for_cpt']);
    }

    public function get_taxonomies_for_cpt() {
        check_ajax_referer('gan_queue_nonce');
        $post_type = sanitize_text_field($_POST['post_type']);
        
        // Busca todas as taxonomias vinculadas a este CPT
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $output = [];

        foreach ($taxonomies as $tax) {
            if (!$tax->public || !$tax->show_ui) continue;
            
            $terms = get_terms(['taxonomy' => $tax->name, 'hide_empty' => false]);
            foreach ($terms as $term) {
                $output[] = [
                    'id'   => $term->term_id,
                    'name' => $term->name . ' (' . $tax->label . ')',
                    'tax'  => $tax->name
                ];
            }
        }
        wp_send_json_success($output);
    }


    public function delete_item() {
        check_ajax_referer('gan_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'gan_queue', ['id' => (int) $_POST['item_id']]);
        wp_send_json_success();
    }

    public function move_item() {
        check_ajax_referer('gan_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        global $wpdb;
        $id = (int) $_POST['item_id'];
        $direction = $_POST['direction'];
        $table = $wpdb->prefix . 'gan_queue';
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
        check_ajax_referer('gan_queue_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $item_id = (int) $_POST['item_id'];
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_queue';
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id));

        if ($item && ($item->status === 'pending' || $item->status === 'error')) {
            $wpdb->update($table_name, ['status' => 'processing'], ['id' => $item_id]);
            
            $generator = new Generator();
            $result = $generator->generate_content($item->keyword, $item->niche);

            if (!is_wp_error($result)) {
                $manager = new PostManager();
                // Passa o post_type vindo do banco de dados (automático)
                $post_id = $manager->create_post($result, $item->niche, $item->category_ids, $item->post_type);

                if ($post_id) {
                    $wpdb->update($table_name, ['status' => 'completed', 'post_id' => $post_id, 'processed_at' => current_time('mysql')], ['id' => $item_id]);
                    wp_send_json_success();
                }
            }
            $wpdb->update($table_name, ['status' => 'error'], ['id' => $item_id]);
            wp_send_json_error(is_wp_error($result) ? $result->get_error_message() : 'Erro no processamento');
        }
        wp_send_json_error('Item já processado');
    }
}
