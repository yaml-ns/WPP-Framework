<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Requests;

use WP_REST_Request;
use YamlNs\WppFramework\Http\Validation\Validator;

abstract class FormRequest
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $validated = null;

    public function __construct(protected readonly WP_REST_Request $request)
    {
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        if ($this->validated === null) {
            $this->validated = (new Validator())->validate($this->all(), $this->rules(), $this->messages());
        }

        return $this->validated;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        $value = $this->request->get_param($key);

        return $value ?? $default;
    }

    public function request(): WP_REST_Request
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    protected function all(): array
    {
        return $this->request->get_params();
    }
}
