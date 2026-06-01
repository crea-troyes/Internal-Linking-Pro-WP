<?php
if (!defined('ABSPATH')) exit;

final class CMA_Analyzer {

    private array $data;
    private ?array $pagerank_cache = null;
    private array $rows_cache = [];
    private ?array $dashboard_metrics_cache = null;
    private ?array $clusters_cache = null;
    private ?array $link_suggestions_cache = null;

    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Calcul du score de maillage interne
     */
    private function compute_score(int $incoming, int $outgoing, bool $is_isolated): int {

        $score = 0;

        // Score entrants (max 60)
        if ($incoming == 0) $score += 0;
        elseif ($incoming <= 2) $score += 20;
        elseif ($incoming <= 5) $score += 40;
        else $score += 60;

        // Score sortants (max 40)
        if ($outgoing == 0) $score += 0;
        elseif ($outgoing <= 2) $score += 10;
        elseif ($outgoing <= 5) $score += 25;
        else $score += 40;

        // pénalité isolation
        if ($is_isolated) {
            $score -= 20;
        }

        if ($score < 0) $score = 0;
        if ($score > 100) $score = 100;

        return $score;
    }

    public function get_table_rows(string $filter): array {
        return $this->build_rows($filter, 'table');
    }

    public function get_orphans_global(string $filter): array {
        return $this->build_rows($filter, 'orphans');
    }

    public function get_isolated_posts(): array {

        $items = $this->data['items'] ?? [];
        $post_in  = $this->data['posts_only_in'] ?? [];
        $out_external_count = $this->data['out_external_count'] ?? [];
        $edges = $this->data['edges'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];
        $pagerank = $this->compute_pagerank();

        $rows = [];

        $pages_incoming = [];

        foreach ($edges as $e) {

            $from = (int)($e['from'] ?? 0);
            $to   = (int)($e['to'] ?? 0);

            if (($items[$from]['type'] ?? '') !== 'page') {
                continue;
            }

            if (!isset($pages_incoming[$to])) {
                $pages_incoming[$to] = 0;
            }

            $pages_incoming[$to]++;
        }

        foreach ($post_in as $id => $incoming_count) {

            if (($items[$id]['type'] ?? '') !== 'post') {
                continue;
            }

            if ($incoming_count == 0) {

                $out = count($this->data['posts_only_out'][$id] ?? []);
                $score = $this->compute_score($incoming_count, $out, true);

                $words = (int)($items[$id]['words'] ?? 0);

                $ratio = 0;
                if ($words > 0) {
                    $ratio = round(($out / $words) * 100, 2);
                }

                $rows[] = [
                    'type' => 'Article',
                    'title' => $items[$id]['title'] ?? '',
                    'url' => $items[$id]['url'] ?? '',
                    'out_int' => $out,
                    'in_int' => $post_in[$id] ?? 0,
                    'inpage_int' => $pages_incoming[$id] ?? 0,
                    'out_ext' => $out_external_count[$id] ?? 0,
                    'score' => $score,
                    'pagerank' => $pagerank[$id] ?? 0,
                    'ratio_links' => $ratio,
                    'words' => $words
                ];
            }
        }

        return $rows;
    }

