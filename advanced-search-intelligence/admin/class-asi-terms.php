<?php
if (!function_exists('asi_termos_busca_page')) :

function asi_termos_busca_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'asi_termos_busca';

    // Parâmetros de filtro/página via GET (server-side)
    $paged      = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page   = 20;
    $search     = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Exclusão única
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
        echo '<div class="notice notice-success"><p>Termo excluído.</p></div>';
    }

    // Query total
    $where = $search ? $wpdb->prepare("WHERE termo LIKE %s", "%{$search}%") : '';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

    // Consulta paginada
    $offset = ($paged - 1) * $per_page;
    $sql    = "SELECT * FROM $table $where ORDER BY criado_em DESC LIMIT %d OFFSET %d";
    $rows   = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

    // Cabeçalho
    echo '<div class="wrap"><h1>Termos Buscados</h1>';
    echo '<form method="get" style="margin-bottom:1em;">';
    echo '<input type="hidden" name="page" value="asi-termos-busca" />';
    echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Buscar termo..." />';
    echo '<input type="submit" class="button" value="Pesquisar" />';
    echo '</form>';

    // Ações em lote
    echo '<button id="asi-delete-all" class="button button-secondary">Excluir Todos</button> ';
    echo '<a href="' . esc_url(add_query_arg(['action' => 'asi_export_csv'])) . '" class="button button-primary">Exportar CSV</a>';

    // Tabela
    echo '<table class="widefat fixed striped"><thead><tr>
            <th>ID</th><th>Termo</th><th>Data/Hora</th><th>Ações</th>
        </tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $r) {
            $del = add_query_arg(['delete' => $r->id]);
            echo "<tr>
                    <td>{$r->id}</td>
                    <td>" . esc_html($r->termo) . "</td>
                    <td>{$r->criado_em}</td>
                    <td><a href='" . esc_url($del) . "' class='button button-small' onclick='return confirm(\"Excluir este termo?\")'>Excluir</a></td>
                  </tr>";
        }
    } else {
        echo '<tr><td colspan="4">Nenhum termo encontrado.</td></tr>';
    }
    echo '</tbody></table>';

    // Paginação
    $total_pages = ceil($total / $per_page);
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $class = $i === $paged ? 'current' : '';
            $link  = add_query_arg(['paged' => $i, 's' => $search]);
            echo "<a class='page-button {$class}' href='" . esc_url($link) . "'>{$i}</a> ";
        }
        echo '</div></div>';
    }

    echo '</div>';
}

endif;
