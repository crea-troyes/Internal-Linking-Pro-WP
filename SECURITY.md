# Security Policy

## Supported Version

Security fixes are applied to the latest published release.

| Version | Supported |
| --- | --- |
| `2.5.x` | Yes |
| `< 2.5` | No |

## Reporting a Vulnerability

Do not open a public GitHub issue for a suspected vulnerability.

Send a private report to the repository owner through the private security-reporting channel configured on GitHub. If GitHub private vulnerability reporting is not enabled yet, enable it before publishing the repository:

`Settings > Security > Private vulnerability reporting`

Include:

- affected plugin version;
- WordPress and PHP versions;
- required user role;
- reproduction steps;
- impact assessment;
- suggested mitigation, if available.

The maintainer should acknowledge receipt within 7 days and coordinate disclosure after a fix is available.

## Security Design

- Dashboard scans require `manage_options` and an AJAX nonce.
- Settings updates and conflict recalculation require `manage_options` and an admin-post nonce.
- Gutenberg REST suggestions require permission to edit the requested post.
- User-controlled request data is sanitized before use.
- HTML output is escaped according to context.
- Analysis runs locally and does not send content to third-party services.
