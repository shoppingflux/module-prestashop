<?php

namespace ShoppingfeedPrefix\Prestashop\ModuleLibGuzzleAdapter\Interfaces;

use ShoppingfeedPrefix\Psr\Http\Message\RequestInterface;

/**
 * HTTP Client Interface
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Victor Bocharsky <victor@symfonycasts.com>
 *
 * @see https://github.com/php-fig/http-client/blob/master/src/ClientInterface.php
 */
interface HttpClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param \ShoppingfeedPrefix\Psr\Http\Message\RequestInterface $request
     *
     * @return \ShoppingfeedPrefix\Psr\Http\Message\ResponseInterface
     *
     * @throws ClientExceptionInterface If an error happens while processing the request
     */
    public function sendRequest(RequestInterface $request);
}
