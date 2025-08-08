<?php
/*
Plugin Name: Advanced Search Intelligence
Description: Plugin que registra buscas, cria painel administrativo com exportação, autocomplete baseado em dados reais e substituição inteligente de termos.
Version: 1.0
Author: ChatGPT Custom Dev
*/


require_once plugin_dir_path(__FILE__) . 'admin/class-asi-admin.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax-instrucoes.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-asi-terms.php';
require_once plugin_dir_path(__FILE__) . 'admin/ajax-termos.php';


// Define constantes para o plugin
define( 'ASI_VERSION', '1.0.3' ); // Versão atualizada
define( 'ASI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Inclui arquivos do plugin
require_once ASI_PLUGIN_DIR . 'includes/class-asi-activator.php';
require_once ASI_PLUGIN_DIR . 'admin/class-asi-admin.php';
require_once ASI_PLUGIN_DIR . 'public/class-asi-public.php';

// Funções de ativação e desativação
function activate_advanced_search_intelligence() {
    ASI_Activator::activate();
}

// 1. Criar tabelas ao ativar (Logs e Sinônimos)
register_activation_hook( __FILE__, 'activate_advanced_search_intelligence' );

// Core do plugin
class Advanced_Search_Intelligence {

    protected $asi_admin;
    protected $asi_public;

    public function __construct() {
        $this->asi_admin = new ASI_Admin();
        $this->asi_public = new ASI_Public();

        // Registra hooks
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this->asi_admin, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this->asi_admin, 'register_settings' ) );
        add_action( 'admin_post_process_synonym_form', array( $this->asi_admin, 'process_synonym_form' ) );
        add_action( 'admin_enqueue_scripts', array( $this->asi_admin, 'enqueue_admin_scripts' ) );

        add_action( 'wp_enqueue_scripts', array( $this->asi_public, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this->asi_public, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_asi_autocomplete_suggestions', array( $this->asi_public, 'autocomplete_suggestions_callback' ) );
        add_action( 'wp_ajax_nopriv_asi_autocomplete_suggestions', array( $this->asi_public, 'autocomplete_suggestions_callback' ) );

        // Hook para registrar busca e redirecionar categoria
        add_action('template_redirect', array($this->asi_public, 'register_search_term'));

        // Hook para aplicar sinônimos à busca principal
        add_filter('pre_get_posts', array($this->asi_public, 'apply_synonyms_to_search'));

        // Hook para o shortcode
        add_shortcode('custom_search_form', array($this->asi_public, 'render_search_form'));

        // Hook para exportar dados
        add_action('admin_post_asi_export_logs', array($this->asi_admin, 'export_search_logs_to_csv'));

        // Hook para exibir resumo na página de arquivo de categoria
        add_filter('the_content', array($this->asi_public, 'display_excerpt_on_archives'));
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'advanced-search-intelligence', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}

// Inicia o plugin
$advanced_search_intelligence = new Advanced_Search_Intelligence();


// ASI_Activator class (includes/class-asi-activator.php)
// NOTA: Esta classe deve ser definida em includes/class-asi-activator.php
if ( ! class_exists( 'ASI_Activator' ) ) {
    class ASI_Activator {
        public static function activate() {
            global $wpdb;

            // Tabela de Logs de Busca
            $logs_table = $wpdb->prefix . 'custom_search_logs';
            $charset_collate = $wpdb->get_charset_collate();
            $sql_logs = "CREATE TABLE IF NOT EXISTS $logs_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                termo TEXT NOT NULL,
                categoria INT DEFAULT 0,
                buscado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_logs);

            // Tabela de Sinônimos (com novas colunas)
            $synonyms_table = $wpdb->prefix . 'asi_synonyms';
            $sql_synonyms = "CREATE TABLE IF NOT EXISTS $synonyms_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                search_term VARCHAR(255) NOT NULL,
                replacement_term VARCHAR(255) NOT NULL,
                linked_categories LONGTEXT DEFAULT NULL, -- Armazena IDs de categoria como JSON
                linked_posts LONGTEXT DEFAULT NULL,     -- Armazena IDs de posts como JSON
                PRIMARY KEY (id),
                UNIQUE KEY search_term (search_term)
            ) $charset_collate;";
            dbDelta($sql_synonyms);
        }
    }
}