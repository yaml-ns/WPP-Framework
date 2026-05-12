# WPP Framework

[![CI](https://github.com/yaml-ns/WPP-Framework/actions/workflows/tests.yml/badge.svg)](https://github.com/yaml-ns/WPP-Framework/actions/workflows/tests.yml)

Reusable PHP framework for building application-style WordPress plugins: REST APIs, admin screens, business services, assets, cron, permissions, modules and PHP views.

License: MIT.

Namespace:

```php
YamlNs\WppFramework
```

## Goal

WPP Framework is a shared Composer library installed inside each consumer plugin. It avoids rewriting the same infrastructure for every plugin:

- plugin bootstrap;
- dependency injection container;
- service providers;
- REST routes and middleware;
- security helpers;
- custom post types and taxonomies;
- metaboxes and meta fields;
- admin pages and admin forms;
- admin CRUD screens;
- asset enqueueing;
- WordPress settings;
- cron jobs;
- AJAX actions;
- modules;
- PHP view rendering.

The framework supports multiple active plugins in the same WordPress installation. Each plugin gets its own `PluginContext`, container and provider list.

## Installation

Inside a consumer plugin:

```json
{
  "require": {
    "yaml-ns/wpp-framework": "^0.9"
  }
}
```

Then run:

```bash
composer install
```

The framework package is a Composer `library`, not a `wordpress-plugin`.

## Public Bootstrap

The main file of a consumer plugin can stay small:

```php
<?php
/**
 * Plugin Name: My Plugin
 */

declare(strict_types=1);

use YamlNs\WppFramework\Wpp;

if (!defined('ABSPATH')) {
    exit;
}

$pluginFile = __FILE__;
$pluginDir = plugin_dir_path($pluginFile);

require_once $pluginDir . 'vendor/autoload.php';

$config = require $pluginDir . 'config/plugin.php';

register_activation_hook($pluginFile, static function () use ($pluginFile, $config): void {
    Wpp::activate($pluginFile, $config);
});

add_action('plugins_loaded', static function () use ($pluginFile, $config): void {
    Wpp::boot($pluginFile, $config);
});

register_deactivation_hook($pluginFile, static function () use ($pluginFile, $config): void {
    Wpp::deactivate($pluginFile, $config);
});
```

`Wpp::activate()` registers declarative structures that affect rewrite rules, such as custom post types, runs lifecycle migrations, then flushes rewrite rules.

`Wpp::boot()` creates the `PluginContext`, retrieves the isolated framework instance for this plugin, registers configured providers, then boots the plugin.

`Wpp::deactivate()` clears declared cron jobs and flushes rewrite rules.

For full cleanup when the plugin is deleted, add an `uninstall.php` file in the consumer plugin:

```php
<?php
declare(strict_types=1);

use YamlNs\WppFramework\Wpp;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$pluginFile = __DIR__ . '/my-plugin.php';

require_once __DIR__ . '/vendor/autoload.php';

Wpp::uninstall($pluginFile, require __DIR__ . '/config/plugin.php');
```

## Configuration

Example `config/plugin.php`:

```php
<?php
declare(strict_types=1);

use MyPlugin\PostTypes\BookPostType;
use MyPlugin\Shortcodes\BookListShortcode;
use MyPlugin\Taxonomies\BookGenreTaxonomy;
use YamlNs\WppFramework\Core\Container;

return [
    'slug' => 'my-plugin',
    'name' => 'My Plugin',
    'version' => '1.0.0',
    'text_domain' => 'my-plugin',
    'rest_namespace' => 'my-plugin/v1',
    'i18n' => [
        'path' => 'languages',
    ],
    'post_types' => [
        BookPostType::class,
    ],
    'taxonomies' => [
        BookGenreTaxonomy::class,
    ],
    'admin' => require __DIR__ . '/admin.php',
    'admin_forms' => require __DIR__ . '/admin-forms.php',
    'admin_crud' => require __DIR__ . '/admin-crud.php',
    'meta_boxes' => require __DIR__ . '/meta-boxes.php',
    'routes' => [
        __DIR__ . '/../routes/api.php',
    ],
    'shortcodes' => [
        'book_list' => [BookListShortcode::class, 'render'],
    ],
    'assets' => require __DIR__ . '/assets.php',
    'lifecycle' => [
        'option' => 'my_plugin_version',
        'migrations' => [
            '1.0.0' => static function (Container $container): void {
                // Initial migration.
            },
        ],
    ],
    'logger' => [
        'enabled' => true,
        'min_level' => 'warning',
    ],
    'uninstall' => [
        'options' => [
            'my_plugin_version',
            'my_plugin_settings',
        ],
        'remove_capabilities' => true,
    ],
    'providers' => [
        // Custom providers only when the plugin needs specific behavior.
    ],
];
```

Each plugin must explicitly declare a unique `slug` and `rest_namespace`. `Wpp::boot()` and `Wpp::activate()` reject application configs that do not provide them.

For standard needs, `Wpp::boot()` can read `post_types`, `taxonomies`, `meta_boxes`, `shortcodes`, `assets`, `admin`, `admin_forms`, `admin_crud`, `routes`, `rest_controllers`, `settings`, `cron`, `ajax`, `i18n`, `capabilities`, `lifecycle`, `logger` and `uninstall` directly from config.

The config is validated before boot and activation so common mistakes fail early.

## Recommended Plugin Structure

```txt
my-plugin/
  my-plugin.php
  composer.json
  app/
    Admin/
    Http/
      Controllers/
      Requests/
    Policies/
    PostTypes/
    Providers/
    Repositories/
    Services/
    Shortcodes/
    Taxonomies/
  assets/
    admin.css
    admin.js
  config/
    plugin.php
    admin.php
    admin-crud.php
    assets.php
    cron.php
    settings.php
  routes/
    api.php
  resources/
    views/
      admin/
      shortcodes/
  vendor/
```

The framework stays in `vendor/`. The plugin's application code stays in `app/`.

## Create A Plugin From Scratch

Minimal flow for a `product-catalog` plugin:

1. Create the WordPress plugin and install the framework:

```bash
composer require yaml-ns/wpp-framework
```

2. Add the main `product-catalog.php` file with `Wpp::activate()`, `Wpp::boot()` and `Wpp::deactivate()`.

3. Create `config/plugin.php` with the plugin identity:

```php
return [
    'slug' => 'product-catalog',
    'name' => 'Product Catalog',
    'version' => '1.0.0',
    'rest_namespace' => 'products/v1',
    'post_types' => [
        ProductPostType::class,
    ],
    'routes' => [
        __DIR__ . '/../routes/products.php',
    ],
    'admin_crud' => require __DIR__ . '/admin-crud.php',
];
```

4. Generate the common pieces:

```bash
vendor/bin/wpp make:crud Product --plugin=/path/to/product-catalog --namespace=ProductCatalog
vendor/bin/wpp make:admin-crud Product --plugin=/path/to/product-catalog --namespace=ProductCatalog
```

5. Adapt fields in the repository, requests and `config/admin-crud.php`.

6. Add a view or shortcode when the plugin needs frontend output.

The `examples/product-catalog-plugin/` directory shows this flow with a `product` CPT, taxonomy, meta fields, settings, admin CRUD, REST API and shortcode.

## Architecture Guidelines

Use config for stable declarative parts: CPTs, taxonomies, assets, routes, metaboxes, admin CRUD, cron and settings.

Use `routes/*.php` to connect REST URLs to controllers. Controllers should handle HTTP use cases, not route registration.

Use a repository to isolate WordPress access (`WP_Query`, post meta, terms). Controllers, shortcodes and admin CRUD screens can then share the same data access logic.

Use a `FormRequest` when a REST or admin action needs explicit validation. Use a `Policy` when authorization depends on the action or resource.

Create a custom `ServiceProvider` only when you need to register business services, wire an external integration, add container bindings or boot behavior that does not belong in declarative config.

## Views

Plugin PHP views live in `resources/views/` and can be rendered with `ViewRenderer`:

```php
final class BookListShortcode extends BaseShortcode
{
    public function __construct(ViewRenderer $viewRenderer)
    {
        parent::__construct($viewRenderer);
    }

    public function render(): string
    {
        return $this->view('shortcodes/book-list', [
            'books' => [],
        ]);
    }
}
```

View data is extracted with `extract(..., EXTR_SKIP)`, so a view receives `$books`, `$query`, `$context`, etc. directly. The `context` variable is reserved by the framework.

The view renderer is intentionally native PHP, but it supports layouts, includes, sections and stacks:

```php
<?php $view->extends('layouts/admin'); ?>

<?php $view->section('content'); ?>
    <h1><?php echo esc_html($title); ?></h1>
    <?php $view->include('partials/table', ['items' => $items]); ?>
<?php $view->endSection(); ?>

<?php $view->push('scripts'); ?>
    <script>window.myPluginReady = true;</script>
<?php $view->endPush(); ?>
```

In `resources/views/layouts/admin.php`:

```php
<div class="wrap">
    <?php echo $view->yield('content'); ?>
</div>

<?php echo $view->stack('scripts'); ?>
```

## Container

The container centralizes plugin services: repositories, external API clients, import services, policies and similar application objects. For simple plugins, automatic autowiring is enough. Create a custom provider when a specific implementation must be configured.

```php
use Psr\Log\LoggerInterface;
use YamlNs\WppFramework\Providers\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ProductImporter::class);
        $this->container->tag([CsvImporter::class, ApiImporter::class], 'importers');

        $this->container
            ->when(ProductSyncService::class)
            ->needs(LoggerInterface::class)
            ->give(SyncLogger::class);
    }
}
```

`tagged('importers')` returns all tagged implementations. Contextual bindings are useful when two services need the same interface but different implementations.

## Modules

Modules can be enabled from config:

```php
'modules' => [
    [
        'enabled' => true,
        'providers' => [
            BillingModuleProvider::class,
            ReportingModuleProvider::class,
        ],
    ],
],
```

A module is simply a group of providers. This keeps the core small and lets each plugin choose its own boundaries.

## Included Providers

### `AdminPageServiceProvider`

Registers admin pages from config:

```php
return [
    'pages' => [
        [
            'menu_title' => 'My Plugin',
            'page_title' => 'My Plugin',
            'capability' => 'manage_options',
            'slug' => 'my-plugin',
            'template' => 'resources/views/admin/dashboard.php',
            'icon' => 'dashicons-admin-generic',
        ],
    ],
];
```

`AdminPageServiceProvider` is the declarative API for consumer plugin admin pages. `AdminServiceProvider` is only the internal/debug framework admin page.

### `AdminFormServiceProvider`

Handles admin forms through `admin-post.php`, checks capability, validates nonce, sanitizes fields and stores an option when no custom handler is provided.

### `AssetServiceProvider`

Enqueues admin and frontend assets. Admin assets can be limited to specific screens with `only`, and scripts can receive data with `localize`.

### `LifecycleServiceProvider`

Stores the installed version and runs pending migrations during `Wpp::activate()`, not during every request.

### `Logger`

The framework depends on `psr/log` and binds `Psr\Log\LoggerInterface` to its default `Logger`. A consumer plugin can replace this binding in a custom provider, including with Monolog.

### `BaseRepository`

`BaseRepository` provides a base for CPT repositories:

```php
final class BookRepository extends BaseRepository
{
    public function __construct(private readonly BookPostType $postType) {}

    protected function postType(): string
    {
        return $this->postType->name();
    }

    protected function metaFields(): array
    {
        return [
            'price' => '_book_price',
            'isbn',
        ];
    }
}
```

Available methods: `query()`, `all()`, `latest()`, `paginate()`, `where()`, `find()`, `findAny()`, `findOrFail()`, `create()`, `update()`, `delete()`, `toArray()`.

`create()` and `update()` accept aliases such as `title`, `content`, `excerpt`, `status` and `slug`, plus WordPress fields declared by `writableFields()`. Meta and taxonomy values are synchronized only when declared in `metaFields()` / `taxonomyFields()`.

### `MetaBoxServiceProvider`

Declares metaboxes and meta fields from config. The provider handles nonce, `register_post_meta()`, simple HTML rendering and saving.

Checkbox fields are stored as postmeta strings `'1'` / `'0'`. Their default REST type is therefore `string`. If you need a real REST boolean, set `meta_type` and provide suitable sanitization.

Field sanitization goes through `FieldSanitizer`, shared by metaboxes and admin CRUD, to keep `number`, `integer`, `float`, `select_multiple` and `checkboxes` behavior consistent.

### `AdminCrudServiceProvider`

Declares a simple admin CRUD interface for a CPT repository. The provider adds an admin page, renders `index` / `form` views, checks capability and nonce, then calls `create()`, `update()` and `delete()` on the repository.

```php
'admin_crud' => [
    'resources' => [
        [
            'slug' => 'books',
            'label' => 'Books',
            'capability' => 'manage_books',
            'force_delete' => false,
            'repository' => BookRepository::class,
            'policy' => BookPolicy::class,
            'views' => [
                'index' => 'admin/books/index',
                'form' => 'admin/books/form',
            ],
            'filters' => [
                's' => ['label' => 'Search', 'query' => 'search', 'type' => 'search'],
                'status' => [
                    'label' => 'Status',
                    'query' => 'post_status',
                    'type' => 'select',
                    'options' => ['publish' => 'Published', 'draft' => 'Draft'],
                ],
                'featured' => [
                    'label' => 'Featured',
                    'meta_key' => '_book_featured',
                    'type' => 'select',
                    'options' => ['1' => 'Yes', '0' => 'No'],
                ],
            ],
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text'],
                'content' => ['label' => 'Content', 'type' => 'textarea'],
                'price' => ['label' => 'Price', 'type' => 'float', 'meta_key' => '_book_price'],
            ],
            'rules' => [
                'title' => ['required', 'string', 'max:120'],
                'price' => ['nullable', 'numeric', 'min:0'],
            ],
        ],
    ],
],
```

Admin-post actions are derived from the slug: `books_store`, `books_update`, `books_delete`, `books_bulk`.

Index views receive `$filters`, `$activeFilters`, `$currentPage` and `$totalPages`, so they can build search, filters, pagination and bulk actions.

When admin validation fails, the provider redirects back to the form with `message=validation_failed`, stores errors and old input in a short transient, then injects `$errors` and `$values` into the view.

### `ShortcodeServiceProvider`

Declares shortcodes and lets the container resolve class callbacks:

```php
return [
    'shortcodes' => [
        'product_catalog' => [ProductCatalogShortcode::class, 'render'],
    ],
];
```

Shortcode tags must match `a-z`, `0-9`, `_` and `-`.

## REST

The framework registers a public health endpoint:

```txt
/wp-json/{rest_namespace}/health
```

Recommended REST route declaration:

```php
<?php
declare(strict_types=1);

use MyPlugin\Http\Controllers\BookRestController;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\RestRouter;

return static function (RestRouter $router, PluginContext $context): void {
    $router->get($context->restNamespace(), '/books', [BookRestController::class, 'index']);
    $router->get($context->restNamespace(), '/books/(?P<id>\d+)', [BookRestController::class, 'show']);
};
```

Then register the file in `config/plugin.php`:

```php
'routes' => [
    __DIR__ . '/../routes/api.php',
],
```

The controller stays focused on HTTP use cases and does not need to know `RestRouter`.

`BaseRestController` provides `paginated()`, `notFound()`, `forbidden()`, `deleted()` and `handle()`. `handle()` converts a `ValidationException` into a `WP_Error` `wpp_validation_failed` with status `422`, and other exceptions into `400` errors.

### Form Requests

REST controllers can type a request class that extends `FormRequest`. The container builds it from `WP_REST_Request`, and `validated()` throws `ValidationException` when data is invalid.

Available rules: `required`, `nullable`, `sometimes`, `required_if`, `required_with`, `string`, `numeric`, `integer`, `email`, `boolean`, `array`, `min`, `max`, `size`, `in`, `not_in`, `same`, `different`, `confirmed`, `url`, `date`, `alpha_dash`, `slug`, `json`, `regex`, `exists:post,post_type`, `exists:term,taxonomy`, `exists:user`.

The validator also accepts custom injected rules:

```php
$validator = new Validator([
    'sku' => static fn (mixed $value): bool => preg_match('/^[A-Z0-9-]+$/', (string) $value) === 1,
]);
```

### Resource Routing

`RestRouter` can declare standard REST resource routes:

```php
$router->apiResource('/books', BookRestController::class);
```

For non-numeric identifiers:

```php
$router->apiResource('/books', BookRestController::class, args: [
    'id_pattern' => '[a-zA-Z0-9_-]+',
]);
```

Generated routes:

```txt
GET    /books
POST   /books
GET    /books/{id}
PUT    /books/{id}
PATCH  /books/{id}
DELETE /books/{id}
```

### Policies

Controllers can use an injected policy through the protected `$policy` property and call `authorize()`.

`ResourcePolicy` uses WordPress `read` for reads by default, `edit_posts` for create, `edit_post` for update and `delete_post` for delete. If a resource should be publicly readable, override `viewAnyCapability()` and `viewCapability()` to return `null`.

## CLI

The package exposes a Composer binary named `wpp`:

```bash
vendor/bin/wpp make:post-type Product
vendor/bin/wpp make:repository Product
vendor/bin/wpp make:taxonomy ProductCategory
vendor/bin/wpp make:shortcode ProductCatalog
vendor/bin/wpp make:rest-controller Product
vendor/bin/wpp make:routes Api
vendor/bin/wpp make:resource Product
vendor/bin/wpp make:crud Product
vendor/bin/wpp make:admin-crud Product
vendor/bin/wpp make:provider Billing
vendor/bin/wpp make:admin-page Settings
```

Useful options:

```bash
vendor/bin/wpp make:post-type Book --plugin=/path/to/plugin --namespace=MyPlugin
```

`make:resource` generates a CPT, repository, read-only REST controller and routes file. `make:crud` generates the full REST CRUD stack. `make:admin-crud` generates `config/admin-crud.php` and admin `index/form` views.

The generator creates application files only. You still declare the post type, route file and/or `admin_crud` in `config/plugin.php`, keeping configuration explicit.

## Tests

The project contains unit tests for the container, REST router, validation, views, CLI generator and the most sensitive providers using local WordPress stubs.

Local PHP/Composer:

```bash
composer install
vendor/bin/phpunit
```

Docker:

```bash
docker compose run --rm tests
```

CI definitions are provided for GitHub Actions and GitLab CI:

```txt
.github/workflows/tests.yml
.gitlab-ci.yml
```

Pipelines run `composer validate --strict`, `composer install` and `vendor/bin/phpunit`.

## Releases

The project follows SemVer. Public versions should be created with Git tags (`v0.9.0`, `v1.0.0`, etc.) rather than a `version` field in `composer.json`.

Changes are tracked in `CHANGELOG.md`, and the release process is documented in `RELEASE.md`.

Stability target:

- `0.9.x`: pilot production use in internal plugins;
- `1.0.0`: public API freeze for the main framework surface.

## Uninstall

Deletion cleanup is declarative:

```php
'uninstall' => [
    'options' => [
        'my_plugin_version',
        'my_plugin_settings',
    ],
    'site_options' => [],
    'remove_capabilities' => true,
],
```

## Example

A concrete example is available in:

```txt
examples/product-catalog-plugin/
```

It creates a `product` custom post type, `product_category` taxonomy, settings page, admin CRUD, metaboxes, REST routes and displays products with:

```txt
[product_catalog]
```

Example REST routes:

```txt
/wp-json/products/v1/products
/wp-json/products/v1/products/{id}
```
