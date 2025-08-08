<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Fulltext_Index_Weight_Manager {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_weight_manager_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_fiwm_update_weight', array( $this, 'ajax_update_weight' ) );
        add_action( 'wp_ajax_fiwm_delete_weight', array( $this, 'ajax_delete_weight' ) );
        add_action( 'admin_post_fiwm_export_csv', array( $this, 'export_csv' ) );
        add_action( 'admin_post_fiwm_import_csv', array( $this, 'import_csv' ) );
    }

    public function add_weight_manager_page() {
        add_submenu_page(
            'edit.php?post_type=post',
            'Gerenciar Pesos de Palavras',
            'Gerenciar Pesos',
            'manage_options',
            'fulltext-weight-manager',
            array( $this, 'render_weight_manager_page' )
        );
    }

    public function enqueue_scripts($hook) {
        if ( strpos($hook, 'fulltext-weight-manager') === false ) return;

        wp_enqueue_script('fiwm-admin-js', plugin_dir_url(__FILE__) . 'js/fiwm-admin.js', array('jquery'), null, true);
        wp_localize_script('fiwm-admin-js', 'FIWM_AJAX', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fiwm_nonce'),
        ));
        wp_enqueue_style('fiwm-admin-css', plugin_dir_url(__FILE__) . 'css/fiwm-admin.css');
    }

    public function render_weight_manager_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'fulltext_index';
        $results = $wpdb->get_results("SELECT DISTINCT palavra, peso FROM {$table} ORDER BY palavra ASC", ARRAY_A);

        ?>
        <div class="wrap">
            <h1>Gerenciador de Pesos das Palavras Indexadas</h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="fiwm_import_csv">
                <?php wp_nonce_field('fiwm_import_csv', 'fiwm_import_nonce'); ?>
                <input type="file" name="csv_file" required>
                <select name="mode">
                    <option value="replace">Substituir Tudo</option>
                    <option value="merge">Mesclar/Atualizar</option>
                </select>
                <button type="submit" class="button button-primary">Importar CSV</button>
                <a href="<?php echo admin_url('admin-post.php?action=fiwm_export_csv&_wpnonce=' . wp_create_nonce('fiwm_export_csv')); ?>" class="button">Exportar CSV</a>
            </form>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Palavra</th>
                        <th>Peso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $results as $row ): ?>
                        <tr data-word="<?php echo esc_attr($row['palavra']); ?>">
                            <td><?php echo esc_html($row['palavra']); ?></td>
                            <td>
                                <input type="number" step="0.1" value="<?php echo esc_attr($row['peso']); ?>" class="fiwm-weight-input" />
                            </td>
                            <td>
                                <button class="button fiwm-update">Atualizar</button>
                                <button class="button fiwm-delete">Remover</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ajax_update_weight() {
        check_ajax_referer('fiwm_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'fulltext_index';

        $word = sanitize_text_field($_POST['word']);
        $weight = floatval($_POST['weight']);

        $wpdb->update($table, ['peso' => $weight], ['palavra' => $word]);
        wp_send_json_success(['message' => 'Peso atualizado.']);
    }

    public function ajax_delete_weight() {
        check_ajax_referer('fiwm_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'fulltext_index';

        $word = sanitize_text_field($_POST['word']);
        $wpdb->delete($table, ['palavra' => $word]);
        wp_send_json_success(['message' => 'Palavra removida.']);
    }

    public function export_csv() {
        if ( ! current_user_can('manage_options') || ! wp_verify_nonce($_GET['_wpnonce'], 'fiwm_export_csv') ) {
            wp_die('Acesso negado.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fulltext_index';
        $rows = $wpdb->get_results("SELECT DISTINCT palavra, peso FROM {$table}", ARRAY_A);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pesos-indexacao.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['palavra', 'peso']);

        foreach ( $rows as $row ) {
            fputcsv($output, [$row['palavra'], $row['peso']]);
        }

        fclose($output);
        exit;
    }

    public function import_csv() {
        if ( ! current_user_can('manage_options') || ! wp_verify_nonce($_POST['fiwm_import_nonce'], 'fiwm_import_csv') ) {
            wp_die('Acesso negado.');
        }

        if ( empty($_FILES['csv_file']['tmp_name']) ) {
            wp_redirect(add_query_arg('error', 'empty_file', wp_get_referer()));
            exit;
        }

        $mode = $_POST['mode'] === 'merge' ? 'merge' : 'replace';
        global $wpdb;
        $table = $wpdb->prefix . 'fulltext_index';

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ( ! $handle ) wp_die('Erro ao abrir o arquivo.');

        fgetcsv($handle); // header

        if ( $mode === 'replace' ) {
            $wpdb->query("DELETE FROM {$table}");
        }

        while ( ($data = fgetcsv($handle)) !== false ) {
            $palavra = sanitize_text_field($data[0]);
            $peso = floatval($data[1]);

            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE palavra = %s", $palavra));
            if ( $mode === 'merge' && $exists ) {
                $wpdb->update($table, ['peso' => $peso], ['palavra' => $palavra]);
            } else {
                $wpdb->insert($table, ['palavra' => $palavra, 'peso' => $peso]);
            }
        }

        fclose($handle);
        wp_redirect(remove_query_arg('error', wp_get_referer()));
        exit;
    }
}
