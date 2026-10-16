<?php
if (!defined('ABSPATH')) exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no data is modified.
$crea3_ilp_view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';

$crea3_ilp_col_in = ($crea3_ilp_view === "isolated_posts")
    ? "<th>" . esc_html__('Incoming links (pages)', 'crea3-internal-linking-pro') . "</th>"
    : "<th>" . esc_html__('Incoming links (posts + pages)', 'crea3-internal-linking-pro') . "</th>";

echo '<p>' . esc_html($intro) . '</p>';

echo '<div class="cma-table-wrap">';
echo '<div class="cma-search-wrapper"><input type="text" id="cma-search" placeholder="' . esc_attr__('Search content...', 'crea3-internal-linking-pro') . '"><div id="cma-count"></div></div>';
echo '<table id="tableau" class="widefat cma-dynamic-table striped">';
echo '<tr>';
echo '<th>' . esc_html__('Type', 'crea3-internal-linking-pro') . '</th>';
echo '<th>' . esc_html__('Title', 'crea3-internal-linking-pro') . '</th>';
if ($crea3_ilp_view === "isolated_posts") { echo '<th>' . esc_html__('Incoming links (posts)', 'crea3-internal-linking-pro') . '</th>'; }
echo wp_kses_post($crea3_ilp_col_in);
echo '<th>' . esc_html__('Internal outgoing links', 'crea3-internal-linking-pro') . '</th>';
echo '<th>' . esc_html__('External outgoing links', 'crea3-internal-linking-pro') . '</th>';
echo '<th title="' . esc_html__('Outbound internal links per 100 words', 'crea3-internal-linking-pro') . '">' . esc_html__('Internal links / 100 words', 'crea3-internal-linking-pro') . ' 
        <span class="tooltip">
            <span class="tooltip-icon">Ⓘ</span>
            <span class="tooltip-text">
                <strong>'. esc_html__('Internal links per 100 words', 'crea3-internal-linking-pro').'</strong><br>
                    '. esc_html__('Ideal:', 'crea3-internal-linking-pro').' <strong>0.8 to 1.5</strong><br>
                    '. esc_html__('↓ = low internal linking', 'crea3-internal-linking-pro').'<br>
                    '. esc_html__('↑ = overload', 'crea3-internal-linking-pro').'
            </span>
        </span>
    </th>';
echo '<th>PageRank</th>';
echo '<th>' . esc_html__('Internal linking score', 'crea3-internal-linking-pro') . '</th>';
echo '</tr></thead><tbody class="tbodyTable">';

if (empty($rows)) {

    echo '<tr><td colspan="' . ($crea3_ilp_view === 'isolated_posts' ? '10' : '9') . '"><em>'
        . esc_html__('No results found.', 'crea3-internal-linking-pro')
        . '</em></td></tr>';

} else {

    foreach ($rows as $crea3_ilp_r) {

        echo '<tr class="cma-row" data-title="' . esc_attr(strtolower((string)$crea3_ilp_r['title'])) . '">';

        $type       = $crea3_ilp_r['type'] === 'Article' ? 'post' : 'page';
        $crea3_ilp_type_label = $crea3_ilp_r['type'] === 'Article'
            ? __('Post', 'crea3-internal-linking-pro')
            : __('Page', 'crea3-internal-linking-pro');

        echo '<td><span class="cma-badge cma-badge-' . esc_attr($type) . '">'
            . esc_html($crea3_ilp_type_label)
            . '</span></td>';

        $crea3_ilp_title_class = !empty($crea3_ilp_r['is_isolated']) ? 'cma-isolated' : '';

        echo '<td>';

        $crea3_ilp_site_url     = home_url();
        $crea3_ilp_relative_url = str_replace($crea3_ilp_site_url, '', $crea3_ilp_r['url']);

        echo '<strong class="' . esc_attr($crea3_ilp_title_class) . '">'
            . esc_html($crea3_ilp_r['title'])
            . '</strong><br>';

        echo '<small><a class="link_post_cma" href="'
            . esc_url($crea3_ilp_r['url'])
            . '" target="_blank" rel="noopener">'
            . esc_html($crea3_ilp_relative_url)
            . '</a></small>';

        echo '</td>';

        if ($crea3_ilp_view === "isolated_posts") {

            echo '<td><span class="cma-badge in red0">0</span></td>';

            echo '<td><span class="cma-badge in">'
                . esc_html((string) $crea3_ilp_r['inpage_int'])
                . '</span></td>';

        } else {

            echo '<td><span class="cma-badge in">'
                . esc_html((string) $crea3_ilp_r['in_int'])
                . '</span></td>';
        }

        echo '<td><span class="cma-badge out">'
            . esc_html((string) $crea3_ilp_r['out_int'])
            . '</span></td>';

        echo '<td><span class="cma-badge ext">'
            . esc_html((string) $crea3_ilp_r['out_ext'])
            . '</span></td>';

        $crea3_ilp_ratio = isset($crea3_ilp_r['ratio_links']) ? (float)$crea3_ilp_r['ratio_links'] : 0;

        echo '<td><span class="cma-badge ratio">'
            . esc_html(number_format($crea3_ilp_ratio, 2, '.', ''))
            . '</span></td>';

        $crea3_ilp_score = (int)($crea3_ilp_r['score'] ?? 0);

        if ($crea3_ilp_score < 41) {
            $crea3_ilp_class_score = "rouge-score";
        } elseif ($crea3_ilp_score < 76) {
            $crea3_ilp_class_score = "orange-score";
        } else {
            $crea3_ilp_class_score = "vert-score";
        }

        echo '<td><span class="cma-badge pagerank-badge">'
            . esc_html((string) $crea3_ilp_r['pagerank'])
            . '</span></td>';

        echo '<td>
            <span class="cma-badge ' . esc_attr($crea3_ilp_class_score) . '">' . esc_html((string) $crea3_ilp_score) . '</span>
            </td>';

        echo '</tr>';
    }
}

echo '</tbody></table>';
echo '</div>';

?>
