jQuery(document).ready(function($) {

    $('#asi-delete-all').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Excluir todos os termos? Esta ação não pode ser desfeita.')) {
            return;
        }

        $.post(asi_termos_ajax.ajaxurl, {
            action: 'asi_delete_all_termos',
            nonce: asi_termos_ajax.nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Falha ao excluir todos os termos.');
            }
        });
    });

});
