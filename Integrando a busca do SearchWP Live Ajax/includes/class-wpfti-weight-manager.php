<?php
if ( ! defined('ABSPATH') ) exit;

class WPFTI_Weight_Manager {
    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'fti_weights';
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'handle_form_submit']);
        add_action('admin_init', [__CLASS__, 'handle_export_csv']);
    }

    public static function create_table() {
        global $wpdb;
        self::init();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE " . self::$table_name . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            palavra VARCHAR(255) NOT NULL,
            peso INT NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY palavra (palavra)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function get_weights() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::$table_name);
    }

    public static function set_weight($palavra, $peso) {
        global $wpdb;
        $wpdb->replace(self::$table_name, ['palavra'=>$palavra, 'peso'=>$peso], ['%s','%d']);
    }

    public static function delete_weight($id) {
        global $wpdb;
        $wpdb->delete(self::$table_name, ['id'=>$id], ['%d']);
    }

    public static function add_admin_menu() {
        add_submenu_page('tools.php','Pesos de Relevância','WPFTI Pesos','manage_options','wpfti-pesos',[__CLASS__,'admin_page']);
    }

    public static function handle_form_submit() {
        if (isset($_POST['wpfti_nonce']) && wp_verify_nonce($_POST['wpfti_nonce'],'wpfti_save') ) {
            foreach ($_POST['ids'] as $i => $id) {
                $pal = sanitize_text_field($_POST['palavras'][$i]);
                $peso = intval($_POST['pesos'][$i]);
                if ($id) self::set_weight($pal, $peso);
                elseif ($pal) self::set_weight($pal, $peso);
            }
        }
        if (isset($_POST['delete_id']) && wp_verify_nonce($_POST['_wpnonce'],'wpfti_delete_'.$ _POST['delete_id'])) {
            self::delete_weight(intval($_POST['delete_id']));
        }
    }

    public static function handle_export_csv() {
        if (!empty($_GET['wpfti_export_csv']) && current_user_can('manage_options')) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="wpfti-pesos.csv"');
            $weights = self::get_weights();
            echo "palavra,peso\n";
            foreach ($weights as $w) {
                echo $w->palavra . ',' . $w->peso . "\n";
            }
            exit;
        }
    }

    public static function admin_page() {
        $weights = self::get_weights();
        ?>
        <div class="wrap">
            <h1>WPFTI – Pesos de Relevância</h1>
            <form method="post">
                <?php wp_nonce_field('wpfti_save','wpfti_nonce'); ?>
                <table class="wp-list-table widefat striped">
                    <thead><tr><th>Palavra</th><th>Peso</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($weights as $w): ?>
                        <tr>
                            <td><input type="text" name="palavras[]" value="<?php echo esc_attr($w->palavra); ?>"></td>
                            <td><input type="number" name="pesos[]" value="<?php echo esc_attr($w->peso); ?>"></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('wpfti_delete_'.$w->id,'_wpnonce'); ?>
                                    <input type="hidden" name="delete_id" value="<?php echo intval($w->id); ?>">
                                    <button type="submit" class="button button-secondary">Excluir</button>
                                </form>
                            </td>
                            <input type="hidden" name="ids[]" value="<?php echo intval($w->id); ?>">
                        </tr>
                    <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="palavras[]" placeholder="Nova palavra"></td>
                            <td><input type="number" name="pesos[]" placeholder="Peso"></td>
                            <td></td>
                            <input type="hidden" name="ids[]" value="">
                        </tr>
                    </tbody>
                </table>
                <p><input type="submit" class="button button-primary" value="Salvar"></p>
            </form>
            <a href="<?php echo admin_url('?wpfti_export_csv=1'); ?>" class="button">Exportar CSV</a>
        </div>
        <?php
    }
}
