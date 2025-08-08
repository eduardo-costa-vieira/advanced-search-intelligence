<?php
function load_custom_search_assets() {
    wp_enqueue_script(
        'custom-search-js',
        plugin_dir_url(__FILE__) . 'assets/js/search-custom.js',
        [],
        '1.0',
        true
    );

    wp_enqueue_style(
        'custom-search-css',
        plugin_dir_url(__FILE__) . 'assets/css/search-style.css',
        [],
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'load_custom_search_assets');

// Carrega endpoints e classes
require_once plugin_dir_path(__FILE__) . 'includes/class-custom-search-endpoint.php';
new Custom_Search_Endpoint();

// Carrega o analisador semântico
require_once plugin_dir_path(__FILE__) . 'includes/class-fulltext-index-semantic-analyzer.php';
$semantic_analyzer = new FullText_Index_Semantic_Analyzer();
