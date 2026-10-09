<?php

namespace BayPdf;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class DocumentTypes
{
    private array $types = [];

    public function __construct(private VariableResolver $resolver) {}

    public function register(string $key, string $label, array $variables): void
    {
        Validator::make(['key' => $key, 'label' => $label, 'variables' => $variables], [
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'label' => ['required', 'string', 'max:120'],
            'variables' => ['present', 'array', 'list', 'max:100'],
            'variables.*' => ['array:key,label,group,type,required,example,default,format,currency,decimals,fields,max_rows'],
            'variables.*.key' => ['required', 'string', 'distinct', 'max:120', 'regex:/^[a-z][a-z0-9_]*(\\.[a-z][a-z0-9_]*)*$/'],
            'variables.*.label' => ['required', 'string', 'max:120'],
            'variables.*.group' => ['sometimes', 'string', 'max:120'],
            'variables.*.type' => ['required', Rule::in(['text', 'date', 'number', 'money', 'image', 'qr', 'collection'])],
            'variables.*.required' => ['sometimes', 'boolean'],
            'variables.*.example' => ['nullable'],
            'variables.*.default' => ['nullable'],
            'variables.*.format' => ['sometimes', 'string', 'max:30'],
            'variables.*.currency' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
            'variables.*.decimals' => ['sometimes', 'integer', 'between:0,6'],
            'variables.*.fields' => ['sometimes', 'array', 'list'],
            'variables.*.max_rows' => ['sometimes', 'integer', 'min:1', 'max:'.config('baypdf.limits.max_collection_rows')],
        ])->validate();

        $collections = 0;
        foreach ($variables as $index => $variable) {
            if ($variable['type'] === 'collection') {
                $collections++;
                $this->validateCollection($variable, $index);

                continue;
            }
            foreach (['example', 'default'] as $field) {
                if (array_key_exists($field, $variable) && $variable[$field] !== null && ! is_scalar($variable[$field])) {
                    $this->invalid("variables.{$index}.{$field}", "A scalar {$field} is required.");
                }
            }
            if (array_key_exists('fields', $variable) || array_key_exists('max_rows', $variable)) {
                $this->invalid("variables.{$index}", 'Collection settings require a collection variable.');
            }
        }
        if ($collections > config('baypdf.limits.max_collections')) {
            $this->invalid('variables', 'The document type contains too many collections.');
        }
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
            $values[$variable['key']] = $variable['example'] ?? ($variable['type'] === 'collection' ? [] : ($variable['default'] ?? null));
        }

        return $values;
    }

    private function validateCollection(array $variable, int $index): void
    {
        $fields = $variable['fields'] ?? null;
        if (! is_array($fields) || ! array_is_list($fields) || $fields === [] || count($fields) > config('baypdf.limits.max_collection_fields')) {
            $this->invalid("variables.{$index}.fields", 'A bounded non-empty list of collection fields is required.');
        }

        Validator::make(['fields' => $fields], [
            'fields.*' => ['array:key,label,type,required,default,format,currency,decimals'],
            'fields.*.key' => ['required', 'string', 'distinct', 'max:80', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'date', 'number', 'money'])],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.default' => ['nullable', function ($attribute, $value, $fail): void {
                if (! is_scalar($value)) {
                    $fail('A scalar default is required.');
                }
            }],
            'fields.*.format' => ['sometimes', 'string', 'max:30'],
            'fields.*.currency' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
            'fields.*.decimals' => ['sometimes', 'integer', 'between:0,6'],
        ])->validate();

        if (array_key_exists('default', $variable)) {
            $this->invalid("variables.{$index}.default", 'Collection variables use an empty list when data is absent.');
        }
        if (array_key_exists('example', $variable)) {
            $this->resolver->resolve([$variable], [$variable['key'] => $variable['example']]);
        }
    }

    private function invalid(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
