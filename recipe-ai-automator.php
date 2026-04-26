<?php
/**
 * Plugin Name: Recipe AI Automator
 * Plugin URI: https://descomplicandoreceitas.com.br
 * Description: Sistema avançado de automação de posts de receitas com Inteligência Artificial (ChatGPT, Gemini, Claude).
 * Version: 1.3.0
 * Author: Juca Souza Bonini
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recipe-ai-automator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global Constants
define('RAI_PATH', plugin_dir_path(__FILE__));
define('RAI_URL', plugin_dir_url(__FILE__));

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'RecipeAI\\') !== 0) return;
    $file = RAI_PATH . 'src/' . str_replace('\\', '/', substr($class, 9)) . '.php';
    if (file_exists($file)) require $file;
});

// Start Plugin
register_activation_hook(__FILE__, ['\RecipeAI\Core\Installer', 'install']);
register_activation_hook(__FILE__, ['\RecipeAI\Core\Scheduler', 'activate']);
register_deactivation_hook(__FILE__, ['\RecipeAI\Core\Scheduler', 'deactivate']);

add_action('plugins_loaded', function() {
    if (is_admin()) {
        new \RecipeAI\Admin\Settings();
        new \RecipeAI\Core\Ajax();
        // Monitor de updates via GitHub
        new \RecipeAI\Core\Updater('recipe-ai-automator', '1.3.0', 'JucaBonini/recipe-ai-automator');
    }
    new \RecipeAI\Core\Scheduler();
});
