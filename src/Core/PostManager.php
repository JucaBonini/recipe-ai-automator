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
            // 1. Categorias Dinâmicas (Mantendo a nova função que você aprovou)
            if (!empty($category_ids)) {
                $cat_array = array_map('intval', explode(',', $category_ids));
                foreach ($cat_array as $cat_id) {
                    $term = get_term($cat_id);
                    if ($term && !is_wp_error($term)) {
                        wp_set_object_terms($post_id, $cat_id, $term->taxonomy, true);
                    }
                }
            }

            // 2. Imagem Destacada (Mantendo o novo motor realista)
            $img_gen = new ImageGenerator();
            $img_gen->generate_and_attach($post_id, $title, $niche);

            // 3. Meta Dados Exclusivos do Projeto (A RECONSTRUÇÃO)
            if ($niche === 'recipe') {
                $this->save_recipe_meta_sts($post_id, $json_data);
            }

            // 4. SEO Engine Pro (RESTAURADO)
            $this->save_seo_data_sts($post_id, $json_data);

            return $post_id;
        }
        return false;
    }

    private function save_recipe_meta_sts($post_id, $data) {
        $info = $data['quick_info'] ?? [];
        $parts = $data['content_parts'] ?? [];

        // Campos Básicos
        update_post_meta($post_id, '_tempo_preparo', (int) ($info['prep_time'] ?? 0));
        update_post_meta($post_id, '_tempo_cozimento', (int) ($info['cook_time'] ?? 0));
        update_post_meta($post_id, '_total_time', (int) ($info['prep_time'] ?? 0) + (int) ($info['cook_time'] ?? 0));
        update_post_meta($post_id, '_porcoes', $info['yield'] ?? '');
        update_post_meta($post_id, '_dificuldade', $info['difficulty'] ?? 'Fácil');
        update_post_meta($post_id, '_recipe_cuisine', $info['cuisine'] ?? 'Brasileira');

        // Ingredientes (Formato de Array para o Tema)
        if (!empty($parts['ingredients'])) {
            $ing_list = implode("\n", $parts['ingredients']);
            update_post_meta($post_id, '_ingredientes_grupo', ['Ingredientes']);
            update_post_meta($post_id, '_ingredientes', [$ing_list]);
        }

        // Modo de Preparo (Array para cada Passo)
        if (!empty($parts['instructions'])) {
            update_post_meta($post_id, '_instrucoes', $parts['instructions']);
        }

        // FAQ (Otimização SEO God Mode - Arrays Paralelos)
        if (!empty($parts['faq'])) {
            $perguntas = [];
            $respostas = [];
            foreach ($parts['faq'] as $f) {
                $perguntas[] = $f['q'];
                $respostas[] = $f['a'];
            }
            update_post_meta($post_id, '_faq_perguntas', $perguntas);
            update_post_meta($post_id, '_faq_respostas', $respostas);
        }
    }

    private function save_seo_data_sts($post_id, $data) {
        $title = $data['titles'][0] ?? get_the_title($post_id);
        $desc = $data['content_parts']['meta_description'] ?? '';
        $kw = $data['main_keyword'] ?? '';

        // Chaves Exatas do SEO Engine Pro (RESTAURADAS)
        update_post_meta($post_id, '_sts_seo_title', $title);
        update_post_meta($post_id, '_sts_seo_desc', $desc);
        update_post_meta($post_id, '_sts_focus_keyword', $kw);
    }

    private function format_content_by_niche($data, $niche) {
        $parts = $data['content_parts'];
        $html = "<p>{$parts['intro']}</p>\n\n";

        if ($niche === 'recipe') {
            $html .= "<div class='gan-snippet-box' style='background:#f9f9f9; padding:20px; border-radius:10px; border-left:5px solid #ec5b13;'><strong>Destaque da Mary:</strong><p>{$parts['snippet']}</p></div>\n\n";
        } else {
            $html .= "<blockquote>{$parts['snippet']}</blockquote>\n\n";
            $html .= $parts['full_article'] ?? $parts['full_review'] ?? '';
        }

        return $html;
    }
}
