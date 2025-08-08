<?php
add_action('wp_ajax_asi_delete_all_termos',   'asi_delete_all_termos');
add_action('wp_ajax_asi_export_csv',          'asi_export_csv');

function asi_delete_all_termos() {
    check_ajax_referer('asi_termos_nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'asi_termos_busca';
    $wpdb->query("TRUNCATE TABLE {$table}");
    wp_send_json_success('Todos os termos foram excluídos.');
}

function asi_export_csv() {
    // Sem nonce pois é link direto com GET
    global $wpdb;
    $table = $wpdb->prefix . 'asi_termos_busca';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=termos_busca.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Termo', 'Data/Hora']);

    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY criado_em DESC");
    foreach ($rows as $r) {
        fputcsv($output, [ $r->id, $r->termo, $r->criado_em ]);
    }
    exit;
}
