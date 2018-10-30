# Welcome to the Shopping Feed PHP SDK

## Status

[![Build Status](https://status.continuousphp.com/git-hub/shoppingflux/php-sdk?token=49445fa5-6900-499a-9a6c-57d8bdda94e1&branch=develop)](https://continuousphp.com/git-hub/shoppingflux/php-sdk)

## Install

In your project root repository run 
```bash
composer require shoppingfeed/php-sdk
```

This will load the SDK library into a `vendor` repository.  
And thanks to PSR-4 specification you should be able to access the SDK under the namespace `\ShoppingFeed\Sdk`.

## Basic usage

Here are the three basic steps to use the SDK :
1. Authentication to start a new session
2. Retrieve the store(s) you want to manage from the session
3. Manage resources

### Authentication against the API

The Shopping Feed API requires that you are authenticated to perform any calls.

In order to make authenticated call, you should build the client like so:

```php
<?php
namespace ShoppingFeed\Sdk;

// Setup credentials to connect to the API, and create session
$credential = new Credential\Token('api-token');
/** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
$session = Client\Client::createSession($credential);
```

### Accessing your stores

```php
/** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
$store = $session->getMainStore();
$store->getName(); // test-store
$store->getId(); // 1276
// ... and so on
```

If you manage more than one store, you can use the store collection object

```php
/** @var \ShoppingFeed\Sdk\Api\Session\SessionResource $session */
// Get store collection
$stores = $session->getStores();
// Count the number of stores [int]
$stores->count();
// Get a particular store
$store = $stores->select('id-or-store-name');
// Loop over available stores
foreach ($stores as $store) {
	$store->getName(); 
}
```

### SDK guides

- [Authentication in details](docs/manual/authenticate.md)
- [Error handling and debug](docs/manual/error-handling.md)

### SDK resources documentation

- [Inventory management](docs/manual/resources/inventory.md)
- [Order management](docs/manual/resources/order.md)


### Generates XML compliant feed for import

The SDK is able to simplify XML feed creation by providing necessary tools.

Check the documentation at https://github.com/shoppingflux/php-feed-generator to learn how to create compliant feed.

