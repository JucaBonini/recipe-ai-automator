<?php
namespace GeneratorAINow\Core;

class ImageGenerator {
    private $api_key;

    public function __construct() {
        $this->api_key = get_option('gan_api_openai');
    }

    public function generate_and_attach($post_id, $title, $niche = 'recipe') {
        if (empty($this->api_key)) return false;

        $image_url = $this->call_dalle_api($title, $niche);
        
        if ($image_url) {
            return $this->download_and_set_featured($post_id, $image_url, $title);
        }

        return false;
    }

    private function call_dalle_api($title, $niche) {
        $clean_title = str_ireplace(['morte', 'sangue', 'diabo', 'inferno', 'matar'], '', $title);
        
        // Construtor de Prompt por Nicho
        $style_suffix = "High-end professional photography, 8k resolution, cinematic lighting, sharp focus.";
        
        if ($niche === 'recipe') {
            $prompt = "Ultra-realistic food photography of $clean_title. Rustic table setting, soft natural side lighting from a window, steam rising, macro shot focusing on textures. Captured on a Sony A7R IV, 85mm f/1.8 lens. High-end culinary magazine style.";
        } elseif ($niche === 'news') {
            $prompt = "Journalistic photojournalism style image representing: $clean_title. Realistic environment, natural lighting, documentary aesthetic, high detail, taken with a 35mm lens. No text, no logos.";
        } elseif ($niche === 'review') {
            $prompt = "Product photography of $clean_title. Minimalist studio background, clean professional lighting, unboxing aesthetic, focus on industrial design and materials. Sharp focus, macro details.";
        } else {
            $prompt = "Professional high-quality image of $clean_title. $style_suffix";
        }

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode([
                'model'   => 'dall-e-3',
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => '1024x1024', // Quadrada costuma ser mais realista e rápida
                'quality' => 'hd',
                'style'   => 'natural' // 'natural' é menos 'IA' que 'vivid'
            ]),
            'timeout' => 120 
        ]);

        if (is_wp_error($response)) return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return $body['data'][0]['url'] ?? false;
    }

    private function download_and_set_featured($post_id, $url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_sideload_image($url, $post_id, $title, 'id');

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }
        return false;
    }
}
