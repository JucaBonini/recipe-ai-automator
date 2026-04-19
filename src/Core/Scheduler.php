<?php
namespace RecipeAI\Core;

class Scheduler {
    public function __construct() {
        add_action('rai_cron_job', [$this, 'process_queue_by_schedule']);
    }

    public static function activate() {
        if (!wp_next_scheduled('rai_cron_job')) {
            wp_schedule_event(time(), 'hourly', 'rai_cron_job');
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('rai_cron_job');
    }

    public function process_queue_by_schedule() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rai_queue';
        
        $scheduled_hours = get_option('rai_posting_hours', []);
        $current_hour = current_time('H:i'); // Pega hora:minuto atual do WordPress

        // Verifica se a hora atual (apenas a hora) coincide com algum agendamento
        $now_h = current_time('H');
        $should_post = false;

        foreach ($scheduled_hours as $h) {
            $h_parts = explode(':', $h);
            if ($h_parts[0] == $now_h) {
                $should_post = true;
                break;
            }
        }

        if (!$should_post) return;

        // Verifica se já postamos NESTA HORA específica hoje (preventivo)
        $today_hour_start = current_time('Y-m-d') . " $now_h:00:00";
        $already_done = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = 'completed' AND processed_at >= %s", $today_hour_start));

        if ($already_done > 0) return;

        // Pega o item do TOPO da fila (menor sort_order)
        $item = $wpdb->get_row("SELECT * FROM $table_name WHERE status = 'pending' ORDER BY sort_order ASC LIMIT 1");

        if ($item) {
            $wpdb->update($table_name, ['status' => 'processing'], ['id' => $item->id]);

            $generator = new Generator();
            $result = $generator->generate_recipe($item->keyword);

            if (!is_wp_error($result)) {
                $manager = new PostManager();
                $post_id = $manager->create_recipe_post($result);

                if ($post_id) {
                    $wpdb->update($table_name, [
                        'status' => 'completed',
                        'post_id' => $post_id,
                        'processed_at' => current_time('mysql')
                    ], ['id' => $item->id]);
                } else {
                    $wpdb->update($table_name, ['status' => 'error'], ['id' => $item->id]);
                }
            } else {
                $wpdb->update($table_name, ['status' => 'error'], ['id' => $item->id]);
            }
        }
    }
}
