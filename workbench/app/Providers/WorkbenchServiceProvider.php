<?php

namespace Workbench\App\Providers;

use BayPdf\Contracts\ScopeResolver;
use BayPdf\DocumentTypes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\LocalDeveloper;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $root = dirname(__DIR__, 2);
        $this->app['config']->set([
            'app.key' => is_file($root.'/storage/app-key') ? trim(file_get_contents($root.'/storage/app-key')) : null,
            'baypdf.enabled' => true,
            'baypdf.middleware' => ['web', LocalDeveloper::class],
            'baypdf.scoping.enabled' => true,
            'baypdf.font_cache' => $root.'/storage/fonts',
            'filesystems.disks.local' => ['driver' => 'local', 'root' => $root.'/storage/private', 'throw' => true],
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $root.'/database/database.sqlite',
            'session.driver' => 'file',
            'session.files' => $root.'/storage/sessions',
            'cache.default' => 'array',
        ]);
        $this->app->singleton(ScopeResolver::class, static fn (): ScopeResolver => new class implements ScopeResolver
        {
            public function resolve(): string
            {
                return 'workbench:local';
            }
        });
    }

    public function boot(DocumentTypes $types): void
    {
        Gate::define('manage-baypdf', fn ($user): bool => $user->getAuthIdentifier() === 'baypdf-local');
        $types->register('certificate', 'Certificate', [
            ['key' => 'recipient.name', 'label' => 'Recipient name', 'group' => 'Recipient', 'type' => 'text', 'required' => true, 'example' => 'Alex Morgan'],
            ['key' => 'course.title', 'label' => 'Course title', 'group' => 'Course', 'type' => 'text', 'required' => true, 'example' => 'Designing for the future'],
            ['key' => 'issued.date', 'label' => 'Issue date', 'group' => 'Document', 'type' => 'date', 'example' => '2026-10-06'],
            ['key' => 'document.reference', 'label' => 'Reference / QR', 'group' => 'Document', 'type' => 'qr', 'example' => 'BAYPDF-CERTIFICATE-001'],
        ]);
        $types->register('quote', 'Quote', [
            ['key' => 'customer.name', 'label' => 'Customer', 'type' => 'text', 'required' => true, 'example' => 'Alex Morgan'],
            ['key' => 'quote.total', 'label' => 'Total', 'type' => 'money', 'currency' => 'EUR', 'example' => 1250],
            ['key' => 'quote.date', 'label' => 'Date', 'type' => 'date', 'example' => '2026-10-06'],
        ]);
        $types->register('commercial_document', 'Multi-page commercial document', [
            ['key' => 'recipient.name', 'label' => 'Recipient', 'group' => 'Document', 'type' => 'text', 'required' => true, 'example' => 'Northwind Studio'],
            ['key' => 'document.reference', 'label' => 'Reference', 'group' => 'Document', 'type' => 'text', 'required' => true, 'example' => 'DOC-2026-0042'],
            ['key' => 'document.date', 'label' => 'Document date', 'group' => 'Document', 'type' => 'date', 'example' => '2026-10-09'],
            ['key' => 'document.summary', 'label' => 'Summary', 'group' => 'Document', 'type' => 'money', 'currency' => 'EUR', 'example' => 5320.40],
            ['key' => 'document.notes', 'label' => 'Notes', 'group' => 'Document', 'type' => 'text', 'example' => 'Thank you for reviewing this generic document example.'],
            [
                'key' => 'items',
                'label' => 'Items',
                'group' => 'Data',
                'type' => 'collection',
                'max_rows' => 100,
                'fields' => [
                    ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
                    ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'unit_price', 'label' => 'Unit price', 'type' => 'money', 'currency' => 'EUR'],
                    ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'currency' => 'EUR'],
                ],
                'example' => array_map(static fn (int $row): array => [
                    'description' => "Generic service line {$row}",
                    'quantity' => ($row % 3) + 1,
                    'unit_price' => 24.50 + $row,
                    'total' => (($row % 3) + 1) * (24.50 + $row),
                ], range(1, 55)),
            ],
        ]);
    }
}
