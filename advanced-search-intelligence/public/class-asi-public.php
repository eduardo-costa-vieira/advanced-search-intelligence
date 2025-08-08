<?php
/**
 * Funções e classes do frontend do plugin.
 */
class ASI_Public {

    public function register_search_term() {
        // Lógica para redirecionar para a página de arquivo da categoria se apenas uma categoria for selecionada.
        // Isso deve acontecer antes do registro da busca, se a busca for por categoria e sem termo.
        if ( isset($_GET['categoria']) && !empty($_GET['categoria']) && ( !isset($_GET['s']) || empty($_GET['s']) ) ) {
            $category_id = intval($_GET['categoria']);
            $category_link = get_category_link($category_id);
            if ($category_link) {
                // Se a URL já é a da categoria, não redireciona novamente para evitar loop ou refresh desnecessário.
                $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                // Verifica se o category_link (ou uma parte dele sem query params) já está na URL atual
                $parsed_current_url = parse_url($current_url, PHP_URL_PATH);
                $parsed_category_link = parse_url($category_link, PHP_URL_PATH);

                if ( strpos($parsed_current_url, $parsed_category_link) === false ) {
                    wp_redirect($category_link);
                    exit;
                }
            }
        }

        // Continua com o registro da busca se houver um termo.
        // Esta parte deve ser executada apenas para buscas reais com termos, não para redirecionamentos de categoria.
        if (is_search() && isset($_GET['s']) && !empty($_GET['s'])) {
            global $wpdb;
            $termo = sanitize_text_field($_GET['s']);
            $categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
            $wpdb->insert($wpdb->prefix . 'custom_search_logs', [
                'termo' => $termo,
                'categoria' => $categoria,
            ]);
        }
    }

    public function apply_synonyms_to_search($query) {
        if ($query->is_search() && !is_admin() && $query->is_main_query()) {
            global $wpdb;
            $synonyms_table = $wpdb->prefix . 'asi_synonyms';
            $termo = $query->get('s');

            if (empty($termo)) {
                return $query;
            }

            // Normaliza o termo de busca para comparar com os sinônimos
            $normalized_term = strtolower(trim($termo));

            // Procura por um sinônimo exato
            $replacement = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT replacement_term FROM $synonyms_table WHERE search_term = %s",
                    $normalized_term
                )
            );

            // Se encontrou um sinônimo exato, substitui
            if ($replacement) {
                $query->set('s', $replacement);
            }

