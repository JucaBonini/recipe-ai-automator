<?php
namespace RecipeAI\Admin;

class Keywords {
    public function render_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rai_queue';

        // Garante que a tabela está atualizada (Heal on load)
        \RecipeAI\Core\Installer::install();

        // Processa o envio de novas palavras-chave
        if (isset($_POST['rai_add_keywords']) && check_admin_referer('rai_add_kw_action')) {
            $raw_kw = explode("\n", sanitize_textarea_field($_POST['rai_keywords_list']));
            $max_order = (int) $wpdb->get_var("SELECT MAX(sort_order) FROM $table_name");
            
            foreach ($raw_kw as $kw) {
                $kw = sanitize_text_field(trim($kw));
                if (!empty($kw)) {
                    // Verifica se já existe na fila como pendente
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE keyword = %s AND status = 'pending'", $kw));
                    if (!$exists) {
                        $max_order++;
                        $wpdb->insert($table_name, [
                            'keyword' => $kw, 
                            'status' => 'pending', 
                            'sort_order' => $max_order
                        ]);
                    }
                }
            }
            echo '<div class="notice notice-success is-dismissible"><p>Palavras-chave enfileiradas com sucesso!</p></div>';
        }

        $queue = $wpdb->get_results("SELECT * FROM $table_name ORDER BY status DESC, sort_order ASC LIMIT 100");
        ?>
        <div class="wrap" style="max-width: 1100px; margin-top:30px;">
            <div style="background:#fff; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                <h1 style="font-weight:800; margin-bottom:30px;">🛰️ Dashboard de Produção AI</h1>
                
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; gap:20px;">
                    <form method="post" style="flex:2; background:#f9f9f9; padding:25px; border-radius:12px; border:1px solid #eee;">
                        <?php wp_nonce_field('rai_add_kw_action'); ?>
                        <label style="font-weight:700; display:block; margin-bottom:10px; color:#1d2327;">🚀 Adicionar Novos Alvos (Uma Palavra-Chave por linha):</label>
                        <textarea name="rai_keywords_list" style="width:100%; height:80px; border-radius:10px; border:1px solid #ddd; padding:15px; font-size:14px;" placeholder="Ex: Macarrão Carbonara Clássico&#10;Coxinha de Frango Crocante"></textarea>
                        <button type="submit" name="rai_add_keywords" class="button button-primary" style="margin-top:15px; height:45px; padding:0 30px; border-radius:30px; font-weight:700;">Alocar na Fila de Produção</button>
                    </form>

                    <div style="flex:1; background:#f0f6fb; padding:25px; border-radius:12px; border:1px solid #d1e1ee;">
                        <h3 style="margin:0 0 10px 0; color:#2271b1;">🔍 Filtrar Fila</h3>
                        <input type="text" id="rai-search" placeholder="Procurar palavra-chave..." style="width:100%; border-radius:8px; padding:10px; border:1px solid #bcd0e0;">
                        <div style="margin-top:15px; display:flex; gap:10px;">
                            <button class="button rai-bulk-delete" style="color:#d63638; border-color:#d63638;">Limpar Pesquisados</button>
                        </div>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped" style="border:none;">
                    <thead>
                        <tr>
                            <th style="width:50px;">Ordem</th>
                            <th>Palavra-Chave</th>
                            <th>Status</th>
                            <th style="width:300px;">Ações de Controle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue as $item) : ?>
                        <tr id="row-<?php echo $item->id; ?>">
                            <td style="vertical-align:middle; text-align:center; font-weight:700; color:#2271b1;"><?php echo esc_html($item->sort_order); ?></td>
                            <td style="vertical-align:middle;"><strong><?php echo esc_html($item->keyword); ?></strong></td>
                            <td style="vertical-align:middle;">
                                <span style="padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; 
                                <?php 
                                    if ($item->status === 'pending') echo 'background:#fff7e6; color:#fa8c16;';
                                    elseif ($item->status === 'completed') echo 'background:#f6ffed; color:#52c41a;';
                                    else echo 'background:#fff1f0; color:#f5222d;';
                                ?>">
                                    <?php echo esc_html($item->status); ?>
                                </span>
                            </td>
                            <td style="vertical-align:middle;">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <?php if ($item->status === 'pending') : ?>
                                        <button class="button rai-move" data-id="<?php echo $item->id; ?>" data-dir="up" title="Subir">↑</button>
                                        <button class="button rai-move" data-id="<?php echo $item->id; ?>" data-dir="down" title="Descer">↓</button>
                                        <button class="button button-primary rai-run-now" data-id="<?php echo $item->id; ?>">Gerar ⚡</button>
                                        <button class="button button-link-delete rai-delete" data-id="<?php echo $item->id; ?>" style="color:#d63638;">Excluir</button>
                                    <?php endif; ?>
                                    <?php if ($item->post_id) : ?>
                                        <a href="<?php echo get_edit_post_link($item->post_id); ?>" class="button" target="_blank">Ver Conteúdo</a>
                                    <?php endif; ?>
                                    <span class="spinner" id="spinner-<?php echo $item->id; ?>"></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <script>
            jQuery(document).ready(function($) {
                const nonce = '<?php echo wp_create_nonce("rai_queue_nonce"); ?>';

                // Botão Gerar Agora
                $('.rai-run-now').on('click', function() {
                    const btn = $(this); const id = btn.data('id'); const spinner = $('#spinner-' + id);
                    btn.prop('disabled', true); spinner.addClass('is-active');
                    $.post(ajaxurl, { action: 'rai_process_instant', item_id: id, _ajax_nonce: nonce }, function() { location.reload(); });
                });

                // Pesquisa Real-time
                $('#rai-search').on('keyup', function() {
                    const val = $(this).val().toLowerCase();
                    $('tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
                    });
                });

                // Mover Item (Subir/Descer)
                $('.rai-move').on('click', function() {
                    const btn = $(this);
                    const id = btn.data('id'); const dir = btn.data('dir');
                    btn.prop('disabled', true);
                    $.post(ajaxurl, { action: 'rai_move_item', item_id: id, direction: dir, _ajax_nonce: nonce }, function() { location.reload(); });
                });

                // Excluir Item
                $('.rai-delete').on('click', function() {
                    if (!confirm('Deseja realmente remover esta palavra-chave da fila?')) return;
                    const btn = $(this);
                    const id = btn.data('id');
                    btn.prop('disabled', true);
                    $.post(ajaxurl, { action: 'rai_delete_item', item_id: id, _ajax_nonce: nonce }, function() { $('#row-'+id).fadeOut(); });
                });

                // Bulk Delete (Visíveis)
                $('.rai-bulk-delete').on('click', function() {
                    const visibleRows = $('tbody tr:visible .rai-delete');
                    if (visibleRows.length === 0) return;
                    if (!confirm(`Deseja excluir os ${visibleRows.length} itens visíveis na pesquisa?`)) return;
                    
                    visibleRows.each(function() {
                        const id = $(this).data('id');
                        $.post(ajaxurl, { action: 'rai_delete_item', item_id: id, _ajax_nonce: nonce }, function() { $('#row-'+id).fadeOut(); });
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}
