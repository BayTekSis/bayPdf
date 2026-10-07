# Security policy

## Supported versions

BayPdf is preparing its first release. Security fixes currently target the `master` branch. After the first release, use the latest available patch in the `0.1` series.

## Reporting a vulnerability

Report security vulnerabilities privately through [GitHub vulnerability reporting](https://github.com/BayTekSis/bayPdf/security/advisories/new). Include the affected version, reproduction steps and the expected impact. Use synthetic data and omit credentials, personal information and production documents.

Do not disclose exploitable details in public issues. Public issues are for non-sensitive bugs and feature requests.

If the private reporting form is unavailable, contact the [maintainer](https://github.com/BayTekSis) to arrange a private channel before sending any vulnerability details.

## Deployment boundaries

The designer is disabled by default and requires host authentication and the `manage-baypdf` Gate. It has one shared template area, without tenant isolation. Store assets on a private disk and keep the development workbench off the public internet.

See the [security guide](https://github.com/BayTekSis/bayPdf/blob/master/docs/SECURITY.md) for configuration and file handling boundaries.
