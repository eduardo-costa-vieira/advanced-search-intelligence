document.addEventListener("DOMContentLoaded", function () {
    const input = document.querySelector('#custom-search-input');
    const resultsBox = document.querySelector('#custom-search-results');

    if (!input || !resultsBox) return;

    input.addEventListener('keyup', function () {
        const query = this.value.trim();

        if (query.length < 3) {
            resultsBox.innerHTML = '';
            return;
        }

        fetch(`/wp-json/customsearch/v1/query?s=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsBox.innerHTML = '';

                if (data.length === 0) {
                    resultsBox.innerHTML = '<div class="result-item">Nenhum resultado encontrado.</div>';
                    return;
                }

                data.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'result-item';
                    el.innerHTML = `<a href="${item.url}">${item.title}</a><p>${item.excerpt}</p>`;
                    resultsBox.appendChild(el);
                });
            });
    });
});
