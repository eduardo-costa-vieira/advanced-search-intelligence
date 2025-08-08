jQuery(function($){
    let timer;
    $('#wpfti-search').on('input', function() {
        clearTimeout(timer);
        let query = $(this).val();
        if (query.length < 2) return;

        timer = setTimeout(function(){
            $.ajax({
                url: wpfti_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpfti_search',
                    termo: query
                },
                success: function(res) {
                    let html = '<ul>';
                    res.data.forEach(r => {
                        html += `<li><a href="${r.link}">${r.titulo}</a> <span class="peso">Peso: ${r.peso}</span></li>`;
                    });
                    html += '</ul>';
                    $('.wpfti-results').html(html);
                }
            });
        }, 300);
    });
});
