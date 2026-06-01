<?php
if (!defined('ABSPATH')) exit;

final class CMA_Gutenberg {

    public function __construct() {
        add_action('enqueue_block_editor_assets', [$this, 'cma_gutenberg_enqueue_editor_assets']);
        add_action('rest_api_init', [$this, 'cma_gutenberg_register_rest_routes']);
        add_action('save_post_post', [$this, 'cma_link_suggestions_clear_catalog_cache']);
        add_action('save_post_page', [$this, 'cma_link_suggestions_clear_catalog_cache']);
        add_action('trashed_post', [$this, 'cma_link_suggestions_clear_catalog_cache']);
        add_action('deleted_post', [$this, 'cma_link_suggestions_clear_catalog_cache']);
    }

    public function cma_gutenberg_enqueue_editor_assets(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen || !in_array($screen->post_type, ['post', 'page'], true)) {
            return;
        }

        wp_enqueue_script(
            'cma-editor-link-suggestions',
            CMA_URL . 'assets/editor-link-suggestions.js',
            [
                'wp-api-fetch',
                'wp-block-editor',
                'wp-blocks',
                'wp-components',
                'wp-compose',
                'wp-data',
                'wp-edit-post',
                'wp-element',
                'wp-i18n',
                'wp-plugins',
                'wp-rich-text',
            ],
            CMA_VERSION,
            true
        );

        wp_enqueue_style(
            'cma-editor-link-suggestions',
            CMA_URL . 'assets/editor-link-suggestions.css',
            [],
            CMA_VERSION
        );

