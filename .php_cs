<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/classes')
    ->in(__DIR__.'/controllers')
    ->in(__DIR__.'/sql')
    ->in(__DIR__.'/upgrade')
    ->in(__DIR__.'/tests')
;

return PhpCsFixer\Config::create()
    ->setRules(
        [
            '@PSR2'                  => true,
            '@Symfony'               => true,
            'binary_operator_spaces' => [
                'align_double_arrow' => true,
                'align_equals'       => true,
            ],
            'linebreak_after_opening_tag'       => true,
            'not_operator_with_successor_space' => true,
            'ordered_class_elements'            => [
                'sortAlgorithm' => 'alpha',
            ],
            'ordered_imports' => [
                'imports_order' => [
                    'const',
                    'class',
                    'function',
                ],
            ],
        ]
    )
    ->setFinder($finder)
;
