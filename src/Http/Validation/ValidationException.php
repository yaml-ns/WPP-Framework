<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Validation;

final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The given data was invalid.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
