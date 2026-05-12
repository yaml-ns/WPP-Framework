<?php
declare(strict_types=1);

namespace ProductCatalogPlugin\Http\Requests;

use YamlNs\WppFramework\Http\Requests\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,publish,pending,private'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:80'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'external_url' => ['nullable', 'url'],
            'featured' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
        ];
    }
}
