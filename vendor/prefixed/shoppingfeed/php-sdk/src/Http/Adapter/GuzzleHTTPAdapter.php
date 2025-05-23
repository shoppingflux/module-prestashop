<?php
namespace ShoppingFeed\Sdk\Http\Adapter;

use \SfGuzzle\GuzzleHttp;
use SfPsr\Psr\Http\Message\RequestInterface;
use ShoppingFeed\Sdk\Client;
use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingFeed\Sdk\Hal\HalClient;
use ShoppingFeed\Sdk\Http;

/**
 * Http Client Adapter for \SfGuzzle\GuzzleHttp v6
 */
class GuzzleHTTPAdapter implements Http\Adapter\AdapterInterface
{
    /**
     * @var \SfGuzzle\GuzzleHttp\HandlerStack
     */
    private $stack;

    /**
     * @var Client\ClientOptions
     */
    private $options;

    /**
     * @var \SfGuzzle\GuzzleHttp\Client
     */
    private $client;

    /**
     * @var \SfGuzzle\GuzzleHttp\Pool
     */
    private $pool;

    public function __construct(
        Client\ClientOptions $options = null,
        \SfGuzzle\GuzzleHttp\HandlerStack $stack = null
    )
    {
        $this->options = $options ?: new Client\ClientOptions();
        $this->stack   = $stack ?: $this->createHandlerStack();

        $this->initClient();
    }

    /**
     * @inheritdoc
     */
    public function configure(ClientOptions $options)
    {
        $this->options = $options;

        $this->createHandlerStack();
        $this->initClient();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request, array $options = [])
    {
        return $this->client->send($request, $options);
    }

    /**
     * @inheritdoc
     */
    public function batchSend(array $requests, array $options = [])
    {
        $options['rejected'] = $this->createExceptionCallback();

        $this->pool = new \SfGuzzle\GuzzleHttp\Pool($this->client, $requests, $options);
        $this->pool->promise()->wait(true);
    }

    /**
     * @inheritdoc
     */
    public function createRequest($method, $uri, array $headers = [], $body = null)
    {
        if (isset($headers['Content-Type']) && 'multipart/form-data' === $headers['Content-Type']) {
            unset($headers['Content-Type']);
            $body = new \SfGuzzle\GuzzleHttp\Psr7\MultipartStream($body);
        }

        return new \SfGuzzle\GuzzleHttp\Psr7\Request($method, $uri, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function withToken($token)
    {
        $stack = clone $this->stack;
        $stack->push(
            \SfGuzzle\GuzzleHttp\Middleware::mapRequest(function (RequestInterface $request) use ($token) {
                return $request->withHeader('Authorization', 'Bearer ' . trim($token));
            }),
            'token_auth'
        );

        return $this->createClient($this->options->getBaseUri(), $stack);
    }

    /**
     * @param callable|null $callback
     *
     * @return \Closure
     */
    private function createExceptionCallback(callable $callback = null)
    {
        return function (\SfGuzzle\GuzzleHttp\Exception\TransferException $exception) use ($callback) {
            if ($callback && $exception instanceof \SfGuzzle\GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
                $halClient = new HalClient($this->options->getBaseUri(), $this);
                $resource  = $halClient->createResource($exception->getResponse());

                $callback($resource);
            }
        };
    }

    /**
     * Initialise Http Client
     */
    private function initClient()
    {
        $this->client = new \SfGuzzle\GuzzleHttp\Client([
            'handler'  => $this->stack,
            'base_uri' => $this->options->getBaseUri(),
            'headers'  => $this->options->getHeaders(),
        ]);
    }

    /**
     * Create new adapter with given stack
     *
     * @param string                  $baseUri
     * @param \SfGuzzle\GuzzleHttp\HandlerStack $stack
     *
     * @return Http\Adapter\AdapterInterface
     *
     * @throws Http\Exception\MissingDependencyException
     */
    private function createClient($baseUri, $stack)
    {
        $options = $this->options;
        $options->setBaseUri($baseUri);

        return new self($options, $stack);
    }

    /**
     * Create handler stack
     *
     * @return \SfGuzzle\GuzzleHttp\HandlerStack
     */
    private function createHandlerStack()
    {
        $this->stack = \SfGuzzle\GuzzleHttp\HandlerStack::create();
        $logger      = $this->options->getLogger();

        if ($this->options->handleRateLimit()) {
            $handler = new Http\Middleware\RateLimitHandler(3, $logger);
            $this->stack->push(\SfGuzzle\GuzzleHttp\Middleware::retry([$handler, 'decide'], [$handler, 'delay']), 'rate_limit');
        }

        $retryCount = $this->options->getRetryOnServerError();
        if ($retryCount) {
            $handler = new Http\Middleware\ServerErrorHandler($retryCount);
            $this->stack->push(\SfGuzzle\GuzzleHttp\Middleware::retry([$handler, 'decide']), 'retry_count');
        }

        if ($logger) {
            $this->stack->push(\SfGuzzle\GuzzleHttp\Middleware::log($logger, new \SfGuzzle\GuzzleHttp\MessageFormatter()), 'logger');
        }

        return $this->stack;
    }
}
