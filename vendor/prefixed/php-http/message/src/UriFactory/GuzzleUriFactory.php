<?php

namespace ShoppingfeedPrefix\Http\Message\UriFactory;

use ShoppingfeedPrefix\GuzzleHttp\Psr7\Utils;
use ShoppingfeedPrefix\Http\Message\UriFactory;

use function ShoppingfeedPrefix\GuzzleHttp\Psr7\uri_for;

if (!interface_exists(UriFactory::class)) {
    throw new \LogicException('You cannot use "ShoppingfeedPrefix\Http\Message\MessageFactory\GuzzleUriFactory" as the "php-http/message-factory" package is not installed. Try running "composer require php-http/message-factory". Note that this package is deprecated, use "psr/http-factory" instead');
}

/**
 * Creates Guzzle URI.
 *
 * @author David de Boer <david@ddeboer.nl>
 *
 * @deprecated This will be removed in php-http/message2.0. Consider using the official Guzzle PSR-17 factory
 */
final class GuzzleUriFactory implements UriFactory
{
    public function createUri($uri)
    {
        if (class_exists(Utils::class)) {
            return Utils::uriFor($uri);
        }

        return uri_for($uri);
    }
}
