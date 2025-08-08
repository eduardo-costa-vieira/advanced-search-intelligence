<?php
// Página no admin para visualizar termos salvos
function asi_register_admin_menu() {
    add_menu_page(
        'Termos Buscados',
        'Termos Buscados',
        'manage_options',
        'asi-termos-busca',
        'asi_termos_busca_page',
        'dashicons-search',
        26
    );
}
add_action('admin_menu', 'asi_register_admin_menu');

// Página HTML da listagem
function asi_termos_busca_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'asi_termos_busca';

    // Exclusão (via query param)
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
        echo '<div class="notice notice-success"><p>Termo excluído.</p></div>';
    }

    $resultados = $wpdb->get_results("SELECT * FROM $table ORDER BY criado_em DESC");

    echo '<div class="wrap"><h1>Termos Buscados</h1>';
    echo '<table class="widefat"><thead><tr><th>ID</th><th>Termo</th><th>Data/Hora</th><th>Ações</th></tr></thead><tbody>';

    if ($resultados) {
        foreach ($resultados as $linha) {
            $delete_link = admin_url('admin.php?page=asi-termos-busca&delete=' . $linha->id);
            echo '<tr>';
            echo '<td>' . esc_html($linha->id) . '</td>';
            echo '<td>' . esc_html($linha->termo) . '</td>';
            echo '<td>' . esc_html($linha->criado_em) . '</td>';
            echo '<td><a href="' . esc_url($delete_link) . '" onclick="return confirm(\'Excluir este termo?\')" class="button button-small">Excluir</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">Nenhum termo encontrado.</td></tr>';
    }

    echo '</tbody></table></div>';
}
