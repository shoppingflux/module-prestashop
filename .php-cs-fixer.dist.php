<?php

$psconfig = new PrestaShop\CodingStandards\CsFixer\Config();
$rules = $psconfig->getRules();
$rules['nullable_type_declaration_for_default_null_value'] = false;
$rules['blank_line_after_opening_tag'] = false;

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $psconfig->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude(['vendor', 'node_modules', '202/shoppingfeed', '202/guzzlehttp']);

$config = new PhpCsFixer\Config();
$config
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder);


return $config;
