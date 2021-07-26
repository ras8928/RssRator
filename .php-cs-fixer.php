<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0.0|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setIndent("\t")
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR1' => true,
        '@PSR12' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@PHP70Migration' => true,
        'array_push' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_nested_dirname' => true,
        'concat_space' => ['spacing' => 'one'],
        'constant_case' => ['case' => 'upper'],
        'dir_constant' => true,
        'function_to_constant' => true,
        'is_null' => true,
        'logical_operators' => true,
        'modernize_types_casting' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_alias_functions' => true,
        'no_php4_constructor' => true,
        'no_trailing_whitespace_in_string' => true,
        'phpdoc_tag_casing' => true,
        'psr_autoloading' => true,
        'set_type_to_cast' => true,
        'simplified_if_return' => true,
        'simplified_null_return' => true,
        'string_line_ending' => true,
        'ternary_to_elvis_operator' => true,
    ])
    ->setFinder($config->getFinder()->exclude(['vendor', 'obsolete', 'test', 'cache']));
