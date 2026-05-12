<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Contracts\Taxonomy;
use YamlNs\WppFramework\Core\Container;

final class TaxonomyServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     taxonomies?: array<int|string, class-string<Taxonomy>|Taxonomy|array<string, mixed>>
     * } $taxonomies
     */
    public function __construct(Container $container, private readonly array $taxonomies = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        add_action('init', function (): void {
            self::registerTaxonomies($this->container, $this->taxonomies);
        });
    }

    /**
     * @param array{
     *     taxonomies?: array<int|string, class-string<Taxonomy>|Taxonomy|array<string, mixed>>
     * } $taxonomies
     */
    public static function activate(Container $container, array $taxonomies): void
    {
        self::registerTaxonomies($container, $taxonomies);
    }

    /**
     * @param array{
     *     taxonomies?: array<int|string, class-string<Taxonomy>|Taxonomy|array<string, mixed>>
     * } $taxonomies
     */
    private static function registerTaxonomies(Container $container, array $taxonomies): void
    {
        foreach ($taxonomies['taxonomies'] ?? [] as $taxonomy => $definition) {
            [$name, $objectType, $args] = self::resolveTaxonomy($container, $taxonomy, $definition);

            register_taxonomy($name, $objectType, $args);
        }
    }

    /**
     * @return array{0: string, 1: string|string[], 2: array<string, mixed>}
     */
    private static function resolveTaxonomy(Container $container, int|string $key, mixed $definition): array
    {
        if ($definition instanceof Taxonomy) {
            return [$definition->name(), $definition->objectType(), $definition->args()];
        }

        if (is_string($definition) && is_subclass_of($definition, Taxonomy::class)) {
            /** @var Taxonomy $taxonomy */
            $taxonomy = $container->get($definition);

            return [$taxonomy->name(), $taxonomy->objectType(), $taxonomy->args()];
        }

        if (is_string($key) && is_array($definition)) {
            if (!isset($definition['object_type'])) {
                throw new \RuntimeException("Taxonomy {$key} must define object_type.");
            }

            $objectType = $definition['object_type'];
            unset($definition['object_type']);

            return [$key, $objectType, $definition];
        }

        throw new \RuntimeException('Invalid taxonomy definition.');
    }
}
