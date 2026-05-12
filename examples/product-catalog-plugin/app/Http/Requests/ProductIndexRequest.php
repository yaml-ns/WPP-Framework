<?php
declare(strict_types=1);

namespace ProductCatalogPlugin\Http\Requests;

use YamlNs\WppFramework\Http\Requests\FormRequest;

final class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'in_stock' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'per_page must be an integer.',
            'per_page.min' => 'per_page must be at least 1.',
            'per_page.max' => 'per_page cannot be greater than 100.',
            'in_stock.boolean' => 'in_stock must be a boolean.',
        ];
    }
}
