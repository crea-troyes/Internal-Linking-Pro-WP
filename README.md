# ILP - Internal Linking Pro

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B%20%7C%207.0-tested-21759b?logo=wordpress)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-2.1.0-blue.svg)](changelog.txt)

Audit de maillage interne pour WordPress avec cartographie, silos SEO, détection de cannibalisation et suggestions Gutenberg.

[Français](#français) | [English](#english)

---

## Français

### Présentation

ILP - Internal Linking Pro est une extension WordPress d'audit éditorial et SEO consacrée au maillage interne. Elle analyse les articles et pages publiés, calcule des métriques utiles à la priorisation et fournit des outils d'action dans l'administration WordPress et dans l'éditeur Gutenberg.

L'analyse est lancée manuellement et mise en cache. L'extension n'ajoute aucun traitement aux pages publiques et n'envoie aucune donnée vers un service externe.

### Fonctionnalités

- Dashboard synthétique avec score global et métriques de maillage.
- Tableau détaillé des articles et pages avec liens entrants, liens sortants, liens externes, densité et PageRank interne.
- Détection des articles isolés et contenus sans lien interne entrant.
- Suggestions de liens internes à créer.
- Cartographie interactive du réseau de liens avec `vis-network`.
- Analyse des silos : cohérence, fuite, problèmes et recommandations.
- Détection des risques de cannibalisation SEO entre contenus.
- Panneau Gutenberg de suggestions contextuelles avec insertion de lien sur le texte sélectionné.
- Exclusion manuelle de contenus par ID.
- Traductions incluses : anglais par défaut, français et espagnol.

### Compatibilité

- WordPress `6.0+`
- Testé jusqu'à WordPress `7.0`
- PHP `7.4+`
- Éditeur de blocs Gutenberg moderne

### Installation

1. Téléchargez le dépôt ou une archive de release.
2. Placez le dossier dans `wp-content/plugins/`.
3. Activez **ILP - Internal Linking Pro** depuis l'administration WordPress.
4. Ouvrez **Outils > Internal Linking**.
5. Lancez un premier scan manuel.

### Utilisation

Le dashboard centralise les métriques globales. Les onglets permettent ensuite d'explorer les contenus, les articles isolés, les orphelins globaux, les suggestions, les conflits SEO, les silos et la cartographie.

Dans Gutenberg, sélectionnez une expression dans un paragraphe ou un titre. Le panneau **Suggestions de maillage** privilégie les correspondances directes dans les titres, puis affine le classement avec le slug, le contenu et le contexte local.

### Optimisation SEO interne

L'extension aide à :

- identifier les contenus insuffisamment reliés ;
- renforcer les pages stratégiques ;
- différencier les ancres ;
- limiter les fuites entre silos ;
- repérer les contenus éditorialement concurrents ;
- créer des liens pertinents pendant la rédaction.

Les métriques restent des aides à la décision. Elles ne remplacent pas une validation éditoriale humaine.

### Captures d'écran

Les emplacements recommandés et la convention de nommage sont décrits dans [`docs/screenshots/README.md`](docs/screenshots/README.md).

### Informations techniques

- Les scans sont déclenchés depuis l'administration avec nonce et capacité `manage_options`.
- Les résultats du scan sont stockés dans l'option `cma_scan_data`.
- Les analyses lourdes et suggestions Gutenberg utilisent des transients.
- L'endpoint REST Gutenberg vérifie que l'utilisateur peut modifier l'article concerné.
- Les ressources d'administration ne sont chargées que sur la page du plugin.
- Le JavaScript Gutenberg repose sur les API WordPress natives.

### Structure du projet

```text
assets/                 Styles, scripts et dépendance locale vis-network
includes/               Classes PHP du plugin
includes/views/         Vues de l'administration
languages/              Catalogue de traduction et traductions FR / ES
docs/screenshots/       Convention et futures captures d'écran
.github/                Modèles GitHub et validation PHP
crea-maillage-audit.php Point d'entrée WordPress
uninstall.php           Nettoyage des données à la désinstallation
readme.txt              Fiche compatible WordPress.org
```

### Roadmap

- Ajouter des tests automatisés WordPress pour les métriques principales.
- Ajouter une commande WP-CLI pour lancer le scan.
- Ajouter des captures d'écran publiques.
- Étendre les contrôles qualité continus avec WordPress Coding Standards.

### Issues et support

Utilisez les [issues GitHub](../../issues) pour signaler un bug reproductible ou proposer une amélioration. Pour une faille de sécurité, suivez [`SECURITY.md`](SECURITY.md) et évitez toute publication publique prématurée.

### Contribution

Consultez [`CONTRIBUTING.md`](CONTRIBUTING.md) et [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).

### Versioning et releases

Le projet suit [Semantic Versioning](https://semver.org/lang/fr/). Pour chaque release :

1. mettez à jour la version dans `crea-maillage-audit.php` ;
2. mettez à jour `Stable tag` dans `readme.txt` ;
3. documentez les changements dans `changelog.txt` ;
4. créez un tag Git annoté, par exemple `v2.1.0` ;
5. publiez une GitHub Release avec une archive installable.

### Licence et crédits

Le plugin est distribué sous licence [`GPL-2.0-or-later`](LICENSE). La cartographie utilise `vis-network`; consultez [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

---

## English

### Overview

ILP - Internal Linking Pro is a WordPress editorial and SEO audit plugin focused on internal linking. It analyzes published posts and pages, computes prioritization metrics, and provides actionable tools in the WordPress administration area and the Gutenberg editor.

Analysis is triggered manually and cached. The plugin does not add processing to public pages and does not send data to external services.

### Features

- Summary dashboard with a global score and internal-link metrics.
- Detailed content table with incoming links, outgoing links, external links, density, and internal PageRank.
- Detection of isolated posts and content without incoming internal links.
- Internal-link opportunity suggestions.
- Interactive link-network graph powered by `vis-network`.
- Silo analysis: coherence, leakage, issues, and recommendations.
- SEO cannibalization-risk detection across content.
- Gutenberg suggestion panel with link insertion on selected text.
- Manual content exclusion by ID.
- Included translations: English by default, French, and Spanish.

### Compatibility

- WordPress `6.0+`
- Tested up to WordPress `7.0`
- PHP `7.4+`
- Modern Gutenberg block editor

### Installation

1. Download the repository or a release archive.
2. Place the folder in `wp-content/plugins/`.
3. Activate **ILP - Internal Linking Pro** in the WordPress administration area.
4. Open **Tools > Internal Linking**.
5. Run the first manual scan.

### Usage

The dashboard centralizes global metrics. Dedicated tabs then expose content, isolated posts, global orphans, suggestions, SEO conflicts, silos, and the graph view.

In Gutenberg, select an expression in a paragraph or heading. The **Internal linking suggestions** panel prioritizes direct title matches, then refines ranking with slug, content, and local context signals.

### Internal SEO Optimization

The plugin helps editors:

- identify weakly linked content;
- strengthen strategic pages;
- diversify anchor text;
- reduce silo leakage;
- identify competing editorial content;
- create relevant links while writing.

Metrics remain decision-support indicators and should be reviewed editorially.

### Screenshots

Recommended screenshot slots and naming conventions are documented in [`docs/screenshots/README.md`](docs/screenshots/README.md).

### Technical Notes

- Scans are triggered in the administration area with a nonce and the `manage_options` capability.
- Scan results are stored in the `cma_scan_data` option.
- Heavy analysis and Gutenberg suggestions use transients.
- The Gutenberg REST endpoint verifies that the user can edit the requested post.
- Administration assets load only on the plugin page.
- Gutenberg JavaScript uses native WordPress APIs.

### Project Structure

```text
assets/                 Styles, scripts, and local vis-network dependency
includes/               Plugin PHP classes
includes/views/         Administration views
languages/              Translation template and FR / ES translations
docs/screenshots/       Screenshot convention and future screenshots
.github/                GitHub templates and PHP validation
crea-maillage-audit.php WordPress entry point
uninstall.php           Data cleanup on uninstall
readme.txt              WordPress.org-compatible plugin page
```

### Roadmap

- Add automated WordPress tests for primary metrics.
- Add a WP-CLI scan command.
- Add public screenshots.
- Extend continuous quality checks with WordPress Coding Standards.

### Issues and Support

Use [GitHub issues](../../issues) to report a reproducible bug or request an improvement. For a security issue, follow [`SECURITY.md`](SECURITY.md) and avoid premature public disclosure.

### Contributing

Read [`CONTRIBUTING.md`](CONTRIBUTING.md) and [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).

### Versioning and Releases

The project follows [Semantic Versioning](https://semver.org/). For each release:

1. update the version in `crea-maillage-audit.php`;
2. update `Stable tag` in `readme.txt`;
3. document changes in `changelog.txt`;
4. create an annotated Git tag, for example `v2.1.0`;
5. publish a GitHub Release with an installable archive.

### License and Credits

The plugin is licensed under [`GPL-2.0-or-later`](LICENSE). The graph view uses `vis-network`; see [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).
