<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->exclude('vendor-scoped')
    ->notPath('*/Resources/config/*')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'parameters']],
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_blank_lines_after_class_opening' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
