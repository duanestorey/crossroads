<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/vendor/duanestorey/crossroads-core/src', __DIR__ . '/vendor/duanestorey/crossroads-core/plugins']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
        'no_unused_imports' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
