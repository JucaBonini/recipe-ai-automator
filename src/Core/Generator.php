<?php
namespace RecipeAI\Core;

class Generator {
    private $api_key_gemini;
    private $api_key_openai;
    private $api_key_claude;

    public function __construct() {
        $this->api_key_gemini = get_option('rai_api_gemini');
        $this->api_key_openai = get_option('rai_api_openai');
        $this->api_key_claude = get_option('rai_api_claude');
    }

    public function generate_recipe($keyword) {
        $prompt = $this->get_system_prompt($keyword);

        if (!empty($this->api_key_openai)) {
            return $this->call_openai($prompt);
        }

        if (!empty($this->api_key_gemini)) {
            return $this->call_gemini($prompt);
        }

        return new \WP_Error('no_api_key', 'Nenhuma chave de API configurada (OpenAI ou Gemini).');
    }

    private function call_openai($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $body = [
            'model' => 'gpt-4o',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object']
        ];

        $response = wp_remote_post($url, [
            'body'    => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key_openai
            ],
            'timeout' => 90
        ]);

        if (is_wp_error($response)) return $response;
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $this->clean_and_decode($data['choices'][0]['message']['content']);
        }

        return new \WP_Error('openai_error', $data['error']['message'] ?? 'Erro na OpenAI.');
    }

    private function call_gemini($prompt) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $this->api_key_gemini;
        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['response_mime_type' => 'application/json']
        ];

        $response = wp_remote_post($url, [
            'body'    => json_encode($body),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 120
        ]);

        if (is_wp_error($response)) return $response;
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->clean_and_decode($data['candidates'][0]['content']['parts'][0]['text']);
        }
        return new \WP_Error('gemini_error', 'Falha no Gemini.');
    }

    private function clean_and_decode($text) {
        // Remove blocos de código markdown (```json ... ```)
        $text = preg_replace('/^```json|```$/m', '', $text);
        $text = trim($text);
        
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return new \WP_Error('json_error', 'Falha ao decodificar JSON: ' . json_last_error_msg());
    }

    private function get_system_prompt($keyword) {
        $lang_code = get_option('rai_target_language', 'pt_BR');
        $languages = [
            'pt_BR' => 'Português (Brasil)',
            'en_US' => 'English (USA)',
            'es_ES' => 'Español (España)'
        ];
        $target_lang = $languages[$lang_code] ?? 'Português (Brasil)';

        return "Você é um Especialista em SEO Generativo (GEO), Engenheiro de Busca por Voz (AEO) e Chef Sênior para o blog Descomplicando Receitas.
        TODO O CONTEÚDO DEVE SER ESCRITO NO IDIOMA: [$target_lang].
        
        Crie um conteúdo lendário e otimizado para a palavra-chave: [$keyword].
        
        Siga RIGOROSAMENTE as diretrizes de 2026:
        - Introdução Humana: Use ganchos reais, memórias ou problemas comuns. NUNCA use nomes fixos ou padrões repetitivos.
        - GEO Spike: Forneça um dado técnico exclusivo (segredo do chef) sobre temperatura ou química dos alimentos para citação de IA.
        - AEO focus: Frases diretas para busca por voz no Snippet e FAQ.
        - AdSense Strategy: Use ganchos de curiosidade que estimulem o scroll profundo.
        
        Siga RIGOROSAMENTE esta estrutura JSON para entrega:
        {
            \"titles\": [\"3 opções de títulos magnéticos (adicione [TITULO_PRINCIPAL] no melhor)\"],
            \"quick_info\": {
                \"prep_time\": \"Tempo de preparo (ex: 15 min)\",
                \"cook_time\": \"Tempo de cozimento (ex: 45 min)\",
                \"yield\": \"Rendimento (ex: 6 porções)\",
                \"difficulty\": \"Fácil, Médio ou Difícil\"
            },
            \"content_parts\": {
                \"intro\": \"Narrativa humana e envolvente focado em retenção (dwell time).\",
                \"snippet\": \"Snippet de resposta rápida (AEO) para Voz e Posição Zero.\",
                \"engagement_block\": \"Dica de retenção (estimulando o scroll para as variações).\",
                \"utensils\": [\"Lista técnica de utensílios necessários\"],
                \"ingredients\": [\"Ingredientes (um por linha)\"],
                \"instructions\": [\"Passo a passo com micro-dicas técnicas entre parênteses\"],
                \"nutrition_table\": {
                    \"calories\": \"\", \"carbs\": \"\", \"protein\": \"\", \"total_fats\": \"\", 
                    \"saturated_fats\": \"\", \"fiber\": \"\", \"sodium\": \"\"
                },
                \"tips\": \"Dica técnica exclusiva do Chef para ganho de informação (GEO).\",
                \"variations\": [\"3 variações: Fit, Airfryer e Econômica\"],
                \"serving\": \"Sugestão de serviço premium\",
                \"storage\": \"Como armazenar e reaquecer (foco em utilidade)\",
                \"faq\": [{\"q\":\"pergunta de busca por voz\",\"a\":\"resposta curta e direta\"}],
                \"engagement_final\": \"Pergunta para interação nos comentários\",
                \"conclusion_cta\": \"CTA final de compartilhamento\"
            },
            \"meta_description\": \"150 caracteres focados em CTR para o Google Search.\"
        }";
    }
}
