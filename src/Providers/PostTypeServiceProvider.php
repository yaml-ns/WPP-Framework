<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Contracts\PostType;
use YamlNs\WppFramework\Core\Container;

final class PostTypeServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     post_types?: array<int|string, class-string<PostType>|PostType|array<string, mixed>>
     * } $postTypes
     */
    public function __construct(Container $container, private readonly array $postTypes = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        add_action('init', function (): void {
            self::registerPostTypes($this->container, $this->postTypes);
        });
    }

    /**
     * @param array{
     *     post_types?: array<int|string, class-string<PostType>|PostType|array<string, mixed>>
     * } $postTypes
     */
    public static function activate(Container $container, array $postTypes): void
    {
        self::registerPostTypes($container, $postTypes);
    }

    /**
     * @param array{
     *     post_types?: array<int|string, class-string<PostType>|PostType|array<string, mixed>>
     * } $postTypes
     */
    private static function registerPostTypes(Container $container, array $postTypes): void
    {
        foreach ($postTypes['post_types'] ?? [] as $postType => $definition) {
            [$name, $args] = self::resolvePostType($container, $postType, $definition);

            register_post_type($name, $args);
        }
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function resolvePostType(Container $container, int|string $key, mixed $definition): array
    {
        if ($definition instanceof PostType) {
            return [$definition->name(), $definition->args()];
        }

        if (is_string($definition) && is_subclass_of($definition, PostType::class)) {
            /** @var PostType $postType */
            $postType = $container->get($definition);

            return [$postType->name(), $postType->args()];
        }

        if (is_string($key) && is_array($definition)) {
            return [$key, $definition];
        }

        throw new \RuntimeException('Invalid post type definition.');
    }
}
