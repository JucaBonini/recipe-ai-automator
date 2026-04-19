<?php
namespace RecipeAI\Admin;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_menu() {
        // Menu Pai - Aponta para a Fila, mas o nome do menu lateral será apenas "Recipe AI 🤖"
        add_menu_page(
            'Recipe AI',
            'Recipe AI 🤖',
            'manage_options',
            'recipe-ai-main',
            '', // Deixe vazio aqui para não duplicar, o submenu cuidará do desenho
            'dashicons-superhero',
            25
        );

        // Submenu 1 - Forçamos o mesmo slug do pai para ele "sumir" ou ser o padrão sem duplicar o título
        add_submenu_page(
            'recipe-ai-main',
            'Fila de Produção',
            'Fila de Produção 🛰️',
            'manage_options',
            'recipe-ai-main',
            [new \RecipeAI\Admin\Keywords(), 'render_page']
        );

        // Submenu 2 - Configurações
        add_submenu_page(
            'recipe-ai-main',
            'Configurações',
            'Configurações ⚙️',
            'manage_options',
            'recipe-ai-settings',
            [$this, 'render_page']
        );
    }

    public function register_settings() {
        register_setting('rai_settings_group', 'rai_api_openai', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('rai_settings_group', 'rai_api_gemini', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('rai_settings_group', 'rai_api_claude', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('rai_settings_group', 'rai_posts_per_day', ['sanitize_callback' => 'intval']);
        register_setting('rai_settings_group', 'rai_posting_hours', ['sanitize_callback' => [$this, 'sanitize_hours']]);
        register_setting('rai_settings_group', 'rai_post_status', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('rai_settings_group', 'rai_target_language', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function sanitize_hours($hours) {
        if (!is_array($hours)) return [];
        return array_map('sanitize_text_field', $hours);
    }

    public function render_page() {
        $posts_per_day = (int) get_option('rai_posts_per_day', 1);
        $hours = get_option('rai_posting_hours', []);
        $target_lang = get_option('rai_target_language', 'pt_BR');
        ?>
        <div class="wrap" style="max-width: 900px; margin-top: 30px;">
            <style>
                .rai-card { background: #fff; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); overflow: hidden; border:none; }
                .rai-header { background: linear-gradient(135deg, #1d2327 0%, #333 100%); color: #fff; padding: 40px; }
                .rai-header h1 { color: #fff; margin: 0; font-size: 32px; font-weight: 800; display:flex; align-items:center; gap:15px; }
                .rai-tabs { display:flex; background:#f0f0f1; padding:0 20px; border-bottom:1px solid #ddd; }
                .rai-tab { padding:15px 25px; cursor:pointer; font-weight:600; color:#666; border-bottom:3px solid transparent; transition:0.3s; }
                .rai-tab.active { color:#2271b1; border-color:#2271b1; }
                .rai-form { padding:40px; }
                .rai-input { width:100%; padding:12px; border-radius:8px; border:1px solid #ddd; margin-top:5px; margin-bottom:15px; }
                .rai-save-btn { background:#2271b1; color:#fff; border:none; padding:15px 40px; border-radius:30px; font-weight:700; cursor:pointer; transition:0.3s; }
                .rai-save-btn:hover { background:#135e96; transform:translateY(-2px); box-shadow:0 10px 20px rgba(34,113,177,0.2); }
                .rai-field-label { font-weight:700; color:#1d2327; display:block; margin-bottom:5px; }
                .rai-hour-tag { background:#f0f0f1; padding:10px 15px; border-radius:10px; display:inline-block; margin-right:10px; margin-bottom:10px; border:1px solid #ddd; }
            </style>

            <div class="rai-card">
                <div class="rai-header">
                    <h1><?php _e('Recipe AI Automator', 'recipe-ai-automator'); ?> <span style="font-size:12px; background:rgba(255,255,255,0.2); padding:4px 10px; border-radius:20px; font-weight:normal;">v1.2.0</span></h1>
                    <p style="opacity:0.7; margin-top:10px;"><?php _e('Global Content Engine & Smart Scheduling.', 'recipe-ai-automator'); ?></p>
                </div>

                <div class="rai-tabs">
                    <div class="rai-tab active" data-tab="api"><?php _e('API Keys', 'recipe-ai-automator'); ?></div>
                    <div class="rai-tab" data-tab="content"><?php _e('Conteúdo', 'recipe-ai-automator'); ?></div>
                    <div class="rai-tab" data-tab="automation"><?php _e('Agendamento', 'recipe-ai-automator'); ?></div>
                </div>

                <form method="post" action="options.php" class="rai-form">
                    <?php settings_fields('rai_settings_group'); ?>
                    
                    <div id="tab-api" class="rai-tab-content">
                        <label class="rai-field-label"><?php _e('OpenAI API Key (ChatGPT/DALL-E)', 'recipe-ai-automator'); ?></label>
                        <input type="password" name="rai_api_openai" class="rai-input" value="<?php echo esc_attr(get_option('rai_api_openai')); ?>" placeholder="sk-...">
                        
                        <label class="rai-field-label"><?php _e('Google Gemini API Key', 'recipe-ai-automator'); ?></label>
                        <input type="password" name="rai_api_gemini" class="rai-input" value="<?php echo esc_attr(get_option('rai_api_gemini')); ?>">
                    </div>

                    <div id="tab-content" class="rai-tab-content" style="display:none;">
                        <label class="rai-field-label"><?php _e('Idioma das Receitas', 'recipe-ai-automator'); ?></label>
                        <select name="rai_target_language" class="rai-input">
                            <option value="pt_BR" <?php selected($target_lang, 'pt_BR'); ?>>Português (Brasil)</option>
                            <option value="en_US" <?php selected($target_lang, 'en_US'); ?>>English (USA)</option>
                            <option value="es_ES" <?php selected($target_lang, 'es_ES'); ?>>Español (España)</option>
                        </select>
                        <p class="description"><?php _e('A IA gerará todo o conteúdo (título, instruções, metas) neste idioma.', 'recipe-ai-automator'); ?></p>
                    </div>

                    <div id="tab-automation" class="rai-tab-content" style="display:none;">
                        <label class="rai-field-label">Frequência de Postagem (Posts/Dia)</label>
                        <input type="number" name="rai_posts_per_day" id="rai_posts_count" class="rai-input" style="width:100px;" value="<?php echo $posts_per_day; ?>" min="1" max="24">

                        <div id="rai_hours_container" style="margin-top:20px; padding:20px; background:#f9f9f9; border-radius:10px;">
                            <label class="rai-field-label">Definir Horários Exatos:</label>
                            <div id="rai_hour_fields">
                                <?php for ($i = 0; $i < $posts_per_day; $i++) : ?>
                                    <div class="rai-hour-tag">
                                        Post <?php echo $i + 1; ?>: 
                                        <input type="time" name="rai_posting_hours[<?php echo $i; ?>]" value="<?php echo esc_attr($hours[$i] ?? '12:00'); ?>">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <label class="rai-field-label" style="margin-top:30px;">Status das Novas Receitas</label>
                        <select name="rai_post_status" class="rai-input">
                            <option value="draft" <?php selected(get_option('rai_post_status'), 'draft'); ?>>Rascunho (Análise Manual)</option>
                            <option value="publish" <?php selected(get_option('rai_post_status'), 'publish'); ?>>Publicar Direto (Modo Turbo)</option>
                        </select>
                    </div>

                    <div style="margin-top:30px;">
                        <?php submit_button(__('Salvar Configurações de Elite', 'recipe-ai-automator'), 'rai-save-btn'); ?>
                    </div>
                </form>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('.rai-tab').on('click', function() {
                    $('.rai-tab').removeClass('active');
                    $(this).addClass('active');
                    $('.rai-tab-content').hide();
                    $('#tab-' + $(this).data('tab')).show();
                });

                $('#rai_posts_count').on('input change', function() {
                    const count = parseInt($(this).val());
                    const container = $('#rai_hour_fields');
                    let html = '';
                    for (let i = 0; i < count; i++) {
                        html += `<div class="rai-hour-tag">Post ${i+1}: <input type="time" name="rai_posting_hours[${i}]" value="12:00"></div>`;
                    }
                    container.html(html);
                });
            });
            </script>
        </div>
        <?php
    }
}
