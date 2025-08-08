jQuery(document).ready(function($) {
    $('#asi_instrucoes_vinculadas').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    term: params.term,
                    action: 'asi_buscar_posts'
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        multiple: true,
        width: '100%',
        placeholder: 'Digite para buscar posts...'
    });
});
