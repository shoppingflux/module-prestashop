<?php
/**
 * Copyright since 2019 Shopping Feed
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @copyright Since 2019 Shopping Feed
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

return [
    [
        'name' => 'shoppingfeed',
        'stubs' => [
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\FileIntegrityStub::class => [
                'repository' => 'shoppingflux/module-prestashop',
            ],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\GithubVersionStub::class => [
                'repository' => 'shoppingflux/module-prestashop',
            ],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\ConflictsStub::class => [],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\DatabaseStub::class => [
                'optimize' => false,
                'integrity' => false,
            ],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\ConfigurationStub::class => [],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\HooksStub::class => [],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\OverridesStub::class => [],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\HostStub::class => [],
            ShoppingfeedClasslib\Extensions\Diagnostic\Stubs\Concrete\LogsStub::class => [],
        ],
        'meta' => [
            'doc' => 'https://desk.202-ecommerce.com/portal/en/kb/articles/shoppingfeed-prestashop-module-developper-guide',
            'help' => 'help',
        ],
    ],
];
