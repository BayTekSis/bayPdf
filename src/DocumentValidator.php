<?php

namespace BayPdf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DocumentValidator
{
    public function __construct(private Assets $assets) {}

    public function validate(array $document, array $variables): array
    {
        if (($document['schema_version'] ?? 1) === 2) {
            return $this->validateFlowDocument($document, $variables);
        }

        return $this->validateLegacyDocument($document, $variables);
    }

    private function validateLegacyDocument(array $document, array $variables): array
    {
        $document = Validator::make($document, [
            'page' => ['required', 'array:size,orientation'],
            'page.size' => ['required', Rule::in(['A4', 'A5', 'Letter'])],
            'page.orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'elements' => ['present', 'array', 'list', 'max:100'],
            'elements.*' => ['array:id,type,x,y,width,height,content,variable,asset,font_size,font_style,color,fill,align,hidden'],
            ...$this->elementRules('elements.*', false, true),
        ])->validate();

        [$width, $height] = $this->dimensions($document['page']);
        $this->normalizeElements($document['elements'], $variables, $width, $height, 'elements', true);

        return $document;
    }

    private function validateFlowDocument(array $document, array $variables): array
    {
        $maxElements = (int) config('baypdf.limits.max_layout_elements');
        $maxFields = (int) config('baypdf.limits.max_collection_fields');
        $document = Validator::make($document, [
            'schema_version' => ['required', 'integer', Rule::in([2])],
            'page' => ['required', 'array:size,orientation'],
            'page.size' => ['required', Rule::in(['A4', 'A5', 'Letter'])],
            'page.orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'elements' => ['present', 'array', 'list', 'max:'.$maxElements],
            'elements.*' => ['array:id,type,region,repeat,x,y,width,height,content,variable,asset,font_size,font_style,color,fill,align,hidden'],
            ...$this->elementRules('elements.*', true, true),
            'elements.*.region' => ['sometimes', Rule::in(['page', 'header', 'footer'])],
            'elements.*.repeat' => ['sometimes', Rule::in(['first', 'all', 'continuation', 'last'])],
            'flow' => ['required', 'array:first_top,continuation_top,bottom,gap,table,trailing'],
            'flow.first_top' => ['required', 'numeric', 'min:0'],
            'flow.continuation_top' => ['required', 'numeric', 'min:0'],
            'flow.bottom' => ['required', 'numeric', 'min:1'],
            'flow.gap' => ['sometimes', 'numeric', 'between:0,30'],
            'flow.table' => ['required', 'array:id,type,source,x,width,repeat_header,columns,header,row'],
            'flow.table.id' => ['required', 'string', 'max:80'],
            'flow.table.type' => ['required', Rule::in(['collection_table'])],
            'flow.table.source' => ['required', 'string', 'max:120'],
            'flow.table.x' => ['required', 'numeric', 'between:0,300'],
            'flow.table.width' => ['required', 'numeric', 'between:1,300'],
            'flow.table.repeat_header' => ['required', 'boolean'],
            'flow.table.columns' => ['required', 'array', 'list', 'min:1', 'max:'.$maxFields],
            'flow.table.columns.*' => ['array:field,label,width,align'],
            'flow.table.columns.*.field' => ['required', 'string', 'distinct', 'max:80'],
            'flow.table.columns.*.label' => ['required', 'string', 'max:120'],
            'flow.table.columns.*.width' => ['required', 'numeric', 'min:1'],
            'flow.table.columns.*.align' => ['sometimes', Rule::in(['L', 'C', 'R'])],
            ...$this->tableStyleRules('flow.table.header'),
            ...$this->tableStyleRules('flow.table.row'),
            'flow.trailing' => ['present', 'array', 'list', 'max:'.$maxElements],
            'flow.trailing.*' => ['array:id,type,x,width,height,content,variable,asset,font_size,font_style,color,fill,align,hidden,gap_before'],
            ...$this->elementRules('flow.trailing.*', true, false),
            'flow.trailing.*.gap_before' => ['sometimes', 'numeric', 'between:0,30'],
        ])->validate();

        if (count($document['elements']) + count($document['flow']['trailing']) + 1 > $maxElements) {
            throw ValidationException::withMessages(['document' => 'The layout element limit was exceeded.']);
        }

        [$pageWidth, $pageHeight] = $this->dimensions($document['page']);
        $flow = &$document['flow'];
        $flow['gap'] = $flow['gap'] ?? 3;
        if ($flow['first_top'] >= $flow['bottom'] || $flow['continuation_top'] >= $flow['bottom'] || $flow['bottom'] > $pageHeight) {
            throw ValidationException::withMessages(['flow' => 'The flow area must fit within the page and have a positive height.']);
        }

        $this->normalizeElements($document['elements'], $variables, $pageWidth, $pageHeight, 'elements', true);
        foreach ($document['elements'] as $index => &$element) {
            $element['region'] = $element['region'] ?? 'page';
            $element['repeat'] = $element['repeat'] ?? 'first';
            if ($element['region'] === 'header' && min($flow['first_top'], $flow['continuation_top']) + 0.001 < $element['y'] + $element['height']) {
                throw ValidationException::withMessages(["elements.{$index}" => 'Header elements must stay above the flow area.']);
            }
            if ($element['region'] === 'footer' && $flow['bottom'] > $element['y'] + 0.001) {
                throw ValidationException::withMessages(["elements.{$index}" => 'Footer elements must stay below the flow area.']);
            }
        }
        unset($element);

        $schema = array_column($variables, null, 'key');
        $source = $flow['table']['source'];
        if (($schema[$source]['type'] ?? null) !== 'collection') {
            throw ValidationException::withMessages(['flow.table.source' => 'A registered collection variable is required.']);
        }
        if ($flow['table']['x'] + $flow['table']['width'] > $pageWidth + 0.001) {
            throw ValidationException::withMessages(['flow.table' => 'The collection table must fit within the page width.']);
        }
        $columnWidth = array_sum(array_column($flow['table']['columns'], 'width'));
        if (abs($columnWidth - $flow['table']['width']) > 0.001) {
            throw ValidationException::withMessages(['flow.table.columns' => 'Column widths must equal the collection table width.']);
        }
        $fields = array_column($schema[$source]['fields'], null, 'key');
        foreach ($flow['table']['columns'] as $index => &$column) {
            if (! isset($fields[$column['field']])) {
                throw ValidationException::withMessages(["flow.table.columns.{$index}.field" => 'The column must map to a field in the source collection.']);
            }
            $column['align'] = $column['align'] ?? 'L';
        }
        unset($column);
        $flow['table']['header'] = $this->normalizeTableStyle($flow['table']['header'] ?? [], true);
        $flow['table']['row'] = $this->normalizeTableStyle($flow['table']['row'] ?? [], false);
        $minimumCellWidth = 2 * max((float) $flow['table']['header']['padding'], (float) $flow['table']['row']['padding']);
        foreach ($flow['table']['columns'] as $index => $column) {
            if ($column['width'] <= $minimumCellWidth) {
                throw ValidationException::withMessages(["flow.table.columns.{$index}.width" => 'Column width must leave usable space after cell padding.']);
            }
        }

        $this->normalizeElements($flow['trailing'], $variables, $pageWidth, $flow['bottom'] - $flow['continuation_top'], 'flow.trailing', false);

        return $document;
    }

