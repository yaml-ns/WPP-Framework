<?php

declare(strict_types=1);

namespace ProductCatalogPlugin\Shortcodes;

use ProductCatalogPlugin\Repositories\ProductRepository;
use YamlNs\WppFramework\Shortcodes\BaseShortcode;
use YamlNs\WppFramework\Support\OptionRepository;
use YamlNs\WppFramework\View\ViewRenderer;

final class ProductCatalogShortcode extends BaseShortcode
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly OptionRepository $options,
        ViewRenderer $viewRenderer,
    ) {
        parent::__construct($viewRenderer);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function render(array $atts = []): string
    {
        $settings = $this->options->array('product_catalog_settings');
        $defaultLimit = (int) ($settings['default_limit'] ?? 12);
        $featuredFirst = ($settings['display_featured_first'] ?? '0') === '1';
        $inStockOnly = ($settings['in_stock_only'] ?? '0') === '1';

        $atts = shortcode_atts([
            'limit' => max(1, $defaultLimit),
        ], $atts, 'product_catalog');

        $query = $this->products->latest((int) $atts['limit'], $featuredFirst, $inStockOnly);

        return $this->view('shortcodes/product-catalog', [
            'query' => $query,
        ]);
    }
}
