<?php
// Evita acesso direto
if (!defined('ABSPATH')) exit;

/**
 * Classe responsável por realizar análise semântica (TF-IDF) nos posts indexados.
 */
class FullText_Index_Semantic_Analyzer {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fulltext_index';

        // Agendando a análise diária
        add_action('fulltext_semantic_analysis_event', [$this, 'run_semantic_analysis']);
        if (!wp_next_scheduled('fulltext_semantic_analysis_event')) {
            wp_schedule_event(time(), 'daily', 'fulltext_semantic_analysis_event');
        }
    }

    /**
     * Executa a análise semântica completa.
     */
    public function run_semantic_analysis() {
        $posts = $this->get_all_posts_with_words();
        $idf = $this->calculate_idf($posts);

        foreach ($posts as $post_id => $words) {
            $tfidf_scores = [];

            $word_counts = array_count_values($words);
            $total_words = count($words);

            foreach ($word_counts as $word => $count) {
                $tf = $count / $total_words;
                $score = isset($idf[$word]) ? $tf * $idf[$word] : $tf;
                $tfidf_scores[$word] = round($score, 5);
            }

            $this->update_weights($post_id, $tfidf_scores);
        }
    }

    /**
     * Retorna todos os posts com suas palavras indexadas.
     */
    private function get_all_posts_with_words() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT post_id, palavra FROM {$this->table_name}", ARRAY_A);

        $posts = [];
        foreach ($results as $row) {
            $post_id = intval($row['post_id']);
            $palavra = sanitize_text_field($row['palavra']);
            $posts[$post_id][] = $palavra;
        }

        return $posts;
    }

    /**
     * Calcula o IDF (log(N / df)) para cada palavra.
     */
    private function calculate_idf($posts) {
        $doc_count = count($posts);
        $word_doc_freq = [];

        foreach ($posts as $words) {
            foreach (array_unique($words) as $word) {
                if (!isset($word_doc_freq[$word])) {
                    $word_doc_freq[$word] = 1;
                } else {
                    $word_doc_freq[$word]++;
                }
            }
        }

        $idf = [];
        foreach ($word_doc_freq as $word => $df) {
            $idf[$word] = log($doc_count / ($df + 1)) + 1; // Evita divisão por zero
        }

        return $idf;
    }

    /**
     * Atualiza os pesos na tabela.
     */
    private function update_weights($post_id, $tfidf_scores) {
        global $wpdb;

        foreach ($tfidf_scores as $word => $weight) {
            $wpdb->update(
                $this->table_name,
                ['peso' => $weight],
                ['post_id' => $post_id, 'palavra' => $word],
                ['%f'],
                ['%d', '%s']
            );
        }
    }

    /**
     * Permite reexecutar manualmente via botão (caso necessário)
     */
    public function trigger_now() {
        $this->run_semantic_analysis();
    }
}