        wp_localize_script('cma-editor-link-suggestions', 'CMAGutenbergSuggestions', [
            'restPath' => '/cma/v1/link-suggestions',
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'suggestedAnchors' => __('Suggested anchors', 'crea-maillage-audit'),
                'linkInserted' => __('Link inserted', 'crea-maillage-audit'),
                'insertLink' => __('Insert link', 'crea-maillage-audit'),
                'reselectText' => __('Select the text again in the content and retry.', 'crea-maillage-audit'),
                'detectedExpression' => __('Detected expression', 'crea-maillage-audit'),
                'addLink' => __('Add link', 'crea-maillage-audit'),
                'suggestionsPanel' => __('Internal linking suggestions', 'crea-maillage-audit'),
                'recommendationsHelp' => __('Recommended content to strengthen your internal linking.', 'crea-maillage-audit'),
                'analyzedSelection' => __('Analyzed selection', 'crea-maillage-audit'),
                'loading' => __('Analyzing...', 'crea-maillage-audit'),
                'loadingError' => __('Loading error.', 'crea-maillage-audit'),
                'runScanNotice' => __('Run a dashboard scan to enrich suggestions.', 'crea-maillage-audit'),
                'suggestionsFound' => __('Suggestions found', 'crea-maillage-audit'),
                'noSuggestion' => __('No relevant suggestions at this time.', 'crea-maillage-audit'),
                'detectedOpportunities' => __('Detected opportunities', 'crea-maillage-audit'),
            ],
        ]);
    }

    public function cma_gutenberg_register_rest_routes(): void {
        register_rest_route('cma/v1', '/link-suggestions', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => [$this, 'cma_editor_can_request_suggestions'],
            'callback' => [$this, 'cma_link_suggestions_rest_response'],
            'args' => [
                'postId' => [
                    'type' => 'integer',
                    'required' => false,
                    'sanitize_callback' => 'absint',
                ],
                'title' => $this->cma_editor_rest_text_arg(500),
                'slug' => $this->cma_editor_rest_text_arg(300),
                'content' => $this->cma_editor_rest_text_arg(250000),
                'selection' => $this->cma_editor_rest_text_arg(5000),
                'blockContext' => $this->cma_editor_rest_text_arg(5000),
            ],
        ]);
    }

    private function cma_editor_rest_text_arg(int $max_length): array {
        return [
            'type' => 'string',
            'required' => false,
            'validate_callback' => static function ($value) use ($max_length): bool {
                return is_string($value) && strlen($value) <= $max_length;
            },
        ];
    }

    public function cma_editor_can_request_suggestions(WP_REST_Request $request): bool {
        $post_id = (int)$request->get_param('postId');

        if ($post_id > 0) {
            return current_user_can('edit_post', $post_id);
        }

        return current_user_can('edit_posts');
    }

    public function cma_link_suggestions_rest_response(WP_REST_Request $request): WP_REST_Response {
        $payload = [
            'post_id' => (int)$request->get_param('postId'),
            'title' => sanitize_text_field((string)$request->get_param('title')),
            'slug' => sanitize_title((string)$request->get_param('slug')),
            'content' => wp_kses_post((string)$request->get_param('content')),
            'selection' => sanitize_text_field((string)$request->get_param('selection')),
            'block_context' => sanitize_text_field((string)$request->get_param('blockContext')),
        ];

        $scan_data = get_option(CMA_OPTION_KEY);
        $catalog = $this->cma_link_suggestions_get_published_catalog();
        $scan_version = is_array($scan_data) ? (string)($scan_data['generated_at'] ?? 'fallback') : 'fallback';
        $excluded_signature = implode(',', $this->cma_link_suggestions_get_excluded_ids());
        $hash = md5($scan_version . ':' . $catalog['signature'] . ':' . $excluded_signature . ':' . wp_json_encode($payload));
        $cache_key = 'cma_editor_suggestions_v12_' . $hash;
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return rest_ensure_response($cached);
        }

        $response = $this->cma_link_suggestions_get_recommendations($payload);
        set_transient($cache_key, $response, 10 * MINUTE_IN_SECONDS);

        return rest_ensure_response($response);
    }

    public function cma_link_suggestions_get_recommendations(array $context): array {
        require_once CMA_PATH . 'includes/class-cma-analyzer.php';

        $scan_data = $this->cma_link_suggestions_get_scan_data();
        $items = $scan_data['items'] ?? [];
        $excluded_lookup = array_flip($this->cma_link_suggestions_get_excluded_ids());
        $current_post_id = (int)($context['post_id'] ?? 0);
        $selection = trim((string)($context['selection'] ?? ''));
        $block_context = trim((string)($context['block_context'] ?? ''));
        $is_selection_context = $selection !== '';
        $context_text = $selection !== ''
            ? $selection
            : $this->cma_link_suggestions_extract_context_text($context);
        $article_context_text = $this->cma_link_suggestions_extract_context_text($context);

        $context_tokens = $this->cma_link_suggestions_extract_tokens($context_text);
        $selection_variants = $this->cma_link_suggestions_get_selection_variants($selection);
        $selection_variant_tokens = $this->cma_link_suggestions_extract_variant_tokens($selection_variants);
        $block_context_tokens = $this->cma_link_suggestions_extract_tokens($block_context);
        $has_rich_block_context = $is_selection_context
            && count($block_context_tokens) >= 2
            && $this->cma_link_suggestions_normalize_phrase($block_context) !== $this->cma_link_suggestions_normalize_phrase($selection);
        $article_context_tokens = $this->cma_link_suggestions_extract_tokens($article_context_text);
        $existing_linked_ids = $this->cma_link_suggestions_get_existing_linked_ids((string)($context['content'] ?? ''), $items);
        $cluster_map = $this->cma_link_suggestions_get_cluster_map($scan_data);
        $pagerank = $this->cma_link_suggestions_compute_pagerank($scan_data);
        $content_index = $this->cma_link_suggestions_get_content_index($items, $scan_data);

        $recommendations = [];
        $fallback_recommendations = [];
        $best_selection_match_priority = 0;

        foreach ($items as $target_id => $item) {
            $target_id = (int)$target_id;

            if ($target_id <= 0 || isset($excluded_lookup[$target_id]) || $target_id === $current_post_id || in_array($target_id, $existing_linked_ids, true)) {
                continue;
            }

            if (!in_array(($item['type'] ?? ''), ['post', 'page'], true)) {
                continue;
            }

            $target_title = (string)($item['title'] ?? '');
            $target_slug = str_replace('-', ' ', (string)($item['slug'] ?? ''));
            $target_content_text = (string)($content_index[$target_id] ?? '');
            $title_tokens = $this->cma_link_suggestions_extract_tokens($target_title);
            $slug_tokens = $this->cma_link_suggestions_extract_tokens($target_slug);
            $content_tokens = $this->cma_link_suggestions_extract_tokens($target_content_text);
            $title_similarity = $this->cma_link_suggestions_similarity_score($context_tokens, $title_tokens);
            $slug_similarity = $this->cma_link_suggestions_similarity_score($context_tokens, $slug_tokens);
            $content_similarity = $this->cma_link_suggestions_content_overlap_score($context_tokens, $content_tokens);
            $title_phrase_match_score = $this->cma_link_suggestions_phrase_match_score($context_text, $target_title);
            $slug_phrase_match_score = $this->cma_link_suggestions_phrase_match_score($context_text, $target_slug);
            $content_phrase_match_score = $this->cma_link_suggestions_phrase_match_score($context_text, $target_content_text);
            $phrase_match_score = max($title_phrase_match_score, $slug_phrase_match_score, $content_phrase_match_score);
            $semantic_score = max($title_similarity, $slug_similarity, $content_similarity, $phrase_match_score);
            $block_title_similarity = $has_rich_block_context
                ? $this->cma_link_suggestions_similarity_score($block_context_tokens, $title_tokens)
                : 0;
            $block_content_similarity = $has_rich_block_context
                ? $this->cma_link_suggestions_content_overlap_score($block_context_tokens, $content_tokens)
                : 0;
            $block_title_phrase_match_score = $has_rich_block_context
                ? $this->cma_link_suggestions_phrase_match_score($block_context, $target_title)
                : 0;
            $block_content_phrase_match_score = $has_rich_block_context
                ? $this->cma_link_suggestions_phrase_match_score($block_context, $target_content_text)
                : 0;
            $block_context_score = max(
                $block_title_similarity,
                $block_content_similarity,
                $block_title_phrase_match_score,
                $block_content_phrase_match_score
            );
            $title_exact_match = $this->cma_link_suggestions_contains_phrase($target_title, $selection);
            $slug_exact_match = $this->cma_link_suggestions_contains_phrase($target_slug, $selection);
            $title_selection_match = $this->cma_link_suggestions_contains_any_phrase($target_title, $selection_variants);
            $slug_selection_match = $this->cma_link_suggestions_contains_any_phrase($target_slug, $selection_variants);
            $content_selection_match = $this->cma_link_suggestions_contains_any_phrase($target_content_text, $selection_variants);
            $keyword_similarity = $this->cma_link_suggestions_similarity_score($selection_variant_tokens, $title_tokens);
            $title_token_coverage = $this->cma_link_suggestions_content_overlap_score($selection_variant_tokens, $title_tokens);
            $title_relevance_score = max($keyword_similarity, $title_token_coverage, $title_phrase_match_score);
            $selection_match_priority = 0;
            $direct_relevance_score = 0;
            $block_context_priority = 0;

            if ($is_selection_context) {
                /*
                 * The selected expression is the search query. Context may refine
                 * a result later, but it must never make an unrelated result eligible.
                 */
                if ($title_exact_match) {
                    $selection_match_priority = 6;
                    $direct_relevance_score = 100;
                } elseif ($title_selection_match) {
                    $selection_match_priority = 5;
                    $direct_relevance_score = 96;
                } elseif ($title_relevance_score >= 80) {
                    $selection_match_priority = 4;
                    $direct_relevance_score = max(88, min(94, $title_relevance_score));
                } elseif ($title_relevance_score >= 55) {
                    $selection_match_priority = 3;
                    $direct_relevance_score = max(76, min(87, $title_relevance_score));
                } elseif ($slug_exact_match) {
                    $selection_match_priority = 3;
                    $direct_relevance_score = 82;
                } elseif ($slug_selection_match) {
                    $selection_match_priority = 3;
                    $direct_relevance_score = 78;
                } elseif ($content_selection_match) {
                    $selection_match_priority = 2;
                    $direct_relevance_score = count($context_tokens) > 1 ? 62 : 54;
                } elseif (count($context_tokens) > 1 && $content_similarity >= 65) {
                    $selection_match_priority = 1;
                    $direct_relevance_score = max(50, min(59, $content_similarity));
                }
            }

            if ($has_rich_block_context) {
                if ($block_title_phrase_match_score >= 70 || $block_title_similarity >= 48) {
                    $block_context_priority = 2;
                } elseif ($block_content_phrase_match_score >= 70 || $block_content_similarity >= 58) {
                    $block_context_priority = 1;
                }
            }

            $best_selection_match_priority = max($best_selection_match_priority, $selection_match_priority);
            $article_context_score = max(
                $this->cma_link_suggestions_similarity_score($article_context_tokens, $title_tokens),
                $this->cma_link_suggestions_content_overlap_score($article_context_tokens, $content_tokens)
            );

            $same_cluster = $current_post_id > 0
                && !empty($cluster_map[$current_post_id])
                && !empty($cluster_map[$target_id])
                && (int)$cluster_map[$current_post_id]['id'] === (int)$cluster_map[$target_id]['id'];

            $incoming = (int)($scan_data['incoming_global'][$target_id] ?? 0);
            $posts_only_in = (int)($scan_data['posts_only_in'][$target_id] ?? 0);
            $outgoing = count($scan_data['out_internal'][$target_id] ?? []);
            $target_pagerank = (int)($pagerank[$target_id] ?? 0);

            $score = $this->cma_link_suggestions_calculate_score(
                $semantic_score,
                $same_cluster,
                $target_pagerank,
                $incoming,
                $outgoing,
                $posts_only_in,
                $is_selection_context
            );

            if ($title_similarity >= 50) {
                $score += 8;
            }

            if ($phrase_match_score >= 70) {
                $score += 12;
            }

            if ($is_selection_context) {
                // Context refines an explicit search query, but can never outweigh title relevance.
                $context_bonus = min(3, (int)round(
                    ($block_context_score * 0.02)
                    + ($article_context_score * 0.005)
                    + ($same_cluster ? 1 : 0)
                ));
                $score = $direct_relevance_score + $context_bonus;
            }

            $score = max(0, min(100, $score));

            $relations = $this->cma_link_suggestions_get_relation_types(
                $same_cluster,
                $semantic_score,
                $target_pagerank,
                $incoming,
                $outgoing,
                $posts_only_in
            );

            $recommendation = [
                'id' => $target_id,
                'title' => wp_strip_all_tags((string)($item['title'] ?? '')),
                'url' => (string)($item['url'] ?? get_permalink($target_id)),
                'score' => $score,
                'semanticScore' => $semantic_score,
                'contextScore' => $article_context_score,
                'blockContextScore' => $block_context_score,
                'directRelevanceScore' => $direct_relevance_score,
                'titleRelevanceScore' => $title_relevance_score,
                'selectionMatchPriority' => $selection_match_priority,
                'blockContextPriority' => $block_context_priority,
                'relations' => $relations,
                'reason' => $this->cma_link_suggestions_get_reason($relations, $same_cluster, $semantic_score),
                'anchors' => $this->cma_link_suggestions_generate_anchors($item, $context_tokens),
            ];

            $is_highly_relevant_selection = (
                $selection_match_priority >= 1
                && $direct_relevance_score >= 50
            );

            if (
                $is_selection_context
                    ? ($is_highly_relevant_selection && $score >= 50)
                    : ($semantic_score >= 18 && $score >= 30)
            ) {
                $recommendations[] = $recommendation;
            } else {
                // Fallback contextuel : utile pour compléter les résultats sans afficher de contenus arbitraires.
                $recommendation['score'] = max(10, min(49, (int)round(
                    ($article_context_score * 0.55) +
                    ($same_cluster ? 18 : 0) +
                    min(8, $target_pagerank / 12.5) +
                    (($incoming === 0 || $posts_only_in === 0) ? 4 : 0)
                )));
                $fallback_recommendations[] = $recommendation;
            }
        }

        if ($is_selection_context && $best_selection_match_priority >= 4) {
            $recommendations = array_values(array_filter($recommendations, static function ($recommendation): bool {
                return (int)($recommendation['selectionMatchPriority'] ?? 0) >= 3;
            }));
        }

        usort($recommendations, static function ($a, $b) use ($is_selection_context): int {
            if ($is_selection_context) {
                $priority_comparison = (int)($b['selectionMatchPriority'] ?? 0) <=> (int)($a['selectionMatchPriority'] ?? 0);
                if ($priority_comparison !== 0) {
                    return $priority_comparison;
                }

                $direct_comparison = (int)($b['directRelevanceScore'] ?? 0) <=> (int)($a['directRelevanceScore'] ?? 0);
                if ($direct_comparison !== 0) {
                    return $direct_comparison;
                }

                $title_comparison = (int)($b['titleRelevanceScore'] ?? 0) <=> (int)($a['titleRelevanceScore'] ?? 0);
                if ($title_comparison !== 0) {
                    return $title_comparison;
                }
            }

            $score_comparison = $b['score'] <=> $a['score'];
            if ($score_comparison !== 0) {
                return $score_comparison;
            }

            return (int)($b['blockContextPriority'] ?? 0) <=> (int)($a['blockContextPriority'] ?? 0);
        });
        usort($fallback_recommendations, static fn($a, $b) => $b['score'] <=> $a['score']);

        if (!$is_selection_context && count($recommendations) < 5) {
            $seen = array_fill_keys(array_map(static fn($item) => (int)$item['id'], $recommendations), true);

            foreach ($fallback_recommendations as $fallback) {
                if (isset($seen[(int)$fallback['id']])) {
                    continue;
                }

                $recommendations[] = $fallback;
                $seen[(int)$fallback['id']] = true;

                if (count($recommendations) >= 5) {
                    break;
                }
            }
        }

        $recommendations = array_slice($recommendations, 0, 5);

        return [
            'count' => count($recommendations),
            'selection' => $selection,
            'suggestions' => $recommendations,
            'opportunities' => $this->cma_link_suggestions_get_opportunities($context, $recommendations),
            'hasScanData' => !empty($items),
        ];
    }

    private function cma_link_suggestions_get_scan_data(): array {
        $scan_data = get_option(CMA_OPTION_KEY);
        $catalog = $this->cma_link_suggestions_get_published_catalog();

        if (is_array($scan_data) && !empty($scan_data['items'])) {
            return $this->cma_link_suggestions_merge_catalog_into_scan_data($scan_data, $catalog);
        }

        return [
            'items' => $catalog['items'],
            'catalog_signature' => $catalog['signature'],
            'incoming_global' => [],
            'posts_only_in' => [],
            'out_internal' => [],
            'edges' => [],
        ];
    }

    public function cma_link_suggestions_clear_catalog_cache(int $post_id = 0): void {
        if (
            $post_id > 0
            && (
                (function_exists('wp_is_post_autosave') && wp_is_post_autosave($post_id))
                || (function_exists('wp_is_post_revision') && wp_is_post_revision($post_id))
            )
        ) {
            return;
        }

        delete_transient('cma_editor_published_catalog_v1');
    }

    private function cma_link_suggestions_get_published_catalog(): array {
        $cached = get_transient('cma_editor_published_catalog_v1');

        if (is_array($cached) && isset($cached['items'], $cached['signature'])) {
            return $cached;
        }

        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ]);

        $items = [];
        $signature_parts = [];

        foreach ($posts as $post) {
            $items[$post->ID] = [
                'id' => $post->ID,
                'type' => $post->post_type,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'url' => get_permalink($post->ID),
            ];
            $signature_parts[] = $post->ID . ':' . $post->post_modified_gmt;
        }

        sort($signature_parts);

        $catalog = [
            'items' => $items,
            'signature' => md5(implode('|', $signature_parts)),
        ];

        set_transient('cma_editor_published_catalog_v1', $catalog, 15 * MINUTE_IN_SECONDS);

        return $catalog;
    }

    /**
     * Keep the latest published catalogue as the source of truth while reusing
     * graph metrics from the dashboard scan whenever they are still relevant.
     */
    private function cma_link_suggestions_merge_catalog_into_scan_data(array $scan_data, array $catalog): array {
        $items = isset($catalog['items']) && is_array($catalog['items']) ? $catalog['items'] : [];
        $allowed_ids = array_fill_keys(array_map('intval', array_keys($items)), true);

        $scan_data['items'] = $items;
        $scan_data['catalog_signature'] = isset($catalog['signature']) ? (string)$catalog['signature'] : '';

        foreach (['incoming_global', 'posts_only_in', 'out_external_count'] as $metric_key) {
            if (isset($scan_data[$metric_key]) && is_array($scan_data[$metric_key])) {
                $scan_data[$metric_key] = array_intersect_key($scan_data[$metric_key], $allowed_ids);
            }
        }

        foreach (['out_internal', 'posts_only_out'] as $links_key) {
            if (!isset($scan_data[$links_key]) || !is_array($scan_data[$links_key])) {
                continue;
            }

            $filtered_links = [];
            foreach ($scan_data[$links_key] as $source_id => $target_ids) {
                $source_id = (int)$source_id;
                if (!isset($allowed_ids[$source_id]) || !is_array($target_ids)) {
                    continue;
                }

                $filtered_links[$source_id] = array_values(array_filter(
                    array_map('intval', $target_ids),
                    static function (int $target_id) use ($allowed_ids): bool {
                        return isset($allowed_ids[$target_id]);
                    }
                ));
            }
            $scan_data[$links_key] = $filtered_links;
        }

        if (isset($scan_data['edges']) && is_array($scan_data['edges'])) {
            $scan_data['edges'] = array_values(array_filter(
                $scan_data['edges'],
                static function ($edge) use ($allowed_ids): bool {
                    return is_array($edge)
                        && isset($allowed_ids[(int)($edge['from'] ?? 0)])
                        && isset($allowed_ids[(int)($edge['to'] ?? 0)]);
                }
            ));
        }

        return $scan_data;
    }

    private function cma_link_suggestions_get_excluded_ids(): array {
        $raw = (string)get_option(CMA_EXCLUDED_IDS_OPTION, '');
        $parts = preg_split('/[\s,;]+/', $raw);

        if (!is_array($parts)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('absint', $parts))));
        sort($ids);

        return $ids;
    }

    private function cma_link_suggestions_get_content_index(array $items, array $scan_data): array {
        $ids = array_values(array_filter(array_map('intval', array_keys($items))));

        if (empty($ids)) {
            return [];
        }

        $cache_key = 'cma_editor_content_index_' . md5((string)($scan_data['generated_at'] ?? 'fallback') . ':' . (string)($scan_data['catalog_signature'] ?? '') . ':' . count($ids));
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__in' => $ids,
            'orderby' => 'post__in',
            'no_found_rows' => true,
        ]);

        $index = [];

        foreach ($posts as $post) {
            $headings = [];

            if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $post->post_content, $matches)) {
                foreach ($matches[1] as $heading) {
                    $headings[] = wp_strip_all_tags($heading);
                }
            }

            $plain_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
            $plain_content = function_exists('mb_substr')
                ? mb_substr($plain_content, 0, 6000, 'UTF-8')
                : substr($plain_content, 0, 6000);

            $index[$post->ID] = trim(implode(' ', [
                $post->post_title,
                $post->post_name,
                $post->post_excerpt,
                implode(' ', $headings),
                $plain_content,
            ]));
        }

        set_transient($cache_key, $index, 6 * HOUR_IN_SECONDS);

        return $index;
    }

    private function cma_link_suggestions_extract_context_text(array $context): string {
        $content = (string)($context['content'] ?? '');
        $headings = [];

        if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $content, $matches)) {
            foreach ($matches[1] as $heading) {
                $headings[] = wp_strip_all_tags($heading);
            }
        }

        return trim(implode(' ', [
            (string)($context['title'] ?? ''),
            (string)($context['slug'] ?? ''),
            implode(' ', $headings),
            wp_strip_all_tags($content),
        ]));
    }

    private function cma_link_suggestions_extract_tokens(string $text): array {
        $text = remove_accents(wp_strip_all_tags($text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', (string)$text);
        $text = str_replace('-', ' ', (string)$text);
        $parts = preg_split('/\s+/', trim((string)$text));

        if (!is_array($parts)) {
            return [];
        }

        $stopwords = ['les','des','une','un','dans','pour','avec','sans','sur','aux','par','que','qui','quoi','dont','est','sont','vous','nous','vos','nos','leur','leurs','plus','moins','tout','tous','toute','comment','faire','creer','creation','guide','tuto','tutoriel','meilleur','meilleure','exemple','exemples','wordpress','site','page','article'];

        return array_values(array_unique(array_filter($parts, static function($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords, true);
        })));
    }

    private function cma_link_suggestions_similarity_score(array $source_tokens, array $target_tokens): int {
        if (empty($source_tokens) || empty($target_tokens)) {
            return 0;
        }

        $common = array_intersect($source_tokens, $target_tokens);
        $coverage = count($common) / max(1, min(count($source_tokens), count($target_tokens)));
        $jaccard = count($common) / max(1, count(array_unique(array_merge($source_tokens, $target_tokens))));

        return (int)round(min(100, (($coverage * 70) + ($jaccard * 30))));
    }

    private function cma_link_suggestions_content_overlap_score(array $source_tokens, array $target_tokens): int {
        if (empty($source_tokens) || empty($target_tokens)) {
            return 0;
        }

        $common = array_intersect($source_tokens, $target_tokens);
        $coverage = count($common) / max(1, count($source_tokens));

        return (int)round(min(100, $coverage * 100));
    }

    private function cma_link_suggestions_phrase_match_score(string $source_text, string $target_text): int {
        $source = $this->cma_link_suggestions_normalize_phrase($source_text);
        $target = $this->cma_link_suggestions_normalize_phrase($target_text);

        if ($source === '' || $target === '') {
            return 0;
        }

        if (strpos($target, $source) !== false) {
            return 100;
        }

        $source_tokens = $this->cma_link_suggestions_extract_tokens($source);
        if (count($source_tokens) < 2) {
            return 0;
        }

        foreach ([3, 2] as $size) {
            for ($i = 0; $i <= count($source_tokens) - $size; $i++) {
                $phrase = implode(' ', array_slice($source_tokens, $i, $size));
                if (strpos($target, $phrase) !== false) {
                    return $size === 3 ? 85 : 70;
                }
            }
        }

        return 0;
    }

    private function cma_link_suggestions_contains_phrase(string $haystack, string $needle): bool {
        $haystack = $this->cma_link_suggestions_normalize_phrase($haystack);
        $needle = $this->cma_link_suggestions_normalize_phrase($needle);

        if ($haystack === '' || $needle === '') {
            return false;
        }

        return (bool)preg_match('/(?:^|\s)' . preg_quote($needle, '/') . '(?:$|\s)/', $haystack);
    }

    private function cma_link_suggestions_contains_any_phrase(string $haystack, array $needles): bool {
        foreach ($needles as $needle) {
            if ($this->cma_link_suggestions_contains_phrase($haystack, (string)$needle)) {
                return true;
            }
        }

        return false;
    }

    private function cma_link_suggestions_get_selection_variants(string $selection): array {
        $selection = $this->cma_link_suggestions_normalize_phrase($selection);
        if ($selection === '') {
            return [];
        }

        /*
         * Integrators can provide editorial synonyms without introducing a heavy
         * NLP dependency. Example: ['seo' => ['referencement naturel']].
         */
        $synonym_map = apply_filters('cma_link_suggestions_synonyms', []);
        $variants = [$selection];

        if (is_array($synonym_map)) {
            foreach ($synonym_map as $term => $synonyms) {
                if ($this->cma_link_suggestions_normalize_phrase((string)$term) !== $selection) {
                    continue;
                }

                foreach ((array)$synonyms as $synonym) {
                    $synonym = $this->cma_link_suggestions_normalize_phrase((string)$synonym);
                    if ($synonym !== '') {
                        $variants[] = $synonym;
                    }
                }
            }
        }

        return array_values(array_unique($variants));
    }

    private function cma_link_suggestions_extract_variant_tokens(array $variants): array {
        $tokens = [];

        foreach ($variants as $variant) {
            $tokens = array_merge($tokens, $this->cma_link_suggestions_extract_tokens((string)$variant));
        }

        return array_values(array_unique($tokens));
    }

    private function cma_link_suggestions_normalize_phrase(string $text): string {
        $text = remove_accents(wp_strip_all_tags($text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', (string)$text);
        $text = str_replace('-', ' ', (string)$text);

        return trim((string)preg_replace('/\s+/', ' ', (string)$text));
    }

    private function cma_link_suggestions_calculate_score(int $semantic_score, bool $same_cluster, int $pagerank, int $incoming, int $outgoing, int $posts_only_in, bool $is_selection_context = false): int {
        $score = min(40, $semantic_score * 0.4);
        $score += $same_cluster ? 20 : 0;
        $score += min(10, $pagerank / 10);
        $score += ($incoming === 0 || $posts_only_in === 0) ? 5 : 0;
        $score += ($outgoing >= 2 && $incoming >= 1) ? 10 : min(10, ($outgoing + $incoming) * 1.5);
        $score += ($semantic_score >= 55 && $pagerank >= 40) ? 15 : min(15, $semantic_score / 8);

        if ($is_selection_context && $semantic_score >= 25) {
            $score += 24;
        } elseif ($is_selection_context) {
            $score += 14;
        }

        return max(0, min(100, (int)round($score)));
    }

    private function cma_link_suggestions_get_relation_types(bool $same_cluster, int $semantic_score, int $pagerank, int $incoming, int $outgoing, int $posts_only_in): array {
        $relations = [];

        if ($same_cluster) $relations[] = __('Same cluster', 'crea-maillage-audit');
        if ($semantic_score >= 65) $relations[] = __('Related topic', 'crea-maillage-audit');
        if ($semantic_score >= 35 && $semantic_score < 65) $relations[] = __('Complementary', 'crea-maillage-audit');
        if ($pagerank >= 70) $relations[] = __('Pillar content', 'crea-maillage-audit');
        if ($incoming === 0 || $posts_only_in === 0) $relations[] = __('Orphan content to strengthen', 'crea-maillage-audit');
        if ($pagerank >= 45 && $outgoing >= 2) $relations[] = __('Strong SEO potential', 'crea-maillage-audit');

        return empty($relations) ? [__('Strategic content', 'crea-maillage-audit')] : array_values(array_unique($relations));
    }

    public function cma_link_suggestions_generate_anchors(array $item, array $context_tokens = []): array {
        $title = wp_strip_all_tags((string)($item['title'] ?? ''));
        $slug = str_replace('-', ' ', (string)($item['slug'] ?? ''));
        $tokens = $this->cma_link_suggestions_extract_tokens($title . ' ' . $slug);
        $common = array_values(array_intersect($context_tokens, $tokens));
        $anchors = [];

        if (count($common) >= 2) {
            $anchors[] = implode(' ', array_slice($common, 0, 3));
        }

        if ($title !== '') {
            $anchors[] = $title;
        }

        if (count($tokens) >= 2) {
            $anchors[] = implode(' ', array_slice($tokens, 0, 2));
            $anchors[] = implode(' ', array_slice($tokens, 0, 3));
        }

        if ($slug !== '') {
            $anchors[] = $slug;
        }

        $anchors = array_values(array_unique(array_filter(array_map('trim', $anchors))));

        return array_slice($anchors, 0, 4);
    }

    private function cma_link_suggestions_get_existing_linked_ids(string $content, array $items): array {
        $linked_ids = [];

        foreach ($items as $id => $item) {
            $url = (string)($item['url'] ?? '');
            if ($url !== '' && strpos($content, $url) !== false) {
                $linked_ids[] = (int)$id;
            }
        }

        return array_values(array_unique($linked_ids));
    }

    private function cma_link_suggestions_get_cluster_map(array $scan_data): array {
        if (empty($scan_data['items'])) {
            return [];
        }

        $threshold = (int)get_option('cma_cluster_threshold', 3);
        $cache_key = 'cma_editor_cluster_map_' . md5((string)($scan_data['generated_at'] ?? 'fallback') . ':' . (string)($scan_data['catalog_signature'] ?? '') . ':' . $threshold);
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        // Wrapper de lecture : on réutilise l'analyseur existant sans modifier son comportement.
        $analyzer = new CMA_Analyzer($scan_data);
        $clusters = $analyzer->get_clusters();
        $map = [];

        foreach ($clusters as $cluster) {
            $cluster_id = (int)($cluster['pillar']['id'] ?? 0);
            $cluster_title = (string)($cluster['pillar']['title'] ?? '');

            if ($cluster_id <= 0) {
                continue;
            }

            $map[$cluster_id] = ['id' => $cluster_id, 'title' => $cluster_title];

            foreach (($cluster['pages'] ?? []) as $page) {
                $page_id = (int)($page['id'] ?? 0);
                if ($page_id > 0) {
                    $map[$page_id] = ['id' => $cluster_id, 'title' => $cluster_title];
                }
            }
        }

        set_transient($cache_key, $map, 6 * HOUR_IN_SECONDS);

        return $map;
    }

    private function cma_link_suggestions_compute_pagerank(array $scan_data): array {
        $cache_key = 'cma_editor_pagerank_' . md5((string)($scan_data['generated_at'] ?? 'fallback') . ':' . (string)($scan_data['catalog_signature'] ?? ''));
        $cached = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $items = $scan_data['items'] ?? [];
        $edges = $scan_data['edges'] ?? [];
        $nodes = array_keys($items);
        $total = count($nodes);

        if ($total === 0) {
            return [];
        }

        $rank = [];
        $out = [];
        $incoming = [];

        foreach ($nodes as $id) {
            $rank[$id] = 1 / $total;
            $out[$id] = [];
            $incoming[$id] = [];
        }

        foreach ($edges as $edge) {
            $from = (int)($edge['from'] ?? 0);
            $to = (int)($edge['to'] ?? 0);

            if (isset($out[$from], $incoming[$to])) {
                $out[$from][] = $to;
                $incoming[$to][] = $from;
            }
        }

        for ($iteration = 0; $iteration < 40; $iteration++) {
            $new = [];
            $dangling = 0;

            foreach ($nodes as $id) {
                if (empty($out[$id])) {
                    $dangling += $rank[$id];
                }
            }

            foreach ($nodes as $id) {
                $sum = 0;
                foreach ($incoming[$id] as $from) {
                    $count = count($out[$from]);
                    if ($count > 0) {
                        $sum += $rank[$from] / $count;
                    }
                }
                $new[$id] = (0.15 / $total) + 0.85 * ($sum + $dangling / $total);
            }

            $rank = $new;
        }

        $max = max($rank);
        foreach ($rank as $id => $value) {
            $rank[$id] = $max > 0 ? (int)round(($value / $max) * 100) : 0;
        }

        set_transient($cache_key, $rank, 6 * HOUR_IN_SECONDS);

        return $rank;
    }

    private function cma_link_suggestions_get_reason(array $relations, bool $same_cluster, int $semantic_score): string {
        if ($same_cluster) {
            return __('This content belongs to the same cluster and strengthens silo consistency.', 'crea-maillage-audit');
        }

        if ($semantic_score >= 65) {
            return __('The topic is closely related to the content being edited.', 'crea-maillage-audit');
        }

        return __('This content complements the topic and can improve the internal journey.', 'crea-maillage-audit');
    }

    private function cma_link_suggestions_get_opportunities(array $context, array $recommendations): array {
        $content_tokens = $this->cma_link_suggestions_extract_tokens($this->cma_link_suggestions_extract_context_text($context));
        $counts = array_count_values($content_tokens);
        arsort($counts);
        $opportunities = [];

        foreach ($counts as $expression => $count) {
            if ($count < 2) {
                continue;
            }

            foreach ($recommendations as $recommendation) {
                $anchor_text = implode(' ', array_map('strtolower', $recommendation['anchors'] ?? []));

                if (strpos($anchor_text, strtolower($expression)) !== false) {
                    $opportunities[] = [
                        'expression' => $expression,
                        'title' => $recommendation['title'],
                        'url' => $recommendation['url'],
                        'anchor' => $recommendation['anchors'][0] ?? $expression,
                        'score' => $recommendation['score'],
                    ];
                    break;
                }
            }

            if (count($opportunities) >= 4) {
                break;
            }
        }

        return $opportunities;
    }
}