    private function elementRules(string $prefix, bool $pageNumber, bool $requiresY): array
    {
        $types = ['text', 'variable', 'image', 'qr', 'line', 'rectangle'];
        if ($pageNumber) {
            $types[] = 'page_number';
        }

        $rules = [
            "{$prefix}.id" => ['required', 'string', 'max:80', 'distinct'],
            "{$prefix}.type" => ['required', Rule::in($types)],
            "{$prefix}.x" => ['required', 'numeric', 'between:0,300'],
            "{$prefix}.width" => ['required', 'numeric', 'between:1,300'],
            "{$prefix}.height" => ['required', 'numeric', 'between:1,300'],
            "{$prefix}.content" => ['nullable', 'string', 'max:5000'],
            "{$prefix}.variable" => ['nullable', 'string', 'max:120'],
            "{$prefix}.asset" => ['nullable', 'string', 'max:255'],
            "{$prefix}.font_size" => ['sometimes', 'numeric', 'between:6,72'],
            "{$prefix}.font_style" => ['sometimes', Rule::in(['', 'B', 'I', 'BI'])],
            "{$prefix}.color" => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            "{$prefix}.fill" => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            "{$prefix}.align" => ['sometimes', Rule::in(['L', 'C', 'R'])],
            "{$prefix}.hidden" => ['sometimes', 'boolean'],
        ];
        if ($requiresY) {
            $rules["{$prefix}.y"] = ['required', 'numeric', 'between:0,300'];
        }

        return $rules;
    }

