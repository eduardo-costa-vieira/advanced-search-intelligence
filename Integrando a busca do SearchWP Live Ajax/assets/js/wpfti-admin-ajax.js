jQuery(function($){
    function loadWeights(){
        $.post(WPFTI_Ajax.ajax_url, {action:'wpfti_admin_get'}, function(res){
            $('#wpfti-ajax-body').html('');
            res.data.forEach(w=>{
                $('#wpfti-ajax-body').append(
                    `<tr>
                        <td>${w.id}</td>
                        <td>${w.palavra}</td>
                        <td><input type="number" data-id="${w.id}" data-palavra="${w.palavra}" class="wpfti-peso-input" value="${w.peso}" /></td>
                    </tr>`
                );
            });
        });
    }

    $(document).on('change','.wpfti-peso-input',function(){
        let id = $(this).data('id'), palavra=$(this).data('palavra'), peso=$(this).val();
        $.post(WPFTI_Ajax.ajax_url,{
            action:'wpfti_admin_update',
            id, palavra, peso,
            _ajax_nonce: WPFTI_Ajax.nonce
        }, function(res){
            if(res.success) loadWeights();
        });
    });

    // carrega ao iniciar
    loadWeights();
});
