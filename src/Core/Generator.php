<?php
namespace GeneratorAINow\Core;

class Generator {
    private $api_key_gemini;
    private $api_key_openai;
    private $api_key_claude;

    public function __construct() {
        $this->api_key_gemini = get_option('gan_api_gemini');
        $this->api_key_openai = get_option('gan_api_openai');
        $this->api_key_claude = get_option('gan_api_claude');
    }

    public function generate_content($keyword, $niche = 'recipe') {
        $prompt = $this->get_system_prompt($keyword, $niche);

        if (!empty($this->api_key_openai)) {
            return $this->call_openai($prompt);
        }

        if (!empty($this->api_key_gemini)) {
            return $this->call_gemini($prompt);
        }

        return new \WP_Error('no_api_key', 'Nenhuma chave de API configurada.');
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

        if (is_wp_error($response)) {
            Logger::log('OpenAI HTTP Error: ' . $response->get_error_message(), 'error', 'OpenAI');
            return $response;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $this->clean_and_decode($data['choices'][0]['message']['content']);
        }

        $error_msg = $data['error']['message'] ?? 'Erro desconhecido na OpenAI.';
        Logger::log('OpenAI API Error: ' . $error_msg, 'error', 'OpenAI');
        return new \WP_Error('openai_error', $error_msg);
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

        if (is_wp_error($response)) {
            Logger::log('Gemini HTTP Error: ' . $response->get_error_message(), 'error', 'Gemini');
            return $response;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->clean_and_decode($data['candidates'][0]['content']['parts'][0]['text']);
        }

        Logger::log('Gemini API Error: Falha no retorno de dados.', 'error', 'Gemini');
        return new \WP_Error('gemini_error', 'Falha no Gemini.');
    }

    private function clean_and_decode($text) {
        $text = preg_replace('/^```json|```$/m', '', $text);
        $text = trim($text);
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        return new \WP_Error('json_error', 'Falha ao decodificar JSON.');
    }

    private function get_system_prompt($keyword, $niche) {
        $lang_code = get_option('gan_target_language', 'pt_BR');
        $languages = ['pt_BR' => 'Português (Brasil)', 'en_US' => 'English (USA)', 'es_ES' => 'Español (España)'];
        $target_lang = $languages[$lang_code] ?? 'Português (Brasil)';

        $base_prompt = "Você é um Especialista em SEO Generativo (GEO) e AEO. Idioma: [$target_lang]. Alvo: [$keyword].\n";

        if ($niche === 'recipe') {
            return $base_prompt . "Nicho: Culinária/Receitas. Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], \"main_keyword\": \"$keyword\", \"quick_info\": {\"prep_time\":\"minutos\",\"cook_time\":\"minutos\",\"yield\":\"x porções\",\"difficulty\":\"Fácil/Médio/Difícil\",\"cuisine\":\"Brasileira\"},
                \"content_parts\": {\"intro\":\"\",\"snippet\":\"\",\"ingredients\":[],\"instructions\":[],\"faq\":[{\"q\":\"\",\"a\":\"\"}],\"meta_description\":\"\"}
            }";
        }

        if ($niche === 'news') {
            return $base_prompt . "Nicho: Notícias/Artigos. Tom: Jornalístico, Informativo. Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], \"quick_info\": {\"source\":\"Fonte citada\",\"date\":\"" . date('d/m/Y') . "\",\"location\":\"Localização\"},
                \"content_parts\": {\"intro\":\"Lide jornalístico\",\"snippet\":\"Resumo executivo\",\"full_article\":\"Artigo completo com H2 e H3 (use markdown)\",\"faq\":[{\"q\":\"\",\"a\":\"\"}],\"meta_description\":\"\"}
            }";
        }

        if ($niche === 'review') {
            return $base_prompt . "Nicho: Reviews/Análises. Tom: Crítico, Imparcial. Estrutura JSON:\n {
                \"titles\": [\"3 opções\"], \"quick_info\": {\"rating\":\"1-5\",\"verdict\":\"Recomendado ou Não\",\"price_range\":\"$\"},
                \"content_parts\": {\"intro\":\"\",\"pros\":[],\"cons\":[],\"full_review\":\"Análise detalhada\",\"faq\":[{\"q\":\"\",\"a\":\"\"}],\"meta_description\":\"\"}
            }";
        }

        return $base_prompt;
    }
}
