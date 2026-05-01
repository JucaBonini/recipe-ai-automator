<?php
namespace GeneratorAINow\Admin;

class Profiles {
    public function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_profiles';

        // Processar salvamento
        if (isset($_POST['gan_save_profile']) && check_admin_referer('gan_save_profile_action')) {
            $name = sanitize_text_field($_POST['profile_name']);
            $niche = sanitize_text_field($_POST['profile_niche']);
            $cpt = sanitize_text_field($_POST['profile_cpt']);
            $tax = sanitize_text_field($_POST['profile_tax']);

            $wpdb->insert($table_name, [
                'name'      => $name,
                'niche'     => $niche,
                'post_type' => $cpt,
                'taxonomy'  => $tax
            ]);
            echo '<div class="notice notice-success is-dismissible"><p>Perfil mapeado com sucesso!</p></div>';
        }

        // Processar exclusão
        if (isset($_GET['delete_profile'])) {
            $wpdb->delete($table_name, ['id' => (int) $_GET['delete_profile']]);
        }

        $profiles = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <div class="wrap gan-wrap" style="max-width: 1000px; margin-top:30px;">
            <div style="background:#fff; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                <h1 style="font-weight:800; margin-bottom:30px;">🎭 <?php esc_html_e('Mapeador Automático de CPTs', 'generator-ai-now'); ?></h1>

                <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:40px;">
                    <!-- Lista de Perfis -->
                    <div>
                        <h3 style="margin-top:0;"><?php esc_html_e('Configurações de Destino Salvas', 'generator-ai-now'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Nome do Perfil</th>
                                    <th>Destino (CPT)</th>
                                    <th>Taxonomia</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($profiles)) : ?>
                                    <tr><td colspan="4">Nenhum mapeamento realizado.</td></tr>
                                <?php else: foreach ($profiles as $p) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($p->name); ?></strong></td>
                                        <td><code style="background:#eee;"><?php echo esc_html($p->post_type); ?></code></td>
                                        <td><code><?php echo esc_html($p->taxonomy); ?></code></td>
                                        <td>
                                            <a href="?page=gan-profiles&delete_profile=<?php echo $p->id; ?>" style="color:#d63638;" onclick="return confirm('Remover este mapeamento?')">Remover</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Formulário de Descoberta -->
                    <div style="background:#f0f6fb; padding:25px; border-radius:12px; border:1px solid #d1e1ee;">
                        <h3 style="margin-top:0; color:#2271b1;"><?php esc_html_e('Mapear Novo Destino', 'generator-ai-now'); ?></h3>
                        <p style="font-size:12px; color:#666;">Selecione um CPT existente no seu site para que o Zeus saiba onde entregar o conteúdo.</p>
                        
                        <form method="post" id="gan-profile-form">
                            <?php wp_nonce_field('gan_save_profile_action'); ?>
                            
                            <label style="display:block; margin-bottom:5px; font-weight:700;">Nome deste Mapeamento:</label>
                            <input type="text" name="profile_name" style="width:100%; margin-bottom:15px;" placeholder="Ex: Meu Blog de Notícias" required>

                            <label style="display:block; margin-bottom:5px; font-weight:700;">Nicho de Escrita AI:</label>
                            <select name="profile_niche" style="width:100%; margin-bottom:15px;">
                                <option value="recipe">🥘 Receita</option>
                                <option value="news">📰 Notícia</option>
                                <option value="review">⭐ Review</option>
                            </select>

                            <label style="display:block; margin-bottom:5px; font-weight:700;">Destinar para qual Post Type?</label>
                            <select name="profile_cpt" id="gan_profile_cpt" style="width:100%; margin-bottom:15px;">
                                <?php foreach ($post_types as $pt) : ?>
                                    <option value="<?php echo $pt->name; ?>"><?php echo esc_html($pt->label); ?> (<?php echo $pt->name; ?>)</option>
                                <?php endforeach; ?>
                            </select>

                            <label style="display:block; margin-bottom:5px; font-weight:700;">Usar qual Taxonomia de Categoria?</label>
                            <select name="profile_tax" id="gan_profile_tax" style="width:100%; margin-bottom:15px;">
                                <option value="category">Categorias Padrão (category)</option>
                                <?php 
                                    // Busca todas as taxonomias públicas do site
                                    $taxonomies = get_taxonomies(['public' => true], 'objects');
                                    foreach($taxonomies as $tax) {
                                        if ($tax->name === 'category') continue;
                                        echo '<option value="'.$tax->name.'">'.$tax->label.' ('.$tax->name.')</option>';
                                    }
                                ?>
                            </select>

                            <button type="submit" name="gan_save_profile" class="button button-primary" style="width:100%; height:45px; border-radius:30px; background:#2271b1; border:none;"><?php esc_html_e('Salvar Mapeamento', 'generator-ai-now'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
