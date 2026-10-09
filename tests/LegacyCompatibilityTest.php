<?php

namespace BayPdf\Tests;

use BayPdf\Assets;
use BayPdf\DocumentTypes;
use BayPdf\DocumentValidator;
use BayPdf\PdfRenderer;
use BayPdf\TemplateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class LegacyCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private string $asset;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('baypdf.pdf_compression', false);
        Storage::fake('local');
        $this->asset = app(Assets::class)->store(UploadedFile::fake()->image('legacy.png', 60, 40));
        app(DocumentTypes::class)->register('legacy_matrix', 'Legacy matrix', $this->variables());
    }

    public function test_legacy_elements_variables_page_formats_and_orientations_remain_single_page(): void
    {
        foreach (['A4', 'A5', 'Letter'] as $size) {
            foreach (['portrait', 'landscape'] as $orientation) {
                $document = $this->document($size, $orientation);
                $validated = app(DocumentValidator::class)->validate($document, $this->variables());
                $pdf = app(PdfRenderer::class)->render($document, $this->data(), $this->variables());

                $this->assertArrayNotHasKey('schema_version', $validated);
                $this->assertArrayNotHasKey('flow', $validated);
                $this->assertSame(1, preg_match_all('/\/Type \/Page\b/', $pdf));
                $this->assertStringContainsString('/Subtype /Image', $pdf);
                $this->assertSame(4, substr_count($pdf, '/Subtype /Image'));
                foreach (['Legacy fixed page', 'Legacy Recipient', '09.10.2026', '12.50', '125.40 EUR'] as $text) {
                    $this->assertStringContainsString(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'), $pdf);
                }
            }
        }
    }

    public function test_legacy_publish_preview_render_and_clone_preserve_the_original_schema(): void
    {
        $manager = app(TemplateManager::class);
        $draft = $manager->create('Legacy document', 'legacy_matrix')->versions->first();
        $draft = $manager->save($draft, $this->document('A4', 'portrait'), 1);

        $this->assertStringStartsWith('%PDF-', $manager->preview($draft));
        $published = $manager->publish($draft, 2);
        $this->assertStringStartsWith('%PDF-', $manager->render($published, $this->data()));

        $clone = $manager->cloneDraft($published);
        $this->assertSame($published->document, $clone->document);
        $this->assertArrayNotHasKey('schema_version', $clone->document);
        $this->assertArrayNotHasKey('flow', $clone->document);
    }

    private function document(string $size, string $orientation): array
    {
        return [
            'page' => ['size' => $size, 'orientation' => $orientation],
            'elements' => [
                ['id' => 'text', 'type' => 'text', 'x' => 8, 'y' => 8, 'width' => 125, 'height' => 9, 'content' => 'Legacy fixed page'],
                ['id' => 'text-variable', 'type' => 'variable', 'x' => 8, 'y' => 20, 'width' => 125, 'height' => 8, 'variable' => 'name'],
                ['id' => 'date-variable', 'type' => 'variable', 'x' => 8, 'y' => 30, 'width' => 60, 'height' => 8, 'variable' => 'date'],
                ['id' => 'number-variable', 'type' => 'variable', 'x' => 73, 'y' => 30, 'width' => 60, 'height' => 8, 'variable' => 'quantity'],
                ['id' => 'money-variable', 'type' => 'variable', 'x' => 8, 'y' => 40, 'width' => 125, 'height' => 8, 'variable' => 'amount'],
                ['id' => 'static-image', 'type' => 'image', 'x' => 8, 'y' => 52, 'width' => 24, 'height' => 18, 'asset' => $this->asset],
                ['id' => 'image-variable', 'type' => 'image', 'x' => 36, 'y' => 52, 'width' => 24, 'height' => 18, 'variable' => 'image'],
                ['id' => 'qr', 'type' => 'qr', 'x' => 64, 'y' => 52, 'width' => 18, 'height' => 18, 'content' => 'LEGACY-QR'],
                ['id' => 'qr-variable', 'type' => 'qr', 'x' => 87, 'y' => 62, 'width' => 18, 'height' => 18, 'variable' => 'code'],
                ['id' => 'line', 'type' => 'line', 'x' => 87, 'y' => 58, 'width' => 46, 'height' => 1],
                ['id' => 'rectangle', 'type' => 'rectangle', 'x' => 8, 'y' => 78, 'width' => 125, 'height' => 20, 'fill' => '#e5edde'],
            ],
        ];
    }

    private function variables(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'example' => 'Legacy Recipient'],
            ['key' => 'date', 'label' => 'Date', 'type' => 'date', 'format' => 'd.m.Y', 'example' => '2026-10-09'],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'decimals' => 2, 'example' => 12.5],
            ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'currency' => 'EUR', 'example' => 125.40],
            ['key' => 'image', 'label' => 'Image', 'type' => 'image', 'example' => $this->asset],
            ['key' => 'code', 'label' => 'Code', 'type' => 'qr', 'example' => 'LEGACY-VARIABLE-QR'],
        ];
    }

    private function data(): array
    {
        return ['name' => 'Legacy Recipient', 'date' => '2026-10-09', 'quantity' => 12.5, 'amount' => 125.40, 'image' => $this->asset, 'code' => 'LEGACY-VARIABLE-QR'];
    }
}
