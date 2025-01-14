<?php

$config = new PrestaShop\CodingStandards\CsFixer\Config();

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude(['vendor', 'node_modules', '202/shoppingfeed', '202/guzzlehttp']);

return $config;
