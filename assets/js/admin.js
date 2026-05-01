/* global: ganData */
jQuery(document).ready(function($) {
    // Garante que temos os dados do WordPress
    if (typeof ganData === 'undefined') return;

    var ajaxurl = ganData.ajaxurl;
    var nonce = ganData.nonce;

    /* =========================================================
       Settings Page — Tab switching
    ========================================================= */
    $('.gan-tab').on('click', function() {
        $('.gan-tab').removeClass('active');
        $(this).addClass('active');
        $('.gan-tab-content').hide();
        $('#tab-' + $(this).data('tab')).show();
    });

    /* =========================================================
       Keywords Page — Dynamic CPT Categories
    ========================================================= */
    function load_taxonomies(cpt) {
        var container = $('#gan_category_container');
        container.html('<div style="padding:15px; color:#ec5b13;" class="gan-loading">⚡ Buscando categorias de '+cpt+'...</div>');

        $.post(ajaxurl, { 
            action: 'gan_get_taxonomies', 
            post_type: cpt, 
            _ajax_nonce: nonce 
        }, function(response) {
            if (response.success) {
                var html = '';
                if (response.data.length === 0) {
                    html = '<div style="padding:15px; color:#d63638;">⚠️ Nenhuma categoria disponível para este destino.</div>';
                } else {
                    response.data.forEach(function(item) {
                        html += '<label class="gan-cat-item">';
                        html += '<input type="checkbox" name="gan_target_cats[]" value="' + item.id + '"> ' + item.name;
                        html += '</label>';
                    });
                }
                container.html(html);
            }
        });
    }

    // Gatilho ao mudar o seletor
    $('#gan_target_cpt').on('change', function() {
        load_taxonomies($(this).val());
    });

    // Carregamento Inicial
    if ($('#gan_target_cpt').length > 0) {
        load_taxonomies($('#gan_target_cpt').val());
    }

    /* =========================================================
       Keywords Page — Queue management
    ========================================================= */
    $('.gan-run-now').on('click', function() {
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).text('⌛...');
        $.post(ajaxurl, { action: 'gan_process_instant', item_id: id, _ajax_nonce: nonce }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro: ' + (response.data || 'Falha desconhecida'));
                btn.prop('disabled', false).text('Gerar ⚡');
            }
        });
    });

    $('.gan-delete').on('click', function() {
        if (!confirm('Excluir este item?')) return;
        var id = $(this).data('id');
        $.post(ajaxurl, { action: 'gan_delete_item', item_id: id, _ajax_nonce: nonce }, function() {
            $('#row-' + id).fadeOut();
        });
    });
});
