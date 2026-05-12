<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Http;

use YamlNs\WppFramework\Http\Controllers\BaseRestController;
use YamlNs\WppFramework\Http\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;
use WP_Error;

final class TestRestController extends BaseRestController
{
    public function exposePaginated(array $items, int $total, int $pages): \WP_REST_Response
    {
        return $this->paginated($items, $total, $pages);
    }

    public function exposeHandle(callable $callback): \WP_REST_Response|WP_Error
    {
        return $this->handle($callback);
    }
}

final class TestAllowPolicy
{
    public function update(\WP_Post $post): bool
    {
        return $post->ID === 10;
    }
}

final class TestAuthRestController extends BaseRestController
{
    public function __construct(protected readonly object $policy) {}

    public function exposeAuthorize(string $ability, mixed ...$arguments): ?WP_Error
    {
        return $this->authorize($ability, ...$arguments);
    }

    public function exposeNotFound(): WP_Error
    {
        return $this->notFound();
    }

    public function exposeForbidden(): WP_Error
    {
        return $this->forbidden();
    }

    public function exposeDeleted(): \WP_REST_Response
    {
        return $this->deleted();
    }
}

final class BaseRestControllerTest extends TestCase
{
    protected function setUp(): void
    {
        WordPressState::reset();
    }

    public function test_paginated_response_sets_body_and_wordpress_headers(): void
    {
        $response = (new TestRestController())->exposePaginated([['id' => 1]], 10, 2);

        $this->assertSame([
            'items' => [['id' => 1]],
            'total' => 10,
            'total_pages' => 2,
        ], $response->data);
        $this->assertSame('10', $response->headers['X-WP-Total']);
        $this->assertSame('2', $response->headers['X-WP-TotalPages']);
    }

    public function test_authorize_returns_null_when_policy_allows(): void
    {
        $result = (new TestAuthRestController(new TestAllowPolicy()))->exposeAuthorize(
            'update',
            new \WP_Post(10)
        );

        $this->assertNull($result);
    }

    public function test_authorize_returns_forbidden_error_when_policy_denies(): void
    {
        $result = (new TestAuthRestController(new TestAllowPolicy()))->exposeAuthorize(
            'update',
            new \WP_Post(11)
        );

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('wpp_forbidden', $result->code);
        $this->assertSame(403, $result->data['status']);
    }

    public function test_rest_error_helpers_return_standard_errors(): void
    {
        $controller = new TestAuthRestController(new TestAllowPolicy());

        $this->assertSame(404, $controller->exposeNotFound()->data['status']);
        $this->assertSame(403, $controller->exposeForbidden()->data['status']);
        $this->assertSame(204, $controller->exposeDeleted()->status);
    }

    public function test_handle_converts_validation_exception_to_422_error(): void
    {
        $result = (new TestRestController())->exposeHandle(static function (): never {
            throw new ValidationException([
                'email' => ['email is invalid.'],
            ]);
        });

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('wpp_validation_failed', $result->code);
        $this->assertSame(422, $result->data['status']);
        $this->assertSame(['email' => ['email is invalid.']], $result->data['errors']);
    }
}
