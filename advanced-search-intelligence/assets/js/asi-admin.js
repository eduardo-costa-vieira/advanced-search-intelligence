jQuery(document).ready(function($) {
    // --- Gerenciamento de Sinônimos ---
    var $synonymForm = $('#asi-synonym-form');
    var $synonymIdField = $('#synonym_id');
    var $searchTermField = $('#search_term');
    var $replacementTermField = $('#replacement_term');
    var $saveSynonymButton = $('#save-synonym-button');
    var $cancelEditSynonymButton = $('#cancel-edit-synonym');
    var $synonymMessageDiv = $('#asi-form-message');
    var $synonymsListBody = $('#asi-synonyms-list');
    var $synonymSpinner = $synonymForm.find('.spinner');

    // Função para limpar e resetar o formulário de sinônimos
    function resetSynonymForm() {
        $synonymIdField.val('');
        $searchTermField.val('');
        $replacementTermField.val('');
        $saveSynonymButton.text('Salvar Sinônimo');
        $cancelEditSynonymButton.hide();
        $synonymMessageDiv.empty().removeClass('notice notice-success notice-error');
    }

    $synonymForm.on('submit', function(e) {
        e.preventDefault();

        $synonymSpinner.addClass('is-active');
        $synonymMessageDiv.empty().removeClass('notice notice-success notice-error');

        var data = {
            action: 'asi_save_synonyms',
            synonym_id: $synonymIdField.val(),
            search_term: $searchTermField.val(),
            replacement_term: $replacementTermField.val(),
            asi_synonyms_nonce: asiAdminAjax.save_synonyms_nonce
        };

        $.post(asiAdminAjax.ajax_url, data, function(response) {
            $synonymSpinner.removeClass('is-active');

            if (response.success) {
                $synonymMessageDiv.addClass('notice notice-success is-dismissible').html('<p>' + response.data.message + '</p>');
                resetSynonymForm(); // Reseta o formulário após salvar

                var newRowHtml = '<tr id="synonym-' + response.data.id + '"><td>' + response.data.search_term + '</td><td>' + response.data.replacement_term + '</td><td><button type="button" class="button button-small asi-edit-synonym" data-id="' + response.data.id + '">Editar</button> <button type="button" class="button button-small button-link-delete asi-delete-synonym" data-id="' + response.data.id + '">Excluir</button></td></tr>';

                if (response.data.action === 'inserted') {
                    $synonymsListBody.append(newRowHtml);
                } else if (response.data.action === 'updated') {
                    $('#synonym-' + response.data.id).replaceWith(newRowHtml);
                }

                // Re-ordenar a lista
                var rows = $synonymsListBody.children('tr').get();
                rows.sort(function(a, b) {
                    var A = $(a).children('td').eq(0).text().toUpperCase();
                    var B = $(b).children('td').eq(0).text().toUpperCase();
                    return (A < B) ? -1 : (A > B) ? 1 : 0;
                });
                $.each(rows, function(index, row) {
                    $synonymsListBody.append(row);
                });

            } else {
                $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>' + response.data.message + '</p>');
            }
        }).fail(function() {
            $synonymSpinner.removeClass('is-active');
            $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>Erro de comunicação com o servidor.</p>');
        });
    });

    // Delegar evento de clique para o botão de edição
    $synonymsListBody.on('click', '.asi-edit-synonym', function() {
        var synonymId = $(this).data('id');
        $synonymSpinner.addClass('is-active');
        $synonymMessageDiv.empty().removeClass('notice notice-success notice-error');

        $.post(asiAdminAjax.ajax_url, {
            action: 'asi_get_synonym',
            id: synonymId,
            nonce: asiAdminAjax.save_synonyms_nonce // Reutiliza o nonce de save
        }, function(response) {
            $synonymSpinner.removeClass('is-active');
            if (response.success) {
                $synonymIdField.val(response.data.id);
                $searchTermField.val(response.data.search_term);
                $replacementTermField.val(response.data.replacement_term);
                $saveSynonymButton.text('Atualizar Sinônimo');
                $cancelEditSynonymButton.show();
            } else {
                $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>' + response.data.message + '</p>');
            }
        }).fail(function() {
            $synonymSpinner.removeClass('is-active');
            $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>Erro ao buscar sinônimo para edição.</p>');
        });
    });

    // Cancelar edição
    $cancelEditSynonymButton.on('click', function() {
        resetSynonymForm();
    });

    // Delegar evento de clique para o botão de exclusão
    $synonymsListBody.on('click', '.asi-delete-synonym', function() {
        if (!confirm('Tem certeza que deseja excluir este sinônimo?')) {
            return;
        }

        var $rowToRemove = $(this).closest('tr');
        var synonymId = $(this).data('id');
        var $button = $(this);

        $button.prop('disabled', true).text('Excluindo...');
        $synonymMessageDiv.empty().removeClass('notice notice-success notice-error');

        $.post(asiAdminAjax.ajax_url, {
            action: 'asi_delete_synonym',
            id: synonymId,
            nonce: asiAdminAjax.delete_synonym_nonce
        }, function(response) {
            if (response.success) {
                $synonymMessageDiv.addClass('notice notice-success is-dismissible').html('<p>' + response.data.message + '</p>');
                $rowToRemove.remove();
                setTimeout(function() {
                    $synonymMessageDiv.empty().removeClass('notice notice-success is-dismissible');
                }, 3000);
            } else {
                $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>' + response.data.message + '</p>');
                $button.prop('disabled', false).text('Excluir');
            }
        }).fail(function() {
            $synonymMessageDiv.addClass('notice notice-error is-dismissible').html('<p>Erro de comunicação ao excluir sinônimo.</p>');
            $button.prop('disabled', false).text('Excluir');
        });
    });

    // --- Gerenciamento de Configurações ---
    var $settingsForm = $('#asi-settings-form');
    var $settingsMessageDiv = $('#asi-settings-message');
    var $settingsSpinner = $settingsForm.find('.spinner');

    $settingsForm.on('submit', function(e) {
        e.preventDefault();

        $settingsSpinner.addClass('is-active');
        $settingsMessageDiv.empty().removeClass('notice notice-success notice-error');

        var data = {
            action: 'asi_save_settings',
            asi_custom_title_text: $('#asi_custom_title_text').val(),
            asi_empty_search_message: $('#asi_empty_search_message').val(),
            asi_text_color: $('#asi_text_color').val(),
            asi_title_font_size: $('#asi_title_font_size').val(),
            asi_settings_nonce: asiAdminAjax.save_settings_nonce
        };

        $.post(asiAdminAjax.ajax_url, data, function(response) {
            $settingsSpinner.removeClass('is-active');
            if (response.success) {
                $settingsMessageDiv.addClass('notice notice-success is-dismissible').html('<p>' + response.data.message + '</p>');
                setTimeout(function() {
                    $settingsMessageDiv.empty().removeClass('notice notice-success is-dismissible');
                }, 3000);
            } else {
                $settingsMessageDiv.addClass('notice notice-error is-dismissible').html('<p>' + response.data.message + '</p>');
            }
        }).fail(function() {
            $settingsSpinner.removeClass('is-active');
            $settingsMessageDiv.addClass('notice notice-error is-dismissible').html('<p>Erro de comunicação ao salvar configurações.</p>');
        });
    });
});