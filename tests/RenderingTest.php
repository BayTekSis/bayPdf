<?php

namespace BayPdf\Tests;

use BayPdf\Assets;
use BayPdf\DocumentTypes;
use BayPdf\DocumentValidator;
use BayPdf\PdfRenderer;
use BayPdf\VariableResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class RenderingTest extends TestCase
{
    private function document(array $overrides = []): array
    {
        return ['page' => ['size' => 'A4', 'orientation' => 'portrait'], 'elements' => [array_merge([
            'id' => 'title', 'type' => 'text', 'x' => 15, 'y' => 20, 'width' => 180, 'height' => 40,
            'content' => 'für öffentlich — İstanbul, Çağrı, şğüöçı', 'font_size' => 16,
        ], $overrides)]];
    }

    public function test_unicode_pdf_embeds_font_without_writing_vendor_cache(): void
    {
        $pdf = app(PdfRenderer::class)->render($this->document());
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('/FontFile2', $pdf);
        $this->assertStringContainsString('/ToUnicode', $pdf);
        $this->assertDirectoryExists(config('baypdf.font_cache').'/unifont');
        $this->assertFileDoesNotExist(__DIR__.'/../vendor/setasign/tfpdf/font/unifont/dejavusans.mtx.php');
        if (! is_dir(__DIR__.'/../.artifacts')) {
            mkdir(__DIR__.'/../.artifacts', 0777, true);
        }
        file_put_contents(__DIR__.'/../.artifacts/unicode.pdf', $pdf);
    }

    public function test_dynamic_variables_resolve_nested_values_defaults_dates_and_money(): void
    {
        $schema = [
            ['key' => 'customer.name', 'type' => 'text', 'required' => true],
            ['key' => 'invoice.total', 'type' => 'money', 'currency' => 'TRY'],
            ['key' => 'issued', 'type' => 'date', 'format' => 'd.m.Y'],
            ['key' => 'note', 'type' => 'text', 'default' => 'Thank you'],
        ];
        $values = app(VariableResolver::class)->resolve($schema, [
            'customer' => ['name' => 'Çağrı'], 'invoice.total' => 1234.5, 'issued' => '2026-10-06',
        ]);
        $this->assertSame(['customer.name' => 'Çağrı', 'invoice.total' => '1,234.50 TRY', 'issued' => '06.10.2026', 'note' => 'Thank you'], $values);
    }

    public function test_missing_required_value_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(VariableResolver::class)->resolve([['key' => 'name', 'type' => 'text', 'required' => true]], []);
    }

    public function test_invalid_calendar_date_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(VariableResolver::class)->resolve([['key' => 'date', 'type' => 'date']], ['date' => '2026-02-30']);
    }

    public function test_unknown_variable_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(PdfRenderer::class)->render($this->document(['type' => 'variable', 'variable' => 'unknown']));
    }

    public function test_text_overflow_fails_instead_of_silently_losing_content(): void
    {
        $this->expectException(ValidationException::class);
        app(PdfRenderer::class)->render($this->document(['height' => 2]));
    }

    public function test_element_cannot_extend_outside_page(): void
    {
        $this->expectException(ValidationException::class);
        app(PdfRenderer::class)->render($this->document(['x' => 200, 'width' => 30]));
    }

    public function test_landscape_dimensions_match_pdf_media_box(): void
    {
        $document = $this->document();
        $document['page'] = ['size' => 'A5', 'orientation' => 'landscape'];
        $pdf = app(PdfRenderer::class)->render($document);
        $this->assertStringContainsString('/MediaBox [0 0 595.28 419.53]', $pdf);
    }

    public function test_png_and_qr_are_embedded_in_pdf(): void
    {
        Storage::fake('local');
        $key = app(Assets::class)->store(UploadedFile::fake()->image('logo.png', 100, 80));
        $document = $this->document(['type' => 'image', 'asset' => $key]);
        $document['elements'][] = ['id' => 'qr', 'type' => 'qr', 'x' => 20, 'y' => 100, 'width' => 35, 'height' => 35, 'content' => 'BAYPDF-2026'];
        $this->assertStringContainsString('/Subtype /Image', app(PdfRenderer::class)->render($document));
        $this->assertStringStartsWith('baypdf/assets/', $key);
        $this->assertSame(Storage::disk('local')->get($key), app(Assets::class)->bytes($key));
    }

    public function test_remote_image_urls_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes('https://127.0.0.1/private');
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes('baypdf/assets/../../secret.png');
    }

    public function test_unrelated_storage_images_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes('user/avatar.png');
    }

    public function test_duplicate_variable_names_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(DocumentTypes::class)->register('invoice', 'Invoice', [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['key' => 'name', 'label' => 'Other name', 'type' => 'text'],
        ]);
    }

    public function test_document_type_has_no_jugend_dependencies(): void
    {
        app(DocumentTypes::class)->register('invoice', 'Invoice', [['key' => 'client.name', 'label' => 'Client', 'type' => 'text']]);
        app(DocumentTypes::class)->register('certificate', 'Certificate', []);
        $this->assertCount(2, app(DocumentTypes::class)->all());
        $this->assertSame([], app(DocumentValidator::class)->blank()['elements']);
    }
}
