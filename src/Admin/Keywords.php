<?php
namespace GeneratorAINow\Admin;

class Keywords {
    public function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gan_queue';

        \GeneratorAINow\Core\Installer::install();

        if (isset($_POST['gan_add_keywords']) && check_admin_referer('gan_add_kw_action')) {
            $raw_kw = explode("\n", sanitize_textarea_field($_POST['gan_keywords_list']));
            $niche = sanitize_text_field($_POST['gan_target_niche'] ?? 'recipe');
            $post_type = sanitize_text_field($_POST['gan_target_cpt'] ?? 'post');
            $cats = isset($_POST['gan_target_cats']) ? implode(',', array_map('intval', $_POST['gan_target_cats'])) : '';
            $max_order = (int) $wpdb->get_var("SELECT MAX(sort_order) FROM $table_name");

            foreach ($raw_kw as $kw) {
                $kw = sanitize_text_field(trim($kw));
                if (!empty($kw)) {
                    $max_order++;
                    $wpdb->insert($table_name, [
                        'keyword'      => $kw,
                        'post_type'    => $post_type,
                        'niche'        => $niche,
                        'category_ids' => $cats,
                        'status'       => 'pending',
                        'sort_order'   => $max_order
                    ]);
                }
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Lote alocado com sucesso!', 'generator-ai-now') . '</p></div>';
        }

        $queue = $wpdb->get_results("SELECT * FROM $table_name ORDER BY status DESC, sort_order ASC LIMIT 100");
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <div class="wrap gan-wrap" style="max-width: 1100px; margin-top:30px;">
            <div style="background:#fff; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                <h1 style="font-weight:800; margin-bottom:30px;">🛰️ <?php esc_html_e('Dashboard de Produção AI', 'generator-ai-now'); ?></h1>

                <form method="post" style="background:#f9f9f9; padding:25px; border-radius:12px; border:1px solid #eee; margin-bottom:30px;">
                    <?php wp_nonce_field('gan_add_kw_action'); ?>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px; margin-bottom:20px;">
                        <!-- Post Type -->
                        <div>
                            <label style="font-weight:700; display:block; margin-bottom:8px; color:#2271b1;">🏛️ <?php esc_html_e('Destino (CPT):', 'generator-ai-now'); ?></label>
                            <select name="gan_target_cpt" id="gan_target_cpt" style="width:100%; height:45px; border-radius:8px; border:1px solid #ddd;">
                                <?php foreach ($post_types as $pt) : ?>
                                    <option value="<?php echo $pt->name; ?>"><?php echo esc_html($pt->label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nicho -->
                        <div>
                            <label style="font-weight:700; display:block; margin-bottom:8px; color:#ec5b13;">🎯 <?php esc_html_e('Nicho:', 'generator-ai-now'); ?></label>
                            <select name="gan_target_niche" style="width:100%; height:45px; border-radius:8px; border:1px solid #ddd;">
                                <option value="recipe">🥘 Receitas</option>
                                <option value="news">📰 Notícias</option>
                                <option value="review">⭐ Reviews</option>
                            </select>
                        </div>
                        
                        <!-- Categorias -->
                        <div style="position:relative;">
                            <label style="font-weight:700; display:block; margin-bottom:8px; color:#2271b1;">📁 <?php esc_html_e('Categorias:', 'generator-ai-now'); ?></label>
                            <details style="position:relative; width:100%;">
                                <summary style="height:45px; display:flex; align-items:center; padding:0 15px; background:#fff; border:1px solid #ddd; border-radius:8px; cursor:pointer; list-style:none;">
                                    <?php esc_html_e('Selecionar Categorias...', 'generator-ai-now'); ?>
                                    <span style="margin-left:auto;">▼</span>
                                </summary>
                                <div id="gan_category_container" class="gan-cat-grid" style="position:absolute; top:50px; left:0; width:100%; z-index:999; background:#fff; box-shadow:0 10px 20px rgba(0,0,0,0.1); border:1px solid #ddd;">
                                    <!-- Carregado via AJAX -->
                                </div>
                            </details>
                        </div>
                    </div>

                    <label style="font-weight:700; display:block; margin-bottom:10px; color:#1d2327;">🚀 <?php esc_html_e('Palavras-Chave:', 'generator-ai-now'); ?></label>
                    <textarea name="gan_keywords_list" style="width:100%; height:100px; border-radius:10px; border:1px solid #ddd; padding:15px; font-size:14px;"></textarea>
                    
                    <button type="submit" name="gan_add_keywords" class="button button-primary" style="margin-top:20px; height:50px; padding:0 40px; border-radius:30px; font-weight:700; background:#ec5b13; border:none;"><?php esc_html_e('Alocar na Fila', 'generator-ai-now'); ?></button>
                </form>

                <table class="wp-list-table widefat fixed striped" style="border:none;">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Palavra-Chave</th>
                            <th style="width:220px;">Destino / Nicho / Cat</th>
                            <th style="width:100px;">Status</th>
                            <th style="width:150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue as $item) : ?>
                        <tr id="row-<?php echo esc_attr($item->id); ?>">
                            <td><?php echo esc_html($item->sort_order); ?></td>
                            <td><strong><?php echo esc_html($item->keyword); ?></strong></td>
                            <td>
                                <div style="font-weight:700; color:#2271b1; font-size:11px;">🏛️ <?php echo esc_html($item->post_type); ?></div>
                                <div style="font-size:10px; color:#666; margin-bottom:5px;">✨ <?php echo esc_html(ucfirst($item->niche)); ?></div>
                                <div style="display:flex; flex-wrap:wrap; gap:3px;">
                                    <?php 
                                    if (!empty($item->category_ids)) {
                                        $ids = explode(',', $item->category_ids);
                                        foreach ($ids as $id) {
                                            $term = get_term($id);
                                            if ($term && !is_wp_error($term)) {
                                                echo '<span style="background:#eef4fa; color:#2271b1; padding:2px 6px; border-radius:4px; font-size:9px; font-weight:600; border:1px solid #d1e1ee;">' . esc_html($term->name) . '</span>';
                                            }
                                        }
                                    } else {
                                        echo '<span style="color:#999; font-size:9px;">Sem Categoria</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span style="padding:4px 10px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase;
                                <?php
                                    if ($item->status === 'pending') echo 'background:#fff7e6; color:#fa8c16;';
                                    elseif ($item->status === 'completed') echo 'background:#f6ffed; color:#52c41a;';
                                    else echo 'background:#fff1f0; color:#f5222d;';
                                ?>">
                                    <?php echo esc_html($item->status); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <?php if ($item->status === 'pending') : ?>
                                        <button class="button gan-run-now" data-id="<?php echo esc_attr($item->id); ?>">Gerar</button>
                                        <button class="button button-link-delete gan-delete" data-id="<?php echo esc_attr($item->id); ?>" style="color:#d63638;">X</button>
                                    <?php endif; ?>
                                    <?php if ($item->post_id) : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>" class="button" target="_blank">Ver</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
