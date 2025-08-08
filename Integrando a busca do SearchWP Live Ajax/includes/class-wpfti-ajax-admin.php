<?php
if ( ! defined('ABSPATH') ) exit;

class WPFTI_Ajax_Admin {
    public static function add_admin_page() {
        add_submenu_page('tools.php','WPFTI AJAX Admin','Pesos AJAX','manage_options','wpfti-ajax',[__CLASS__,'admin_page']);
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'tools_page_wpfti-ajax') return;
        wp_enqueue_script('wpfti-admin-js', plugin_dir_url(__FILE__).'../assets/js/wpfti-admin-ajax.js',['jquery'],'1.0',true);
        wp_localize_script('wpfti-admin-js','WPFTI_Ajax',[
            'ajax_url'=>admin_url('admin-ajax.php'),
            'nonce'=>wp_create_nonce('wpfti_admin_ajax')
        ]);
    }

    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>Editar Pesos (AJAX)</h1>
            <table class="widefat"><thead><tr><th>ID</th><th>Palavra</th><th>Peso</th></tr></thead><tbody id="wpfti-ajax-body"></tbody></table>
        </div>
        <?php
    }

    public static function init_ajax() {
        add_action('wp_ajax_wpfti_admin_update', [__CLASS__,'update_ajax']);
    }

    public static function update_ajax() {
        check_ajax_referer('wpfti_admin_ajax');
        $id = intval($_POST['id']);
        $peso = intval($_POST['peso']);
        if ($id && current_user_can('manage_options')) {
            WPFTI_Weight_Manager::set_weight(trim($_POST['palavra']), $peso);
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}
WPFTI_Ajax_Admin::init_ajax();
