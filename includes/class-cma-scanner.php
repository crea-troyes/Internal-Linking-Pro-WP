<?php
if (!defined('ABSPATH')) exit;

final class CMA_Scanner {

    /**
     * Lance le scan global du maillage interne.
     * Optimisé pour Gutenberg, les shortcodes et les liens relatifs.
     */
    public function run_global_scan(): array {

        $excluded_ids = $this->get_excluded_ids();

        // 1. Récupération de tous les contenus concernés, hors exclusions
        $all_posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => $excluded_ids,
            'no_found_rows'  => true,
        ]);

        $home_url = home_url();
        $home_host = strtolower((string) wp_parse_url($home_url, PHP_URL_HOST));
        $items = [];
        $out_internal = [];
        $incoming_global = [];
        $out_external_count = [];
        $edges = [];

        // Listes spécifiques pour les articles (posts_only)
        $post_ids_only = [];
        $posts_only_out = [];
        $posts_only_in = [];

        // 2. Initialisation des structures de données
        foreach ($all_posts as $p) {
            $items[$p->ID] = [
                'id'       => $p->ID,
                'type'     => $p->post_type,
                'title'    => $p->post_title,
                'slug'     => $p->post_name,
                'url'      => get_permalink($p->ID),
                'date'     => $p->post_date,
                'modified' => $p->post_modified,
                'words'    => 0, // initialisation
            ];
            $out_internal[$p->ID] = [];
            $incoming_global[$p->ID] = 0;
            $out_external_count[$p->ID] = 0;

            if ($p->post_type === 'post') {
                $post_ids_only[] = $p->ID;
                $posts_only_out[$p->ID] = [];
                $posts_only_in[$p->ID] = 0;
            }
        }

        $post_ids_only_lookup = array_flip($post_ids_only);

        // 3. Analyse du contenu de chaque page/article
        foreach ($all_posts as $post) {

            $content = apply_filters('the_content', $post->post_content);
            if (empty($content)) {
                continue;
            }
            $word_count = $this->count_words_clean($content);
            $items[$post->ID]['words'] = $word_count;

            $previous_libxml_state = libxml_use_internal_errors(true);
            $dom = new DOMDocument();

            $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $content;
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new DOMXPath($dom);
            $links = $xpath->query("//a[@href]");

            foreach ($links as $link) {
                $url = trim($link->getAttribute('href'));
                $anchor = trim(wp_strip_all_tags($link->textContent));

                if (
                    empty($url)
                    || strpos($url, '#') === 0
                    || strpos($url, 'mailto:') === 0
                    || strpos($url, 'tel:') === 0
                ) {
                    continue;
                }

                $is_relative = (strpos($url, '/') === 0 && strpos($url, '//') !== 0);
                $url_host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
                $is_absolute_internal = $url_host !== '' && $home_host !== '' && $url_host === $home_host;

                if ($is_absolute_internal || $is_relative) {

                    $target_id = url_to_postid($url);

                    if (!$target_id && $is_relative) {
                        $target_id = url_to_postid(user_trailingslashit($home_url . $url));
                    }

                    // On ignore si la cible est exclue
                    if ($target_id && in_array((int) $target_id, $excluded_ids, true)) {
                        continue;
                    }

                    if ($target_id && isset($items[$target_id]) && $target_id != $post->ID) {

                        $out_internal[$post->ID][] = $target_id;
                        $edges[] = [
                            'from'   => $post->ID,
                            'to'     => $target_id,
                            'anchor' => $anchor,
                        ];

                        if (
                            isset($post_ids_only_lookup[$post->ID]) &&
                            isset($post_ids_only_lookup[$target_id])
                        ) {
                            $posts_only_out[$post->ID][] = $target_id;
                        }
                    }

                } else {
                    if (strpos($url, 'http') === 0) {
                        $out_external_count[$post->ID]++;
                    }
                }
            }

            $out_internal[$post->ID] = array_values(array_unique($out_internal[$post->ID]));
            if (isset($posts_only_out[$post->ID])) {
                $posts_only_out[$post->ID] = array_values(array_unique($posts_only_out[$post->ID]));
            }

            libxml_clear_errors();
            libxml_use_internal_errors($previous_libxml_state);
            unset($dom, $xpath);
        }

        // 4. Calcul final des compteurs de liens entrants (In-links)
        foreach ($out_internal as $from_id => $targets) {
            foreach ($targets as $target_id) {
                if (isset($incoming_global[$target_id])) {
                    $incoming_global[$target_id]++;
                }
            }
        }

        foreach ($posts_only_out as $from_id => $targets) {
            foreach ($targets as $target_id) {
                if (isset($posts_only_in[$target_id])) {
                    $posts_only_in[$target_id]++;
                }
            }
        }

        return [
            'generated_at'       => time(),
            'excluded_ids'       => $excluded_ids,
            'items'              => $items,
            'out_internal'       => $out_internal,
            'incoming_global'    => $incoming_global,
            'out_external_count' => $out_external_count,
            'edges'              => $edges,
            'posts_only_out'     => $posts_only_out,
            'posts_only_in'      => $posts_only_in,
        ];
    }

    private function get_excluded_ids(): array {
        $raw = (string) get_option(CMA_EXCLUDED_IDS_OPTION, '');

        $parts = preg_split('/[\s,;]+/', $raw);
        if (!is_array($parts)) {
            return [];
        }

        $ids = array_map('intval', $parts);
        $ids = array_filter($ids, static fn($id) => $id > 0);
        $ids = array_values(array_unique($ids));

        sort($ids);

        return $ids;
    }


    private function count_words_clean(string $content): int {

        // supprimer code
        $content = preg_replace('#<pre.*?>.*?</pre>#si', ' ', $content);
        $content = preg_replace('#<code.*?>.*?</code>#si', ' ', $content);

        // supprimer scripts/styles
        $content = preg_replace('#<script.*?>.*?</script>#si', ' ', $content);
        $content = preg_replace('#<style.*?>.*?</style>#si', ' ', $content);

        // enlever balises html
        $text = wp_strip_all_tags($content);

        // nettoyer espaces
        $text = preg_replace('/\s+/', ' ', $text);

        // tokenizer
        $words = str_word_count(strtolower($text), 1);

        return count($words);
    }

}
