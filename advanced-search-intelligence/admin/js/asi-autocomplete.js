jQuery(document).ready(function ($) {
    const searchInput = $('#asi-busca-input');
    searchInput.on('blur', function () {
        const termo = $(this).val();
        if (termo.length > 2) {
            $.post(asi_ajax.ajaxurl, {
                action: 'asi_salvar_termo',
                termo: termo
            });
        }
    });
});
