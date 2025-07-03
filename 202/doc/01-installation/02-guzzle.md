---
category: Installation
name: '2. Problem : Guzzle trouble'
---

## Problem

With version `0.2.1-beta.1`, the SDK uses Guzzle for HTTP communication. However, it uses
a more recent version of Guzzle than Prestashop.  
* If the SDK's Guzzle is used, Prestashop breaks.  
* If Prestashop's Guzzle is used, the SDK breaks.

## Solution

### The prefixing script

The `202/prefix-vendor-namespace.php` script is used to prefix  the Guzzle
library namespaces used by the SDK, as well as the SDK itself. The script
must be used from CLI, and is configured directly in the file.  
However, changes must be made so that :
* The autoloader won't load the "original" Guzzle, or any of its own
dependencies
* The autoloader won't load the "original" SDK, since it
uses the "original" Guzzle
* The autoloader will load the "prefixed" Guzzle, with all its own
"prefixed" dependencies
* The autoloader will load the "rewritten" SDK, which uses the new
Guzzle namespace.

Our composer.json file :
```json
{
  "require": {
    "shoppingfeed/php-sdk": "0.2.1-beta.1"
  },
  "config": {
    "vendor-dir": "vendor"
  },
  "autoload": {
    "files": [
      "vendor/prefixed/guzzlehttp/guzzle/src/functions_include.php",
      "vendor/prefixed/guzzlehttp/psr7/src/functions_include.php",
      "vendor/prefixed/guzzlehttp/promises/src/functions_include.php"
    ],
    "psr-4": {
      "ShoppingfeedPrefix\\GuzzleHttp\\":          "vendor/prefixed/guzzlehttp/guzzle/src/",
      "ShoppingfeedPrefix\\GuzzleHttp\\Psr7\\":    "vendor/prefixed/guzzlehttp/psr7/src/",
      "ShoppingfeedPrefix\\GuzzleHttp\\Promise\\": "vendor/prefixed/guzzlehttp/promises/src/",
      "ShoppingFeed\\Sdk\\":             "vendor/prefixed/shoppingfeed/php-sdk/src/"
    }
  }
}
```

The "directly included files"
(e.g. `vendor/prefixed/guzzlehttp/guzzle/src/functions_include.php`)
must be modified by hand, as they use namespaces in strings.

```php
if (!function_exists('ShoppingfeedPrefix\GuzzleHttp\uri_template')) {
    require __DIR__ . '/functions.php';
}
```

You can find which files should be included directly by looking at the
libraries own `composer.json` files
(e.g. `/guzzlehttp/guzzle/composer.json`).  
You can also check these files to fill the `psr-4` namespaces in the
main `composer.json`.

### Autoloading

As of now, the only way to prevent Composer from autoloading Guzzle is to
rename or remove the folder. Using the `"exclude-from-classmap"` property does not
work, since the namespace can still be resolved using the filesystem.  
We don't have to do it for the SDK however, since we're not trying to
_prevent_ Composer from loading it, but to have it loaded from a
_different path_.  

Nevertheless, the PHP script moves all the "original" libraries to
the `202` folder to solve those problems.

### Usage

From the command line :

```bash
modules/shoppingfeed$ composer install
modules/shoppingfeed$ php 202/prefix-vendor-namespace.php
modules/shoppingfeed$ composer dump-autoload
```

The `vendor/prefixed` folder and the "original" libraries in `202`
should be deleted before attempting a dependency update.  
As mentioned previously, directly included files must be modified by hand.