    private function tableStyleRules(string $prefix): array
    {
        return [
            $prefix => ['sometimes', 'array:font_size,font_style,color,fill,padding,border'],
            "{$prefix}.font_size" => ['sometimes', 'numeric', 'between:6,36'],
            "{$prefix}.font_style" => ['sometimes', Rule::in(['', 'B', 'I', 'BI'])],
            "{$prefix}.color" => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            "{$prefix}.fill" => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            "{$prefix}.padding" => ['sometimes', 'numeric', 'between:0,10'],
            "{$prefix}.border" => ['sometimes', 'boolean'],
        ];
    }

    private function normalizeTableStyle(array $style, bool $header): array
    {
        return array_merge([
            'font_size' => 9,
            'font_style' => $header ? 'B' : '',
            'color' => '#172b29',
            'fill' => $header ? '#e5edde' : null,
            'padding' => 2,
            'border' => true,
        ], $style);
    }

    private function normalizeElements(array &$elements, array $variables, float $pageWidth, float $heightLimit, string $path, bool $absoluteY): void
    {
        $schema = array_column($variables, null, 'key');
        foreach ($elements as $index => &$element) {
            $error = null;
            if ($pageWidth + 0.01 < $element['x'] + $element['width'] || ($absoluteY && $heightLimit + 0.01 < $element['y'] + $element['height'])) {
                $error = 'Element must fit within the page.';
            }
            if (! $absoluteY && $element['height'] > $heightLimit + 0.01) {
                $error = 'Element must fit within the usable flow area.';
            }
            $key = $element['variable'] ?? '';
            if ($key !== '' && ! isset($schema[$key])) {
                $error = 'Unknown variable.';
            }
            if ($element['type'] === 'variable' && ($key === '' || ! in_array($schema[$key]['type'] ?? '', ['text', 'date', 'number', 'money', 'qr'], true))) {
                $error = 'A text-compatible variable is required.';
            }
            if ($element['type'] === 'qr' && $key !== '' && ! in_array($schema[$key]['type'] ?? '', ['text', 'date', 'number', 'money', 'qr'], true)) {
                $error = 'A text-compatible variable is required.';
            }
            if ($element['type'] === 'image' && $key !== '' && ($schema[$key]['type'] ?? '') !== 'image') {
                $error = 'An image variable is required.';
            }
            if ($element['type'] === 'image' && $key === '' && empty($element['asset'])) {
                $error = 'An asset or image variable is required.';
            }
            if ($element['type'] === 'page_number' && $key !== '') {
                $error = 'Page number elements use built-in page context instead of host variables.';
            }
            if ($error) {
                throw ValidationException::withMessages(["{$path}.{$index}" => $error]);
            }
            if ($element['type'] === 'image' && $key === '' && ! empty($element['asset'])) {
                $this->assets->bytes($element['asset']);
            }
            foreach (['content', 'variable', 'asset', 'font_style'] as $field) {
                $element[$field] = $element[$field] ?? '';
            }
        }
        unset($element);
    }

    public function dimensions(array $page): array
    {
        $size = match ($page['size']) {
            'A5' => [148.0, 210.0],
            'Letter' => [215.9, 279.4],
            default => [210.0, 297.0],
        };

        return $page['orientation'] === 'landscape' ? array_reverse($size) : $size;
    }

    public function blank(): array
    {
        return ['page' => ['size' => 'A4', 'orientation' => 'portrait'], 'elements' => []];
    }
}
