<?php
// Evita acesso direto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe responsável por indexar posts para busca full-text.
 */
class FullText_Index_Indexer {

    private $table_name;
    private $semantic_analyzer;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fulltext_index';
        $this->semantic_analyzer = new FullText_Index_Semantic_Analyzer();

        add_action('save_post', [$this, 'index_post'], 10, 3);
    }

    /**
     * Indexa todos os posts publicáveis.
     */
    public function index_all_posts() {
        $post_types = get_post_types(['public' => true], 'names');

        $query = new WP_Query([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($query->posts as $post_id) {
            $this->index_post($post_id, get_post($post_id), true);
        }
    }

    /**
     * Indexa um único post.
     */
    public function index_post($post_id, $post, $update) {
        // Verifica se é revisão ou auto-save
        if ( wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) ) {
            return;
        }

        if ( ! in_array( $post->post_status, ['publish'] ) ) {
            return;
        }

        global $wpdb;

        $content = strip_tags( $post->post_content );
        $title   = $post->post_title;
        $author  = get_the_author_meta( 'display_name', $post->post_author );
        $tags    = wp_get_post_tags( $post_id, ['fields' => 'names'] );
        $tags_str = implode( ',', $tags );

        // Análise semântica do conteúdo
        $semantic_data = $this->semantic_analyzer->analyze_text( $title . ' ' . $content );
        $keywords = implode( ', ', $semantic_data['keywords'] );

        $wpdb->delete( $this->table_name, [ 'post_id' => $post_id ] );

        $wpdb->insert( $this->table_name, [
            'post_id'    => $post_id,
            'title'      => $title,
            'content'    => $content,
            'author'     => $author,
            'tags'       => $tags_str,
            'keywords'   => $keywords,
            'post_type'  => $post->post_type,
            'indexed_at' => current_time( 'mysql' )
        ] );
    }

    /**
     * Remove o índice de um post.
     */
    public function remove_index($post_id) {
        global $wpdb;
        $wpdb->delete($this->table_name, ['post_id' => $post_id]);
    }
}