    public function get_graph_payload(string $filter): array {
        $items = $this->data['items'] ?? [];
        $edges = $this->data['edges'] ?? [];
        $posts_only_in = $this->data['posts_only_in'] ?? [];

        $nodes = [];

        foreach ($items as $id => $it) {

            $type = $it['type'] ?? '';
            if (!$this->match_filter($type, $filter)) continue;

            $is_isolated = ($type === 'post' && ($posts_only_in[$id] ?? 0) == 0);

            $nodes[] = [
                'id' => (int)$id,
                'label' => $it['title'] ?? '',
                'type' => $type,
                'url' => $it['url'] ?? '',
                'is_isolated' => $is_isolated,
            ];
        }

        $allowed = array_flip(array_map(fn($n) => $n['id'], $nodes));

        $filtered_edges = [];

        foreach ($edges as $e) {

            $from = (int)($e['from'] ?? 0);
            $to = (int)($e['to'] ?? 0);

            if (isset($allowed[$from], $allowed[$to])) {
                $filtered_edges[] = ['from' => $from, 'to' => $to];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $filtered_edges,
        ];
    }

    private function build_rows(string $filter, string $mode): array {
        $cache_key = $filter . ':' . $mode;
        if (isset($this->rows_cache[$cache_key])) {
            return $this->rows_cache[$cache_key];
        }

        $items = $this->data['items'] ?? [];
        $incoming_global = $this->data['incoming_global'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];
        $out_external_count = $this->data['out_external_count'] ?? [];
        $posts_only_in = $this->data['posts_only_in'] ?? [];
        $pagerank = $this->compute_pagerank();

        $rows = [];

        foreach ($items as $id => $it) {

            $type = $it['type'] ?? '';

            if (!$this->match_filter($type, $filter)) continue;

            $in = (int)($incoming_global[$id] ?? 0);
            $out_int = count($out_internal[$id] ?? []);
            $out_ext = (int)($out_external_count[$id] ?? 0);

            $words = (int)($items[$id]['words'] ?? 0);

            $ratio = 0;

            if ($words > 0) {
                $ratio = round(($out_int / $words) * 100, 2);
            }

            if ($mode === 'orphans' && $in !== 0) continue;

            $is_isolated = false;

            if ($type === 'post') {
                if (($posts_only_in[$id] ?? 0) == 0) {
                    $is_isolated = true;
                }
            }

            $score = $this->compute_score($in, $out_int, $is_isolated);
            $score_pagerank = $pagerank[$id] ?? 0;

            $rows[] = [
                'type' => ($type === 'page') ? 'Page' : 'Article',
                'title' => strip_tags($it['title']) ?? '',
                'url' => $it['url'] ?? '',
                'out_int' => $out_int,
                'in_int' => $in,
                'out_ext' => $out_ext,
                'is_isolated' => $is_isolated,
                'score' => $score,
                'pagerank' => $score_pagerank,
                'ratio_links' => $ratio,
                'words' => $words
            ];
        }

        $this->rows_cache[$cache_key] = $rows;

        return $rows;
    }

    private function match_filter(string $type, string $filter): bool {

        if ($filter === 'both') return in_array($type, ['post','page'], true);
        if ($filter === 'post') return $type === 'post';
        if ($filter === 'page') return $type === 'page';

        return true;
    }

    private function compute_pagerank(): array {
        if ($this->pagerank_cache !== null) {
            return $this->pagerank_cache;
        }

        $items = $this->data['items'] ?? [];
        $edges = $this->data['edges'] ?? [];

        $nodes = array_keys($items);
        $N = count($nodes);

        if ($N === 0) {
            $this->pagerank_cache = [];
            return [];
        }

        $d = 0.85;

        $rank = [];
        $out = [];
        $incoming = [];

        // initialisation
        foreach ($nodes as $id) {
            $rank[$id] = 1 / $N;
            $out[$id] = [];
            $incoming[$id] = [];
        }

        // construction du graphe
        foreach ($edges as $e) {

            $from = isset($e['from']) ? (int)$e['from'] : 0;
            $to   = isset($e['to']) ? (int)$e['to'] : 0;

            if (!isset($out[$from]) || !isset($incoming[$to])) {
                continue;
            }

            $out[$from][] = $to;
            $incoming[$to][] = $from;
        }

        // paramètres de convergence
        $epsilon = 0.00001;
        $delta = 1;
        $max_iterations = 100;
        $iteration = 0;

        while ($delta > $epsilon && $iteration < $max_iterations) {

            $new = [];
            $delta = 0;

            // calcul du poids des pages sans liens sortants
            $dangling_sum = 0;

            foreach ($nodes as $n) {
                if (count($out[$n]) === 0) {
                    $dangling_sum += $rank[$n];
                }
            }

            foreach ($nodes as $node) {

                $sum = 0;

                foreach ($incoming[$node] as $from) {

                    $count = count($out[$from]);

                    if ($count > 0) {
                        $sum += $rank[$from] / $count;
                    }
                }

                $new[$node] =
                    (1 - $d) / $N +
                    $d * ($sum + $dangling_sum / $N);

                $delta += abs($new[$node] - $rank[$node]);
            }

            $rank = $new;
            $iteration++;
        }

        // normalisation pour affichage (0 → 100)
        $max = max($rank);

        if ($max == 0) {
            foreach ($rank as $id => $v) {
                $rank[$id] = 0;
            }
            $this->pagerank_cache = $rank;
            return $rank;
        }

        foreach ($rank as $id => $v) {
            $rank[$id] = round(($v / $max) * 100);
        }

        $this->pagerank_cache = $rank;

        return $rank;
    }


    public function get_dead_end_pages(string $filter): array {

        $items = $this->data['items'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];
        $out_external_count = $this->data['out_external_count'] ?? [];
        $incoming_global = $this->data['incoming_global'] ?? [];
        $posts_only_in = $this->data['posts_only_in'] ?? [];
        $pagerank = $this->compute_pagerank();

        $rows = [];

        foreach ($items as $id => $it) {

            $type = $it['type'] ?? '';

            if (!$this->match_filter($type, $filter)) {
                continue;
            }

            $out_int = count($out_internal[$id] ?? []);
            $out_ext = (int)($out_external_count[$id] ?? 0);

            // aucune sortie interne ni externe
            if ($out_int !== 0 || $out_ext !== 0) {
                continue;
            }

            $in = (int)($incoming_global[$id] ?? 0);

            $is_isolated = false;

            if ($type === 'post') {
                if (($posts_only_in[$id] ?? 0) == 0) {
                    $is_isolated = true;
                }
            }

            $score = $this->compute_score($in, $out_int, $is_isolated);

            $rows[] = [
                'type' => ($type === 'page') ? 'Page' : 'Article',
                'title' => $it['title'] ?? '',
                'url' => $it['url'] ?? '',
                'out_int' => $out_int,
                'in_int' => $in,
                'out_ext' => $out_ext,
                'is_isolated' => $is_isolated,
                'score' => $score,
                'pagerank' => $pagerank[$id] ?? 0,
                'ratio_links' => 0,
                'words' => 0
            ];
        }

        return $rows;
    }



    public function get_dashboard_metrics(): array {
        if ($this->dashboard_metrics_cache !== null) {
            return $this->dashboard_metrics_cache;
        }

        $rows = $this->build_rows('both', 'table');

        $total = count($rows);
        $internal_links = 0;
        $total_words = 0;
        $orphans = 0;
        $without_internal_outgoing = 0;
        $isolated_posts = 0;
        $strong_pages = 0;
        $incoming_depth_sum = 0;
        $outgoing_depth_sum = 0;

        foreach ($rows as $r) {
            $incoming = (int)$r['in_int'];
            $outgoing = (int)$r['out_int'];

            $internal_links += $outgoing;
            $total_words += $r['words'];
            $incoming_depth_sum += min($incoming, 10) / 10;
            $outgoing_depth_sum += min($outgoing, 6) / 6;

            if ($incoming === 0) {
                $orphans++;
            }

            if ($outgoing === 0) {
                $without_internal_outgoing++;
            }

            if (!empty($r['is_isolated'])) {
                $isolated_posts++;
            }

            if ($incoming > 0 && $outgoing > 0) {
                $strong_pages++;
            }
        }

        // Score global volontairement exigeant : la couverture seule ne suffit pas, il faut aussi une profondeur correcte.
        $incoming_coverage_score = $total ? (($total - $orphans) / $total) * 15 : 0;
        $outgoing_coverage_score = $total ? (($total - $without_internal_outgoing) / $total) * 10 : 0;
        $incoming_depth_score = $total ? ($incoming_depth_sum / $total) * 25 : 0;
        $outgoing_depth_score = $total ? ($outgoing_depth_sum / $total) * 20 : 0;
        $isolation_score = $total ? (($total - $isolated_posts) / $total) * 10 : 0;
        $balanced_pages_score = $total ? ($strong_pages / $total) * 10 : 0;

        $links_per_100_words = 0;

        if ($total_words > 0) {
            $links_per_100_words = round(($internal_links / $total_words) * 100, 2);
        }

        if ($links_per_100_words <= 0) {
            $density_score = 0;
        } elseif ($links_per_100_words < 0.8) {
            $density_score = ($links_per_100_words / 0.8) * 10;
        } elseif ($links_per_100_words <= 1.8) {
            $density_score = 10;
        } else {
            $density_score = max(4, 10 - min(6, ($links_per_100_words - 1.8) * 2));
        }

        $global_score = (int)round(
            $incoming_coverage_score +
            $outgoing_coverage_score +
            $incoming_depth_score +
            $outgoing_depth_score +
            $isolation_score +
            $balanced_pages_score +
            $density_score
        );
        $global_score = max(0, min(100, $global_score));
        $avg_links = $total ? round($internal_links / $total, 2) : 0;

        // pages les plus liées
        $sorted = $rows;

        usort($sorted, function($a, $b){
            return $b['in_int'] <=> $a['in_int'];
        });

        $top_pages = array_slice($sorted, 0, 10);

        // clusters
        $clusters = $this->get_clusters();

        $this->dashboard_metrics_cache = [
            'global_score' => $global_score,
            'avg_links' => $avg_links,
            'links_per_100_words' => $links_per_100_words,
            'top_pages' => $top_pages,
            'clusters' => $clusters,
            'rows' => $rows,
            'orphans' => $orphans,
            'without_internal_outgoing' => $without_internal_outgoing,
            'isolated_posts' => $isolated_posts,
            'strong_pages' => $strong_pages,
            'score_components' => [
                'incoming_coverage' => round($incoming_coverage_score, 2),
                'outgoing_coverage' => round($outgoing_coverage_score, 2),
                'incoming_depth' => round($incoming_depth_score, 2),
                'outgoing_depth' => round($outgoing_depth_score, 2),
                'isolation' => round($isolation_score, 2),
                'balanced_pages' => round($balanced_pages_score, 2),
                'density' => round($density_score, 2),
            ],
        ];

        return $this->dashboard_metrics_cache;
    }




    public function get_clusters(): array {
        if ($this->clusters_cache !== null) {
            return $this->clusters_cache;
        }

        $items = $this->data['items'] ?? [];
        $edges = $this->data['edges'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];
        $pagerank = $this->compute_pagerank();

        $total_pages = count($items);

        $threshold = (int) get_option('cma_cluster_threshold', 3);

        // seuil dynamique minimum
        $dynamic_min = max(3, round($total_pages * 0.005));

        // on prend le plus grand des deux
        $threshold = max($threshold, $dynamic_min);

        $incoming = [];
        $anchors_by_target_source = [];

        foreach ($edges as $e) {

            $from = (int)$e['from'];
            $to   = (int)$e['to'];

            if (!isset($incoming[$to])) {
                $incoming[$to] = [];
            }

            $incoming[$to][] = $from;

            $anchor = trim((string)($e['anchor'] ?? ''));
            if ($anchor !== '') {
                $anchors_by_target_source[$to][$from][] = $anchor;
            }
        }

        $clusters = [];

        foreach ($incoming as $pillar_id => $sources) {
            $sources = array_values(array_unique(array_map('intval', $sources)));

            if (count($sources) < $threshold) {
                continue;
            }

            $pages = [];

            foreach ($sources as $sid) {

                if (!isset($items[$sid])) continue;

                $anchors = array_values(array_unique($anchors_by_target_source[$pillar_id][$sid] ?? []));

                $pages[] = [
                    'id' => $sid,
                    'title' => $items[$sid]['title'],
                    'url' => $items[$sid]['url'],
                    'pagerank' => $pagerank[$sid] ?? 0,
                    'anchors' => $anchors,
                ];
            }

            // === 1. PageRank moyen ===
            $pr_total = 0;

            foreach ($pages as $p) {
                $pr_total += $p['pagerank'];
            }

            $avg_pr = count($pages) ? $pr_total / count($pages) : 0;


            // === 2. Densité interne ===
            $internal_links = 0;
            $source_lookup = array_flip($sources);

            foreach ($sources as $source_id) {
                foreach (($out_internal[$source_id] ?? []) as $target_id) {
                    if (isset($source_lookup[(int)$target_id])) {
                        $internal_links++;
                    }
                }
            }

            $density = count($pages) ? $internal_links / count($pages) : 0;
            $density_score = min(100, $density * 20);


            // === 3. Liens vers le pillar ===
            $links_to_pillar = count($sources);

            $link_ratio = count($pages) ? $links_to_pillar / count($pages) : 0;
            $link_score = $link_ratio * 100;


            // === 4. Taille du cluster ===
            $size_score = min(100, count($pages) * 5);


            // === 5. Score final ===
            $score = (
                0.4 * $avg_pr +
                0.3 * $density_score +
                0.2 * $link_score +
                0.1 * $size_score
            );


            // === 6. Petite pénalité SEO ===
            if ($link_ratio < 0.3) {
                $score -= 10;
            }

            // bornage
            $score = round(max(0, min(100, $score)));

            $clusters[] = [
                'pillar' => [
                    'id' => $pillar_id,
                    'title' => $items[$pillar_id]['title'],
                    'url' => $items[$pillar_id]['url'],
                    'pagerank' => $pagerank[$pillar_id] ?? 0
                ],
                'pages' => $pages,
                'pages_count' => count($pages),
                'score' => $score
            ];
        }

        usort($clusters, function($a,$b){
            return $b['score'] <=> $a['score'];
        });

        $this->clusters_cache = $clusters;

        return $clusters;
    }


    public function get_link_suggestions(): array {
        if ($this->link_suggestions_cache !== null) {
            return $this->link_suggestions_cache;
        }

        $items = $this->data['items'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];

        $suggestions = [];
        $candidate_pairs = $this->cma_build_title_candidate_pairs($items, 8000);

        foreach ($candidate_pairs as $pair) {
            $idA = $pair[0];
            $idB = $pair[1];

            if (empty($items[$idA]) || empty($items[$idB])) {
                continue;
            }

            foreach ([[$idA, $idB], [$idB, $idA]] as $direction) {
                $from_id = $direction[0];
                $to_id = $direction[1];
                $a = $items[$from_id];
                $b = $items[$to_id];

                // déjà lié → skip
                if (in_array($to_id, $out_internal[$from_id] ?? [])) continue;

                // même type (post/page)
                if (($a['type'] ?? '') !== ($b['type'] ?? '')) continue;

                // similarité simple (title)
                similar_text(
                    strtolower($a['title']),
                    strtolower($b['title']),
                    $percent
                );

                if ($percent > 60) {

                    $suggestions[] = [
                        'from' => $a['title'],
                        'to'   => $b['title'],
                        'url_from' => $a['url'],
                        'url_to'   => $b['url'],
                        'score' => round($percent)
                    ];
                }
            }
        }

        // tri par pertinence
        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        $this->link_suggestions_cache = array_slice($suggestions, 0, 50);

        return $this->link_suggestions_cache;
    }

    private function cma_build_title_candidate_pairs(array $items, int $limit): array {
        $pairs = [];
        $index = [];

        foreach ($items as $id => $item) {
            $tokens = $this->cma_light_tokens((string)($item['title'] ?? ''));

            foreach ($tokens as $token) {
                $index[$token][] = (int)$id;
            }
        }

        foreach ($index as $ids) {
            $ids = array_values(array_unique($ids));

            if (count($ids) < 2 || count($ids) > 80) {
                continue;
            }

            for ($i = 0; $i < count($ids); $i++) {
                for ($j = $i + 1; $j < count($ids); $j++) {
                    $a = min($ids[$i], $ids[$j]);
                    $b = max($ids[$i], $ids[$j]);
                    $pairs[$a . ':' . $b] = [$a, $b];

                    if (count($pairs) >= $limit) {
                        return array_values($pairs);
                    }
                }
            }
        }

        return array_values($pairs);
    }

    private function cma_light_tokens(string $text): array {
        $text = remove_accents(wp_strip_all_tags($text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);
        $text = str_replace('-', ' ', (string)$text);
        $parts = preg_split('/\s+/', trim((string)$text));

        if (!is_array($parts)) {
            return [];
        }

        $stopwords = ['les','des','une','un','dans','pour','avec','sans','sur','aux','par','que','qui','comment','faire','guide','tuto','wordpress','site','page','article'];

        return array_values(array_unique(array_filter($parts, static function($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords, true);
        })));
    }



    public function analyze_silo(array $cluster): array {

        $out_internal = $this->data['out_internal'] ?? [];
        $out_external_count = $this->data['out_external_count'] ?? [];
        $items = $this->data['items'] ?? [];

        $pages_ids = array_values(array_unique(array_map('intval', array_column($cluster['pages'], 'id'))));
        $pillar_id = (int)($cluster['pillar']['id'] ?? 0);
        $silo_ids = array_values(array_unique(array_merge($pages_ids, [$pillar_id])));
        $silo_lookup = array_flip($silo_ids);
        $pages_lookup = array_flip($pages_ids);

        $intra_silo_links = 0;
        $inter_silo_links = 0;
        $external_links = 0;
        $links_to_pillar = 0;
        $pages_with_pillar_link = [];
        $pages_with_internal_link = [];
        $pages_with_lateral_link = [];
        $pages_linked_from_pillar = [];

        // Les cibles de out_internal sont dédupliquées par page source : un lien
        // répété dans un même article ne doit pas gonfler artificiellement les taux.
        foreach ($silo_ids as $from) {
            foreach (($out_internal[$from] ?? []) as $target_id) {
                $to = (int)$target_id;

                if (isset($silo_lookup[$to])) {
                    $intra_silo_links++;

                    if (isset($pages_lookup[$from])) {
                        $pages_with_internal_link[$from] = true;
                    }

                    if (isset($pages_lookup[$from]) && $to !== $pillar_id) {
                        $pages_with_lateral_link[$from] = true;
                    }

                    if ($from === $pillar_id && isset($pages_lookup[$to])) {
                        $pages_linked_from_pillar[$to] = true;
                    }
                } elseif (isset($items[$to])) {
                    $inter_silo_links++;
                }

                if ($to === $pillar_id && isset($pages_lookup[$from])) {
                    $links_to_pillar++;
                    $pages_with_pillar_link[$from] = true;
                }
            }
        }

        foreach ($silo_ids as $pid) {
            $external_links += (int)($out_external_count[$pid] ?? 0);
        }

        $page_count = max(1, count($pages_ids));
        $internal_outgoing = $intra_silo_links + $inter_silo_links;
        $total_outgoing = $internal_outgoing + $external_links;
        $retention = $internal_outgoing ? (int)round(($intra_silo_links / $internal_outgoing) * 100) : 0;
        $fuite = $internal_outgoing ? (int)round(($inter_silo_links / $internal_outgoing) * 100) : 0;
        $pillar_coverage = (int)round((count($pages_with_pillar_link) / $page_count) * 100);
        $internal_coverage = (int)round((count($pages_with_internal_link) / $page_count) * 100);
        $lateral_coverage = (int)round((count($pages_with_lateral_link) / $page_count) * 100);
        $pillar_redistribution = (int)round((count($pages_linked_from_pillar) / $page_count) * 100);
        $external_ratio = $total_outgoing ? (int)round(($external_links / $total_outgoing) * 100) : 0;

        // La cohérence mesure la solidité éditoriale du silo. La rétention reste
        // majoritaire, mais un silo en étoile limité à pages -> pilier ne vaut
        // pas un ensemble réellement structuré et parcourable dans les deux sens.
        $coherence = (int)round(
            (0.50 * $retention)
            + (0.15 * $internal_coverage)
            + (0.20 * $lateral_coverage)
            + (0.15 * $pillar_redistribution)
        );

        $issues = [];
        $recommendations = [];

        if ($internal_outgoing === 0) {
            $issues[] = __('No usable internal links detected in this silo.', 'crea-maillage-audit');
            $recommendations[] = __('Add contextual links between the pillar page and the silo member pages.', 'crea-maillage-audit');
        } elseif ($coherence < 50) {
            $issues[] = sprintf(__('Weak internal structure: %d%% consistency.', 'crea-maillage-audit'), $coherence);
            $recommendations[] = __('Prioritize links between related content and return links from the pillar page.', 'crea-maillage-audit');
        } elseif ($coherence < 70) {
            $issues[] = sprintf(__('Internal structure needs improvement: %d%% consistency.', 'crea-maillage-audit'), $coherence);
            $recommendations[] = __('Add contextual links between complementary pages to strengthen the silo journey.', 'crea-maillage-audit');
        }

        if ($fuite > 30) {
            $issues[] = sprintf(__('High internal leakage: %d%% of internal links leave the silo.', 'crea-maillage-audit'), $fuite);
            $recommendations[] = __('Review links to other topics: keep useful links and strengthen links within the silo to rebalance distribution.', 'crea-maillage-audit');
        } elseif ($fuite > 15) {
            $issues[] = sprintf(__('Internal leakage to monitor: %d%% of internal links leave the silo.', 'crea-maillage-audit'), $fuite);
            $recommendations[] = __('Check that links to other silos are intentional and add a relevant internal link when it improves the journey.', 'crea-maillage-audit');
        }

        if ($pillar_redistribution < 40) {
            $issues[] = sprintf(__('Pillar page distributes too little: it links to %d%% of the silo pages.', 'crea-maillage-audit'), $pillar_redistribution);
            $recommendations[] = __('Add links from the pillar page to priority member pages to distribute internal authority more effectively.', 'crea-maillage-audit');
        }

        if ($lateral_coverage < 35 && count($pages_ids) > 1) {
            $issues[] = sprintf(__('Insufficient lateral links: only %d%% of pages link to complementary silo content.', 'crea-maillage-audit'), $lateral_coverage);
            $recommendations[] = __('Link member pages when they answer complementary questions without adding artificial links.', 'crea-maillage-audit');
        }

        if ($external_links >= $page_count && $external_ratio > 55) {
            $issues[] = sprintf(__('Many external links: %d%% of outgoing links go to third-party sites.', 'crea-maillage-audit'), $external_ratio);
            $recommendations[] = __('Keep useful external sources, but ensure they do not overshadow links to your related content.', 'crea-maillage-audit');
        }

        if (empty($issues)) {
            $recommendations[] = __('Consistent silo: maintain the linking structure and monitor future publications.', 'crea-maillage-audit');
        }

        return [
            'coherence' => $coherence,
            'fuite' => $fuite,
            'issues' => $issues,
            'recommendations' => array_values(array_unique($recommendations)),
            'links_to_pillar' => $links_to_pillar,
            'pillar_coverage' => $pillar_coverage,
            'internal_coverage' => $internal_coverage,
            'lateral_coverage' => $lateral_coverage,
            'pillar_redistribution' => $pillar_redistribution,
            'retention' => $retention,
            'intra_silo_links' => $intra_silo_links,
            'inter_silo_links' => $inter_silo_links,
            'external_links' => $external_links,
            'external_ratio' => $external_ratio,
        ];
    }

    public function cma_conflicts_get_cached_analysis(bool $force = false): array {
        $scan_generated_at = (int)($this->data['generated_at'] ?? 0);
        $excluded_hash = md5(wp_json_encode($this->data['excluded_ids'] ?? []));
        $algorithm_version = 2;
        $cached = get_transient('cma_conflicts_cache');

        // Cache lié au dernier scan pour éviter une comparaison pair-à-pair à chaque chargement.
        if (
            !$force
            && is_array($cached)
            && (int)($cached['scan_generated_at'] ?? 0) === $scan_generated_at
            && (string)($cached['excluded_hash'] ?? '') === $excluded_hash
            && (int)($cached['algorithm_version'] ?? 0) === $algorithm_version
        ) {
            return $cached;
        }

        $analysis = $this->cma_conflicts_analyze_cannibalization();
        $analysis['scan_generated_at'] = $scan_generated_at;
        $analysis['excluded_hash'] = $excluded_hash;
        $analysis['algorithm_version'] = $algorithm_version;

        set_transient('cma_conflicts_cache', $analysis, 12 * HOUR_IN_SECONDS);

        return $analysis;
    }

    public function cma_conflicts_analyze_cannibalization(): array {
        // Wrappers dédiés à l'onglet Conflits : aucune méthode existante n'est modifiée.
        $items = $this->cma_conflicts_get_articles_data();
        $anchors_by_target = $this->cma_conflicts_get_anchor_data();
        $sources_by_target = $this->cma_conflicts_get_incoming_sources_data();
        $cluster_map = $this->cma_conflicts_get_cluster_map();
        $scores = $this->cma_conflicts_get_article_scores();

        $candidate_pairs = $this->cma_conflicts_get_candidate_pairs($items, $anchors_by_target, $sources_by_target, $cluster_map);
        $conflicts = [];

        foreach ($candidate_pairs as $pair) {
                $id_a = (int)$pair[0];
                $id_b = (int)$pair[1];

                if (empty($items[$id_a]) || empty($items[$id_b])) {
                    continue;
                }

                $article_a = $items[$id_a];
                $article_b = $items[$id_b];

                $title_similarity = $this->cma_conflicts_compare_text($article_a['title'], $article_b['title']);
                $slug_similarity = $this->cma_conflicts_compare_text($article_a['slug'], $article_b['slug']);
                $common_anchors = $this->cma_conflicts_find_common_anchors(
                    $anchors_by_target[$id_a] ?? [],
                    $anchors_by_target[$id_b] ?? []
                );
                $common_expressions = $this->cma_conflicts_find_common_expressions($article_a, $article_b);

                $same_cluster = !empty($cluster_map[$id_a]['id'])
                    && !empty($cluster_map[$id_b]['id'])
                    && (int)$cluster_map[$id_a]['id'] === (int)$cluster_map[$id_b]['id'];

                $common_sources = array_values(array_intersect(
                    $sources_by_target[$id_a] ?? [],
                    $sources_by_target[$id_b] ?? []
                ));

                $anchor_signal = min(100, count($common_anchors) * 35);
                $expression_signal = min(100, count($common_expressions) * 25);
                $link_competition_signal = min(100, count($common_sources) * 25);

                // Score pondéré sur 100 : titre, slug, ancres, expressions, cluster et sources communes.
                $score = (int)round(
                    ($title_similarity * 0.30) +
                    ($slug_similarity * 0.20) +
                    ($anchor_signal * 0.20) +
                    ($expression_signal * 0.15) +
                    ($same_cluster ? 10 : 0) +
                    ($link_competition_signal * 0.05)
                );

                if ($score < 30) {
                    continue;
                }

                $types = $this->cma_conflicts_detect_types(
                    $title_similarity,
                    $slug_similarity,
                    $common_anchors,
                    $common_expressions,
                    $same_cluster,
                    $common_sources
                );

                $primary_id = $this->cma_conflicts_choose_primary_article($id_a, $id_b, $scores);
                $severity = $this->cma_conflicts_get_severity($score);
                $topic = $this->cma_conflicts_get_topic_label($common_expressions, $common_anchors, $article_a, $article_b);

                $conflicts[] = [
                    'id' => 'cma-conflict-' . $id_a . '-' . $id_b,
                    'score' => $score,
                    'severity' => $severity,
                    'topic' => $topic,
                    'type' => implode(', ', $types),
                    'types' => $types,
                    'recommendation' => $this->cma_conflicts_get_recommendation($severity, $same_cluster, count($common_anchors), count($common_sources)),
                    'risk_explanation' => $this->cma_conflicts_get_risk_explanation($score, $types),
                    'common_expressions' => $common_expressions,
                    'common_anchors' => $common_anchors,
                    'common_sources' => $common_sources,
                    'same_cluster' => $same_cluster,
                    'cluster' => $same_cluster ? ($cluster_map[$id_a] ?? []) : [],
                    'primary_id' => $primary_id,
                    'articles' => [
                        $this->cma_conflicts_prepare_article_detail($article_a, $scores[$id_a] ?? [], $cluster_map[$id_a] ?? [], $primary_id === $id_a),
                        $this->cma_conflicts_prepare_article_detail($article_b, $scores[$id_b] ?? [], $cluster_map[$id_b] ?? [], $primary_id === $id_b),
                    ],
                ];
        }

        usort($conflicts, static fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'summary' => $this->cma_conflicts_build_summary($conflicts),
            'conflicts' => $conflicts,
            'limited_data' => empty($anchors_by_target) || empty($cluster_map),
            'candidate_pairs' => count($candidate_pairs),
        ];
    }

    private function cma_conflicts_get_candidate_pairs(array $items, array $anchors_by_target, array $sources_by_target, array $cluster_map): array {
        $ids = array_keys($items);
        $total = count($ids);
        $pairs = [];

        if ($total <= 150) {
            for ($i = 0; $i < $total; $i++) {
                for ($j = $i + 1; $j < $total; $j++) {
                    $pairs[$this->cma_conflicts_pair_key((int)$ids[$i], (int)$ids[$j])] = [(int)$ids[$i], (int)$ids[$j]];
                }
            }

            return array_values($pairs);
        }

        $token_index = [];
        $cluster_index = [];
        $source_index = [];
        $anchor_index = [];

        foreach ($items as $id => $item) {
            $id = (int)$id;
            $text = trim((string)($item['title'] ?? '') . ' ' . (string)($item['slug'] ?? ''));

            foreach ($this->cma_conflicts_get_tokens($text) as $token) {
                $token_index[$token][] = $id;
            }

            if (!empty($cluster_map[$id]['id'])) {
                $cluster_index[(int)$cluster_map[$id]['id']][] = $id;
            }

            foreach (($sources_by_target[$id] ?? []) as $source_id) {
                $source_index[(int)$source_id][] = $id;
            }

            foreach (($anchors_by_target[$id] ?? []) as $anchor) {
                $anchor_key = implode(' ', array_slice($this->cma_conflicts_get_tokens((string)$anchor), 0, 4));
                if ($anchor_key !== '') {
                    $anchor_index[$anchor_key][] = $id;
                }
            }
        }

        foreach ([$token_index, $cluster_index, $source_index, $anchor_index] as $index) {
            foreach ($index as $group_ids) {
                $group_ids = array_values(array_unique(array_map('intval', (array)$group_ids)));

                if (count($group_ids) < 2 || count($group_ids) > 80) {
                    continue;
                }

                for ($i = 0; $i < count($group_ids); $i++) {
                    for ($j = $i + 1; $j < count($group_ids); $j++) {
                        $a = $group_ids[$i];
                        $b = $group_ids[$j];
                        $pairs[$this->cma_conflicts_pair_key($a, $b)] = [$a, $b];

                        if (count($pairs) >= 12000) {
                            return array_values($pairs);
                        }
                    }
                }
            }
        }

        return array_values($pairs);
    }

    private function cma_conflicts_pair_key(int $id_a, int $id_b): string {
        if ($id_b < $id_a) {
            [$id_a, $id_b] = [$id_b, $id_a];
        }

        return $id_a . ':' . $id_b;
    }

    private function cma_conflicts_get_articles_data(): array {
        $items = $this->data['items'] ?? [];
        $articles = [];

        // On analyse uniquement les articles, car la cannibalisation éditoriale cible les posts.
        foreach ($items as $id => $item) {
            if (($item['type'] ?? '') !== 'post') {
                continue;
            }

            $post = null;
            if (empty($item['slug']) || empty($item['date']) || empty($item['modified'])) {
                $post = get_post((int)$id);
            }

            $url_path = (string)parse_url((string)($item['url'] ?? ''), PHP_URL_PATH);
            $fallback_slug = trim(basename(trim($url_path, '/')));

            $articles[(int)$id] = [
                'id' => (int)$id,
                'title' => (string)($item['title'] ?? ''),
                'slug' => (string)($item['slug'] ?? ($post->post_name ?? $fallback_slug)),
                'url' => (string)($item['url'] ?? ''),
                'date' => (string)($item['date'] ?? ($post->post_date ?? '')),
                'modified' => (string)($item['modified'] ?? ($post->post_modified ?? '')),
            ];
        }

        return $articles;
    }

    private function cma_conflicts_get_anchor_data(): array {
        $edges = $this->data['edges'] ?? [];
        $anchors = [];

        foreach ($edges as $edge) {
            $to = (int)($edge['to'] ?? 0);
            $anchor = trim((string)($edge['anchor'] ?? ''));

            if ($to <= 0 || $anchor === '') {
                continue;
            }

            $normalized = $this->cma_conflicts_normalize_text($anchor, false);
            if ($normalized !== '') {
                $anchors[$to][] = $normalized;
            }
        }

        foreach ($anchors as $id => $values) {
            $anchors[$id] = array_values(array_unique($values));
        }

        return $anchors;
    }

    private function cma_conflicts_get_incoming_sources_data(): array {
        $sources = [];

        foreach (($this->data['edges'] ?? []) as $edge) {
            $from = (int)($edge['from'] ?? 0);
            $to = (int)($edge['to'] ?? 0);

            if ($from > 0 && $to > 0) {
                $sources[$to][] = $from;
            }
        }

        foreach ($sources as $id => $values) {
            $sources[$id] = array_values(array_unique($values));
        }

        return $sources;
    }

    private function cma_conflicts_get_cluster_map(): array {
        $clusters = $this->get_clusters();
        $map = [];

        foreach ($clusters as $cluster) {
            $cluster_id = (int)($cluster['pillar']['id'] ?? 0);
            $cluster_title = (string)($cluster['pillar']['title'] ?? '');

            if ($cluster_id <= 0) {
                continue;
            }

            $map[$cluster_id] = [
                'id' => $cluster_id,
                'title' => $cluster_title,
            ];

            foreach (($cluster['pages'] ?? []) as $page) {
                $page_id = (int)($page['id'] ?? 0);
                if ($page_id > 0) {
                    $map[$page_id] = [
                        'id' => $cluster_id,
                        'title' => $cluster_title,
                    ];
                }
            }
        }

        return $map;
    }

    private function cma_conflicts_get_article_scores(): array {
        $items = $this->data['items'] ?? [];
        $incoming_global = $this->data['incoming_global'] ?? [];
        $out_internal = $this->data['out_internal'] ?? [];
        $posts_only_in = $this->data['posts_only_in'] ?? [];
        $pagerank = $this->compute_pagerank();
        $scores = [];

        foreach ($items as $id => $item) {
            if (($item['type'] ?? '') !== 'post') {
                continue;
            }

            $incoming = (int)($incoming_global[$id] ?? 0);
            $outgoing = count($out_internal[$id] ?? []);
            $is_isolated = (($posts_only_in[$id] ?? 0) == 0);

            $scores[(int)$id] = [
                'score' => $this->compute_score($incoming, $outgoing, $is_isolated),
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'pagerank' => $pagerank[$id] ?? 0,
            ];
        }

        return $scores;
    }

    private function cma_conflicts_normalize_text(string $text, bool $remove_stopwords = true): string {
        // Normalisation volontairement simple pour rester rapide et robuste sans NLP externe.
        $text = wp_strip_all_tags($text);
        $text = remove_accents($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);
        $text = str_replace('-', ' ', $text);
        $text = preg_replace('/\s+/', ' ', trim((string)$text));

        if (!$remove_stopwords || $text === '') {
            return $text;
        }

        $words = array_filter(explode(' ', $text), function($word) {
            return strlen($word) > 2 && !in_array($word, $this->cma_conflicts_stopwords(), true);
        });

        return implode(' ', array_values($words));
    }

    private function cma_conflicts_stopwords(): array {
        return [
            'les','des','une','un','dans','pour','avec','sans','sur','sous','aux','par','que','qui',
            'quoi','dont','est','sont','vous','nous','vos','nos','leur','leurs','plus','moins','tout',
            'tous','toute','comment','faire','creer','creation','guide','tuto','tutoriel','meilleur',
            'meilleure','exemple','exemples','wordpress','site','page','article'
        ];
    }

    private function cma_conflicts_compare_text(string $a, string $b): int {
        $tokens_a = $this->cma_conflicts_get_tokens($a);
        $tokens_b = $this->cma_conflicts_get_tokens($b);

        if (empty($tokens_a) || empty($tokens_b)) {
            return 0;
        }

        $common = array_intersect($tokens_a, $tokens_b);
        $union = array_unique(array_merge($tokens_a, $tokens_b));
        $jaccard = count($union) ? (count($common) / count($union)) * 100 : 0;

        similar_text(implode(' ', $tokens_a), implode(' ', $tokens_b), $similar_text);

        return (int)round(($jaccard * 0.7) + ($similar_text * 0.3));
    }

    private function cma_conflicts_get_tokens(string $text): array {
        $normalized = $this->cma_conflicts_normalize_text($text);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_unique(array_filter(explode(' ', $normalized))));
    }

