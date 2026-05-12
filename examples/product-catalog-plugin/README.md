# Product Catalog Plugin Example

This plugin shows how to use WPP Framework to build an application-style WordPress plugin that:

- declares a `product` custom post type;
- declares a `product_category` taxonomy;
- adds product meta fields in the admin;
- exposes a product CRUD through the REST API;
- protects REST/admin mutations with `ProductPolicy`;
- adds an admin CRUD declared through `admin_crud`;
- adds a settings page;
- displays a frontend catalog with `[product_catalog]`;
- cleans up its options on uninstall.

## Structure

```txt
product-catalog-plugin.php
uninstall.php
config/
  plugin.php
  admin.php
  admin-crud.php
  admin-forms.php
  assets.php
  meta-boxes.php
app/
  Http/
    Controllers/ProductRestController.php
    Requests/ProductIndexRequest.php
    Requests/StoreProductRequest.php
    Requests/UpdateProductRequest.php
  Policies/ProductPolicy.php
  PostTypes/ProductPostType.php
  Taxonomies/ProductCategoryTaxonomy.php
  Repositories/ProductRepository.php
  Shortcodes/ProductCatalogShortcode.php
resources/views/
  admin/product/index.php
  admin/product/form.php
  admin/settings.php
  shortcodes/product-catalog.php
routes/api.php
assets/front.css
```

## What This Example Shows

- `ProductPostType` declares the `product` CPT.
- `ProductCategoryTaxonomy` declares product categories.
- `ProductRepository` centralizes queries, REST mapping, meta fields and taxonomies.
- `ProductRestController` exposes `index`, `show`, `store`, `update`, `destroy`.
- `StoreProductRequest` and `UpdateProductRequest` validate REST payloads.
- `ProductPolicy` restricts create/update/delete to `manage_products`.
- `config/admin-crud.php` generates an admin CRUD for products.
- `config/meta-boxes.php` shows meta field declarations.
- `config/admin-forms.php` stores catalog settings.
- `ProductCatalogShortcode` renders a frontend grid through `ViewRenderer`.

## REST Routes

```txt
/wp-json/products/v1/products
/wp-json/products/v1/products/{id}
```

The list accepts `per_page` and `in_stock`. Routes are declared with `RestRouter::apiResource()`.

## Shortcode

```txt
[product_catalog]
[product_catalog limit="6"]
```

## Settings

The settings page lets users choose:

- the default product count;
- whether featured products should appear first;
- whether only in-stock products should be displayed.

The example is intentionally small, but it touches the important pieces of a real plugin: CPT, taxonomy, meta fields, admin CRUD, REST CRUD, policy, settings, assets and shortcode.
