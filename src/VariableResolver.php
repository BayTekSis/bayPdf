<?php

namespace BayPdf;

use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class VariableResolver
{
    public function resolve(array $schema, array $data): array
    {
        $values = [];
        foreach ($schema as $variable) {
            $key = $variable['key'];
            $value = array_key_exists($key, $data) ? $data[$key] : Arr::get($data, $key, $variable['default'] ?? null);
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
