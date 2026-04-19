<?php
namespace RecipeAI\Core;

class ImageGenerator {
    private $api_key;

    public function __construct() {
        $this->api_key = get_option('rai_api_openai');
    }

    /**
     * Motor Principal de Geração Visual
     */
    public function generate_and_attach($post_id, $recipe_title) {
        if (empty($this->api_key)) return false;

        $image_url = $this->call_dalle_api($recipe_title);
        
        if ($image_url) {
            return $this->download_and_set_featured($post_id, $image_url, $recipe_title);
        }

        return false;
    }

    private function call_dalle_api($title) {
        // Limpa termos que podem causar falsos positivos no filtro da OpenAI
        $clean_title = str_ireplace(['morte', 'sangue', 'diabo', 'inferno', 'matar'], '', $title);
        
        $prompt = "Ultra-realistic professional food photography of $clean_title. Modern gourmet presentation, vibrant colors, soft natural side lighting. Cinematic composition, macro shot focusing on textures. Captured on a Sony A7R IV, 85mm lens, f/1.8. The background is a sophisticated, slightly blurred kitchen environment. 16:9 aspect ratio, high-end culinary magazine style.";

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode([
                'model'   => 'dall-e-3',
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1792x1024',
                'quality' => 'hd',
                'style'   => 'vivid' // Garante cores mais atraentes para o Google Discover
            ]),
            'timeout' => 120 
        ]);

        if (is_wp_error($response)) {
            error_log('RAI Image Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            error_log('RAI OpenAI API Error: ' . $body['error']['message']);
            return false;
        }

        return $body['data'][0]['url'] ?? false;
    }

    private function download_and_set_featured($post_id, $url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Sideload faz o download e cria o anexo
        $attachment_id = media_sideload_image($url, $post_id, $title, 'id');

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }

        return false;
    }
}
