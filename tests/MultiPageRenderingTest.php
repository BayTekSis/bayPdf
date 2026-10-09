<?php

namespace BayPdf\Tests;

use BayPdf\DocumentTypes;
use BayPdf\PdfRenderer;
use BayPdf\TemplateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

final class MultiPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('baypdf.pdf_compression', false);
    }

    public function test_empty_one_and_many_row_tables_paginate_deterministically(): void
    {
        $empty = $this->render([]);
        $one = $this->render([$this->row('One row')]);
        $manyRows = array_map(fn (int $index): array => $this->row("Row {$index}"), range(1, 55));
        $manyA = $this->render($manyRows);
        $manyB = $this->render($manyRows);

        $this->assertSame(1, $this->pageCount($empty));
        $this->assertSame(1, $this->pageCount($one));
        $this->assertGreaterThan(1, $this->pageCount($manyA));
        $this->assertSame($this->pageCount($manyA), $this->pageCount($manyB));
        $this->assertSame($this->textOccurrences($manyA, 'Description'), $this->textOccurrences($manyB, 'Description'));
    }

    public function test_exact_fit_stays_on_one_page_and_one_more_row_creates_page_two(): void
    {
        $document = $this->document();
        $document['flow']['first_top'] = 30;
        $document['flow']['continuation_top'] = 30;
        $document['flow']['bottom'] = 62;
        $document['flow']['trailing'] = [];

        $this->assertSame(1, $this->pageCount($this->render(array_fill(0, 3, $this->row('Fit')), $document)));
        $this->assertSame(2, $this->pageCount($this->render(array_fill(0, 4, $this->row('Overflow')), $document)));
    }

    public function test_wrapped_rows_affect_page_breaks_and_keep_columns_aligned(): void
    {
        $document = $this->document();
        $document['flow']['first_top'] = 30;
        $document['flow']['continuation_top'] = 30;
        $document['flow']['bottom'] = 72;
        $document['flow']['trailing'] = [];
        $short = array_fill(0, 3, $this->row('Short'));
        $wrapped = array_fill(0, 3, $this->row(str_repeat('Long wrapped description ', 6)));

        $this->assertSame(1, $this->pageCount($this->render($short, $document)));
        $this->assertGreaterThan(1, $this->pageCount($this->render($wrapped, $document)));
    }

    public function test_table_header_page_header_footer_and_page_numbers_repeat(): void
    {
        $rows = array_map(fn (int $index): array => $this->row("Row {$index}"), range(1, 55));
        $pdf = $this->render($rows);
        $pages = $this->pageCount($pdf);

        $this->assertGreaterThan(1, $pages);
        $this->assertSame($pages, $this->textOccurrences($pdf, 'Description'));
        $this->assertSame($pages, $this->textOccurrences($pdf, 'Document header'));
        $this->assertSame($pages, $this->textOccurrences($pdf, 'Confidential footer'));
        foreach (range(1, $pages) as $page) {
            $this->assertSame(1, $this->textOccurrences($pdf, "Page {$page} / {$pages}"));
        }
    }

    public function test_first_continuation_last_and_non_repeating_table_header_rules_are_honored(): void
    {
        $document = $this->document();
        $document['flow']['table']['repeat_header'] = false;
        $document['elements'][] = [
            'id' => 'first-only', 'type' => 'text', 'region' => 'header', 'repeat' => 'first',
            'x' => 15, 'y' => 18, 'width' => 50, 'height' => 6, 'content' => 'First only', 'font_size' => 8,
        ];
        $document['elements'][] = [
            'id' => 'continuation-only', 'type' => 'text', 'region' => 'header', 'repeat' => 'continuation',
            'x' => 65, 'y' => 18, 'width' => 70, 'height' => 6, 'content' => 'Continuation only', 'font_size' => 8,
        ];
        $document['elements'][] = [
            'id' => 'last-only', 'type' => 'text', 'region' => 'header', 'repeat' => 'last',
            'x' => 135, 'y' => 18, 'width' => 60, 'height' => 6, 'content' => 'Last only', 'font_size' => 8,
        ];
        $rows = array_map(fn (int $index): array => $this->row("Row {$index}"), range(1, 55));
        $pdf = $this->render($rows, $document);
        $pages = $this->pageCount($pdf);

        $this->assertGreaterThan(1, $pages);
        $this->assertSame(1, $this->textOccurrences($pdf, 'Description'));
        $this->assertSame(1, $this->textOccurrences($pdf, 'First only'));
        $this->assertSame($pages - 1, $this->textOccurrences($pdf, 'Continuation only'));
        $this->assertSame(1, $this->textOccurrences($pdf, 'Last only'));
    }

    public function test_trailing_content_follows_the_last_row_and_moves_to_a_new_page_when_needed(): void
    {
        $document = $this->document();
        $document['flow']['first_top'] = 30;
        $document['flow']['continuation_top'] = 30;
        $document['flow']['bottom'] = 80;

        $fits = $this->render([$this->row('Short')], $document);
        $this->assertSame(1, $this->pageCount($fits));
        $this->assertSame(1, $this->textOccurrences($fits, 'Trailing summary'));

        $document['flow']['trailing'][0]['height'] = 35;
        $moved = $this->render([$this->row('Short')], $document);
        $this->assertSame(2, $this->pageCount($moved));
        $this->assertSame(1, $this->textOccurrences($moved, 'Trailing summary'));
    }

    public function test_single_oversized_row_and_maximum_page_limit_fail_cleanly(): void
    {
        $document = $this->document();
        $document['flow']['first_top'] = 30;
        $document['flow']['continuation_top'] = 30;
        $document['flow']['bottom'] = 55;

        try {
            $this->render([$this->row(str_repeat('Oversized row ', 100))], $document);
            $this->fail('An oversized row was rendered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items.0', $exception->errors());
            $this->assertStringNotContainsString('Oversized row', $exception->getMessage());
        }

        config()->set('baypdf.limits.max_generated_pages', 1);
        $this->expectException(ValidationException::class);
        $this->render(array_fill(0, 20, $this->row('Page limit')), $document);
    }

    public function test_invalid_column_widths_and_unknown_collection_fields_are_rejected(): void
    {
        $document = $this->document();
        $document['flow']['table']['columns'][0]['width'] = 90;

        try {
            $this->render([$this->row('Invalid width')], $document);
            $this->fail('Invalid column widths were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('flow.table.columns', $exception->errors());
        }

        $document = $this->document();
        $document['flow']['table']['columns'][0]['field'] = 'unknown';
        $this->expectException(ValidationException::class);
        $this->render([$this->row('Unknown field')], $document);
    }

    public function test_layout_element_limit_is_enforced_before_rendering(): void
    {
        config()->set('baypdf.limits.max_layout_elements', 3);

        $this->expectException(ValidationException::class);
        $this->render([$this->row('Element limit')]);
    }

    public function test_schema_v2_can_be_saved_published_and_rendered_through_template_manager(): void
    {
        app(DocumentTypes::class)->register('commercial_document', 'Commercial document', [$this->collection()]);
        $manager = app(TemplateManager::class);
        $draft = $manager->create('Multi-page document', 'commercial_document')->versions->first();
        $draft = $manager->save($draft, $this->document(), 1);
        $published = $manager->publish($draft, 2);

        $pdf = $manager->render($published, ['items' => array_fill(0, 55, $this->row('Published row'))]);

        $this->assertNotNull($published->published_at);
        $this->assertGreaterThan(1, $this->pageCount($pdf));
    }

    private function render(array $rows, ?array $document = null): string
    {
        return app(PdfRenderer::class)->render($document ?? $this->document(), ['items' => $rows], [$this->collection()]);
    }

    private function document(): array
    {
        return [
            'schema_version' => 2,
            'page' => ['size' => 'A4', 'orientation' => 'portrait'],
            'elements' => [
                [
                    'id' => 'header', 'type' => 'text', 'region' => 'header', 'repeat' => 'all',
                    'x' => 15, 'y' => 10, 'width' => 180, 'height' => 8, 'content' => 'Document header', 'font_size' => 10,
                ],
                [
                    'id' => 'footer', 'type' => 'text', 'region' => 'footer', 'repeat' => 'all',
                    'x' => 15, 'y' => 281, 'width' => 90, 'height' => 8, 'content' => 'Confidential footer', 'font_size' => 8,
                ],
                [
                    'id' => 'page-number', 'type' => 'page_number', 'region' => 'footer', 'repeat' => 'all',
                    'x' => 105, 'y' => 281, 'width' => 90, 'height' => 8, 'content' => 'Page {current} / {total}', 'font_size' => 8, 'align' => 'R',
                ],
            ],
            'flow' => [
                'first_top' => 25,
                'continuation_top' => 25,
                'bottom' => 275,
                'gap' => 4,
                'table' => [
                    'id' => 'items-table',
                    'type' => 'collection_table',
                    'source' => 'items',
                    'x' => 15,
                    'width' => 180,
                    'repeat_header' => true,
                    'columns' => [
                        ['field' => 'description', 'label' => 'Description', 'width' => 100, 'align' => 'L'],
                        ['field' => 'quantity', 'label' => 'Quantity', 'width' => 30, 'align' => 'R'],
                        ['field' => 'total', 'label' => 'Total', 'width' => 50, 'align' => 'R'],
                    ],
                    'header' => ['font_size' => 9, 'font_style' => 'B', 'color' => '#172b29', 'fill' => '#e5edde', 'padding' => 2, 'border' => true],
                    'row' => ['font_size' => 9, 'font_style' => '', 'color' => '#172b29', 'fill' => null, 'padding' => 2, 'border' => true],
                ],
                'trailing' => [[
                    'id' => 'summary', 'type' => 'text', 'x' => 15, 'width' => 180, 'height' => 15,
                    'content' => 'Trailing summary', 'font_size' => 10, 'gap_before' => 4,
                ]],
            ],
        ];
    }

    private function collection(): array
    {
        return [
            'key' => 'items',
            'label' => 'Items',
            'type' => 'collection',
            'fields' => [
                ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total', 'label' => 'Total', 'type' => 'money', 'currency' => 'EUR'],
            ],
        ];
    }

    private function row(string $description): array
    {
        return ['description' => $description, 'quantity' => 1, 'total' => 10];
    }

    private function pageCount(string $pdf): int
    {
        return preg_match_all('/\/Type \/Page\b/', $pdf);
    }

    private function textOccurrences(string $pdf, string $text): int
    {
        return substr_count($pdf, mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'));
    }
}
