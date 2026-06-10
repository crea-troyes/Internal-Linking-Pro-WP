# User Guide - ILP - Internal Linking Pro

## 1. Overview

**ILP - Internal Linking Pro** is a WordPress internal-linking audit plugin. It analyzes published posts and pages to identify weakly linked content, internal-link opportunities, editorial silos, and SEO cannibalization risks.

The plugin runs locally in WordPress: no data is sent to an external service. The main analysis is triggered manually and its results are cached. It therefore adds no processing to public-facing pages.

The main screen is available in the WordPress administration area:

`Tools > Internal Linking`

Access to this screen and scan controls is restricted to users with the WordPress `manage_options` capability, usually administrators.

## 2. Full Feature List

### General Audit

1. Manual scan of published posts and pages.
2. Cached audit results to prevent any impact on public-site performance.
3. Manual analysis-cache deletion.
4. Result filtering by posts, pages, or posts and pages.
5. Last scan date and time.
6. Manual content exclusion by WordPress ID.

### Internal-Link Analysis

7. Summary dashboard with a global internal-linking score.
8. Analyzed-content count.
9. Internal-link and external-link counts.
10. Average number of internal links per content item.
11. Internal-link density per 100 words.
12. Ranking of content items receiving the most internal links.
13. Detailed table of all analyzed content.
14. Title search in tables.
15. Table sorting by column.
16. Incoming internal-link count.
17. Outgoing internal-link count.
18. Outgoing external-link count.
19. Internal PageRank normalized to 100.
20. Individual internal-linking score.

### SEO Diagnostics

21. Isolated-post detection.
22. Global-orphan detection.
23. Technical detection of content without outgoing links.
24. Suggestions for internal links between related content.
25. Cluster and pillar-page detection.
26. SEO silo analysis: coherence, retention, and leakage.
27. Improvement recommendations for each silo.
28. SEO cannibalization-risk detection across posts.
29. Conflict ranking by severity.
30. Conflict-resolution recommendations.

### Mapping and Editing

31. Interactive internal-link network map.
32. Visual highlighting of pillar pages, clusters, and isolated posts in the graph.
33. Detailed SEO card when clicking a graph node.
34. Suggestion panel directly in the Gutenberg editor.
35. Suggestions based on selected text, title, slug, and editorial context.
36. Suggested link anchors.
37. Link insertion on selected text from Gutenberg.
38. Link-opportunity detection while writing.

## 3. Installation and First Scan

1. Install and activate the plugin in WordPress.
2. Open `Tools > Internal Linking`.
3. Click **Run scan**.
4. Wait for the analysis to finish without closing the page.
5. The page reloads automatically when the scan is complete.

The scan only analyzes content:

- with the **post** (`post`) or **page** (`page`) type;
- with the **published** status;
- that is not listed in the exclusions.

Absolute and relative internal links are supported. Anchor-only links (`#...`), `mailto:` links, and `tel:` links are ignored.

## 4. Common Controls

These controls appear above the results in all analysis tabs except **Settings**.

### Display Filter

The display filter offers three choices:

- **Posts**: displays posts only;
- **Pages**: displays pages only;
- **Posts + Pages**: displays both content types.

The filter changes the results displayed in compatible tabs. It does not rerun the scan.

### Run Scan

The **Run scan** button reruns the full audit. Use it after significant editorial changes, internal-link additions, new publications, or exclusion updates.

### Clear Cache

The **Clear cache** button deletes stored results. Confirmation is required. After deletion, the analysis tabs remain empty until the next scan.

## 5. Understanding the Metrics

### Incoming Internal Links

Number of analyzed content items pointing to the relevant page or post. Content without any global incoming link is considered a **global orphan**.

### Outgoing Internal Links

Number of analyzed content items linked from the relevant page or post. Multiple identical links from one content item to the same target do not artificially increase this count.

### Outgoing External Links

Number of outgoing HTTP links to another domain.

### Internal Links per 100 Words

Outgoing internal-link density relative to content length:

`number of outgoing internal links / number of words x 100`

In the table, the built-in help displays a reference range of **0.8 to 1.5 links per 100 words**. This value remains an indicator: editorial relevance takes priority over adding artificial links.

### Internal PageRank

Internal PageRank estimates the relative importance of content within the site's link network. It is normalized on a scale from 0 to 100: the strongest content item receives 100 and the others are compared with that reference.

### Individual Internal-Linking Score

The individual score, out of 100, combines:

- incoming-link authority, progressively scored up to 55 points;
- outgoing-link coverage, progressively scored up to 30 points;
- internal-link density, for up to 15 points;
- a 10-point penalty for posts that receive no link from another post.

Table colors make interpretation easier:

- red: score below 41;
- orange: score from 41 to 75;
- green: score from 76 to 100.

### Isolated Post and Orphan Content: What Is the Difference?

An **isolated post** is a post that does not receive any link from another post. It may still receive a link from a page.

A **global orphan** is a post or page that does not receive any internal link from the analyzed content. This diagnostic is therefore broader.

