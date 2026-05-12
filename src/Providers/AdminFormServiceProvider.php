<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Admin\AdminForm;
use YamlNs\WppFramework\Core\Container;

final class AdminFormServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     forms?: array<int, array<string, mixed>>
     * } $adminForms
     */
    public function __construct(Container $container, private readonly array $adminForms = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        foreach ($this->adminForms['forms'] ?? [] as $form) {
            $action = sanitize_key((string) ($form['action'] ?? ''));

            if ($action === '') {
                throw new \RuntimeException('Admin form action is required.');
            }

            add_action("admin_post_{$action}", fn (): mixed => $this->handle($form));
        }
    }

    /**
     * @param array<string, mixed> $form
     */
    private function handle(array $form): mixed
    {
        $capability = (string) ($form['capability'] ?? 'manage_options');

        if (!current_user_can($capability)) {
            wp_die(esc_html__('Forbidden', 'wpp-framework'));
        }

        check_admin_referer($this->nonceAction($form), $this->nonceName($form));
        $source = wp_unslash($_POST);
        $source = is_array($source) ? $source : [];

        if (isset($form['handler'])) {
            return $this->container->call($this->resolveCallback($form['handler']), [
                'form' => $form,
                'data' => $this->sanitize($form, $source),
            ]);
        }

        $option = (string) ($form['option'] ?? '');

        if ($option === '') {
            throw new \RuntimeException('Admin form option is required when no custom handler is configured.');
        }

        update_option($option, $this->sanitize($form, $source));
        $this->redirectBack($form, $source);

        return null;
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function sanitize(array $form, array $source): array
    {
        $data = [];

        foreach ($form['fields'] ?? [] as $key => $field) {
            $field = is_array($field) ? $field : [];
            $name = sanitize_key((string) $key);
            $type = (string) ($field['type'] ?? 'text');

            if ($type === 'checkbox') {
                $data[$name] = isset($source[$name]) ? '1' : '0';
                continue;
            }

            $value = $source[$name] ?? ($field['default'] ?? '');
            $data[$name] = $this->sanitizeValue($value, $type);
        }

        return $data;
    }

    private function sanitizeValue(mixed $value, string $type): mixed
    {
        if (is_array($value) && !in_array($type, ['select_multiple', 'checkboxes'], true)) {
            $value = reset($value);
            $value = $value === false ? '' : $value;
        }

        return match ($type) {
            'email' => sanitize_email((string) $value),
            'url' => esc_url_raw((string) $value),
            'number', 'integer' => (int) $value,
            'float' => (float) $value,
            'textarea' => sanitize_textarea_field((string) $value),
            'select_multiple', 'checkboxes' => array_map('sanitize_text_field', array_map('strval', (array) $value)),
            default => sanitize_text_field((string) $value),
        };
    }

    /**
     * @param array<string, mixed> $form
     * @param array<string, mixed> $source
     */
    private function redirectBack(array $form, array $source): void
    {
        $redirect = (string) ($source['_wp_http_referer'] ?? ($form['redirect'] ?? admin_url()));
        $redirect = add_query_arg('settings-updated', '1', wp_validate_redirect($redirect, admin_url()));

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function nonceAction(array $form): string
    {
        return AdminForm::nonceActionFor((string) ($form['id'] ?? $form['action']));
    }

    /**
     * @param array<string, mixed> $form
     */
    private function nonceName(array $form): string
    {
        return AdminForm::nonceNameFor((string) ($form['id'] ?? $form['action']));
    }

    private function resolveCallback(mixed $callback): callable
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new \RuntimeException('Admin form handler must be callable.');
        }

        return $callback;
    }
}
