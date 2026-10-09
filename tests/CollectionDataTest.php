<?php

namespace BayPdf\Tests;

use BayPdf\DocumentTypes;
use BayPdf\DocumentValidator;
use BayPdf\TemplateManager;
use BayPdf\VariableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

final class CollectionDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_one_and_many_row_collections_are_resolved_deterministically(): void
    {
        $schema = [$this->collection()];
        $resolver = app(VariableResolver::class);

        $this->assertSame(['items' => []], $resolver->resolve($schema, ['items' => []]));
        $this->assertSame([
            'items' => [[
                'description' => 'Consulting',
                'quantity' => '2.000',
                'unit_price' => '125.50 EUR',
                'service_date' => '09.10.2026',
            ]],
        ], $resolver->resolve($schema, ['items' => [[
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 125.5,
            'service_date' => '2026-10-09',
        ]]]));

        $rows = array_fill(0, 25, [
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 5,
            'service_date' => '2026-10-09',
        ]);
        $this->assertCount(25, $resolver->resolve($schema, ['items' => $rows])['items']);
    }

    public function test_collection_schema_and_example_rows_are_snapshotted_and_cloned(): void
    {
        $collection = $this->collection([
            'example' => [[
                'description' => 'Example line',
                'quantity' => 1,
                'unit_price' => 10,
                'service_date' => '2026-10-09',
            ]],
        ]);
        app(DocumentTypes::class)->register('commercial_document', 'Commercial document', [$collection]);

        $version = app(TemplateManager::class)->create('Document', 'commercial_document')->versions->first();
        $clone = app(TemplateManager::class)->cloneDraft($version);

        $this->assertSame($collection, $version->variables[0]);
        $this->assertSame($version->variables, $clone->variables);
        $this->assertSame($collection['example'], app(DocumentTypes::class)->examples($version->variables)['items']);
    }

    public function test_collection_must_be_a_list_of_array_records(): void
    {
        $resolver = app(VariableResolver::class);

        try {
            $resolver->resolve([$this->collection()], ['items' => ['row' => ['description' => 'Invalid']]]);
            $this->fail('A keyed collection was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $resolver->resolve([$this->collection()], ['items' => ['invalid row']]);
    }

    public function test_unknown_and_missing_required_row_fields_are_rejected_without_echoing_values(): void
    {
        $resolver = app(VariableResolver::class);

        try {
            $resolver->resolve([$this->collection()], ['items' => [[
                'description' => 'Sensitive value',
                'quantity' => 1,
                'unit_price' => 1,
                'service_date' => '2026-10-09',
                'unexpected' => 'secret',
            ]]]);
            $this->fail('An unknown row field was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items.0', $exception->errors());
            $this->assertStringNotContainsString('secret', $exception->getMessage());
        }

        try {
            $resolver->resolve([$this->collection()], ['items' => [[
                'quantity' => 1,
                'unit_price' => 1,
                'service_date' => '2026-10-09',
            ]]]);
            $this->fail('A missing required row field was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items.0.description', $exception->errors());
        }
    }

    public function test_nested_values_and_unsupported_collection_field_types_are_rejected(): void
    {
        try {
            app(VariableResolver::class)->resolve([$this->collection()], ['items' => [[
                'description' => ['nested'],
                'quantity' => 1,
                'unit_price' => 1,
                'service_date' => '2026-10-09',
            ]]]);
            $this->fail('Nested row data was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items.0.description', $exception->errors());
        }

        $collection = $this->collection();
        $collection['fields'][0]['type'] = 'image';

        $this->expectException(ValidationException::class);
        app(DocumentTypes::class)->register('unsupported', 'Unsupported', [$collection]);
    }

    public function test_collection_variable_cannot_be_used_as_a_scalar_text_element(): void
    {
        $document = [
            'page' => ['size' => 'A4', 'orientation' => 'portrait'],
            'elements' => [[
                'id' => 'items',
                'type' => 'variable',
                'variable' => 'items',
                'x' => 10,
                'y' => 10,
                'width' => 100,
                'height' => 20,
            ]],
        ];

        $this->expectException(ValidationException::class);
        app(DocumentValidator::class)->validate($document, [$this->collection()]);
    }

    public function test_collection_row_string_and_payload_limits_are_enforced(): void
    {
        config()->set('baypdf.limits.max_collection_rows', 1);
        $resolver = app(VariableResolver::class);
        $row = [
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 1,
            'service_date' => '2026-10-09',
        ];

        try {
            $resolver->resolve([$this->collection()], ['items' => [$row, $row]]);
            $this->fail('The row limit was not enforced.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items', $exception->errors());
        }

        config()->set('baypdf.limits.max_collection_rows', 500);
        config()->set('baypdf.limits.max_collection_string_bytes', 4);
        try {
            $resolver->resolve([$this->collection()], ['items' => [[...$row, 'description' => '12345']]]);
            $this->fail('The string byte limit was not enforced.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.items.0.description', $exception->errors());
        }

        config()->set('baypdf.limits.max_collection_string_bytes', 5000);
        config()->set('baypdf.limits.max_collection_payload_kb', 1);
        $this->expectException(ValidationException::class);
        $resolver->resolve([$this->collection()], ['items' => [[...$row, 'description' => str_repeat('x', 1100)]]]);
    }

    public function test_text_elements_cannot_bind_collection_values(): void
    {
        $document = [
            'page' => ['size' => 'A4', 'orientation' => 'portrait'],
            'elements' => [[
                'id' => 'bound-text', 'type' => 'text', 'variable' => 'items',
                'x' => 10, 'y' => 10, 'width' => 100, 'height' => 20,
            ]],
        ];

        $this->expectException(ValidationException::class);
        app(DocumentValidator::class)->validate($document, [$this->collection()]);
    }

    public function test_document_type_collection_and_field_count_limits_are_enforced(): void
    {
        config()->set('baypdf.limits.max_collections', 1);
        $second = $this->collection(['key' => 'services', 'label' => 'Services']);

        try {
            app(DocumentTypes::class)->register('too_many_collections', 'Too many', [$this->collection(), $second]);
            $this->fail('The collection count limit was not enforced.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('variables', $exception->errors());
        }

        config()->set('baypdf.limits.max_collections', 10);
        config()->set('baypdf.limits.max_collection_fields', 2);
        $this->expectException(ValidationException::class);
        app(DocumentTypes::class)->register('too_many_fields', 'Too many fields', [$this->collection()]);
    }

    private function collection(array $overrides = []): array
    {
        return array_replace([
            'key' => 'items',
            'label' => 'Items',
            'type' => 'collection',
            'fields' => [
                ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
                ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'decimals' => 3],
                ['key' => 'unit_price', 'label' => 'Unit price', 'type' => 'money', 'currency' => 'EUR', 'decimals' => 2],
                ['key' => 'service_date', 'label' => 'Service date', 'type' => 'date', 'format' => 'd.m.Y'],
            ],
        ], $overrides);
    }
}
