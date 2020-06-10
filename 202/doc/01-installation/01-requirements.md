---
category: Installation
name: 1. Requirements
---

PHP 5.6  
Latest PrestaShop 1.6 version (1.6.1.23 as of now), or PrestaShop 1.7.2.4 to
1.7.6.0  
[totpsclasslib](https://gitlab.202-ecommerce.com/202-internal/totpsclasslib)  
[composer](https://getcomposer.org/)  
[Shopping Feed's PHP SDK](https://github.com/shoppingflux/php-sdk); this module
uses version `0.2.1-beta.1`, which creates some [problems](/#2-problem-guzzle-trouble).

**DO NOT** run `composer install` without reading about our [problems with
Guzzle](/#2-problem-guzzle-trouble) !
Running `composer dump-autoload` should be fine, though.