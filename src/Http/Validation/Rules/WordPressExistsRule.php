<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Validation\Rules;

final class WordPressExistsRule
{
    /**
     * @param array<int, string> $parameters
     * @param array<string, mixed> $data
     */
    public function __invoke(mixed $value, array $parameters, array $data = []): bool
    {
        return match ($parameters[0] ?? '') {
            'post' => $this->postExists($value, $parameters[1] ?? null),
            'term' => $this->termExists($value, $parameters[1] ?? null),
            'user' => $this->userExists($value),
            default => false,
        };
    }

    private function postExists(mixed $value, ?string $postType): bool
    {
        if (!function_exists('get_post')) {
            return false;
        }

        $post = get_post((int) $value);

        if ($post === null) {
            return false;
        }

        return $postType === null || $post->post_type === $postType;
    }

    private function termExists(mixed $value, ?string $taxonomy): bool
    {
        if (!function_exists('get_term')) {
            return false;
        }

        $term = get_term((int) $value, $taxonomy ?? '');

        if (function_exists('is_wp_error') && is_wp_error($term)) {
            return false;
        }

        return $term !== null;
    }

    private function userExists(mixed $value): bool
    {
        if (!function_exists('get_user_by')) {
            return false;
        }

        return get_user_by('id', (int) $value) !== false;
    }
}
