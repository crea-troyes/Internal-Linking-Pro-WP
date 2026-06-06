=== ILP - Internal Linking Pro ===
Contributors: creatroyes
Tags: seo, internal links, gutenberg, pagerank, content audit
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit and improve WordPress internal linking with graph analysis, silos, SEO conflicts, and Gutenberg suggestions.

== Description ==

ILP - Internal Linking Pro provides a manual, cached internal-link audit for published WordPress posts and pages.

Main features:

* Summary dashboard with internal-link metrics.
* Detailed content table with incoming and outgoing internal links.
* Detection of isolated posts and global orphan content.
* Internal-link opportunity suggestions.
* Interactive graph view.
* Silo coherence and leakage analysis.
* SEO cannibalization-risk detection.
* Gutenberg sidebar suggestions with quick link insertion.
* Manual exclusions by content ID.
* English, French, and Spanish translations.

The plugin runs audits only when an administrator requests a scan. It does not add front-end processing and does not send site data to an external service.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.
3. Open Tools > Internal Linking.
4. Click Run scan.

== Frequently Asked Questions ==

= Does the plugin affect front-end performance? =

No. Scans run manually in the administration area and their results are cached.

= Does the plugin send data to third-party services? =

No. Analysis is performed locally in WordPress.

= Which content types are analyzed? =

Published posts and pages are analyzed. Individual IDs can be excluded in the plugin settings.

= How do Gutenberg suggestions work? =

Select text in a paragraph or heading. The sidebar prioritizes direct title matches, then uses slug, content, and local context signals to refine the ranking.

== Screenshots ==

1. Dashboard overview.
2. Detailed content table.
3. Interactive graph view.
4. Silo analysis.
5. Cannibalization conflicts.
6. Gutenberg internal-link suggestions.

== Changelog ==

= 2.1.0 =

* Redesigned the administration interface across the dashboard, tabs, conflicts, and graph details.
* Added responsive layouts for laptop, tablet, and mobile screens while preserving the large-screen interface.
* Modernized dashboard statistics, cluster tables, filters, and scan controls.
* Added cluster URLs to the dashboard and improved empty-title handling with an Accueil fallback.
* Improved Gutenberg suggestion ranking and refreshed candidates from published content.
* Improved administration asset loading, security guards, and uninstall cleanup.

== Upgrade Notice ==

= 2.1.0 =

Introduces a responsive administration interface and improves audit readability across all plugin tabs.