    private function cma_conflicts_get_phrases(string $text): array {
        $tokens = $this->cma_conflicts_get_tokens($text);
        $phrases = [];

        foreach ([2, 3] as $size) {
            for ($i = 0; $i <= count($tokens) - $size; $i++) {
                $phrases[] = implode(' ', array_slice($tokens, $i, $size));
            }
        }

        return array_values(array_unique($phrases));
    }

    private function cma_conflicts_find_common_expressions(array $article_a, array $article_b): array {
        $text_a = trim($article_a['title'] . ' ' . $article_a['slug']);
        $text_b = trim($article_b['title'] . ' ' . $article_b['slug']);

        $phrases = array_intersect(
            $this->cma_conflicts_get_phrases($text_a),
            $this->cma_conflicts_get_phrases($text_b)
        );

        $tokens = array_intersect(
            $this->cma_conflicts_get_tokens($text_a),
            $this->cma_conflicts_get_tokens($text_b)
        );

        return array_slice(array_values(array_unique(array_merge($phrases, $tokens))), 0, 8);
    }

    private function cma_conflicts_find_common_anchors(array $anchors_a, array $anchors_b): array {
        $common = [];

        foreach ($anchors_a as $anchor_a) {
            foreach ($anchors_b as $anchor_b) {
                if ($anchor_a === $anchor_b || $this->cma_conflicts_compare_text($anchor_a, $anchor_b) >= 60) {
                    $common[] = $anchor_a;
                    break;
                }
            }
        }

        return array_slice(array_values(array_unique($common)), 0, 8);
    }

