<?php
/**
 * Plugin Name: Generator AI Now
 * Plugin URI:  https://descomplicandoreceitas.com.br
 * Description: High-performance content generator using AI (OpenAI/Gemini). Fully compatible with WordPress 2026 standards.
 * Version:     1.5.0
 * Author:      jucasouza
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: generator-ai-now
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global Constants - Zeus Prefix (GAN)
define('GAN_VERSION', '1.5.0');
define('GAN_PATH', plugin_dir_path(__FILE__));
define('GAN_URL', plugin_dir_url(__FILE__));

// Autoloader Simples
spl_autoload_register(function ($class) {
    $prefix = 'GeneratorAINow\\';
    $base_dir = GAN_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Inicialização
add_action('plugins_loaded', function() {
    new \GeneratorAINow\Admin\Settings();
    new \GeneratorAINow\Admin\Keywords();
    new \GeneratorAINow\Core\Ajax();
});
