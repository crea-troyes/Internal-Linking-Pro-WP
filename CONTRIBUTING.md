# Contributing

Thank you for considering a contribution to ILP - Internal Linking Pro.

## Before Opening an Issue

- Check whether the issue already exists.
- Confirm the problem on a recent WordPress installation.
- Include the WordPress version, PHP version, plugin version, and browser when relevant.
- Describe the smallest reproducible scenario.
- Do not publish security vulnerabilities in a public issue. Follow [`SECURITY.md`](SECURITY.md).

## Development Setup

1. Clone the repository into `wp-content/plugins/crea-maillage-audit`.
2. Activate the plugin from WordPress.
3. Open **Tools > Internal Linking** and run a scan.
4. Test Gutenberg changes with a published content catalogue containing representative posts and pages.

The plugin has no required Composer or npm dependency. The graph library is intentionally vendored in `assets/vendor/` to avoid front-end network requests.

## Code Guidelines

- Keep compatibility with PHP `7.4+` and WordPress `6.0+`.
- Follow WordPress Coding Standards where practical.
- Prefix new plugin functions, classes, options, transients, and hooks with `cma_`.
- Escape output as late as possible.
- Sanitize and validate request data before use.
- Protect state-changing actions with capability checks and nonces.
- Avoid loading scripts outside the relevant administration or editor screen.
- Preserve the existing behavior of unrelated dashboard tabs.

## Quality Checks

Run PHP syntax checks:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Run JavaScript syntax checks when Node.js is available:

```bash
node --check assets/admin.js
node --check assets/editor-link-suggestions.js
```

Run WordPress Coding Standards when PHPCS and WPCS are installed:

```bash
phpcs -p
```

## Pull Requests

- Keep pull requests focused.
- Document user-visible changes in `changelog.txt`.
- Update `readme.txt` when WordPress.org-facing behavior changes.
- Add manual test instructions for affected dashboard tabs or Gutenberg workflows.

By participating, you agree to follow [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).