## 6. Dashboard Tab

The **Dashboard** tab provides an overview of the internal-linking structure.

### Global Score

The **Internal linking score** gauge displays a global score out of 100. This score takes into account:

- the proportion of content with incoming links;
- the proportion of content with outgoing links;
- the progressive depth of incoming and outgoing links;
- the proportion of posts that are not isolated;
- the proportion of content with both incoming and outgoing links;
- the global internal-link density per 100 words.
- the proportion of content contained in the site's largest connected internal-link component.

### Analyzed Content

This block displays the total number of analyzed content items and divides them into:

- **Correct**: content with at least one incoming and one outgoing link;
- **Isolated**: posts without incoming links from other posts;
- **Without outgoing internal links**: content without outgoing internal links.

### Most Linked

List of the content items receiving the most internal links. Use it to find already-strong pages and verify whether they match your SEO priorities.

### Links Suggestions

Displays the total number of detected opportunities and the first five suggestions. The **View opportunities** button opens the full **Link suggestions** tab.

### Main Statistics

The dashboard also displays:

- orphan content;
- content without outgoing internal links;
- isolated posts;
- the number of clusters;
- the average number of internal links per content item;
- total internal links;
- total external links;
- global internal-link density per 100 words.

### Cluster List

The cluster table displays:

- the pillar page;
- the number of content items linked to that pillar page;
- a cluster score;
- a **Details** button.

The details list up to 30 cluster content items and the anchors used to point to the pillar page. Columns can be sorted.

## 7. Table Tab

The **Table** tab lists detailed results for the content matching the current filter.

Each row displays:

- the content type;
- the title and URL;
- incoming internal links;
- outgoing internal links;
- outgoing external links;
- internal-link density per 100 words;
- internal PageRank;
- the individual internal-linking score.

The **Search content...** field filters rows by words present in the title. Click a column header to sort the table. Click a content URL to open it in a new tab.

## 8. Isolated Posts Tab

The **Isolated posts** tab only lists posts that do not receive any link from another post.

The table separates:

- **Incoming links (posts)**: always zero in this view;
- **Incoming links (pages)**: number of links received from pages.

Use this tab to reconnect posts to the editorial journey. Start with strategic posts and add contextual links from related posts.

The **Pages** filter returns no results in this tab because the isolated-post concept only applies to posts here.

## 9. Global Orphans Tab

The **Global orphans** tab lists posts and pages without any incoming internal link.

Content appearing in this list is difficult to discover from the other analyzed content. Check:

- whether it should remain available;
- whether it deserves a link from a pillar page;
- whether it should be linked from several complementary content items;
- whether it is obsolete and should be merged, redirected, or excluded from the analysis.

## 10. Link Suggestions Tab

The **Link suggestions** tab suggests internal links between related content items.

For each suggestion, the table displays:

- the source content (**From**);
- the target content (**To**);
- their URLs;
- a relevance score (**Relevance**).

The score primarily measures editorial relevance. It combines:

- overlap between significant content, title, and slug terms, with rarer terms weighted more heavily;
- title sequence and slug similarity;
- proximity in the existing internal-link graph, limited to 5% of the displayed relevance.

Suggestions can connect a post and a page when they are topically relevant. Existing links and weak matches are excluded.
The target's lack of incoming links is used only to order suggestions with the same relevance; it does not inflate the displayed percentage.
Run a new scan after updating the plugin so existing cached content receives its thematic keyword profile.

Suggestions are sorted by decreasing relevance and limited to the top 50 opportunities. Always validate editorial relevance before adding a link.

## 11. Conflicts Tab

The **Conflicts** tab detects posts that may target an overly similar search intent and compete with each other in Google.

### Summary

The upper section displays:

- the total number of conflicts;
- **Critical**, **Medium**, and **Light** conflicts;
- the number of affected content items;
- the most affected cluster;
- the most frequently involved expression;
- the date of the analysis used.

### Filters

The **All**, **Critical**, **Medium**, and **Light** buttons filter displayed conflicts.

### Table

Each conflict displays:

- its severity;
- the relevant expression or topic;
- the number of affected content items;
- a similarity score;
- the types of signals detected;
- a recommendation;
- a **Details** button.

### Conflict Details

The **Details** button displays:

- the suggested primary content;
- the competing content;
- the last update date;
- the internal score and incoming and outgoing links;
- the potential cluster;
- shared expressions and anchors;
- a simplified map;
- the risk explanation;
- the recommended action.

### Calculation and Interpretation

The conflict score combines title similarity, slug similarity, shared anchors, shared expressions, membership in the same cluster, and shared link sources.

The levels are:

- **Critical**: score greater than or equal to 75;
- **Medium**: score from 50 to 74;
- **Light**: score from 30 to 49.

The **Recalculate conflicts** button forces this analysis to be recomputed. Use it after merging content, changing editorial angles, adjusting anchors, or creating redirects.

