<?php
if ( ! defined('ABSPATH') ) exit;

class WPFTI_Search_Endpoint {
    public static function init() {
        add_action('rest_api_init', [__CLASS__,'register_route']);
    }

    public static function register_route() {
        register_rest_route('wpfti/v1','/search',[
            'methods'=>'GET',
            'callback'=>[__CLASS__,'handle_search'],
            'permission_callback'=>'__return_true'
        ]);
    }

    public static function handle_search($req) {
        global $wpdb;
        $q = sanitize_text_field($req->get_param('q'));
        if (strlen($q) < 2) return [];

        // Base: busca na sua tabela de index
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT post_id, palavra, peso FROM {$wpdb->prefix}fulltext_index WHERE palavra LIKE %s", '%'.$wpdb->esc_like($q).'%'),
            ARRAY_A
        );

        $weights = [];
        foreach ($wpdb->get_results("SELECT palavra,peso FROM {$wpdb->prefix}fti_weights") as $w) {
            $weights[$w->palavra] = intval($w->peso);
        }

        $results = [];
        foreach ($rows as $r) {
            $base = substr_count(strtolower($r['palavra']), strtolower($q));
            $peso_custom = $weights[strtolower($r['palavra'])] ?? 1;

            $tags = wp_get_post_tags($r['post_id'], ['fields'=>'names']);
            $cats = wp_get_post_terms($r['post_id'],'category',['fields'=>'names']);

            $lsi = 0;
            foreach(array_merge($tags,$cats) as $t) {
                if (stripos($t, $q)!==false) $lsi++;
            }

            $score = ($base * $peso_custom) + $lsi;

            $results[] = [
                'post_id'=>$r['post_id'],
                'title'=>get_the_title($r['post_id']),
                'url'=>get_permalink($r['post_id']),
                'peso'=>$peso_custom,
                'score'=>$score
            ];
        }

        usort($results, fn($a,$b)=>$b['score']<=>$a['score']);
        return rest_ensure_response($results);
    }
}
