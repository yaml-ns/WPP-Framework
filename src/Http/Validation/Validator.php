<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Validation;

use YamlNs\WppFramework\Http\Validation\Rules\WordPressExistsRule;

final class Validator
{
    /**
     * @var array<string, callable>
     */
    private array $customRules;

    /**
     * @param array<string, callable> $rules
     */
    public function __construct(array $rules = [])
    {
        $this->customRules = array_merge([
            'exists' => new WordPressExistsRule(),
        ], $rules);
    }

    public function extend(string $name, callable $rule): void
    {
        $this->customRules[$name] = $rule;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed>
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $definition) {
            $fieldRules = is_array($definition) ? $definition : explode('|', $definition);
            $value = $data[$field] ?? null;
            $present = array_key_exists($field, $data);
            $nullable = in_array('nullable', $fieldRules, true);
            $sometimes = in_array('sometimes', $fieldRules, true);
            $required = in_array('required', $fieldRules, true) || $this->isConditionallyRequired($fieldRules, $data);

            if ($sometimes && !$present) {
                continue;
            }

            if (!$present || $value === null || $value === '' || $value === []) {
                if ($required) {
                    $errors[$field][] = $this->message($messages, $field, 'required', "{$field} is required.");
                }

                if ($nullable || !$required) {
                    continue;
                }
            }

            foreach ($fieldRules as $rule) {
                if (
                    $rule === 'required'
                    || $rule === 'nullable'
                    || $rule === 'sometimes'
                    || str_starts_with($rule, 'required_if:')
                    || str_starts_with($rule, 'required_with:')
                ) {
                    continue;
                }

                [$name, $parameters] = $this->parseRule($rule);

                if ($name === 'confirmed') {
                    if ($value !== ($data["{$field}_confirmation"] ?? null)) {
                        $errors[$field][] = $this->message($messages, $field, $name, "{$field} is invalid.");
                    }

                    continue;
                }

                if ($name === 'same') {
                    if ($value !== ($data[$parameters[0] ?? ''] ?? null)) {
                        $errors[$field][] = $this->message($messages, $field, $name, "{$field} is invalid.");
                    }

                    continue;
                }

                if ($name === 'different') {
                    if ($value === ($data[$parameters[0] ?? ''] ?? null)) {
                        $errors[$field][] = $this->message($messages, $field, $name, "{$field} is invalid.");
                    }

                    continue;
                }

                $result = $this->passes($name, $parameters, $value, $data);

                if ($result !== true) {
                    $errors[$field][] = is_string($result)
                        ? $result
                        : $this->message($messages, $field, $name, "{$field} is invalid.");
                }
            }

            if (!isset($errors[$field]) && $present) {
                $validated[$field] = $this->cast($value, $fieldRules);
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $parameters] = explode(':', $rule, 2);

        return [$name, $parameters === '' ? [] : explode(',', $parameters)];
    }

    /**
     * @param array<int, string> $parameters
     * @param array<string, mixed> $data
     */
    private function passes(string $rule, array $parameters, mixed $value, array $data): bool|string
    {
        if (isset($this->customRules[$rule])) {
            $result = ($this->customRules[$rule])($value, $parameters, $data);

            return is_string($result) ? $result : $result === true;
        }

        return match ($rule) {
            'string' => is_string($value) || is_numeric($value),
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'boolean' => is_bool($value) || in_array((string) $value, ['0', '1', 'true', 'false'], true),
            'array' => is_array($value),
            'min' => $this->min($value, (float) ($parameters[0] ?? 0)),
            'max' => $this->max($value, (float) ($parameters[0] ?? 0)),
            'in' => in_array((string) $value, $parameters, true),
            'not_in' => !in_array((string) $value, $parameters, true),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date' => strtotime((string) $value) !== false,
            'alpha_dash' => is_string($value) && preg_match('/^[A-Za-z0-9_-]+$/', $value) === 1,
            'slug' => is_string($value) && preg_match('/^[a-z0-9_-]+$/', $value) === 1,
            'json' => $this->json($value),
            'regex' => isset($parameters[0]) && @preg_match($parameters[0], (string) $value) === 1,
            'size' => $this->size($value, (float) ($parameters[0] ?? 0)),
            default => throw new \RuntimeException("Unknown validation rule [{$rule}]."),
        };
    }

    /**
     * @param array<int, string> $rules
     * @param array<string, mixed> $data
     */
    private function isConditionallyRequired(array $rules, array $data): bool
    {
        foreach ($rules as $rule) {
            [$name, $parameters] = $this->parseRule($rule);

            if ($name === 'required_if') {
                $other = $parameters[0] ?? '';
                $expected = array_slice($parameters, 1);

                if ($other !== '' && in_array((string) ($data[$other] ?? null), $expected, true)) {
                    return true;
                }
            }

            if ($name === 'required_with') {
                foreach ($parameters as $other) {
                    if (isset($data[$other]) && $data[$other] !== '') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function min(mixed $value, float $min): bool
    {
        if (is_array($value)) {
            return count($value) >= $min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        return strlen((string) $value) >= $min;
    }

    private function max(mixed $value, float $max): bool
    {
        if (is_array($value)) {
            return count($value) <= $max;
        }

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        return strlen((string) $value) <= $max;
    }

    private function size(mixed $value, float $size): bool
    {
        if (is_array($value)) {
            return count($value) === (int) $size;
        }

        if (is_numeric($value)) {
            return (float) $value === $size;
        }

        return strlen((string) $value) === (int) $size;
    }

    private function json(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param array<int, string> $rules
     */
    private function cast(mixed $value, array $rules): mixed
    {
        if (in_array('integer', $rules, true)) {
            return (int) $value;
        }

        if (in_array('numeric', $rules, true)) {
            return (float) $value;
        }

        if (in_array('boolean', $rules, true)) {
            return in_array((string) $value, ['1', 'true'], true);
        }

        return $value;
    }

    /**
     * @param array<string, string> $messages
     */
    private function message(array $messages, string $field, string $rule, string $default): string
    {
        return $messages["{$field}.{$rule}"] ?? $messages[$field] ?? $default;
    }
}