    private function cma_conflicts_detect_types(int $title_similarity, int $slug_similarity, array $common_anchors, array $common_expressions, bool $same_cluster, array $common_sources): array {
        $types = [];

        if ($title_similarity >= 55) $types[] = __('Very similar titles', 'crea-maillage-audit');
        if ($slug_similarity >= 55) $types[] = __('Similar slugs', 'crea-maillage-audit');
        if (!empty($common_anchors)) $types[] = __('Similar internal anchors', 'crea-maillage-audit');
        if (!empty($common_expressions)) $types[] = __('Same important expressions', 'crea-maillage-audit');
        if ($same_cluster) $types[] = __('Content in the same cluster with similar intent', 'crea-maillage-audit');
        if (!empty($common_sources)) $types[] = __('Competing internal links to multiple similar pages', 'crea-maillage-audit');

        return empty($types) ? [__('Combined weak signals', 'crea-maillage-audit')] : $types;
    }

    private function cma_conflicts_get_severity(int $score): string {
        if ($score >= 75) return 'critical';
        if ($score >= 50) return 'medium';
        return 'light';
    }

    private function cma_conflicts_choose_primary_article(int $id_a, int $id_b, array $scores): int {
        $a = $scores[$id_a] ?? [];
        $b = $scores[$id_b] ?? [];

        $weight_a = ((int)($a['pagerank'] ?? 0) * 2) + ((int)($a['incoming'] ?? 0) * 3) + (int)($a['score'] ?? 0);
        $weight_b = ((int)($b['pagerank'] ?? 0) * 2) + ((int)($b['incoming'] ?? 0) * 3) + (int)($b['score'] ?? 0);

        return $weight_b > $weight_a ? $id_b : $id_a;
    }

