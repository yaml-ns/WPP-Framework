<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Console;

final class MakeCommand
{
    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? '';
        $name = $argv[2] ?? '';
        $plugin = $this->option($argv, '--plugin') ?? getcwd();
        $namespace = $this->option($argv, '--namespace') ?? $this->detectNamespace($plugin);

        if ($command === '' || $name === '' || !is_dir($plugin)) {
            $this->usage();
            return 1;
        }

        $studly = $this->studly($name);
        $kebab = $this->kebab($name);

        return match ($command) {
            'make:post-type' => $this->write($plugin, "app/PostTypes/{$studly}PostType.php", $this->postType($namespace, $name)),
            'make:repository' => $this->write($plugin, "app/Repositories/{$studly}Repository.php", $this->repository($namespace, $name)),
            'make:taxonomy' => $this->write($plugin, "app/Taxonomies/{$studly}Taxonomy.php", $this->taxonomy($namespace, $name)),
            'make:shortcode' => $this->write($plugin, "app/Shortcodes/{$studly}Shortcode.php", $this->shortcode($namespace, $name)),
            'make:rest-controller' => $this->write($plugin, "app/Http/Controllers/{$studly}RestController.php", $this->restController($namespace, $name)),
            'make:routes' => $this->write($plugin, "routes/{$kebab}.php", $this->routes()),
            'make:resource' => $this->resource($plugin, $namespace, $name),
            'make:crud' => $this->crud($plugin, $namespace, $name),
            'make:admin-crud' => $this->adminCrud($plugin, $namespace, $name),
            'make:provider' => $this->write($plugin, "app/Providers/{$studly}ServiceProvider.php", $this->provider($namespace, $name)),
            'make:admin-page' => $this->write($plugin, "resources/views/admin/{$kebab}.php", $this->adminView($name)),
            default => $this->unknown($command),
        };
    }

    /**
     * @param array<int, string> $argv
     */
    private function option(array $argv, string $name): ?string
    {
        foreach ($argv as $index => $arg) {
            if ($arg === $name) {
                return $argv[$index + 1] ?? null;
            }

            if (str_starts_with($arg, $name . '=')) {
                return substr($arg, strlen($name) + 1);
            }
        }

        return null;
    }

    private function detectNamespace(string $plugin): string
    {
        $composer = $plugin . DIRECTORY_SEPARATOR . 'composer.json';

        if (is_file($composer)) {
            $data = json_decode((string) file_get_contents($composer), true);
            $psr4 = $data['autoload']['psr-4'] ?? [];

            foreach ($psr4 as $namespace => $paths) {
                foreach ((array) $paths as $path) {
                    $normalized = trim(str_replace('\\', '/', (string) $path), '/');

                    if (is_string($namespace) && in_array($normalized, ['app', 'app/'], true)) {
                        return trim($namespace, '\\');
                    }
                }
            }

            foreach (array_keys($psr4) as $namespace) {
                if (is_string($namespace)) {
                    return trim($namespace, '\\');
                }
            }
        }

        return 'App';
    }

    private function write(string $plugin, string $relative, string $contents): int
    {
        $path = rtrim($plugin, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (is_file($path)) {
            fwrite(STDERR, "File already exists: {$path}\n");
            return 1;
        }

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $contents);
        fwrite(STDOUT, "Created {$path}\n");

        return 0;
    }

    private function unknown(string $command): int
    {
        fwrite(STDERR, "Unknown command: {$command}\n");
        $this->usage();

        return 1;
    }

    private function usage(): void
    {
        fwrite(STDERR, "Usage: wpp make:post-type|make:repository|make:taxonomy|make:shortcode|make:rest-controller|make:routes|make:resource|make:crud|make:admin-crud|make:provider|make:admin-page Name [--plugin=/path] [--namespace=PluginNs]\n");
    }

    private function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    private function snake(string $name): string
    {
        return trim(strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('-', '_', $name))), '_');
    }

    private function kebab(string $name): string
    {
        return str_replace('_', '-', $this->snake($name));
    }

    private function routePlural(string $name): string
    {
        $segment = str_replace('_', '-', $this->snake($name));

        if (str_ends_with($segment, 'y') && !preg_match('/[aeiou]y$/', $segment)) {
            return substr($segment, 0, -1) . 'ies';
        }

        if (str_ends_with($segment, 's')) {
            return $segment . 'es';
        }

        return $segment . 's';
    }

    private function postType(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'PostType';
        $slug = $this->snake($name);
        $label = $this->studly($name);

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\PostTypes;

use YamlNs\\WppFramework\\Contracts\\PostType;

final class {$class} implements PostType
{
    public function name(): string
    {
        return '{$slug}';
    }

    public function args(): array
    {
        return [
            'label' => '{$label}',
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    }
}
PHP;
    }

    private function repository(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'Repository';
        $postTypeClass = $this->studly($name) . 'PostType';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Repositories;

use {$namespace}\\PostTypes\\{$postTypeClass};
use YamlNs\\WppFramework\\Repositories\\BaseRepository;

final class {$class} extends BaseRepository
{
    public function __construct(private readonly {$postTypeClass} \$postType) {}

    protected function postType(): string
    {
        return \$this->postType->name();
    }
}
PHP;
    }

    private function taxonomy(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'Taxonomy';
        $slug = $this->snake($name);
        $label = $this->studly($name);

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Taxonomies;

use YamlNs\\WppFramework\\Contracts\\Taxonomy;

final class {$class} implements Taxonomy
{
    public function name(): string
    {
        return '{$slug}';
    }

    public function objectType(): string|array
    {
        return 'post';
    }

    public function args(): array
    {
        return [
            'label' => '{$label}',
            'hierarchical' => true,
            'show_in_rest' => true,
        ];
    }
}
PHP;
    }

    private function shortcode(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'Shortcode';
        $view = 'shortcodes/' . $this->kebab($name);

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Shortcodes;

use YamlNs\\WppFramework\\Shortcodes\\BaseShortcode;
use YamlNs\\WppFramework\\View\\ViewRenderer;

final class {$class} extends BaseShortcode
{
    public function __construct(ViewRenderer \$viewRenderer)
    {
        parent::__construct(\$viewRenderer);
    }

    public function render(array \$atts = []): string
    {
        return \$this->view('{$view}', [
            'atts' => \$atts,
        ]);
    }
}
PHP;
    }

    private function restController(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'RestController';
        $repositoryClass = $this->studly($name) . 'Repository';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Http\\Controllers;

use {$namespace}\\Repositories\\{$repositoryClass};
use YamlNs\\WppFramework\\Http\\Controllers\\BaseRestController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class {$class} extends BaseRestController
{
    public function __construct(private readonly {$repositoryClass} \$repository) {}

    public function index(WP_REST_Request \$request): WP_REST_Response
    {
        \$query = \$this->repository->latest((int) (\$request->get_param('per_page') ?? 12));
        \$items = array_map(fn (\$post): array => \$this->repository->toArray(\$post), \$query->posts);

        return \$this->paginated(\$items, (int) \$query->found_posts, (int) \$query->max_num_pages);
    }

    public function show(WP_REST_Request \$request): WP_REST_Response|WP_Error
    {
        \$post = \$this->repository->find((int) \$request->get_param('id'));

        if (\$post === null) {
            return \$this->error('resource_not_found', 'Resource not found.', 404);
        }

        return \$this->ok(\$this->repository->toArray(\$post));
    }
}
PHP;
    }

    private function routes(): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

use YamlNs\\WppFramework\\Core\\PluginContext;
use YamlNs\\WppFramework\\Http\\RestRouter;

return static function (RestRouter \$router, PluginContext \$context): void {
    // \$router->get(\$context->restNamespace(), '/items', [ItemRestController::class, 'index']);
};
PHP;
    }

    private function resource(string $plugin, string $namespace, string $name): int
    {
        $studly = $this->studly($name);
        $kebab = $this->kebab($name);
        $status = 0;

        $files = [
            "app/PostTypes/{$studly}PostType.php" => $this->postType($namespace, $name),
            "app/Repositories/{$studly}Repository.php" => $this->repository($namespace, $name),
            "app/Http/Controllers/{$studly}RestController.php" => $this->restController($namespace, $name),
            "routes/{$kebab}.php" => $this->readOnlyResourceRoutes($namespace, $name),
        ];

        foreach ($files as $relative => $contents) {
            $result = $this->write($plugin, $relative, $contents);
            $status = $status === 0 ? $result : $status;
        }

        fwrite(STDOUT, "\nAdd this to config/plugin.php:\n");
        fwrite(STDOUT, "  'post_types' => [{$namespace}\\PostTypes\\{$studly}PostType::class],\n");
        fwrite(STDOUT, "  'routes' => [__DIR__ . '/../routes/{$kebab}.php'],\n");

        return $status;
    }

    private function crud(string $plugin, string $namespace, string $name): int
    {
        $studly = $this->studly($name);
        $kebab = $this->kebab($name);
        $status = 0;

        $files = [
            "app/PostTypes/{$studly}PostType.php" => $this->postType($namespace, $name),
            "app/Repositories/{$studly}Repository.php" => $this->repository($namespace, $name),
            "app/Policies/{$studly}Policy.php" => $this->policy($namespace, $name),
            "app/Http/Requests/Store{$studly}Request.php" => $this->storeRequest($namespace, $name),
            "app/Http/Requests/Update{$studly}Request.php" => $this->updateRequest($namespace, $name),
            "app/Http/Controllers/{$studly}RestController.php" => $this->crudRestController($namespace, $name),
            "routes/{$kebab}.php" => $this->resourceRoutes($namespace, $name),
        ];

        foreach ($files as $relative => $contents) {
            $result = $this->write($plugin, $relative, $contents);
            $status = $status === 0 ? $result : $status;
        }

        fwrite(STDOUT, "\nAdd this to config/plugin.php:\n");
        fwrite(STDOUT, "  'post_types' => [{$namespace}\\PostTypes\\{$studly}PostType::class],\n");
        fwrite(STDOUT, "  'routes' => [__DIR__ . '/../routes/{$kebab}.php'],\n");

        return $status;
    }

    private function adminCrud(string $plugin, string $namespace, string $name): int
    {
        $kebab = $this->kebab($name);
        $status = 0;

        $files = [
            "config/admin-crud.php" => $this->adminCrudConfig($namespace, $name),
            "resources/views/admin/{$kebab}/index.php" => $this->adminCrudIndexView($name),
            "resources/views/admin/{$kebab}/form.php" => $this->adminCrudFormView($name),
        ];

        foreach ($files as $relative => $contents) {
            $result = $this->write($plugin, $relative, $contents);
            $status = $status === 0 ? $result : $status;
        }

        fwrite(STDOUT, "\nAdd this to config/plugin.php:\n");
        fwrite(STDOUT, "  'admin_crud' => require __DIR__ . '/admin-crud.php',\n");

        return $status;
    }

    private function resourceRoutes(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'RestController';
        $route = '/' . $this->routePlural($name);

        return <<<PHP
<?php
declare(strict_types=1);

use {$namespace}\\Http\\Controllers\\{$class};
use YamlNs\\WppFramework\\Http\\RestRouter;

return static function (RestRouter \$router): void {
    \$router->apiResource('{$route}', {$class}::class);
};
PHP;
    }

    private function readOnlyResourceRoutes(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'RestController';
        $route = '/' . $this->routePlural($name);

        return <<<PHP
<?php
declare(strict_types=1);

use {$namespace}\\Http\\Controllers\\{$class};
use YamlNs\\WppFramework\\Core\\PluginContext;
use YamlNs\\WppFramework\\Http\\RestRouter;

return static function (RestRouter \$router, PluginContext \$context): void {
    \$router->get(\$context->restNamespace(), '{$route}', [{$class}::class, 'index']);
    \$router->get(\$context->restNamespace(), '{$route}/(?P<id>\\d+)', [{$class}::class, 'show'], [], [
        'id' => [
            'type' => 'integer',
            'required' => true,
        ],
    ]);
};
PHP;
    }

    private function crudRestController(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'RestController';
        $policyClass = $this->studly($name) . 'Policy';
        $repositoryClass = $this->studly($name) . 'Repository';
        $storeRequestClass = 'Store' . $this->studly($name) . 'Request';
        $updateRequestClass = 'Update' . $this->studly($name) . 'Request';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Http\\Controllers;

use {$namespace}\\Http\\Requests\\{$storeRequestClass};
use {$namespace}\\Http\\Requests\\{$updateRequestClass};
use {$namespace}\\Policies\\{$policyClass};
use {$namespace}\\Repositories\\{$repositoryClass};
use YamlNs\\WppFramework\\Http\\Controllers\\BaseRestController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class {$class} extends BaseRestController
{
    public function __construct(
        private readonly {$repositoryClass} \$repository,
        protected readonly {$policyClass} \$policy
    ) {}

    public function index(WP_REST_Request \$request): WP_REST_Response|WP_Error
    {
        if (\$error = \$this->authorize('viewAny')) {
            return \$error;
        }

        \$query = \$this->repository->paginate(
            (int) (\$request->get_param('page') ?? 1),
            (int) (\$request->get_param('per_page') ?? 12)
        );
        \$items = array_map(fn (\$post): array => \$this->repository->toArray(\$post), \$query->posts);

        return \$this->paginated(\$items, (int) \$query->found_posts, (int) \$query->max_num_pages);
    }

    public function show(WP_REST_Request \$request): WP_REST_Response|WP_Error
    {
        \$post = \$this->repository->find((int) \$request->get_param('id'));

        if (\$post === null) {
            return \$this->notFound();
        }

        if (\$error = \$this->authorize('view', \$post)) {
            return \$error;
        }

        return \$this->ok(\$this->repository->toArray(\$post));
    }

    public function store({$storeRequestClass} \$request): WP_REST_Response|WP_Error
    {
        if (\$error = \$this->authorize('create')) {
            return \$error;
        }

        \$post = \$this->repository->create(\$request->validated());

        return \$this->created(\$this->repository->toArray(\$post));
    }

    public function update({$updateRequestClass} \$request): WP_REST_Response|WP_Error
    {
        \$id = (int) \$request->param('id');
        \$post = \$this->repository->findAny(\$id);

        if (\$post === null) {
            return \$this->notFound();
        }

        if (\$error = \$this->authorize('update', \$post)) {
            return \$error;
        }

        \$post = \$this->repository->update(\$id, \$request->validated());

        return \$this->ok(\$this->repository->toArray(\$post));
    }

    public function destroy(WP_REST_Request \$request): WP_REST_Response|WP_Error
    {
        \$post = \$this->repository->findAny((int) \$request->get_param('id'));

        if (\$post === null) {
            return \$this->notFound();
        }

        if (\$error = \$this->authorize('delete', \$post)) {
            return \$error;
        }

        \$this->repository->delete(\$post);

        return \$this->deleted();
    }
}
PHP;
    }

    private function policy(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'Policy';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Policies;

use YamlNs\\WppFramework\\Auth\\ResourcePolicy;

final class {$class} extends ResourcePolicy
{
    protected function createCapability(): ?string
    {
        return 'edit_posts';
    }

    protected function updateCapability(): ?string
    {
        return 'edit_post';
    }

    protected function deleteCapability(): ?string
    {
        return 'delete_post';
    }
}
PHP;
    }

    private function adminCrudConfig(string $namespace, string $name): string
    {
        $studly = $this->studly($name);
        $kebab = $this->kebab($name);
        $label = $this->studly($name);
        $slug = $this->routePlural($name);

        return <<<PHP
<?php
declare(strict_types=1);

use {$namespace}\\Policies\\{$studly}Policy;
use {$namespace}\\Repositories\\{$studly}Repository;

return [
    'resources' => [
        [
            'slug' => '{$slug}',
            'label' => '{$label}',
            'menu_title' => '{$label}',
            'page_title' => '{$label}',
            'capability' => 'manage_options',
            'force_delete' => false,
            'repository' => {$studly}Repository::class,
            'policy' => {$studly}Policy::class,
            'views' => [
                'index' => 'admin/{$kebab}/index',
                'form' => 'admin/{$kebab}/form',
            ],
            'filters' => [
                's' => [
                    'label' => 'Search',
                    'query' => 'search',
                    'type' => 'search',
                ],
                'status' => [
                    'label' => 'Status',
                    'query' => 'post_status',
                    'type' => 'select',
                    'options' => [
                        'publish' => 'Published',
                        'draft' => 'Draft',
                    ],
                ],
            ],
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'required' => true,
                ],
                'content' => [
                    'label' => 'Content',
                    'type' => 'textarea',
                ],
                'status' => [
                    'label' => 'Status',
                    'type' => 'select',
                    'default' => 'publish',
                    'options' => [
                        'draft' => 'Draft',
                        'publish' => 'Published',
                    ],
                ],
            ],
            'rules' => [
                'title' => ['required', 'string', 'max:120'],
                'content' => ['nullable', 'string'],
                'status' => ['required', 'in:draft,publish'],
            ],
        ],
    ],
];
PHP;
    }

    private function adminCrudIndexView(string $name): string
    {
        $title = $this->studly($name);

        return <<<PHP
<?php
if (!defined('ABSPATH')) {
    exit;
}

\$slug = sanitize_key((string) \$resource['slug']);
\$paginationArgs = array_filter(
    array_map('strval', (array) \$activeFilters),
    static fn (string \$value): bool => \$value !== ''
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html((string) (\$resource['label'] ?? '{$title}')); ?></h1>
    <a href="<?php echo esc_url(\$createUrl); ?>" class="page-title-action"><?php echo esc_html__('Add New', 'default'); ?></a>

    <hr class="wp-header-end">

    <?php if (\$filters !== []): ?>
        <form method="get" style="margin: 16px 0;">
            <input type="hidden" name="page" value="<?php echo esc_attr(\$slug); ?>">
            <?php foreach (\$filters as \$key => \$filter): ?>
                <?php \$filter = is_array(\$filter) ? \$filter : []; ?>
                <?php \$type = (string) (\$filter['type'] ?? 'text'); ?>
                <?php \$value = (string) (\$activeFilters[\$key] ?? ''); ?>
                <label for="<?php echo esc_attr('filter-' . (string) \$key); ?>" class="screen-reader-text">
                    <?php echo esc_html((string) (\$filter['label'] ?? \$key)); ?>
                </label>
                <?php if (\$type === 'select'): ?>
                    <select id="<?php echo esc_attr('filter-' . (string) \$key); ?>" name="<?php echo esc_attr((string) \$key); ?>">
                        <option value=""><?php echo esc_html((string) (\$filter['placeholder'] ?? __('All', 'default'))); ?></option>
                        <?php foreach ((array) (\$filter['options'] ?? []) as \$optionValue => \$optionLabel): ?>
                            <option value="<?php echo esc_attr((string) \$optionValue); ?>" <?php selected(\$value, (string) \$optionValue); ?>>
                                <?php echo esc_html((string) \$optionLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?php echo esc_attr(\$type === 'search' ? 'search' : 'text'); ?>" id="<?php echo esc_attr('filter-' . (string) \$key); ?>" name="<?php echo esc_attr((string) \$key); ?>" value="<?php echo esc_attr(\$value); ?>" placeholder="<?php echo esc_attr((string) (\$filter['label'] ?? \$key)); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <?php submit_button(__('Filter', 'default'), 'secondary', '', false); ?>
        </form>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(\$adminForm->actionUrl()); ?>">
        <?php echo \$adminForm->actionField(\$slug . '_bulk'); ?>
        <?php echo \$adminForm->nonceFields(\$slug . '_bulk'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value=""><?php echo esc_html__('Bulk actions', 'default'); ?></option>
                    <option value="delete"><?php echo esc_html__('Delete', 'default'); ?></option>
                </select>
                <?php submit_button(__('Apply', 'default'), 'action', '', false); ?>
            </div>
            <?php if (\$totalPages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo esc_html(sprintf(_n('%s item', '%s items', (int) \$query->found_posts, 'default'), number_format_i18n((int) \$query->found_posts))); ?></span>
                    <?php for (\$page = 1; \$page <= \$totalPages; \$page++): ?>
                        <?php \$pageUrl = add_query_arg(array_merge(\$paginationArgs, ['paged' => (string) \$page]), \$baseUrl); ?>
                        <a class="button <?php echo \$page === \$currentPage ? 'button-primary' : ''; ?>" href="<?php echo esc_url(\$pageUrl); ?>">
                            <?php echo esc_html((string) \$page); ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <table class="widefat striped">
            <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" onclick="document.querySelectorAll('[data-bulk-id]').forEach((item) => item.checked = this.checked);">
                </td>
                <th><?php echo esc_html__('Title', 'default'); ?></th>
                <th><?php echo esc_html__('Status', 'default'); ?></th>
                <th><?php echo esc_html__('Actions', 'default'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (\$query->posts as \$post): ?>
                <tr>
                    <th class="check-column">
                        <input data-bulk-id type="checkbox" name="ids[]" value="<?php echo esc_attr((string) \$post->ID); ?>">
                    </th>
                    <td><?php echo esc_html(get_the_title(\$post)); ?></td>
                    <td><?php echo esc_html(\$post->post_status); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg('id', (string) \$post->ID, add_query_arg('action', 'edit', \$baseUrl))); ?>">
                            <?php echo esc_html__('Edit', 'default'); ?>
                        </a>
                        <button type="submit" class="button-link-delete" name="delete_id" value="<?php echo esc_attr((string) \$post->ID); ?>">
                            <?php echo esc_html__('Delete', 'default'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
PHP;
    }

    private function adminCrudFormView(string $name): string
    {
        $title = $this->studly($name);

        return <<<PHP
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(\$post ? __('Edit {$title}', 'default') : __('Add {$title}', 'default')); ?></h1>

    <?php if (\$errors !== []): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html__('Please correct the highlighted fields.', 'default'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(\$formAction); ?>">
        <?php echo \$adminForm->actionField(\$action); ?>
        <?php echo \$adminForm->nonceFields(\$nonceId); ?>
        <?php if (\$post): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr((string) \$post->ID); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
            <?php foreach (\$fields as \$name => \$field): ?>
                <?php \$field = is_array(\$field) ? \$field : []; ?>
                <?php \$type = (string) (\$field['type'] ?? 'text'); ?>
                <?php \$value = \$values[\$name] ?? ''; ?>
                <?php \$fieldErrors = (array) (\$errors[\$name] ?? []); ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr((string) \$name); ?>"><?php echo esc_html((string) (\$field['label'] ?? \$name)); ?></label>
                    </th>
                    <td>
                        <?php if (\$type === 'textarea'): ?>
                            <textarea class="large-text" rows="8" id="<?php echo esc_attr((string) \$name); ?>" name="<?php echo esc_attr((string) \$name); ?>"><?php echo esc_textarea((string) \$value); ?></textarea>
                        <?php elseif (\$type === 'select'): ?>
                            <select id="<?php echo esc_attr((string) \$name); ?>" name="<?php echo esc_attr((string) \$name); ?>">
                                <?php foreach ((array) (\$field['options'] ?? []) as \$optionValue => \$optionLabel): ?>
                                    <option value="<?php echo esc_attr((string) \$optionValue); ?>" <?php selected((string) \$value, (string) \$optionValue); ?>>
                                        <?php echo esc_html((string) \$optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (\$type === 'select_multiple'): ?>
                            <?php \$selectedValues = array_map('strval', (array) \$value); ?>
                            <select id="<?php echo esc_attr((string) \$name); ?>" name="<?php echo esc_attr((string) \$name); ?>[]" multiple>
                                <?php foreach ((array) (\$field['options'] ?? []) as \$optionValue => \$optionLabel): ?>
                                    <option value="<?php echo esc_attr((string) \$optionValue); ?>" <?php selected(in_array((string) \$optionValue, \$selectedValues, true)); ?>>
                                        <?php echo esc_html((string) \$optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (\$type === 'checkbox'): ?>
                            <label>
                                <input type="checkbox" id="<?php echo esc_attr((string) \$name); ?>" name="<?php echo esc_attr((string) \$name); ?>" value="1" <?php checked((string) \$value, '1'); ?>>
                                <?php echo esc_html((string) (\$field['description'] ?? '')); ?>
                            </label>
                        <?php elseif (\$type === 'checkboxes'): ?>
                            <?php \$checkedValues = array_map('strval', (array) \$value); ?>
                            <?php foreach ((array) (\$field['options'] ?? []) as \$optionValue => \$optionLabel): ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="<?php echo esc_attr((string) \$name); ?>[]" value="<?php echo esc_attr((string) \$optionValue); ?>" <?php checked(in_array((string) \$optionValue, \$checkedValues, true)); ?>>
                                    <?php echo esc_html((string) \$optionLabel); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input class="regular-text" type="<?php echo esc_attr(\$type === 'number' ? 'number' : 'text'); ?>" id="<?php echo esc_attr((string) \$name); ?>" name="<?php echo esc_attr((string) \$name); ?>" value="<?php echo esc_attr((string) \$value); ?>">
                        <?php endif; ?>
                        <?php foreach (\$fieldErrors as \$message): ?>
                            <p class="description error"><?php echo esc_html((string) \$message); ?></p>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
PHP;
    }

    private function storeRequest(string $namespace, string $name): string
    {
        $class = 'Store' . $this->studly($name) . 'Request';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Http\\Requests;

use YamlNs\\WppFramework\\Http\\Requests\\FormRequest;

final class {$class} extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,publish,pending,private'],
        ];
    }
}
PHP;
    }

    private function updateRequest(string $namespace, string $name): string
    {
        $class = 'Update' . $this->studly($name) . 'Request';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Http\\Requests;

use YamlNs\\WppFramework\\Http\\Requests\\FormRequest;

final class {$class} extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'min:1'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,publish,pending,private'],
        ];
    }
}
PHP;
    }

    private function provider(string $namespace, string $name): string
    {
        $class = $this->studly($name) . 'ServiceProvider';

        return <<<PHP
<?php
declare(strict_types=1);

namespace {$namespace}\\Providers;

use YamlNs\\WppFramework\\Providers\\ServiceProvider;

final class {$class} extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;
    }

    private function adminView(string $name): string
    {
        $title = $this->studly($name);

        return <<<PHP
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('{$title}', 'default'); ?></h1>
</div>
PHP;
    }
}
