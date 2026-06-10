<?php
if (!defined('ABSPATH')) exit;

$view = isset($_GET['view']) ? sanitize_key(wp_unslash((string) $_GET['view'])) : 'dashboard';

$col_in = ($view === "isolated_posts")
    ? "<th>" . esc_html__('Incoming links (pages)', 'crea-maillage-audit') . "</th>"
    : "<th>" . esc_html__('Incoming links (posts + pages)', 'crea-maillage-audit') . "</th>";

echo '<p>' . esc_html($intro) . '</p>';

echo '<div class="cma-table-wrap">';
echo '<div class="cma-search-wrapper"><input type="text" id="cma-search" placeholder="' . esc_attr__('Search content...', 'crea-maillage-audit') . '"><div id="cma-count"></div></div>';
echo '<table id="tableau" class="widefat cma-dynamic-table striped">';
echo '<tr>';
echo '<th>' . esc_html__('Type', 'crea-maillage-audit') . '</th>';
echo '<th>' . esc_html__('Title', 'crea-maillage-audit') . '</th>';
if ($view === "isolated_posts") { echo '<th>' . esc_html__('Incoming links (posts)', 'crea-maillage-audit') . '</th>'; }
echo $col_in;
echo '<th>' . esc_html__('Internal outgoing links', 'crea-maillage-audit') . '</th>';
echo '<th>' . esc_html__('External outgoing links', 'crea-maillage-audit') . '</th>';
echo '<th title="' . esc_html__('Outbound internal links per 100 words', 'crea-maillage-audit') . '">' . esc_html__('Internal links / 100 words', 'crea-maillage-audit') . ' 
        <span class="tooltip">
            <span class="tooltip-icon">Ⓘ</span>
            <span class="tooltip-text">
                <strong>'. esc_html__('Internal links per 100 words', 'crea-maillage-audit').'</strong><br>
                    '. esc_html__('Ideal:', 'crea-maillage-audit').' <strong>0.8 to 1.5</strong><br>
                    '. esc_html__('↓ = low internal linking', 'crea-maillage-audit').'<br>
                    '. esc_html__('↑ = overload', 'crea-maillage-audit').'
            </span>
        </span>
    </th>';
echo '<th>PageRank</th>';
echo '<th>' . esc_html__('Internal linking score', 'crea-maillage-audit') . '</th>';
echo '</tr></thead><tbody class="tbodyTable">';

if (empty($rows)) {

    echo '<tr><td colspan="' . ($view === 'isolated_posts' ? '10' : '9') . '"><em>'
        . esc_html__('No results found.', 'crea-maillage-audit')
        . '</em></td></tr>';

} else {

    foreach ($rows as $r) {

        echo '<tr class="cma-row" data-title="' . esc_attr(strtolower((string)$r['title'])) . '">';

        $type       = $r['type'] === 'Article' ? 'post' : 'page';
        $type_label = $r['type'] === 'Article'
            ? __('Post', 'crea-maillage-audit')
            : __('Page', 'crea-maillage-audit');

        echo '<td><span class="cma-badge cma-badge-' . $type . '">'
            . esc_html($type_label)
            . '</span></td>';

        $title_class = !empty($r['is_isolated']) ? 'cma-isolated' : '';

        echo '<td>';

        $site_url     = home_url();
        $relative_url = str_replace($site_url, '', $r['url']);

        echo '<strong class="' . esc_attr($title_class) . '">'
            . esc_html($r['title'])
            . '</strong><br>';

        echo '<small><a class="link_post_cma" href="'
            . esc_url($r['url'])
            . '" target="_blank" rel="noopener">'
            . esc_html($relative_url)
            . '</a></small>';

        echo '</td>';

        if ($view === "isolated_posts") {

            echo '<td><span class="cma-badge in red0">0</span></td>';

            echo '<td><span class="cma-badge in">'
                . esc_html((string) $r['inpage_int'])
                . '</span></td>';

        } else {

            echo '<td><span class="cma-badge in">'
                . esc_html((string) $r['in_int'])
                . '</span></td>';
        }

        echo '<td><span class="cma-badge out">'
            . esc_html((string) $r['out_int'])
            . '</span></td>';

        echo '<td><span class="cma-badge ext">'
            . esc_html((string) $r['out_ext'])
            . '</span></td>';

        $ratio = isset($r['ratio_links']) ? (float)$r['ratio_links'] : 0;

        echo '<td><span class="cma-badge ratio">'
            . esc_html(number_format($ratio, 2, '.', ''))
            . '</span></td>';

        $score = (int)($r['score'] ?? 0);

        if ($score < 41) {
            $class_score = "rouge-score";
        } elseif ($score < 76) {
            $class_score = "orange-score";
        } else {
            $class_score = "vert-score";
        }

        $pagerank = isset($r['pagerank']) ? (float)$r['pagerank'] : 0.0;

        echo '<td><span class="cma-badge pagerank-badge">'
            . esc_html(number_format($pagerank, 1, '.', ''))
            . '</span></td>';

        echo '<td>
            <span class="cma-badge ' . $class_score . '">' . $score . '</span>
            </td>';

        echo '</tr>';
    }
}

echo '</tbody></table>';
echo '</div>';

?>

<script>
document.getElementById('cma-search').addEventListener('input', function() {

    let search = this.value.toLowerCase().trim();
    let words = search.split(/\s+/);
    let visibleCount = 0;

    let rows = document.querySelectorAll('.cma-row');

    rows.forEach(row => {
        let title = row.dataset.title;

        let match = words.every(word => title.includes(word));

        if (match) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    if (visibleCount === 0) {
        document.getElementById('cma-count').classList.add('empty');
    } else {
        document.getElementById('cma-count').classList.remove('empty');
    }

document.getElementById('cma-count').innerText = visibleCount + " <?php echo esc_js(__('content item(s) found', 'crea-maillage-audit')); ?>";

});
</script>