This feature supports editorial decision-making. Two similar posts do not necessarily compete if their search intents are clearly distinct.

## 12. Silos Tab

The **Silos** tab checks whether content is organized around coherent pillar pages.

A silo is detected around a page receiving enough internal links. The minimum threshold is configurable in **Settings**. A dynamic minimum threshold also applies depending on site size.

### Columns

- **Silo**: pillar-page title and URL;
- **Pages**: number of attached content items;
- **Coherence**: internal-journey strength;
- **Leakage**: proportion of internal links sent outside the silo;
- **Issues**: detected problems;
- **Recommendations**: suggested improvements.

### Coherence

Coherence combines:

- link retention within the silo;
- internal links from member content;
- lateral links between complementary content items;
- redistribution of links from the pillar page to member content.

### Leakage

Leakage only measures internal links sent to other silos. External links are diagnosed separately.

Visual reference points:

- leakage less than or equal to 15%: satisfactory;
- leakage from 16% to 30%: monitor;
- leakage greater than 30%: high.

Recommendations may suggest strengthening links to the pillar page, creating lateral links, limiting links to other topics, or balancing external links.

## 13. Graph View Tab

The **Graph view** tab displays an interactive map of the internal-link network.

### Usage

- drag the map to move around;
- use the mouse wheel or navigation controls to zoom;
- click a node to display its direct neighbors and SEO card;
- double-click in the graph to reset the view;
- click an empty area to remove highlighting.

### Visual Interpretation

- yellow circle: pillar page;
- red node: isolated post;
- green node: page outside a cluster;
- blue node: post outside a cluster;
- colored border: content attached to a cluster;
- arrow: internal-link direction.

Node size increases with the number of incoming links. The side panel displays the content type, incoming and outgoing links, potential pillar-page role, cluster, cluster score, and content URL.

## 14. Settings Tab

The **Settings** tab configures exclusions and cluster detection.

### Excluded Content

Enter the WordPress IDs of content items to ignore. Accepted separators are commas, spaces, semicolons, and line breaks.

Example:

```text
12, 45, 78
```

Common use cases:

- legal notices;
- temporary pages;
- sponsored campaigns;
- content that should not participate in the internal-linking strategy.

Exclusions are applied to existing results as soon as they are saved. However, rerun a scan after significant changes to keep the entire cache up to date.

### Cluster Settings

The **Minimum incoming links** field sets the minimum number of incoming links required for content to qualify as a potential pillar page.

Reference points displayed in the interface:

- `3`: weak cluster;
- `8` to `10`: strong cluster.

Accepted values range from `1` to `100`. Click **Save settings** to store changes.

## 15. Gutenberg Panel

The plugin adds an **Internal linking suggestions** panel to the Gutenberg block editor for posts and pages.

### General Suggestions

While you write content, the panel analyzes its title, slug, section headings, and text. It recommends up to five relevant content items to link.

Each suggestion displays:

- the target title and URL;
- a score;
- badges describing the detected relationship;
- a reason;
- suggested anchors;
- an **Insert link** button.

### Suggestions Based on a Selection

For a more precise suggestion:

1. select an expression in a paragraph or heading;
2. wait for the panel to refresh;
3. check the expression displayed under **Analyzed selection**;
4. choose a suggestion;
5. click a suggested anchor or **Insert link**.

The plugin attempts to insert the link directly on the selected text. If the selection was lost, select the text again and retry.

### Data Used

Gutenberg suggestions work even without a recent scan thanks to the published-content catalog. However, a dashboard scan enriches recommendations with graph data: incoming links, outgoing links, PageRank, and clusters.

## 16. Technical View for Content Without Outgoing Links

The plugin code contains an additional view named `dead_end`. It lists content without any outgoing internal or external link.

This view currently has no visible tab in the navigation bar. It can be opened manually with a URL such as:

```text
/wp-admin/tools.php?page=cma-maillage&view=dead_end
```

This technical view complements the **Without outgoing links** dashboard counter, but its criterion is stricter: it requires the absence of both internal **and** external links.

## 17. Recommended Workflow

1. Run a scan after a publishing phase or redesign.
2. Review the **Dashboard** to identify general trends.
3. Process high-priority **Global orphans** and **Isolated posts**.
4. Review weak content in **Table**.
5. Add relevant opportunities from **Link suggestions**.
6. Fix structural issues reported in **Silos**.
7. Review **Critical** and then **Medium** issues in **Conflicts**.
8. Use **Graph view** to validate the overall structure.
9. Use the Gutenberg panel when writing new content.
10. Rerun the scan to measure the effects of your changes.

## 18. Known Limitations

- Metrics only cover published posts and pages.
- Custom post types are not analyzed.
- Automatic suggestions require editorial validation.
- A high score does not guarantee better SEO performance.
- A useful and natural link is better than an artificial addition intended only to increase a metric.
- The plugin detects cannibalization risks, not actual Google rankings.
- Changes made after the last scan are not fully reflected in the dashboard until a new scan is run.
