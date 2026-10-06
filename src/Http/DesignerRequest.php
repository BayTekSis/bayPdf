<?php

namespace BayPdf\Http;

use BayPdf\DocumentTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DesignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentication and host Gate apply to every package route.
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'store' => [
                'name' => ['required', 'string', 'max:120'],
                'document_type' => ['required', Rule::in(array_column(app(DocumentTypes::class)->all(), 'key'))],
            ],
            'update' => [
                'document' => ['required', 'array'],
                'lock_version' => ['required', 'integer', 'min:1'],
            ],
            'publish' => ['lock_version' => ['required', 'integer', 'min:1']],
            'preview' => ['document' => ['sometimes', 'array']],
            'upload' => ['file' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:'.config('baypdf.max_upload_kb')]],
            default => [],
        };
    }
}
