<?php

declare(strict_types=1);

namespace ShoppingfeedPrefix\Prestashop\ModuleLibGuzzleAdapter\Guzzle7;

use GuzzleHttp\Exception as GuzzleExceptions;
use GuzzleHttp\Promise\PromiseInterface;
use ShoppingfeedPrefix\Http\Client\Exception as HttplugException;
use ShoppingfeedPrefix\Http\Promise\Promise as HttpPromise;
use ShoppingfeedPrefix\Prestashop\ModuleLibGuzzleAdapter\Guzzle7\Exception\UnexpectedValueException;
use ShoppingfeedPrefix\Psr\Http\Message\RequestInterface;
use ShoppingfeedPrefix\Psr\Http\Message\ResponseInterface;

/**
 * Wrapper around Guzzle promises.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class Promise implements HttpPromise
{
    /**
     * @var PromiseInterface
     */
    private $promise;

    /**
     * @var string State of the promise
     */
    private $state;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var HttplugException
     */
    private $exception;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(PromiseInterface $promise, RequestInterface $request)
    {
        $this->request = $request;
        $this->state = self::PENDING;
        $this->promise = $promise->then(function ($response) {
            $this->response = $response;
            $this->state = self::FULFILLED;

            return $response;
        }, function ($reason) use ($request) {
            $this->state = self::REJECTED;

            if ($reason instanceof HttplugException) {
                $this->exception = $reason;
            } elseif ($reason instanceof GuzzleExceptions\GuzzleException) {
                $this->exception = $this->handleException($reason, $request);
            } elseif ($reason instanceof \Throwable) {
                $this->exception = new HttplugException\TransferException('Invalid exception returned from Guzzle7', 0, $reason);
            } else {
                $this->exception = new UnexpectedValueException('Reason returned from Guzzle7 must be an Exception');
            }

            throw $this->exception;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        return new static($this->promise->then($onFulfilled, $onRejected), $this->request);
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function wait($unwrap = true)
    {
        $this->promise->wait(false);

        if ($unwrap) {
            if (self::REJECTED == $this->getState()) {
                throw $this->exception;
            }

            return $this->response;
        }
    }

    /**
     * Converts a Guzzle exception into an Httplug exception.
     *
     * @return HttplugException
     */
    private function handleException(GuzzleExceptions\GuzzleException $exception, RequestInterface $request)
    {
        if ($exception instanceof GuzzleExceptions\ConnectException) {
            return new HttplugException\NetworkException($exception->getMessage(), $exception->getRequest(), $exception);
        }

        if ($exception instanceof GuzzleExceptions\RequestException) {
            // Make sure we have a response for the HttpException
            if ($exception->hasResponse()) {
                return new HttplugException\HttpException(
                    $exception->getMessage(),
                    $exception->getRequest(),
                    $exception->getResponse(),
                    $exception
                );
            }

            return new HttplugException\RequestException($exception->getMessage(), $exception->getRequest(), $exception);
        }

        return new HttplugException\TransferException($exception->getMessage(), 0, $exception);
    }
}
