<?php

declare(strict_types=1);

$directories = array_values(array_filter([
    __DIR__ . '/src',
    __DIR__ . '/tests',
    __DIR__ . '/examples',
    __DIR__ . '/config',
], 'is_dir'));

$files = array_values(array_filter([
    __DIR__ . '/bin/wpp',
], 'is_file'));

$finder = PhpCsFixer\Finder::create()
    ->in($directories)
    ->append($files)
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
    ])
    ->setFinder($finder);
