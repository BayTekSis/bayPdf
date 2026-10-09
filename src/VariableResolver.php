<?php

namespace BayPdf;

use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use JsonException;

final class VariableResolver
{
    public function resolve(array $schema, array $data): array
    {
        $values = [];
        $collectionRows = 0;
        $collectionBytes = 0;
        foreach ($schema as $variable) {
            $key = $variable['key'];
            $fallback = $variable['type'] === 'collection' ? [] : ($variable['default'] ?? null);
            $value = array_key_exists($key, $data) ? $data[$key] : Arr::get($data, $key, $fallback);
            if ($variable['type'] === 'collection') {
                $values[$key] = $this->collection($key, $value, $variable, $collectionRows, $collectionBytes);

                continue;
            }
            if ($value === null || $value === '') {
                if ($variable['required'] ?? false) {
                    $this->invalid($key, 'A required value is missing.');
                }
                $values[$key] = '';

                continue;
            }
            if (! is_scalar($value) || mb_strlen((string) $value) > 5000) {
                $this->invalid($key, 'Values must be scalar and at most 5000 characters.');
            }
            $values[$key] = match ($variable['type']) {
                'date' => $this->date($key, $value, $variable['format'] ?? 'd.m.Y'),
                'money', 'number' => $this->number($key, $value, $variable),
                default => (string) $value,
            };
        }

        return $values;
    }

    private function collection(string $key, mixed $value, array $variable, int &$totalRows, int &$totalBytes): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $this->invalid($key, 'A collection must be a list of records.');
        }
        if (($variable['required'] ?? false) && $value === []) {
            $this->invalid($key, 'At least one collection row is required.');
        }

        $totalRows += count($value);
        $rowLimit = min((int) ($variable['max_rows'] ?? config('baypdf.limits.max_collection_rows')), (int) config('baypdf.limits.max_collection_rows'));
        if (count($value) > $rowLimit || $totalRows > config('baypdf.limits.max_collection_rows')) {
            $this->invalid($key, 'The collection row limit was exceeded.');
        }

        try {
            $totalBytes += strlen(json_encode($value, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            $this->invalid($key, 'The collection payload is not valid JSON-compatible data.');
        }
        if ($totalBytes > config('baypdf.limits.max_collection_payload_kb') * 1024) {
            $this->invalid($key, 'The collection payload limit was exceeded.');
        }

        $fields = array_column($variable['fields'], null, 'key');
        $resolved = [];
        foreach ($value as $rowIndex => $row) {
            if (! is_array($row) || ($row !== [] && array_is_list($row))) {
                $this->invalid("{$key}.{$rowIndex}", 'A collection row must be a record.');
            }
            if (array_diff(array_keys($row), array_keys($fields)) !== []) {
                $this->invalid("{$key}.{$rowIndex}", 'The collection row contains an unknown field.');
            }

            $record = [];
            foreach ($fields as $fieldKey => $field) {
                $path = "{$key}.{$rowIndex}.{$fieldKey}";
                $fieldValue = array_key_exists($fieldKey, $row) ? $row[$fieldKey] : ($field['default'] ?? null);
                if ($fieldValue === null || $fieldValue === '') {
                    if ($field['required'] ?? false) {
                        $this->invalid($path, 'A required collection field is missing.');
                    }
                    $record[$fieldKey] = '';

                    continue;
                }
                if (! is_scalar($fieldValue) || strlen((string) $fieldValue) > config('baypdf.limits.max_collection_string_bytes')) {
                    $this->invalid($path, 'Collection field values must be scalar and within the configured byte limit.');
                }
                $record[$fieldKey] = match ($field['type']) {
                    'date' => $this->date($path, $fieldValue, $field['format'] ?? 'd.m.Y'),
                    'money', 'number' => $this->number($path, $fieldValue, $field),
                    default => (string) $fieldValue,
                };
            }
            $resolved[] = $record;
        }

        return $resolved;
    }

    private function date(string $key, mixed $value, string $format): string
    {
        if (str_contains((string) $value, "\0")) {
            $this->invalid($key, 'Dates must be valid YYYY-MM-DD values.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
        if (! $date || $date->format('Y-m-d') !== (string) $value) {
            $this->invalid($key, 'Dates must be valid YYYY-MM-DD values.');
        }

        return $date->format($format);
    }

    private function number(string $key, mixed $value, array $variable): string
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            $this->invalid($key, 'A finite numeric value is required.');
        }
        $number = number_format((float) $value, $variable['decimals'] ?? 2, '.', ',');

        return $variable['type'] === 'money' ? $number.' '.($variable['currency'] ?? 'EUR') : $number;
    }

    private function invalid(string $key, string $message): never
    {
        throw ValidationException::withMessages(['data.'.$key => $message]);
    }
}
