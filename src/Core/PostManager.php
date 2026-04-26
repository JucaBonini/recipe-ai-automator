<?php
namespace RecipeAI\Core;

class PostManager {
    public function create_recipe_post($json_data) {
        if (!$json_data || !isset($json_data['content_parts'])) return false;

        $title = $json_data['titles'][0];
        // O conteúdo aqui gera o "corpo" para AdSense e Retenção
        $content = $this->format_content($json_data['content_parts']);

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => get_option('rai_post_status', 'draft'),
            'post_type'    => 'post',
            'post_author'  => get_current_user_id()
        ];

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // 1. Gera e anexa a imagem via DALL-E 3
            $img_gen = new ImageGenerator();
            $img_gen->generate_and_attach($post_id, $title);

            // 2. Salva Metadados REAIS do Tema (Otimizado sts-recipe-2)
            $this->save_meta_data($post_id, $json_data);

            // 3. Salva SEO (Yoast / RankMath)
            $this->save_seo_data($post_id, $json_data);

            return $post_id;
        }

        return false;
    }

    private function format_content($parts) {
        // Mantém a introdução narrativa da Mary para Scroll Depth
        $html = "<!-- Narrativa da Mary -->\n<p>{$parts['intro']}</p>\n\n";
        $html .= "<!-- Snippet Retenção -->\n<div class='rai-snippet-box' style='background:#f9f9f9; padding:20px; border-radius:10px; border-left:5px solid #ec5b13;'><strong>Por que você vai amar esta receita?</strong><p>{$parts['snippet']}</p></div>\n\n";
        $html .= "<p><strong>{$parts['engagement_block']}</strong></p>\n\n";
        
        // Aqui o conteúdo textual serve para reforçar o SEO Semântico
        $html .= "<h2>Dicas de Especialista</h2>\n<p>{$parts['tips']}</p>\n\n";
        
        $html .= "<h2>Variações da Receita</h2>\n<ul>";
        foreach ($parts['variations'] as $v) $html .= "<li>$v</li>";
        $html .= "</ul>\n\n";

        $html .= "\n<p><strong>{$parts['engagement_final']}</strong></p>\n";
        $html .= "<p>{$parts['conclusion_cta']}</p>";

        return $html;
    }

    private function save_meta_data($post_id, $data) {
        $info = $data['quick_info'];
        $parts = $data['content_parts'];
        $nutri = $parts['nutrition_table'];

        // Campos Básicos do Tema sts-recipe-2
        update_post_meta($post_id, '_tempo_preparo', (int) filter_var($info['prep_time'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_tempo_cozimento', (int) filter_var($info['cook_time'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_porcoes', $info['yield']);
        update_post_meta($post_id, '_dificuldade', $info['difficulty']);
        update_post_meta($post_id, '_recipe_cuisine', 'Brasileira');
        update_post_meta($post_id, '_diet_type', $parts['storage'] ?? 'Tradicional');

        // Nutrição Real do Tema (Extraindo apenas números para compatibilidade com type="number")
        update_post_meta($post_id, '_calorias', (int) filter_var($nutri['calories'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_carboidratos', (int) filter_var($nutri['carbs'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_proteinas', (int) filter_var($nutri['protein'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_gorduras', (int) filter_var($nutri['total_fats'], FILTER_SANITIZE_NUMBER_INT));
        update_post_meta($post_id, '_nutri_serving', $data['quick_info']['yield'] ?: '1 porção (aprox.)');
        update_post_meta($post_id, '_nutri_source', 'Cálculo Baseado em IA (Tabelas Médias)');

        // Estrutura de Ingredientes (Array para o Tema)
        update_post_meta($post_id, '_ingredientes_grupo', ['Ingredientes']);
        update_post_meta($post_id, '_ingredientes', [implode("\n", $parts['ingredients'])]);

        // Estrutura de Instruções (Array para o Tema)
        update_post_meta($post_id, '_instrucoes', $parts['instructions']);

        // Dicas da Mary e Utensílios (Formatados para Editor Visual)
        update_post_meta($post_id, '_informacoes_adicionais', wpautop($parts['tips']));
        
        $utensilios_html = "<ul>";
        foreach ($parts['utensils'] as $u) $utensilios_html .= "<li>" . esc_html($u) . "</li>";
        $utensilios_html .= "</ul>";
        update_post_meta($post_id, '_utensilios', $utensilios_html);

        // Calcula Tempo Total automaticamente
        $total = (int) filter_var($info['prep_time'], FILTER_SANITIZE_NUMBER_INT) + (int) filter_var($info['cook_time'], FILTER_SANITIZE_NUMBER_INT);
        update_post_meta($post_id, '_total_time', $total);

        // 🟢 FAQ Automático (Integração God Mode sts-recipe-2)
        $faq_perguntas = [];
        $faq_respostas = [];
        if (!empty($parts['faq'])) {
            foreach ($parts['faq'] as $f) {
                $faq_perguntas[] = sanitize_text_field($f['q']);
                $faq_respostas[] = sanitize_textarea_field($f['a']);
            }
        }
        update_post_meta($post_id, '_faq_perguntas', $faq_perguntas);
        update_post_meta($post_id, '_faq_respostas', $faq_respostas);
    }

    private function save_seo_data($post_id, $data) {
        $title = $data['titles'][0];
        $desc = $data['meta_description'];

        // SEO Engine Pro Integration (sts-seo-engine)
        update_post_meta($post_id, '_sts_focus_keyword', $title);
        update_post_meta($post_id, '_sts_seo_title', $title . ' - ' . get_bloginfo('name'));
        update_post_meta($post_id, '_sts_seo_desc', $desc);
        
        // Compatibilidade Extra (Yoast/RankMath)
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $desc);
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $title);
        update_post_meta($post_id, '_rank_math_description', $desc);
    }
}
