<?php

namespace ShoppingfeedPrefix\Http\Message\Formatter;

use ShoppingfeedPrefix\Http\Message\Formatter;
use ShoppingfeedPrefix\Psr\Http\Message\RequestInterface;
use ShoppingfeedPrefix\Psr\Http\Message\ResponseInterface;

/**
 * Normalize a request or a response into a string or an array.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class SimpleFormatter implements Formatter
{
    public function formatRequest(RequestInterface $request)
    {
        return sprintf(
            '%s %s %s',
            $request->getMethod(),
            $request->getUri()->__toString(),
            $request->getProtocolVersion()
        );
    }

    public function formatResponse(ResponseInterface $response)
    {
        return sprintf(
            '%s %s %s',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getProtocolVersion()
        );
    }

    /**
     * Formats a response in context of its request.
     *
     * @return string
     */
    public function formatResponseForRequest(ResponseInterface $response, RequestInterface $request)
    {
        return $this->formatResponse($response);
    }
}
