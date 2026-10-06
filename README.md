# BayPdf

Edit document layouts without changing PHP code for every adjustment. BayPdf adds versioned PDF templates and a visual designer to Laravel applications.

**Status:** Local development version. No GitHub remote or Packagist release exists yet. `bay/baypdf` is the development package name; its availability has not been verified.

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

Until a Packagist release is available, use a local Composer repository. Run these commands **in the consuming application**:

```bash
composer config repositories.baypdf path C:/laragon/www/bayPdf
composer require bay/baypdf:@dev
php artisan vendor:publish --tag=baypdf-config
php artisan vendor:publish --tag=baypdf-assets
php artisan migrate
```

Adjust the path to the local BayPdf checkout. These commands publish package files and run the application's pending migrations, including the two `baypdf_*` tables. See the [installation guide](docs/INSTALLATION.md).

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
| [Installation](docs/INSTALLATION.md) | Composer, configuration, access and updates |
| [Variables](docs/VARIABLES.md) | Types, examples, required fields and snapshots |
| [Templates and API](docs/TEMPLATES.md) | JSON layout, versions and PHP API |
| [Security](docs/SECURITY.md) | Permissions, private files and boundaries |
| [Development](docs/DEVELOPMENT.md) | Testbench, skills, tests and builds |
| [GitHub and distribution](docs/PUBLISHING.md) | Repository, CI, versioning and Packagist |
| [Troubleshooting](docs/TROUBLESHOOTING.md) | Installation and output errors |
| [Architecture](docs/ARCHITECTURE.md) | Technical decisions |
| [Verification](docs/VERIFICATION.md) | Executed checks and environment |

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

[Contributing](CONTRIBUTING.md) · [Changelog](CHANGELOG.md) · [Public API](PUBLIC_API.md)

## License and credits

BayPdf is open-source software licensed under the MIT License.

Copyright © 2026 BayPass.

BayPdf builds on several open-source projects and resources, including:

tFPDF / FPDF for PDF generation
endroid/qr-code for QR code generation
Vue for the visual designer
DejaVu Fonts for bundled font support

Third-party components remain subject to their respective licenses and copyright notices. See THIRD_PARTY_NOTICES.md for details.
