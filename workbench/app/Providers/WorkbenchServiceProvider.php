<?php

namespace Workbench\App\Providers;

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
            'baypdf.font_cache' => $root.'/storage/fonts',
            'filesystems.disks.local' => ['driver' => 'local', 'root' => $root.'/storage/private', 'throw' => true],
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $root.'/database/database.sqlite',
            'session.driver' => 'file',
            'session.files' => $root.'/storage/sessions',
            'cache.default' => 'array',
        ]);
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
    }
}
