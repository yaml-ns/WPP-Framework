<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Support;

use Psr\Log\LogLevel;
use YamlNs\WppFramework\Contracts\RestController;

final class ConfigValidator
{
    /**
     * @param array<string, mixed> $config
     */
    public static function validate(array $config, bool $requireIdentity = false): void
    {
        self::validateIdentity($config, $requireIdentity);
        self::validateAdmin($config['admin'] ?? null);
        self::validateAdminCrud($config['admin_crud'] ?? null);
        self::validateAdminForms($config['admin_forms'] ?? null);
        self::validateMetaBoxes($config['meta_boxes'] ?? null);
        self::validateLogger($config['logger'] ?? null);
        self::validateAssets($config['assets'] ?? null);
        self::validateRestControllers($config['rest_controllers'] ?? null);
        self::validateRoutes($config['routes'] ?? null);
        self::validateShortcodes($config['shortcodes'] ?? null);
        self::validateUninstall($config['uninstall'] ?? null);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function validateIdentity(array $config, bool $required): void
    {
        foreach (['slug', 'rest_namespace'] as $key) {
            if (!isset($config[$key]) || (string) $config[$key] === '') {
                if ($required) {
                    throw new \RuntimeException("Config key [{$key}] is required.");
                }

                continue;
            }

            if ($key === 'slug' && !preg_match('/^[a-z0-9_-]+$/', (string) $config[$key])) {
                throw new \RuntimeException('Config key [slug] must only contain lowercase letters, numbers, underscores and dashes.');
            }

            if ($key === 'rest_namespace' && !preg_match('/^[a-z0-9_-]+\/v[0-9]+$/', trim((string) $config[$key], '/'))) {
                throw new \RuntimeException('Config key [rest_namespace] must look like my-plugin/v1.');
            }
        }
    }

    private static function validateAdmin(mixed $admin): void
    {
        if ($admin === null) {
            return;
        }

        if (!is_array($admin)) {
            throw new \RuntimeException('Config key [admin] must be an array.');
        }

        foreach ($admin['pages'] ?? [] as $index => $page) {
            if (!is_array($page)) {
                throw new \RuntimeException("Admin page [{$index}] must be an array.");
            }

            foreach (['menu_title', 'slug'] as $required) {
                if ((string) ($page[$required] ?? '') === '') {
                    throw new \RuntimeException("Admin page [{$index}] must define [{$required}].");
                }
            }

            if (!isset($page['callback']) && !isset($page['template'])) {
                throw new \RuntimeException("Admin page [{$index}] must define [callback] or [template].");
            }
        }
    }

    private static function validateAdminCrud(mixed $adminCrud): void
    {
        if ($adminCrud === null) {
            return;
        }

        if (!is_array($adminCrud)) {
            throw new \RuntimeException('Config key [admin_crud] must be an array.');
        }

        if (isset($adminCrud['resources']) && !is_array($adminCrud['resources'])) {
            throw new \RuntimeException('Admin CRUD [resources] must be an array.');
        }

        foreach ($adminCrud['resources'] ?? [] as $index => $resource) {
            if (!is_array($resource)) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] must be an array.");
            }

            foreach (['slug', 'repository', 'views', 'fields'] as $required) {
                if (!isset($resource[$required]) || $resource[$required] === '') {
                    throw new \RuntimeException("Admin CRUD resource [{$index}] must define [{$required}].");
                }
            }

            if (!is_string($resource['repository']) || $resource['repository'] === '') {
                throw new \RuntimeException("Admin CRUD resource [{$index}] repository must be a non-empty class string.");
            }

            if (isset($resource['policy']) && (!is_string($resource['policy']) || $resource['policy'] === '')) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] policy must be a non-empty class string.");
            }

            if (!preg_match('/^[a-z0-9_-]+$/', (string) $resource['slug'])) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] has an invalid [slug].");
            }

            if (!is_array($resource['views'] ?? null) || !isset($resource['views']['index'], $resource['views']['form'])) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] must define views [index] and [form].");
            }

            foreach (['index', 'form'] as $view) {
                if (!is_string($resource['views'][$view]) || $resource['views'][$view] === '') {
                    throw new \RuntimeException("Admin CRUD resource [{$index}] view [{$view}] must be a non-empty string.");
                }
            }

            if (!is_array($resource['fields'] ?? null)) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] fields must be an array.");
            }

            foreach ($resource['fields'] as $fieldName => $field) {
                self::validateAdminCrudField($index, $fieldName, $field);
            }

            if (isset($resource['filters'])) {
                if (!is_array($resource['filters'])) {
                    throw new \RuntimeException("Admin CRUD resource [{$index}] filters must be an array.");
                }

                foreach ($resource['filters'] as $filterName => $filter) {
                    self::validateAdminCrudFilter($index, $filterName, $filter);
                }
            }

            if (isset($resource['rules'])) {
                self::validateAdminCrudRules($index, $resource['rules']);
            }

            if (isset($resource['messages'])) {
                self::validateStringMap($resource['messages'], "Admin CRUD resource [{$index}] messages");
            }

            if (isset($resource['force_delete']) && !is_bool($resource['force_delete'])) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] force_delete must be a boolean.");
            }

            if (isset($resource['per_page']) && (!is_int($resource['per_page']) || $resource['per_page'] < 1)) {
                throw new \RuntimeException("Admin CRUD resource [{$index}] per_page must be a positive integer.");
            }
        }
    }

    private static function validateAdminCrudField(int|string $resourceIndex, int|string $fieldName, mixed $field): void
    {
        if (!is_array($field)) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] field [{$fieldName}] must be an array.");
        }

        if (isset($field['type']) && (!is_string($field['type']) || $field['type'] === '')) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] field [{$fieldName}] type must be a non-empty string.");
        }

        foreach (['label', 'description', 'meta_key'] as $key) {
            if (isset($field[$key]) && !is_string($field[$key])) {
                throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] field [{$fieldName}] {$key} must be a string.");
            }
        }

        if (isset($field['required']) && !is_bool($field['required'])) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] field [{$fieldName}] required must be a boolean.");
        }

        if (isset($field['options']) && !is_array($field['options'])) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] field [{$fieldName}] options must be an array.");
        }
    }

    private static function validateAdminCrudFilter(int|string $resourceIndex, int|string $filterName, mixed $filter): void
    {
        if (!is_array($filter)) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] filter [{$filterName}] must be an array.");
        }

        foreach (['label', 'type', 'query', 'meta_key', 'compare', 'value_type'] as $key) {
            if (isset($filter[$key]) && !is_string($filter[$key])) {
                throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] filter [{$filterName}] {$key} must be a string.");
            }
        }

        if (isset($filter['query']) && !in_array($filter['query'], ['search', 'post_status'], true)) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] filter [{$filterName}] has an unsupported query.");
        }

        if (!isset($filter['query']) && !isset($filter['meta_key'])) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] filter [{$filterName}] must define [query] or [meta_key].");
        }

        if (isset($filter['options']) && !is_array($filter['options'])) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] filter [{$filterName}] options must be an array.");
        }
    }

    private static function validateAdminCrudRules(int|string $resourceIndex, mixed $rules): void
    {
        if (!is_array($rules)) {
            throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] rules must be an array.");
        }

        foreach ($rules as $field => $definition) {
            if (is_string($definition)) {
                continue;
            }

            if (!is_array($definition)) {
                throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] rule [{$field}] must be a string or array.");
            }

            foreach ($definition as $rule) {
                if (!is_string($rule) || $rule === '') {
                    throw new \RuntimeException("Admin CRUD resource [{$resourceIndex}] rule [{$field}] contains an invalid rule.");
                }
            }
        }
    }

    private static function validateStringMap(mixed $map, string $label): void
    {
        if (!is_array($map)) {
            throw new \RuntimeException("{$label} must be an array.");
        }

        foreach ($map as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new \RuntimeException("{$label} must contain string keys and string values.");
            }
        }
    }

    private static function validateAdminForms(mixed $adminForms): void
    {
        if ($adminForms === null) {
            return;
        }

        if (!is_array($adminForms)) {
            throw new \RuntimeException('Config key [admin_forms] must be an array.');
        }

        foreach ($adminForms['forms'] ?? [] as $index => $form) {
            if (!is_array($form)) {
                throw new \RuntimeException("Admin form [{$index}] must be an array.");
            }

            $id = (string) ($form['id'] ?? '');
            $action = (string) ($form['action'] ?? '');

            if ($id === '' || $action === '') {
                throw new \RuntimeException("Admin form [{$index}] must define [id] and [action].");
            }

            if (!preg_match('/^[a-z0-9_-]+$/', $id) || !preg_match('/^[a-z0-9_-]+$/', $action)) {
                throw new \RuntimeException("Admin form [{$index}] has an invalid [id] or [action].");
            }

            if (!isset($form['option']) && !isset($form['handler'])) {
                throw new \RuntimeException("Admin form [{$index}] must define [option] or [handler].");
            }
        }
    }

    private static function validateMetaBoxes(mixed $metaBoxes): void
    {
        if ($metaBoxes === null) {
            return;
        }

        if (!is_array($metaBoxes)) {
            throw new \RuntimeException('Config key [meta_boxes] must be an array.');
        }

        foreach ($metaBoxes['boxes'] ?? [] as $index => $box) {
            if (!is_array($box)) {
                throw new \RuntimeException("Meta box [{$index}] must be an array.");
            }

            foreach (['id', 'title', 'screen'] as $required) {
                if (($box[$required] ?? '') === '') {
                    throw new \RuntimeException("Meta box [{$index}] must define [{$required}].");
                }
            }
        }
    }

    private static function validateRestControllers(mixed $controllers): void
    {
        if ($controllers === null) {
            return;
        }

        if (!is_array($controllers)) {
            throw new \RuntimeException('Config key [rest_controllers] must be an array.');
        }

        foreach ($controllers as $index => $controller) {
            if ($controller instanceof RestController) {
                continue;
            }

            if (is_string($controller) && class_exists($controller) && is_subclass_of($controller, RestController::class)) {
                continue;
            }

            throw new \RuntimeException("REST controller [{$index}] must implement " . RestController::class . '.');
        }
    }

    private static function validateLogger(mixed $logger): void
    {
        if ($logger === null) {
            return;
        }

        if (!is_array($logger)) {
            throw new \RuntimeException('Config key [logger] must be an array.');
        }

        $levels = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];

        if (isset($logger['min_level']) && !in_array($logger['min_level'], $levels, true)) {
            throw new \RuntimeException('Logger [min_level] must be a valid PSR-3 level.');
        }
    }

    private static function validateAssets(mixed $assets): void
    {
        if ($assets === null) {
            return;
        }

        if (!is_array($assets)) {
            throw new \RuntimeException('Config key [assets] must be an array.');
        }

        foreach (['admin', 'frontend'] as $groupKey) {
            foreach ($assets[$groupKey]['scripts'] ?? [] as $index => $script) {
                if (!is_array($script)) {
                    throw new \RuntimeException("Asset script [{$groupKey}.{$index}] must be an array.");
                }

                if (isset($script['localize'])) {
                    if (!is_array($script['localize']) || (string) ($script['localize']['object_name'] ?? '') === '') {
                        throw new \RuntimeException("Asset script [{$groupKey}.{$index}] localize must define [object_name].");
                    }
                }
            }
        }
    }

    private static function validateRoutes(mixed $routes): void
    {
        if ($routes === null) {
            return;
        }

        if (!is_array($routes)) {
            throw new \RuntimeException('Config key [routes] must be an array.');
        }

        foreach ($routes as $index => $path) {
            if (!is_string($path) || $path === '') {
                throw new \RuntimeException("Route file [{$index}] must be a non-empty string path.");
            }

            if (!is_file($path)) {
                throw new \RuntimeException("Route file not found: {$path}");
            }
        }
    }

    private static function validateShortcodes(mixed $shortcodes): void
    {
        if ($shortcodes === null) {
            return;
        }

        if (!is_array($shortcodes)) {
            throw new \RuntimeException('Config key [shortcodes] must be an array.');
        }

        foreach (array_keys($shortcodes) as $tag) {
            if (!preg_match('/^[a-z0-9_-]+$/', (string) $tag)) {
                throw new \RuntimeException("Invalid shortcode tag: {$tag}");
            }
        }
    }

    private static function validateUninstall(mixed $uninstall): void
    {
        if ($uninstall === null) {
            return;
        }

        if (!is_array($uninstall)) {
            throw new \RuntimeException('Config key [uninstall] must be an array.');
        }

        foreach (['options', 'site_options'] as $key) {
            if (isset($uninstall[$key]) && !is_array($uninstall[$key])) {
                throw new \RuntimeException("Uninstall [{$key}] must be an array.");
            }
        }
    }
}
