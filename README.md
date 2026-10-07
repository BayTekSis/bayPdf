# BayPdf

Edit document layouts without changing PHP code for every adjustment. BayPdf adds versioned PDF templates and a visual designer to Laravel applications.

**Status:** Preparing the first release. Source and CI are available on [GitHub](https://github.com/BayTekSis/bayPdf). Until a tagged release is registered on Packagist, install the development branch through Composer's VCS repository support.

## Run locally

From the BayPdf source directory:

```bash
composer install
npm ci
npm run build
composer workbench:prepare
composer serve
```

Open [http://127.0.0.1:8127/baypdf](http://127.0.0.1:8127/baypdf). The workbench uses its own SQLite database and accepts only loopback requests. Its development user exists only in the workbench. Do not expose it to the internet or through a reverse proxy.

On Windows PowerShell, use `npm.cmd` and `npx.cmd` if script execution restrictions prevent `npm` or `npx` from running.

## Requirements

| Use | Requirement |
|---|---|
| Application runtime | PHP 8.3+, Laravel 12 or 13, GD, mbstring |
| Database | Laravel connection and the corresponding PDO driver |
| Files | Private storage disk and a writable font cache directory |
| Package development | Composer 2, Node 22.12+ or 24+, npm |
| Tests | SQLite, Orchestra Testbench and development dependencies |

**Applications consuming the package do not need Node/npm.** Compiled Vue assets, CSS and fonts ship in the Composer package. `package.json` is private; there is no separate npm package.

## Install in a Laravel application

Before the first Packagist release, run these commands **in the consuming application**:

```bash
composer config repositories.baypdf vcs https://github.com/BayTekSis/bayPdf
composer require bay/baypdf:dev-master
php artisan vendor:publish --tag=baypdf-config
php artisan vendor:publish --tag=baypdf-assets
php artisan migrate
```

The development branch can change; use a tagged version for production. Once `0.1.0` is available on Packagist, use `composer require bay/baypdf:^0.1` without the VCS configuration. These commands publish package files and run the application's pending migrations, including the two `baypdf_*` tables. See the [installation guide](https://github.com/BayTekSis/bayPdf/blob/master/docs/INSTALLATION.md).

Set `enabled` to `true` in `config/baypdf.php`. The designer is disabled by default. When enabled, it uses `web`, `auth` and the `manage-baypdf` Gate. Connect the Gate to the application's permission system:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('manage-baypdf', fn (User $user): bool =>
    $user->can('manage-documents')
);
```

Define the `manage-documents` ability in the application. BayPdf grants no access automatically. Authenticated users with this permission can open `/baypdf`.

The designer has one shared template area and no built-in tenant isolation. Users who pass the Gate can access all templates and assets; do not expose these routes to tenant customers.

## Register document variables

In the application service provider's `boot` method:

```php
use BayPdf\DocumentTypes;

public function boot(DocumentTypes $types): void
{
    $types->register('certificate', 'Certificate of participation', [
        [
            'key' => 'recipient.name',
            'label' => 'Recipient name',
            'group' => 'Recipient',
            'type' => 'text',
            'required' => true,
            'example' => 'Ayşe Yılmaz',
        ],
        [
            'key' => 'issued.date',
            'label' => 'Issue date',
            'type' => 'date',
            'example' => '2026-10-06',
            'format' => 'd.m.Y',
        ],
    ]);
}
```

Choose **New template**, select a document type, then click a variable to add it to the page. Position elements by dragging, using arrow keys or entering millimetre values. Save the draft, check the PDF preview and publish the version.

## Generate a PDF from a published version

In an application controller or job, after authorizing access:

```php
use BayPdf\Models\TemplateVersion;
use BayPdf\TemplateManager;

// $versionId identifies a published version selected and authorized by the application.
$version = TemplateVersion::query()->findOrFail($versionId);

$pdf = app(TemplateManager::class)->render($version, [
    'recipient' => ['name' => 'Ayşe Yılmaz'],
    'issued' => ['date' => '2026-10-06'],
]);

return response($pdf, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="certificate.pdf"',
    'Cache-Control' => 'private, no-store',
]);
```

The application authorizes and supplies the data; BayPdf does not fetch it from application models. Previews use variable examples, falling back to defaults. Published versions are immutable; clone a new draft to make changes.

## Scope and limitations

- Text, variables, PNG/JPEG images, QR codes, lines and rectangles; layer ordering, hiding, duplication and undo/redo.
- A4, A5 and Letter in portrait or landscape, with a fixed single-page layout. No automatic pagination or flowing tables.
- DejaVu Sans regular, bold and italic fonts; German, Turkish and English designer translations.
- Text overflow produces a validation error instead of silently truncating content.
- No HTML/PDF template import, SVG/WebP support or remote image downloads.
- PDF signatures, QR verification services and document archiving belong to the host application.

## Documentation

These guides are currently in Turkish and available in the source repository. The `docs/` directory is excluded from the Composer distribution archive.

| Guide | Contents |
|---|---|
| [Installation](https://github.com/BayTekSis/bayPdf/blob/master/docs/INSTALLATION.md) | Composer, configuration, access and updates |
| [Variables](https://github.com/BayTekSis/bayPdf/blob/master/docs/VARIABLES.md) | Types, examples, required fields and snapshots |
| [Templates and API](https://github.com/BayTekSis/bayPdf/blob/master/docs/TEMPLATES.md) | JSON layout, versions and PHP API |
| [Security](https://github.com/BayTekSis/bayPdf/blob/master/docs/SECURITY.md) | Permissions, private files and boundaries |
| [Development](https://github.com/BayTekSis/bayPdf/blob/master/docs/DEVELOPMENT.md) | Testbench, skills, tests and builds |
| [GitHub and distribution](https://github.com/BayTekSis/bayPdf/blob/master/docs/PUBLISHING.md) | Repository, CI, versioning and Packagist |
| [Troubleshooting](https://github.com/BayTekSis/bayPdf/blob/master/docs/TROUBLESHOOTING.md) | Installation and output errors |
| [Architecture](https://github.com/BayTekSis/bayPdf/blob/master/docs/ARCHITECTURE.md) | Technical decisions |
| [Verification](https://github.com/BayTekSis/bayPdf/blob/master/docs/VERIFICATION.md) | Executed checks and environment |

## Development checks

```bash
composer qa
npm test
npm run build
composer workbench:prepare
npx playwright install chromium
npm run test:browser
```

The package uses Orchestra Testbench. Run package commands with `php vendor/bin/testbench`, rather than `php artisan` at the package root.

[Contributing](CONTRIBUTING.md) · [Changelog](CHANGELOG.md) · [Public API](PUBLIC_API.md) · [Security policy](SECURITY.md)

## License and credits

BayPdf is licensed under the [MIT License](LICENSE).

Copyright © 2026 BayPass.

Developed by [Mehmet BAYINDIR](https://github.com/BayTekSis).

- tFPDF / FPDF for PDF generation.
- endroid/qr-code for QR code generation.
- Vue for the visual designer.
- DejaVu Fonts for bundled font support.

Third-party components retain their own licenses and copyright notices. See [third-party notices](THIRD_PARTY_NOTICES.md).
