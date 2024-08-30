<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('translations')
    ->exclude('prestashop')
    ->exclude('dist')
    ->exclude('tests')
    ->exclude('vendor');

$config = (new PrestaShop\CodingStandards\CsFixer\Config())
    ->setUsingCache(false)
    ->setFinder($finder);

return $config;
