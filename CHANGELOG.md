# Changelog

## 0.1.0

## Added

- Added versioned PDF templates with immutable published versions and draft cloning.
- Added a visual designer with text, variables, images, QR codes, and layer controls.
- Added Unicode PDF generation and German, Turkish, and English designer translations.
- Added Laravel 12/13 support on PHP 8.3+, with compiled designer assets included.
- Required host authentication and authorization; the designer is disabled by default and has no built-in tenant isolation.
- Limited layouts to fixed single pages; automatic pagination and flowing tables are not supported.

## Fixed

- Rejected malformed dates and non-list element or variable schemas with validation errors.
- Fixed designer failures for variable groups named `constructor` or `__proto__`.
- Kept editing tools accessible when the template library grows.
