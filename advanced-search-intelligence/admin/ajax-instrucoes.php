<?php
add_action('wp_ajax_asi_salvar_termo', 'asi_salvar_termo');
add_action('wp_ajax_nopriv_asi_salvar_termo', 'asi_salvar_termo');

function asi_salvar_termo() {
    global $wpdb;

    if (!isset($_POST['termo']) || empty($_POST['termo'])) {
        wp_send_json_error('Termo inválido');
    }

    $termo = sanitize_text_field($_POST['termo']);
    $table = $wpdb->prefix . 'asi_termos_busca';

    $wpdb->insert($table, [
        'termo' => $termo,
        'criado_em' => current_time('mysql')
    ]);

    wp_send_json_success('Termo salvo');
}
