<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Http\Validation;

use YamlNs\WppFramework\Http\Validation\ValidationException;
use YamlNs\WppFramework\Http\Validation\Validator;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class ValidatorTest extends TestCase
{
    public function test_validates_and_casts_common_rules(): void
    {
        $validated = (new Validator())->validate([
            'email' => 'test@example.com',
            'limit' => '12',
            'enabled' => '1',
        ], [
            'email' => 'required|email',
            'limit' => ['required', 'integer', 'min:1', 'max:20'],
            'enabled' => ['boolean'],
        ]);

        $this->assertSame([
            'email' => 'test@example.com',
            'limit' => 12,
            'enabled' => true,
        ], $validated);
    }

    public function test_throws_validation_exception_with_custom_messages(): void
    {
        $this->expectException(ValidationException::class);

        try {
            (new Validator())->validate([
                'email' => 'invalid',
            ], [
                'email' => ['required', 'email'],
            ], [
                'email.email' => 'Email is invalid.',
            ]);
        } catch (ValidationException $e) {
            $this->assertSame(['Email is invalid.'], $e->errors()['email']);
            throw $e;
        }
    }

    public function test_exists_rule_can_validate_post_type(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'product', 'publish');

        $validated = (new Validator())->validate([
            'id' => 10,
        ], [
            'id' => ['required', 'exists:post,product'],
        ]);

        $this->assertSame(['id' => 10], $validated);
    }

    public function test_validates_arrays_url_date_sometimes_and_confirmation(): void
    {
        $validated = (new Validator())->validate([
            'tags' => ['a', 'b'],
            'website' => 'https://example.test',
            'published_at' => '2026-05-10',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ], [
            'tags' => ['required', 'array', 'min:1', 'max:3'],
            'website' => ['url'],
            'published_at' => ['date'],
            'missing' => ['sometimes', 'required', 'string'],
            'password' => ['confirmed'],
        ]);

        $this->assertArrayHasKey('tags', $validated);
        $this->assertArrayNotHasKey('missing', $validated);
        $this->assertSame('secret', $validated['password']);
    }

    public function test_validates_additional_rules(): void
    {
        $validated = (new Validator())->validate([
            'type' => 'external',
            'external_url' => 'https://example.test/product',
            'slug' => 'product_1',
            'code' => 'ABC-123',
            'payload' => '{"ok":true}',
            'sku' => 'A123',
            'sku_confirmation' => 'A123',
        ], [
            'external_url' => ['required_if:type,external', 'url'],
            'slug' => ['slug', 'not_in:admin,wp'],
            'code' => ['alpha_dash', 'regex:/^[A-Z0-9-]+$/'],
            'payload' => ['json'],
            'sku' => ['same:sku_confirmation', 'size:4'],
        ]);

        $this->assertSame('https://example.test/product', $validated['external_url']);
        $this->assertSame('product_1', $validated['slug']);
    }

    public function test_accepts_custom_validation_rules(): void
    {
        $validator = new Validator([
            'even' => static fn (mixed $value): bool => ((int) $value) % 2 === 0,
            'starts_with' => static fn (mixed $value, array $parameters): bool|string => str_starts_with((string) $value, (string) ($parameters[0] ?? ''))
                ? true
                : 'The value has an invalid prefix.',
        ]);

        $validated = $validator->validate([
            'count' => '4',
            'sku' => 'PRD-001',
        ], [
            'count' => ['required', 'integer', 'even'],
            'sku' => ['required', 'starts_with:PRD-'],
        ]);

        $this->assertSame(4, $validated['count']);
        $this->assertSame('PRD-001', $validated['sku']);
    }
}
