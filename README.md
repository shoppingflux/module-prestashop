# Shoppingfeed PrestaShop Addons

[![Coding Standart](https://github.com/shoppingflux/module-prestashop/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/shoppingflux/module-prestashop/actions/workflows/php.yml) [![Unit test](https://github.com/shoppingflux/module-prestashop/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/shoppingflux/module-prestashop/actions/workflows/phpunit.yml)

## About

Synchronize stocks, prices, products feed and orders from or to marketplace available on ShoppingFeed



#### Product page on PrestaShop Addons:

This addons is not avalable on PrestaShop Addons.
This Git repository is for developpers only.
Please contact ShoppingFeed customer service to get


## Module version guide

| PrestaShop version | Module version |  Repo               | Doc                |  PHP Version |
|--------------------|----------------|---------------------|--------------------|--------------|
| 1.6.x              | 1.x            |  [master]           | [techincal documentation][module-doc] |   5.6 or greater    |
| 1.7.x              | 1.x            |  [master]           | [techincal documentation][module-doc] |   7.1 or greater    |


## Requirements

PHP version (check Module version guide)


## Installation for merchands

To install module on PrestaShop, download zip package supply by ShoppingFeed customer service or the zip file
named [v1.x.x-prod-shoppingfeed.zip](https://github.com/shoppingflux/module-prestashop/tags) attached on each detail release page.

## Installation for developpers

If you are a developper, this module contain composer.json.dist file. If you clone or download the module from github
repository, run the ```composer install``` is not necessary. You can see why on [module documentation][module-doc] on "Guzzle trouble".

See the [composer documentation][composer-doc] to learn more about the composer.json file.

## Compiling assets
**For development**

We use _Webpack_ to compile our javascript and scss files.
In order to compile those files, you must :
1. have _Node 10+_ installed locally
2. run `npm install` in the root folder to install dependencies
3. then run `npm run watch` to compile assets and watch for file changes

**For production**

Run `npm run build` to compile for production.
Files are minified, `console.log` and comments dropped.

## Contributing

PrestaShop modules are open-source extensions to the PrestaShop e-commerce solution. Everyone is welcome and even encouraged to contribute with their own improvements.

### Requirements

Contributors **must** follow the following rules:

* **Make your Pull Request on the "develop" branch**, NOT the "master" branch.
* Do not update the module's version number.
* Follow [the coding standards][1].

### Process in details

Contributors wishing to edit a module's files should follow the following process:

1. Create your GitHub account, if you do not have one already.
2. Fork the shoppingflux/module-prestashop project to your GitHub account.
3. Clone your fork to your local machine in the ```/modules/shoppingfeed``` directory of your PrestaShop installation.
4. Create a branch in your local clone of the module for your changes.
5. Change the files in your branch. Be sure to follow [the coding standards][1]!
6. Push your changed branch to your fork in your GitHub account.
7. Create a pull request for your changes **on the _'develop'_ branch** of the module's project. Be sure to follow [the commit message norm][2] in your pull request. If you need help to make a pull request, read the [Github help page about creating pull requests][3].
8. Wait for one of the core developers either to include your change in the codebase, or to comment on possible improvements you should make to your code.

That's it: you have contributed to this open-source project! Congratulations!

### Command line launched by github actions

Please launch these command line before submitting a Pull Request.

#### phpcs fixer

```bash
~modules/shoppingfeed$ vendor/bin/php-cs-fixer --fix
```
#### phpstan

You need a docker container to launch phpstan

```
# create the prestashop container
~modules/shoppingfeed$ docker run -tid --rm -v ps-volume:/var/www/html --name temp-ps prestashop/prestashop

# launch phpstan
~modules/shoppingfeed$ docker run --rm --volumes-from temp-ps -v $PWD:/var/www/html/modules/shoppingfeed -e _PS_ROOT_DIR_=/var/www/html --workdir=/var/www/html/modules/shoppingfeed phpstan/phpstan:0.12 analyse --configuration=/var/www/html/modules/shoppingfeed/202/phpstan/phpstan.neon
```

### phpunit

You need a docker container to launch phpunit

```
docker run -tid --rm -v $PWD:/var/www/html/modules/shoppingfeed --name temp-unittest-ps 202ecommerce/prestashop:1.7.8.3
docker exec -t temp-unittest-ps sh /var/www/html/modules/shoppingfeed/202/docker/run_for_unittest.sh
```



[1]: https://devdocs.prestashop.com/1.7/development/coding-standards/
[2]: http://doc.prestashop.com/display/PS16/How+to+write+a+commit+message
[3]: https://help.github.com/articles/using-pull-requests
[composer-doc]: https://getcomposer.org/doc/04-schema.md
[module-doc]: https://docs.202-ecommerce.com/shoppingfeed/
