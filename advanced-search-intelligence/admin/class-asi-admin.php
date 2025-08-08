<?php

class ASI_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Busca Avançada',
            'Busca Avançada',
            'manage_options',
            'advanced-search-intelligence',
            [$this, 'display_plugin_admin_page'],
            'dashicons-search',
            30
        );

        add_submenu_page(
            'advanced-search-intelligence',
            'Termos Buscados',
            'Termos Buscados',
            'manage_options',
            'asi-termos-buscados',
            [$this, 'display_termos_buscados_page']
        );
    }

    public function register_settings() {
        register_setting('asi_settings_group', 'asi_button_position');
        register_setting('asi_settings_group', 'asi_button_color');
        register_setting('asi_settings_group', 'asi_button_hover_color');
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'asi-termos-buscados') !== false) {
            wp_enqueue_script(
                'asi-autocomplete-admin',
                plugin_dir_url(__FILE__) . '../assets/js/asi-autocomplete.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_localize_script('asi-autocomplete-admin', 'asi_ajax_obj', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('asi_nonce')
            ]);

            wp_enqueue_style(
                'asi-admin-style',
                plugin_dir_url(__FILE__) . '../assets/css/asi-admin.css',
                [],
                '1.0'
            );
        }
    }

    public function display_plugin_admin_page() {
        include plugin_dir_path(__FILE__) . 'partials/asi-admin-display.php';
    }

    public function display_termos_buscados_page() {
        include plugin_dir_path(__FILE__) . 'partials/asi-termos-buscados-display.php';
    }
}
