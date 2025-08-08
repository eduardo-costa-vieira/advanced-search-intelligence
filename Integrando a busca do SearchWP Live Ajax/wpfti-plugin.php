<?php
/**
 * Plugin Name: WPFTI – Custom Search com Pesos
 * Description: Busca customizada com pesos ajustáveis, AJAX e exportação.
 * Version: 1.1
 * Author: Eduardo
 */

if ( ! defined('ABSPATH') ) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wpfti-weight-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wpfti-search-endpoint.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wpfti-ajax-admin.php';

// Criação da tabela de pesos
register_activation_hook(__FILE__, ['WPFTI_Weight_Manager', 'create_table']);

// Inicializações
add_action('init', ['WPFTI_Weight_Manager', 'init']);
add_action('init', ['WPFTI_Search_Endpoint', 'init']);
add_action('admin_enqueue_scripts', ['WPFTI_Ajax_Admin', 'enqueue_assets']);
add_action('admin_menu', ['WPFTI_Ajax_Admin', 'add_admin_page']);
