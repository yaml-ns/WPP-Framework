<?php
declare(strict_types=1);

namespace ProductCatalogPlugin\Http\Controllers;

use ProductCatalogPlugin\Http\Requests\ProductIndexRequest;
use ProductCatalogPlugin\Http\Requests\StoreProductRequest;
use ProductCatalogPlugin\Http\Requests\UpdateProductRequest;
use ProductCatalogPlugin\Policies\ProductPolicy;
use ProductCatalogPlugin\Repositories\ProductRepository;
use YamlNs\WppFramework\Http\Controllers\BaseRestController;
use YamlNs\WppFramework\Support\OptionRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ProductRestController extends BaseRestController
{
    public function __construct(
        private readonly ProductRepository $products,
        protected readonly ProductPolicy $policy,
        private readonly OptionRepository $options
    ) {}

    public function index(ProductIndexRequest $request): WP_REST_Response|WP_Error
    {
        if ($error = $this->authorize('viewAny')) {
            return $error;
        }

        $validated = $request->validated();
        $settings = $this->options->array('product_catalog_settings');
        $featuredFirst = ($settings['display_featured_first'] ?? '0') === '1';
        $inStockOnly = ($validated['in_stock'] ?? ($settings['in_stock_only'] ?? false)) === true
            || ($settings['in_stock_only'] ?? '0') === '1';
        $perPage = (int) ($validated['per_page'] ?? ($settings['default_limit'] ?? 12));
        $query = $this->products->latest($perPage, $featuredFirst, $inStockOnly);
        $items = [];

        foreach ($query->posts as $post) {
            $items[] = $this->products->toArray($post);
        }

        return $this->paginated($items, (int) $query->found_posts, (int) $query->max_num_pages);
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = $this->products->find((int) $request->get_param('id'));

        if ($post === null) {
            return $this->notFound('Product not found.');
        }

        if ($error = $this->authorize('view', $post)) {
            return $error;
        }

        return $this->ok($this->products->toArray($post));
    }

    public function store(StoreProductRequest $request): WP_REST_Response|WP_Error
    {
        if ($error = $this->authorize('create')) {
            return $error;
        }

        $post = $this->products->create($request->validated());

        return $this->created($this->products->toArray($post));
    }

    public function update(UpdateProductRequest $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->param('id');
        $post = $this->products->findAny($id);

        if ($post === null) {
            return $this->notFound('Product not found.');
        }

        if ($error = $this->authorize('update', $post)) {
            return $error;
        }

        $post = $this->products->update($id, $request->validated());

        return $this->ok($this->products->toArray($post));
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = $this->products->findAny((int) $request->get_param('id'));

        if ($post === null) {
            return $this->notFound('Product not found.');
        }

        if ($error = $this->authorize('delete', $post)) {
            return $error;
        }

        $this->products->delete($post);

        return $this->deleted();
    }
}