    private function cma_conflicts_get_topic_label(array $expressions, array $anchors, array $article_a, array $article_b): string {
        if (!empty($expressions)) {
            return (string)$expressions[0];
        }

        if (!empty($anchors)) {
            return (string)$anchors[0];
        }

        $tokens = array_intersect(
            $this->cma_conflicts_get_tokens($article_a['title']),
            $this->cma_conflicts_get_tokens($article_b['title'])
        );

        return !empty($tokens) ? implode(' ', array_slice($tokens, 0, 3)) : __('Related topic', 'crea-maillage-audit');
    }

    private function cma_conflicts_get_recommendation(string $severity, bool $same_cluster, int $anchor_count, int $source_count): string {
        if ($severity === 'critical') {
            return __('Define primary content, then merge or redirect overlapping content.', 'crea-maillage-audit');
        }

        if ($same_cluster && $anchor_count > 0) {
            return __('Reorient content toward a different intent and use more distinct internal anchors.', 'crea-maillage-audit');
        }

        if ($source_count > 0) {
            return __('Strengthen links to the primary content and differentiate links to the secondary content.', 'crea-maillage-audit');
        }

        if ($severity === 'medium') {
            return __('Update H1 headings or SEO titles to clearly separate each content intent.', 'crea-maillage-audit');
        }

        return __('Monitor only, or add differentiating internal links if rankings overlap.', 'crea-maillage-audit');
    }