            // PONTO 4: Força a filtragem por categoria se uma categoria foi selecionada
            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $category_id = intval($_GET['categoria']);
                $query->set('cat', $category_id);
            }
        }
        return $query;
    }

    public function render_search_form() {
        $categories = get_categories(['hide_empty' => false]);
        $custom_title = get_option('asi_custom_title_text', 'Pesquisar na base de conhecimento...');
        $text_color = get_option('asi_text_color', '#000000');
        $title_font_size = get_option('asi_title_font_size', '24px');
        $no_results_message = get_option('asi_no_results_message', 'Sem retorno para essa busca, verifique o que foi digitado e a categoria selecionada.');


        ob_start(); ?>
        <div class="asi-search-wrapper">
            <h3 class="asi-custom-title" style="color: <?php echo esc_attr($text_color); ?>; font-size: <?php echo esc_attr($title_font_size); ?>;">
                <?php echo esc_html($custom_title); ?>
            </h3>
            <form method="get" action="<?php echo esc_url(home_url('/')); ?>" id="asi-search-form" target="_blank">
                <input type="text" name="s" id="asi-busca" placeholder="Digite sua busca..." autocomplete="off">
                <div id="asi-autocomplete-container">
                    <div id="asi-autocomplete-results" class="asi-autocomplete-results"></div>
                </div>
                <select name="categoria" id="asi-categoria">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= esc_attr($cat->term_id) ?>" <?php selected(isset($_GET['categoria']) ? $_GET['categoria'] : '', $cat->term_id); ?>><?= esc_html($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" id="asi-search-button">
                    <svg class="asi-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
            <div id="asi-search-message" class="asi-search-message"></div>
            <div id="asi-no-results-message" class="asi-no-results-message"></div>
        </div>
        <?php return ob_get_clean();
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'asi-public-css', ASI_PLUGIN_URL . 'assets/css/asi-public.css', array(), ASI_VERSION, 'all' );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'asi-autocomplete-js', ASI_PLUGIN_URL . 'assets/js/asi-autocomplete.js', array( 'jquery' ), ASI_VERSION, true );
        wp_localize_script( 'asi-autocomplete-js', 'asiAutocompleteAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asi_autocomplete_nonce'),
            'empty_search_message' => get_option('asi_empty_search_message', 'Preencha a sua busca'),
            'no_results_message' => get_option('asi_no_results_message', 'Sem retorno para essa busca, verifique o que foi digitado e a categoria selecionada.'), // Mensagem para "Nada encontrado"
            'is_search_page' => is_search(), // Informa se estamos em uma página de busca
            'has_posts' => have_posts(), // Informa se a busca retornou posts
            'category_selected' => isset($_GET['categoria']) && !empty($_GET['categoria']), // Informa se uma categoria foi selecionada
        ));
    }

    public function autocomplete_suggestions_callback() {
        check_ajax_referer('asi_autocomplete_nonce', 'nonce');

        if ( ! isset($_GET['term']) ) {
            wp_send_json_success([]);
        }

        global $wpdb;
        $search_term = sanitize_text_field($_GET['term']);
        $suggestions = [];

        // 1. Sugestão de Sinônimos (se houver match parcial ou completo)
        $synonyms_table = $wpdb->prefix . 'asi_synonyms';
        $synonym_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT replacement_term FROM $synonyms_table WHERE search_term = %s OR %s LIKE CONCAT('%%', search_term, '%%') LIMIT 1",
                strtolower(trim($search_term)), // Exato match
                '%' . $wpdb->esc_like(strtolower(trim($search_term))) . '%' // Match parcial
            )
        );

        $synonym_suggestion_text = '';
        if ($synonym_data) {
            $synonym_suggestion_text = $synonym_data->replacement_term;
        }

        // 2. Sugestões de Termos do Log (os 10 mais populares que contêm o termo)
        $logs_table = $wpdb->prefix . 'custom_search_logs';
        $log_terms = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT termo FROM $logs_table WHERE termo LIKE %s GROUP BY termo ORDER BY COUNT(termo) DESC LIMIT 10",
                '%' . $wpdb->esc_like($search_term) . '%'
            )
        );

        // 3. Sugestões de Títulos de Posts/Páginas (os 5 mais relevantes que contêm o termo)
        $post_suggestions = [];
        $args = array(
            'post_type'      => array('post', 'page'), // Tipos de posts que você quer incluir
            's'              => $search_term,
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'orderby'        => 'relevance',
            'order'          => 'DESC',
        );
        // PONTO 4: Incluir categoria na busca de autocomplete
        if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
            $args['cat'] = intval($_GET['categoria']);
        }

        $query_posts = new WP_Query($args);

        if ($query_posts->have_posts()) {
            while ($query_posts->have_posts()) {
                $query_posts->the_post();
                $post_suggestions[] = [
                    'title' => get_the_title(),
                    'link'  => get_permalink()
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'synonym_suggestion' => $synonym_suggestion_text,
            'log_terms' => $log_terms,
            'post_suggestions' => $post_suggestions,
            'search_term' => $search_term
        ]);
    }

    /**
     * Filtra o conteúdo dos posts em páginas de arquivo (incluindo categorias) para mostrar apenas o resumo.
     *
     * @param string $content O conteúdo original do post.
     * @return string O conteúdo truncado ou original.
     */
    public function display_excerpt_on_archives($content) {
        // Aplica apenas em páginas de arquivo (categoria, tag, autor, data, etc.) e na página de busca.
        if ( ( is_category() || is_tag() || is_author() || is_date() || is_archive() || is_search() ) && ! is_singular() ) {
            // Verifica se o post tem um resumo (excerpt) definido
            if (has_excerpt()) {
                $content = get_the_excerpt();
            } else {
                // Se não tiver resumo, trunca o conteúdo completo
                $excerpt_length = 55; // Número de palavras (pode ser ajustado)
                $content = wp_trim_words($content, $excerpt_length, ' [...]');
            }
            // PONTO 1: O link "Ler Instrução" foi removido daqui
        }
        return $content;
    }
}