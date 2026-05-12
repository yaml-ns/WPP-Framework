<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use WP_Error;
use WP_Post;
use YamlNs\WppFramework\Admin\AdminForm;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Fields\FieldSanitizer;
use YamlNs\WppFramework\Http\Validation\ValidationException;
use YamlNs\WppFramework\Http\Validation\Validator;
use YamlNs\WppFramework\Repositories\BaseRepository;
use YamlNs\WppFramework\View\ViewRenderer;

final class AdminCrudServiceProvider extends ServiceProvider
{
    private FieldSanitizer $sanitizer;

    /**
     * @param array{
     *     resources?: array<int, array<string, mixed>>
     * } $adminCrud
     */
    public function __construct(Container $container, private readonly array $adminCrud = [])
    {
        parent::__construct($container);
        $this->sanitizer = $container->get(FieldSanitizer::class);
    }

    public function boot(): void
    {
        add_action('admin_menu', function (): void {
            foreach ($this->adminCrud['resources'] ?? [] as $resource) {
                $this->registerPage($resource);
            }
        });

        foreach ($this->adminCrud['resources'] ?? [] as $resource) {
            $slug = sanitize_key((string) ($resource['slug'] ?? ''));

            if ($slug === '') {
                throw new \RuntimeException('Admin CRUD resource [slug] is required.');
            }

            add_action("admin_post_{$slug}_store", function () use ($resource): void {
                $this->handleStore($resource);
            });
            add_action("admin_post_{$slug}_update", function () use ($resource): void {
                $this->handleUpdate($resource);
            });
            add_action("admin_post_{$slug}_delete", function () use ($resource): void {
                $this->handleDelete($resource);
            });
            add_action("admin_post_{$slug}_bulk", function () use ($resource): void {
                $this->handleBulk($resource);
            });
        }
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function registerPage(array $resource): void
    {
        $slug = sanitize_key((string) ($resource['slug'] ?? ''));
        $capability = (string) ($resource['capability'] ?? 'manage_options');
        $menuTitle = (string) ($resource['menu_title'] ?? $resource['label'] ?? $slug);
        $pageTitle = (string) ($resource['page_title'] ?? $menuTitle);
        $callback = function () use ($resource): void {
            $this->render($resource);
        };

        if (isset($resource['parent_slug'])) {
            add_submenu_page(
                (string) $resource['parent_slug'],
                $pageTitle,
                $menuTitle,
                $capability,
                $slug,
                $callback,
                $resource['position'] ?? null,
            );

            return;
        }

        add_menu_page(
            $pageTitle,
            $menuTitle,
            $capability,
            $slug,
            $callback,
            $resource['icon'] ?? 'dashicons-admin-post',
            $resource['position'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function render(array $resource): void
    {
        $this->ensureCapability($resource);

        $action = sanitize_key((string) ($_GET['action'] ?? 'index'));

        match ($action) {
            'create' => $this->renderForm($resource, null),
            'edit' => $this->renderForm($resource, $this->findPostFromQuery($resource)),
            default => $this->renderIndex($resource),
        };
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function renderIndex(array $resource): void
    {
        $repository = $this->repository($resource);
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $perPage = max(1, (int) ($resource['per_page'] ?? 20));
        $query = $repository->query(array_merge([
            'posts_per_page' => max(1, (int) ($resource['per_page'] ?? 20)),
            'paged' => $page,
            'post_status' => $resource['post_status'] ?? ['publish', 'draft', 'pending', 'private'],
        ], $this->filterArgs($resource)));

        echo $this->view()->render((string) ($resource['views']['index'] ?? ''), [
            'resource' => $resource,
            'query' => $query,
            'repository' => $repository,
            'fields' => $resource['fields'] ?? [],
            'filters' => $resource['filters'] ?? [],
            'activeFilters' => $this->activeFilters($resource),
            'baseUrl' => $this->pageUrl($resource),
            'createUrl' => add_query_arg('action', 'create', $this->pageUrl($resource)),
            'currentPage' => $page,
            'totalPages' => max(1, (int) $query->max_num_pages),
            'adminForm' => $this->container->get(AdminForm::class),
        ]);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function renderForm(array $resource, ?WP_Post $post): void
    {
        if ($post === null && ($error = $this->authorize($resource, 'create')) instanceof WP_Error) {
            wp_die(esc_html($this->errorMessage($error)));
        }

        if ($post !== null && ($error = $this->authorize($resource, 'update', $post)) instanceof WP_Error) {
            wp_die(esc_html($this->errorMessage($error)));
        }

        $slug = sanitize_key((string) $resource['slug']);
        $action = $post === null ? "{$slug}_store" : "{$slug}_update";

        $flash = $this->validationFlash($resource);
        $old = is_array($flash['old'] ?? null) ? $flash['old'] : [];

        echo $this->view()->render((string) ($resource['views']['form'] ?? ''), [
            'resource' => $resource,
            'post' => $post,
            'fields' => $resource['fields'] ?? [],
            'values' => array_merge($this->values($resource, $post), $old),
            'errors' => is_array($flash['errors'] ?? null) ? $flash['errors'] : [],
            'formAction' => $this->adminForm()->actionUrl(),
            'action' => $action,
            'nonceId' => $action,
            'baseUrl' => $this->pageUrl($resource),
            'adminForm' => $this->adminForm(),
        ]);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function handleStore(array $resource): void
    {
        $this->ensureCapability($resource);
        $slug = sanitize_key((string) $resource['slug']);
        check_admin_referer(AdminForm::nonceActionFor("{$slug}_store"), AdminForm::nonceNameFor("{$slug}_store"));

        if (($error = $this->authorize($resource, 'create')) instanceof WP_Error) {
            wp_die(esc_html($this->errorMessage($error)));
        }

        $source = $this->post();

        try {
            $payload = $this->payload($resource, $source);
        } catch (ValidationException $e) {
            $this->redirectValidation($resource, $e, $source, ['action' => 'create']);
            return;
        }

        $post = $this->repository($resource)->create($payload);
        $this->redirect($resource, ['message' => 'created', 'id' => (string) $post->ID]);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function handleUpdate(array $resource): void
    {
        $this->ensureCapability($resource);
        $source = $this->post();
        $slug = sanitize_key((string) $resource['slug']);
        $post = $this->repository($resource)->findAny((int) ($source['id'] ?? 0));

        if (!$post instanceof WP_Post) {
            wp_die(esc_html__('Resource not found.', 'wpp-framework'));
        }

        check_admin_referer(AdminForm::nonceActionFor("{$slug}_update"), AdminForm::nonceNameFor("{$slug}_update"));

        if (($error = $this->authorize($resource, 'update', $post)) instanceof WP_Error) {
            wp_die(esc_html($this->errorMessage($error)));
        }

        try {
            $payload = $this->payload($resource, $source);
        } catch (ValidationException $e) {
            $this->redirectValidation($resource, $e, $source, [
                'action' => 'edit',
                'id' => (string) $post->ID,
            ]);
            return;
        }

        $this->repository($resource)->update($post, $payload);
        $this->redirect($resource, ['message' => 'updated', 'id' => (string) $post->ID]);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function handleDelete(array $resource): void
    {
        $this->ensureCapability($resource);
        $source = $this->post();
        $slug = sanitize_key((string) $resource['slug']);
        $post = $this->repository($resource)->findAny((int) ($source['id'] ?? 0));

        if (!$post instanceof WP_Post) {
            wp_die(esc_html__('Resource not found.', 'wpp-framework'));
        }

        check_admin_referer(AdminForm::nonceActionFor("{$slug}_delete"), AdminForm::nonceNameFor("{$slug}_delete"));

        if (($error = $this->authorize($resource, 'delete', $post)) instanceof WP_Error) {
            wp_die(esc_html($this->errorMessage($error)));
        }

        $this->repository($resource)->delete($post, (bool) ($resource['force_delete'] ?? false));
        $this->redirect($resource, ['message' => 'deleted']);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function handleBulk(array $resource): void
    {
        $this->ensureCapability($resource);
        $source = $this->post();
        $slug = sanitize_key((string) $resource['slug']);
        check_admin_referer(AdminForm::nonceActionFor("{$slug}_bulk"), AdminForm::nonceNameFor("{$slug}_bulk"));

        $singleDeleteId = isset($source['delete_id']) ? (int) $source['delete_id'] : 0;

        if ($singleDeleteId <= 0 && ($source['bulk_action'] ?? '') !== 'delete') {
            $this->redirect($resource, ['message' => 'no_action']);
            return;
        }

        $ids = $singleDeleteId > 0 ? [$singleDeleteId] : array_map('intval', (array) ($source['ids'] ?? []));
        $deleted = 0;
        $repository = $this->repository($resource);

        foreach ($ids as $id) {
            $post = $repository->findAny($id);

            if (!$post instanceof WP_Post) {
                continue;
            }

            if (($error = $this->authorize($resource, 'delete', $post)) instanceof WP_Error) {
                continue;
            }

            if ($repository->delete($post, (bool) ($resource['force_delete'] ?? false))) {
                $deleted++;
            }
        }

        $this->redirect($resource, ['message' => 'bulk_deleted', 'deleted' => (string) $deleted]);

        return;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    private function sanitize(array $resource, array $source): array
    {
        $data = [];

        foreach ($resource['fields'] ?? [] as $name => $field) {
            $name = sanitize_key((string) $name);
            $field = is_array($field) ? $field : [];
            $type = (string) ($field['type'] ?? 'text');

            if ($type === 'checkbox') {
                $data[$name] = isset($source[$name]) ? '1' : '0';
                continue;
            }

            $value = $source[$name] ?? ($field['default'] ?? '');
            $data[$name] = $this->sanitizer->sanitize($value, $field);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function payload(array $resource, array $source): array
    {
        $data = $this->sanitize($resource, $source);

        if (!isset($resource['rules'])) {
            return $data;
        }

        return (new Validator())->validate($data, (array) $resource['rules'], (array) ($resource['messages'] ?? []));
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    private function filterArgs(array $resource): array
    {
        $args = [];
        $metaQuery = [];

        foreach ($resource['filters'] ?? [] as $name => $filter) {
            $name = sanitize_key((string) $name);
            $filter = is_array($filter) ? $filter : [];
            $value = $_GET[$name] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (($filter['query'] ?? '') === 'search') {
                $args['s'] = sanitize_text_field((string) $value);
                continue;
            }

            if (($filter['query'] ?? '') === 'post_status') {
                $args['post_status'] = sanitize_key((string) $value);
                continue;
            }

            if (isset($filter['meta_key'])) {
                $metaQuery[] = [
                    'key' => (string) $filter['meta_key'],
                    'value' => sanitize_text_field((string) $value),
                    'compare' => (string) ($filter['compare'] ?? '='),
                    'type' => (string) ($filter['value_type'] ?? 'CHAR'),
                ];
            }
        }

        if ($metaQuery !== []) {
            $args['meta_query'] = $metaQuery;
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    private function activeFilters(array $resource): array
    {
        $active = [];

        foreach (array_keys($resource['filters'] ?? []) as $name) {
            $name = sanitize_key((string) $name);
            $active[$name] = $_GET[$name] ?? '';
        }

        return $active;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    private function values(array $resource, ?WP_Post $post): array
    {
        $values = [];

        foreach ($resource['fields'] ?? [] as $name => $field) {
            $name = sanitize_key((string) $name);
            $field = is_array($field) ? $field : [];

            if (!$post instanceof WP_Post) {
                $values[$name] = $field['default'] ?? '';
                continue;
            }

            $values[$name] = match ($name) {
                'title' => get_the_title($post),
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                default => get_post_meta($post->ID, (string) ($field['meta_key'] ?? $name), true),
            };
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function findPostFromQuery(array $resource): ?WP_Post
    {
        return $this->repository($resource)->findAny((int) ($_GET['id'] ?? 0));
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function repository(array $resource): BaseRepository
    {
        $repository = $this->container->get((string) ($resource['repository'] ?? ''));

        if (!$repository instanceof BaseRepository) {
            throw new \RuntimeException('Admin CRUD [repository] must extend BaseRepository.');
        }

        return $repository;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function authorize(array $resource, string $ability, mixed ...$args): bool|WP_Error
    {
        $policyClass = (string) ($resource['policy'] ?? '');

        if ($policyClass === '') {
            return true;
        }

        $policy = $this->container->get($policyClass);

        if (!method_exists($policy, $ability)) {
            throw new \RuntimeException("Admin CRUD policy does not define [{$ability}].");
        }

        $result = $policy->{$ability}(...$args);

        if ($result instanceof WP_Error) {
            return $result;
        }

        return $result === true ? true : new WP_Error('wpp_forbidden', 'Forbidden.', ['status' => 403]);
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function ensureCapability(array $resource): void
    {
        $capability = (string) ($resource['capability'] ?? 'manage_options');

        if (!current_user_can($capability)) {
            wp_die(esc_html__('Forbidden', 'wpp-framework'));
        }
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, string> $args
     */
    private function redirect(array $resource, array $args): void
    {
        $url = $this->pageUrl($resource);

        foreach ($args as $key => $value) {
            $url = add_query_arg($key, $value, $url);
        }

        wp_safe_redirect($url);
        exit;
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $source
     * @param array<string, string> $args
     */
    private function redirectValidation(array $resource, ValidationException $exception, array $source, array $args): void
    {
        set_transient($this->validationTransientKey($resource), [
            'errors' => $exception->errors(),
            'old' => $this->sanitize($resource, $source),
        ], 300);

        $this->redirect($resource, array_merge($args, ['message' => 'validation_failed']));
    }

    /**
     * @param array<string, mixed> $resource
     * @return array{errors?: array<string, array<int, string>>, old?: array<string, mixed>}
     */
    private function validationFlash(array $resource): array
    {
        $key = $this->validationTransientKey($resource);
        $flash = get_transient($key);
        delete_transient($key);

        return is_array($flash) ? $flash : [];
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function validationTransientKey(array $resource): string
    {
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;

        return 'wpp_admin_crud_validation_' . md5((string) ($resource['slug'] ?? '') . '|' . (string) $userId);
    }

    /**
     * @param array<string, mixed> $resource
     */
    private function pageUrl(array $resource): string
    {
        return add_query_arg('page', sanitize_key((string) $resource['slug']), admin_url('admin.php'));
    }

    /**
     * @return array<string, mixed>
     */
    private function post(): array
    {
        $post = wp_unslash($_POST);

        return is_array($post) ? $post : [];
    }

    private function adminForm(): AdminForm
    {
        return $this->container->get(AdminForm::class);
    }

    private function view(): ViewRenderer
    {
        return $this->container->get(ViewRenderer::class);
    }

    private function errorMessage(WP_Error $error): string
    {
        return (string) $error->get_error_message();
    }
}
