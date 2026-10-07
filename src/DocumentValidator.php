<?php

namespace BayPdf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class DocumentValidator
{
    public function validate(array $document, array $variables): array
    {
        $document = Validator::make($document, [
            'page' => ['required', 'array:size,orientation'],
            'page.size' => ['required', Rule::in(['A4', 'A5', 'Letter'])],
            'page.orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'elements' => ['present', 'array', 'list', 'max:100'],
            'elements.*' => ['array:id,type,x,y,width,height,content,variable,asset,font_size,font_style,color,fill,align,hidden'],
            'elements.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'elements.*.type' => ['required', Rule::in(['text', 'variable', 'image', 'qr', 'line', 'rectangle'])],
            'elements.*.x' => ['required', 'numeric', 'between:0,300'],
            'elements.*.y' => ['required', 'numeric', 'between:0,300'],
            'elements.*.width' => ['required', 'numeric', 'between:1,300'],
            'elements.*.height' => ['required', 'numeric', 'between:1,300'],
            'elements.*.content' => ['nullable', 'string', 'max:5000'],
            'elements.*.variable' => ['nullable', 'string', 'max:120'],
            'elements.*.asset' => ['nullable', 'string', 'max:255'],
            'elements.*.font_size' => ['sometimes', 'numeric', 'between:6,72'],
            'elements.*.font_style' => ['sometimes', Rule::in(['', 'B', 'I', 'BI'])],
            'elements.*.color' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'elements.*.fill' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'elements.*.align' => ['sometimes', Rule::in(['L', 'C', 'R'])],
            'elements.*.hidden' => ['sometimes', 'boolean'],
        ])->validate();

        [$width, $height] = $this->dimensions($document['page']);
        $schema = array_column($variables, null, 'key');
        foreach ($document['elements'] as $i => $element) {
            $error = null;
            if ($width + 0.01 < $element['x'] + $element['width'] || $height + 0.01 < $element['y'] + $element['height']) {
                $error = 'Element must fit within the page.';
            }
            $key = $element['variable'] ?? '';
            if ($key !== '' && ! isset($schema[$key])) {
                $error = 'Unknown variable.';
            }
            if ($element['type'] === 'variable' && ($key === '' || ($schema[$key]['type'] ?? '') === 'image')) {
                $error = 'A text-compatible variable is required.';
            }
            if ($element['type'] === 'image' && $key !== '' && ($schema[$key]['type'] ?? '') !== 'image') {
                $error = 'An image variable is required.';
            }
            if ($element['type'] === 'image' && $key === '' && empty($element['asset'])) {
                $error = 'An asset or image variable is required.';
            }
            if ($error) {
                throw ValidationException::withMessages(["elements.{$i}" => $error]);
            }
            foreach (['content', 'variable', 'asset', 'font_style'] as $field) {
                $document['elements'][$i][$field] = $element[$field] ?? '';
            }
        }

        return $document;
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
