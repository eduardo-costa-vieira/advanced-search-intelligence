// ...dentro do método perform_search()...

$pesos = [];
foreach ($wpdb->get_results("SELECT palavra, peso FROM {$wpdb->prefix}fti_weights") as $peso) {
    $pesos[$peso->palavra] = intval($peso->peso);
}

foreach ($resultados_brutos as $row) {
    $base_score = substr_count(strtolower($row->palavra), strtolower($query));
    $peso_custom = $pesos[strtolower($row->palavra)] ?? 1;

    // Extra: ponto a mais se for tag ou categoria do post
    $tags = wp_get_post_tags($row->post_id, ['fields' => 'names']);
    $cats = wp_get_post_terms($row->post_id, 'category', ['fields' => 'names']);

    $lsi_bonus = 0;
    foreach (array_merge($tags, $cats) as $term) {
        if (stripos($term, $query) !== false) {
            $lsi_bonus++;
        }
    }

    $relevancia = ($base_score * $peso_custom) + $lsi_bonus;

    $resultados[] = [
        'post_id' => $row->post_id,
        'titulo' => get_the_title($row->post_id),
        'palavra' => $row->palavra,
        'peso' => $peso_custom,
        'relevancia' => $relevancia,
        'link' => get_permalink($row->post_id),
    ];
}
