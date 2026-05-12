<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Fields;

class FieldSanitizer
{
    /**
     * @param array<string, mixed> $field
     */
    public function sanitize(mixed $value, array $field): mixed
    {
        $type = (string) ($field['type'] ?? 'text');

        if (is_array($value) && !in_array($type, ['select_multiple', 'checkboxes'], true)) {
            $value = reset($value);
            $value = $value === false ? '' : $value;
        }

        return match ($type) {
            'checkbox' => (string) $value === '1' ? '1' : '0',
            'email' => sanitize_email((string) $value),
            'url' => esc_url_raw((string) $value),
            'number', 'float' => is_numeric($value) ? (float) $value : 0.0,
            'integer' => is_numeric($value) ? (int) $value : 0,
            'textarea' => sanitize_textarea_field((string) $value),
            'select_multiple', 'checkboxes' => array_map('sanitize_text_field', array_map('strval', (array) $value)),
            default => sanitize_text_field((string) $value),
        };
    }

    public function metaType(string $fieldType): string
    {
        return match ($fieldType) {
            'number', 'float' => 'number',
            default => 'string',
        };
    }
}
