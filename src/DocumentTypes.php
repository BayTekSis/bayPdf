<?php

namespace BayPdf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

final class DocumentTypes
{
    private array $types = [];

    public function register(string $key, string $label, array $variables): void
    {
        Validator::make(['key' => $key, 'label' => $label, 'variables' => $variables], [
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'label' => ['required', 'string', 'max:120'],
            'variables' => ['present', 'array', 'max:100'],
            'variables.*' => ['array:key,label,group,type,required,example,default,format,currency,decimals'],
            'variables.*.key' => ['required', 'string', 'distinct', 'max:120', 'regex:/^[a-z][a-z0-9_]*(\\.[a-z][a-z0-9_]*)*$/'],
            'variables.*.label' => ['required', 'string', 'max:120'],
            'variables.*.group' => ['sometimes', 'string', 'max:120'],
            'variables.*.type' => ['required', Rule::in(['text', 'date', 'number', 'money', 'image', 'qr'])],
            'variables.*.required' => ['sometimes', 'boolean'],
            'variables.*.example' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_scalar($value)) {
                    $fail('A scalar example is required.');
                }
            }],
            'variables.*.default' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_scalar($value)) {
                    $fail('A scalar default is required.');
                }
            }],
            'variables.*.format' => ['sometimes', 'string', 'max:30'],
            'variables.*.currency' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
            'variables.*.decimals' => ['sometimes', 'integer', 'between:0,6'],
        ])->validate();
        if (isset($this->types[$key])) {
            throw new InvalidArgumentException("Document type already registered: {$key}");
        }
        $this->types[$key] = compact('key', 'label', 'variables');
    }

    public function get(string $key): array
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Unknown document type: {$key}");
    }

    public function all(): array
    {
        return array_values($this->types);
    }

    public function examples(array $variables): array
    {
        $values = [];
        foreach ($variables as $variable) {
            $values[$variable['key']] = $variable['example'] ?? $variable['default'] ?? null;
        }

        return $values;
    }
}
