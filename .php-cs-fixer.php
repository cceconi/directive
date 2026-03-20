<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests/src',
    ])
    ->name('*.php')
    ->notPath('vendor');

return (new Config())
    ->setRules([
        '@PER-CS2.0'                       => true,
        '@PHP84Migration'                  => true,
        'declare_strict_types'             => true,
        'ordered_imports'                  => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                => true,
        'array_syntax'                     => ['syntax' => 'short'],
        'trailing_comma_in_multiline'      => ['elements' => ['arrays', 'parameters', 'arguments']],
        'phpdoc_to_comment'                => false,
        'single_quote'                     => true,
        'blank_line_after_opening_tag'     => true,
        'concat_space'                     => ['spacing' => 'one'],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');