<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Fields\FieldSanitizer;
use YamlNs\WppFramework\Http\PostInput;

final class MetaBoxServiceProvider extends ServiceProvider
{
    private PostInput $postInput;
    private FieldSanitizer $sanitizer;

    /**
     * @param array{
     *     boxes?: array<int, array<string, mixed>>
     * } $metaBoxes
     */
    public function __construct(Container $container, private readonly array $metaBoxes = [])
    {
        parent::__construct($container);
        $this->postInput = $container->get(PostInput::class);
        $this->sanitizer = $container->get(FieldSanitizer::class);
    }

    public function boot(): void
    {
        add_action('init', function (): void {
            $this->registerMeta();
        });

        add_action('add_meta_boxes', function (): void {
            $this->registerBoxes();
        });

        add_action('save_post', function (int $postId): void {
            $this->save($postId);
        });
    }

    private function registerMeta(): void
    {
        foreach ($this->boxes() as $box) {
            foreach ($box['fields'] ?? [] as $key => $field) {
                foreach ((array) $box['screen'] as $screen) {
                    register_post_meta((string) $screen, (string) $key, [
                        'single' => $field['single'] ?? true,
                        'type' => $field['meta_type'] ?? $this->sanitizer->metaType((string) ($field['type'] ?? 'text')),
                        'sanitize_callback' => $field['sanitize_callback'] ?? fn (mixed $value): mixed => $this->sanitizer->sanitize($value, $field),
                        'show_in_rest' => $field['show_in_rest'] ?? false,
                        'auth_callback' => $field['auth_callback'] ?? fn (): bool => current_user_can('edit_posts'),
                    ]);
                }
            }
        }
    }

    private function registerBoxes(): void
    {
        foreach ($this->boxes() as $box) {
            add_meta_box(
                (string) $box['id'],
                (string) $box['title'],
                function (\WP_Post $post) use ($box): void {
                    $this->renderBox($post, $box);
                },
                $box['screen'],
                $box['context'] ?? 'normal',
                $box['priority'] ?? 'default'
            );
        }
    }

    /**
     * @param array<string, mixed> $box
     */
    private function renderBox(\WP_Post $post, array $box): void
    {
        wp_nonce_field($this->nonceAction($box), $this->nonceName($box));

        echo '<table class="form-table" role="presentation"><tbody>';

        foreach ($box['fields'] ?? [] as $key => $field) {
            $value = get_post_meta($post->ID, (string) $key, true);
            $this->renderField((string) $key, $field, $value);
        }

        echo '</tbody></table>';
    }

    private function save(int $postId): void
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $postId)) {
            return;
        }

        $source = $this->postInput->all();
        $postType = get_post_type($postId);

        foreach ($this->boxes() as $box) {
            if ($postType !== false && !in_array($postType, (array) ($box['screen'] ?? []), true)) {
                continue;
            }

            $nonceName = $this->nonceName($box);

            if (!isset($source[$nonceName]) || !wp_verify_nonce((string) $source[$nonceName], $this->nonceAction($box))) {
                continue;
            }

            foreach ($box['fields'] ?? [] as $key => $field) {
                $key = (string) $key;
                $field = is_array($field) ? $field : [];
                $value = $source[$key] ?? null;

                if (($field['type'] ?? 'text') === 'checkbox') {
                    $value = $value === null ? '0' : '1';
                }

                if ($value === null) {
                    delete_post_meta($postId, $key);
                    continue;
                }

                update_post_meta($postId, $key, $this->sanitizer->sanitize($value, $field));
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function boxes(): array
    {
        return $this->metaBoxes['boxes'] ?? [];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function renderField(string $key, array $field, mixed $value): void
    {
        $type = (string) ($field['type'] ?? 'text');
        $label = (string) ($field['label'] ?? $key);

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';

        if ($type === 'textarea') {
            echo '<textarea class="large-text" rows="4" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea((string) $value) . '</textarea>';
        } elseif ($type === 'checkbox') {
            echo '<label><input type="checkbox" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="1" ' . checked((string) $value, '1', false) . '> ' . esc_html($field['description'] ?? '') . '</label>';
        } else {
            echo '<input class="regular-text" type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr((string) $value) . '">';
        }

        if ($type !== 'checkbox' && isset($field['description'])) {
            echo '<p class="description">' . esc_html((string) $field['description']) . '</p>';
        }

        echo '</td></tr>';
    }

    /**
     * @param array<string, mixed> $box
     */
    private function nonceAction(array $box): string
    {
        return 'wpp_meta_box_' . (string) $box['id'];
    }

    /**
     * @param array<string, mixed> $box
     */
    private function nonceName(array $box): string
    {
        return '_wpp_meta_box_' . (string) $box['id'];
    }
}
