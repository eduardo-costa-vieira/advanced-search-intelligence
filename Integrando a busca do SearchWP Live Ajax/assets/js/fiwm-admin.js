jQuery(document).ready(function($) {
    $('.fiwm-update').on('click', function() {
        let row = $(this).closest('tr');
        let word = row.data('word');
        let weight = row.find('.fiwm-weight-input').val();

        $.post(FIWM_AJAX.ajax_url, {
            action: 'fiwm_update_weight',
            nonce: FIWM_AJAX.nonce,
            word: word,
            weight: weight
        }, function(response) {
            alert(response.data.message);
        });
    });

    $('.fiwm-delete').on('click', function() {
        if (!confirm('Tem certeza que deseja remover esta palavra?')) return;

        let row = $(this).closest('tr');
        let word = row.data('word');

        $.post(FIWM_AJAX.ajax_url, {
            action: 'fiwm_delete_weight',
            nonce: FIWM_AJAX.nonce,
            word: word
        }, function(response) {
            row.remove();
        });
    });
});
