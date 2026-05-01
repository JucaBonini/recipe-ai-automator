<?php
namespace GeneratorAINow\Core;

class PostManager {
    public function create_post($json_data, $niche = 'recipe', $category_ids = '', $post_type = 'post') {
        if (!$json_data || !isset($json_data['content_parts'])) return false;

        $title = $json_data['titles'][0] ?? 'Sem Título';
        $content = $this->format_content_by_niche($json_data, $niche);
        
        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => get_option('gan_post_status', 'draft'),
            'post_type'    => $post_type,
            'post_author'  => get_current_user_id()
        ];

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Inteligência Zeus: Detecta a taxonomia correta para cada ID de categoria
            if (!empty($category_ids)) {
                $cat_array = array_map('intval', explode(',', $category_ids));
                foreach ($cat_array as $cat_id) {
                    $term = get_term($cat_id);
                    if ($term && !is_wp_error($term)) {
                        wp_set_object_terms($post_id, $cat_id, $term->taxonomy, true);
                    }
                }
            }

            $img_gen = new ImageGenerator();
            $img_gen->generate_and_attach($post_id, $title, $niche);


            if ($niche === 'recipe') {
                $this->save_recipe_meta($post_id, $json_data);
            } else {
                $this->save_generic_meta($post_id, $json_data, $niche);
            }

            $this->save_seo_data($post_id, $json_data);
            return $post_id;
        }
        return false;
    }

    private function format_content_by_niche($data, $niche) {
        $parts = $data['content_parts'];
        $html = "<p>{$parts['intro']}</p>\n\n";

        if ($niche === 'recipe') {
            $html .= "<div class='gan-snippet-box' style='background:#f9f9f9; padding:20px; border-radius:10px; border-left:5px solid #ec5b13;'><strong>Destaque:</strong><p>{$parts['snippet']}</p></div>\n\n";
        } elseif ($niche === 'news') {
            $html .= "<blockquote>{$parts['snippet']}</blockquote>\n\n";
            $html .= $parts['full_article'] ?? '';
        } elseif ($niche === 'review') {
            $html .= "<h3>Análise Prós & Contras</h3><div style='display:grid;grid-template-columns:1fr 1fr;gap:20px;'>";
            $html .= "<div style='color:green;'><strong>Prós:</strong><ul>";
            foreach (($parts['pros'] ?? []) as $p) $html .= "<li>$p</li>";
            $html .= "</ul></div><div style='color:red;'><strong>Contras:</strong><ul>";
            foreach (($parts['cons'] ?? []) as $c) $html .= "<li>$c</li>";
            $html .= "</ul></div></div>\n\n";
            $html .= $parts['full_review'] ?? '';
        }

        if (!empty($parts['faq'])) {
            $html .= "<h2>Perguntas Frequentes</h2>";
            foreach ($parts['faq'] as $f) {
                $html .= "<div><strong>" . esc_html($f['q']) . "</strong><p>" . esc_html($f['a']) . "</p></div>";
            }
        }

        return $html;
    }

    private function save_recipe_meta($post_id, $data) {
        $info = $data['quick_info'];
        $parts = $data['content_parts'];
        update_post_meta($post_id, '_tempo_preparo', (int) ($info['prep_time'] ?? 0));
        update_post_meta($post_id, '_porcoes', $info['yield'] ?? '');
        update_post_meta($post_id, '_ingredientes', [implode("\n", $parts['ingredients'] ?? [])]);
        update_post_meta($post_id, '_instrucoes', $parts['instructions'] ?? []);
    }

    private function save_generic_meta($post_id, $data, $niche) {
        $info = $data['quick_info'];
        if ($niche === 'news') {
            update_post_meta($post_id, '_gan_source', $info['source'] ?? '');
            update_post_meta($post_id, '_gan_location', $info['location'] ?? '');
        } elseif ($niche === 'review') {
            update_post_meta($post_id, '_gan_rating', $info['rating'] ?? '');
            update_post_meta($post_id, '_gan_verdict', $info['verdict'] ?? '');
        }
    }

    private function save_seo_data($post_id, $data) {
        $title = $data['titles'][0] ?? '';
        $desc = $data['meta_description'] ?? '';
        update_post_meta($post_id, '_sts_seo_title', $title);
        update_post_meta($post_id, '_sts_seo_desc', $desc);
    }
}
