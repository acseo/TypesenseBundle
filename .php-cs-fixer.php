<?php

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,

        'align_multiline_comment' => [
            'comment_type' => 'all_multiline',
        ],
        'native_function_invocation' => false,
        'no_multiline_whitespace_around_double_arrow' => true,
        'array_indentation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'backtick_to_shell_exec' => true,
        'blank_line_before_statement' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'escape_implicit_backslashes' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'fully_qualified_strict_types' => true,
        'function_to_constant' => [
            'functions' => ['get_called_class', 'get_class', 'php_sapi_name', 'phpversion', 'pi'],
        ],
        'single_line_comment_style' => ['hash'],
        'header_comment' => [
            'header' => '',
        ],
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'logical_operators' => true,
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'native_constant_invocation' => false,
        'no_binary_string' => true,
        'no_null_property_initialization' => true,
        'no_php4_constructor' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'non_printable_character' => [
            'use_escape_sequences_in_strings' => true,
        ],
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_to_return_type' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => [
            'sort_algorithm' => 'none',
            'null_adjustment' => 'always_last',
        ],
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_annotation' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'pow_to_exponentiation' => true,
        'psr_autoloading' => true,
        'random_api_migration' => true,
        'return_assignment' => true,
        'error_suppression' => true,
        'single_line_comment_style' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'string_line_ending' => true,
        'ternary_to_null_coalescing' => true,
        'void_return' => false,
        'yoda_style' => [
            'always_move_variable' => false,
            'equal' => false, 
            'identical' => false, 
            'less_and_greater' => false
        ],
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal'
        ],
        'phpdoc_align' => [
            'align' => 'vertical'
        ]
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->notName('Kernel.php')
            ->notName('bootstrap.php')
            ->in([
                __DIR__.'/src',
                __DIR__.'/tests',
            ])
    )
;
