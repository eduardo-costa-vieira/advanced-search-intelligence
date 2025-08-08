<?php
/**
 * Lida com a ativação do plugin.
 */
class ASI_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de Logs de Busca
        $search_logs_table = $wpdb->prefix . 'custom_search_logs';
        $search_logs_sql = "CREATE TABLE IF NOT EXISTS $search_logs_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            termo TEXT NOT NULL,
            categoria INT DEFAULT 0,
            buscado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($search_logs_sql);

        // Tabela para Sinônimos
        $synonyms_table = $wpdb->prefix . 'asi_synonyms';
        $synonyms_sql = "CREATE TABLE IF NOT EXISTS $synonyms_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(255) NOT NULL,
            replacement_term VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY search_term (search_term)
        ) $charset_collate;";
        dbDelta($synonyms_sql);

        // Define opções padrão para as configurações, se não existirem
        add_option('asi_custom_title_text', 'Pesquisar na base de conhecimento...');
        add_option('asi_empty_search_message', 'Preencha a sua busca');
        add_option('asi_text_color', '#000000'); // Cor padrão do texto do título
        add_option('asi_title_font_size', '24px'); // Tamanho da fonte padrão do título
    }
}