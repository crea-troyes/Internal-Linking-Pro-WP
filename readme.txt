=== Crea3 Internal Linking Pro ===
Contributors: crea3
Tags: seo, internal links, gutenberg, pagerank, content audit
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit your internal links, find orphaned content, visualize link structure, analyze SEO silos, and get Gutenberg link suggestions.

== Description ==

Crea3 Internal Linking Pro is an administration tool for auditing the internal linking of published WordPress posts and pages.

Scans are started manually and their results are cached. The plugin does not add analysis work to front-end page requests.

= Internal linking audit =

* Review incoming and outgoing internal links for every analyzed post and page.
* Count outgoing external links and internal links per 100 words.
* Identify globally orphaned content and isolated posts.
* Compare content using an internal linking score and a normalized PageRank indicator.
* Filter reports by posts, pages, or both.
* Exclude selected content IDs from the analysis.

= Visual analysis =

* Use the summary dashboard to review the main internal-link metrics.
* Explore relationships in an interactive link graph.
* Review detected content clusters and their pillar pages.
* Measure silo coherence, internal leakage, lateral coverage, and pillar-page distribution.

= Editorial assistance =

* Find contextual internal-link opportunities between related content.
* Review potential SEO cannibalization conflicts and suggested actions.
* Open link suggestions from the Gutenberg editor.
* Insert a suggested link into selected paragraph or heading text.

= Privacy and performance =

All analysis is performed locally on the WordPress installation. The plugin does not send analyzed content to an external service and does not include tracking or telemetry.

The front end is not scanned on every visit. Analysis runs only after an authorized administrator starts a scan from the plugin screen.

= Languages =

The interface is available in:

* English (default)
* French (`fr_FR`)
* Spanish (`es_ES`)
* German (`de_DE`)
* Italian (`it_IT`)

The administration interface is responsive and supports desktop, tablet, and mobile screen sizes.

== Installation ==

1. Install and activate Crea3 Internal Linking Pro from the WordPress Plugins screen, or upload the plugin folder to `/wp-content/plugins/`.
2. In the WordPress administration area, open Tools > Internal Linking.
3. Select the content filter you want to use.
4. Click Run scan and wait for the confirmation message.
5. Review the dashboard and the available audit views.

Run a new scan after making substantial content or internal-link changes. Use Clear cache if you want to remove the stored analysis before scanning again.

== Frequently Asked Questions ==

= Does the plugin affect front-end performance? =

The plugin does not run a site-wide audit during front-end requests. Scans are launched manually in the administration area and their results are cached.

= Does the plugin send content or analytics data to third parties? =

No. Content analysis is performed locally in WordPress. The plugin does not include tracking or telemetry.

= Which content types are analyzed? =

The audit analyzes published posts and pages. Specific content IDs can be excluded from the plugin settings.

= Are links to the home page included? =

Yes. When WordPress uses a static front page, links to the site root are associated with that page and included in its incoming-link count.

= Why should I run a new scan? =

Reports use cached scan data. Run a new scan after publishing, deleting, or substantially editing content and after changing internal links.

= How do Gutenberg suggestions work? =

Select text in a paragraph or heading. The editor sidebar uses titles, slugs, content, existing links, and local context to rank relevant internal-link opportunities. You remain in control of every inserted link.

= Can content be excluded from an audit? =

Yes. Enter the post or page IDs in the plugin settings. Excluded content is removed from the current reports without deleting the content itself.

= Which permissions are required? =

The audit screens, settings, cache actions, and manual scans are restricted to administrators with the `manage_options` capability.

= What happens when the plugin is uninstalled? =

The plugin removes its saved options, cached audit data, and editor-suggestion transients during uninstall.

== Screenshots ==

1. Dashboard overview.
2. Detailed content table.
3. Silo analysis.
4. Interactive graph view.
5. Cannibalization conflicts.

== Changelog ==

= 2.7.0 =

* Fixed the static front-page title in content tables and the Most linked dashboard card, including previously cached scans.
* Added a site-name fallback when a static front page does not have its own title.
* Added explicit recognition and incoming-link counting for absolute and relative links pointing to the site root.
* Added complete French, Spanish, German, and Italian translation catalogs, with English remaining the default language.
* Added support for WordPress.org language packs with bundled translation catalogs as a fallback.
* Synchronized all PO and MO files with the current 232 translatable strings and validated plurals and format placeholders.
* Made all administration views responsive, including navigation tabs, controls, dashboards, data tables, suggestions, silos, conflict reports, graphs, and settings.
* Added controlled horizontal scrolling for complex data tables on tablet and mobile screens.
* Fixed malformed CSS that could prevent some responsive rules from being applied consistently.
* Improved wrapping for long titles, URLs, translated labels, tooltips, buttons, notices, and graph details.
* Replaced an exclusionary content query with in-memory exclusion handling to avoid the `post__not_in` performance warning.
* Documented intentional read-only request parameters, the WordPress core `the_content` hook, and uninstall cleanup for Plugin Check.
* Removed the flagged `plugin_locale` hook call while preserving locale-aware translation loading.
* Corrected WordPress.org readme metadata, including the plugin name and `Tested up to` format.
* Expanded and reorganized the readme with feature, privacy, performance, language, installation, FAQ, screenshot, and upgrade information.
* Removed `.DS_Store` files from the plugin distribution.
* Updated the plugin header, asset version constant, stable tag, translation metadata, and language catalogs to version 2.7.0.

= 2.6.2 =

* Prefixed view and uninstall variables for WordPress coding standards compliance.
* Removed the discouraged execution-time override from manual scans.
* Removed hidden operating-system files from the distribution.

= 2.6.1 =

* Fixed the translation domain to match the plugin slug.
* Updated the translation template metadata and filename.

= 2.6.0 =

* Renamed the plugin and translation domain to Crea3 Internal Linking Pro.
* Prefixed global identifiers and added a non-destructive legacy-data migration.
* Moved administration JavaScript and CSS into properly enqueued assets.
* Hardened administration actions and REST handling for directory review.

== Upgrade Notice ==

= 2.7.0 =

Improves front-page analysis, multilingual support, responsive administration screens, and WordPress.org directory compliance.