    private function cma_conflicts_get_risk_explanation(int $score, array $types): string {
        return sprintf(
            __('Risk score %1$d/100 based on: %2$s. This content may send overly similar topical signals to Google.', 'crea-maillage-audit'),
            $score,
            implode(', ', $types)
        );
    }

    private function cma_conflicts_prepare_article_detail(array $article, array $score, array $cluster, bool $is_primary): array {
        return [
            'id' => (int)$article['id'],
            'title' => (string)$article['title'],
            'url' => (string)$article['url'],
            'date' => (string)$article['date'],
            'modified' => (string)$article['modified'],
            'score' => (int)($score['score'] ?? 0),
            'incoming' => (int)($score['incoming'] ?? 0),
            'outgoing' => (int)($score['outgoing'] ?? 0),
            'pagerank' => (int)($score['pagerank'] ?? 0),
            'cluster' => (string)($cluster['title'] ?? ''),
            'is_primary' => $is_primary,
        ];
    }

    private function cma_conflicts_build_summary(array $conflicts): array {
        $severity_counts = ['critical' => 0, 'medium' => 0, 'light' => 0];
        $article_ids = [];
        $clusters = [];
        $topics = [];

        foreach ($conflicts as $conflict) {
            $severity = $conflict['severity'] ?? 'light';
            if (isset($severity_counts[$severity])) {
                $severity_counts[$severity]++;
            }

            $topic = (string)($conflict['topic'] ?? '');
            if ($topic !== '') {
                $topics[$topic] = ($topics[$topic] ?? 0) + 1;
            }

            if (!empty($conflict['cluster']['title'])) {
                $cluster_title = (string)$conflict['cluster']['title'];
                $clusters[$cluster_title] = ($clusters[$cluster_title] ?? 0) + 1;
            }

            foreach (($conflict['articles'] ?? []) as $article) {
                $article_ids[(int)$article['id']] = true;
            }
        }

        arsort($clusters);
        arsort($topics);

        return [
            'total' => count($conflicts),
            'critical' => $severity_counts['critical'],
            'medium' => $severity_counts['medium'],
            'light' => $severity_counts['light'],
            'affected_posts' => count($article_ids),
            'top_cluster' => !empty($clusters) ? (string)array_key_first($clusters) : '',
            'top_topic' => !empty($topics) ? (string)array_key_first($topics) : '',
        ];
    }


}
