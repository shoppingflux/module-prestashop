<?php
/**
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 */

namespace ShoppingfeedAddon\Services;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ShoppingFeed\Feed\Csv\CsvProductFeedWriter;
use ShoppingFeed\Feed\Product;
use ShoppingFeed\Feed\ProductFeedMetadata;
use ShoppingFeed\Feed\ProductFeedResult;
use ShoppingFeed\Feed\ProductFeedWriterInterface;
use ShoppingFeed\Feed\ProductGenerator;
use ShoppingFeed\Feed\Xml\XmlProductFeedWriter;

class SfProductGenerator
{
    const VALIDATE_NONE = 0;
    const VALIDATE_EXCLUDE = 1;
    const VALIDATE_EXCEPTION = 2;

    /**
     * File destination for the output feed
     *
     * @var string
     */
    protected $uri;

    /**
     * Extra Attributes for the generated feed
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @var callable[]
     */
    protected $processors = [];

    /**
     * @var callable[]
     */
    protected $filters = [];

    /**
     * @var callable[]
     */
    protected $mappers = [];

    /**
     * @var ProductFeedMetadata
     */
    protected $metadata;

    /**
     * @var bool
     */
    protected $validate;

    /**
     * @var string
     */
    protected $writer;

    /**
     * @var array
     */
    protected static $writers = [
        'xml' => XmlProductFeedWriter::class,
        'csv' => CsvProductFeedWriter::class,
    ];

    /**
     * @param string $alias The short name used to select writer later on instance scope
     * @param string $writerClass The writer class that implements ProductFeedWriterInterface
     *
     * @throws \InvalidArgumentException When the $$writerClass is not an implementation of ProductFeedWriterInterface
     */
    public static function registerWriter($alias, $writerClass)
    {
        if (!in_array(ProductFeedWriterInterface::class, class_implements($writerClass), true)) {
            throw new \InvalidArgumentException(sprintf('Writer class shoud implements "%s" interface', ProductFeedWriterInterface::class));
        }

        self::$writers[$alias] = $writerClass;
    }

    /**
     * Check if the alias is already registered
     *
     * @param string $alias
     *
     * @return bool
     */
    public static function hasWriterAlias($alias)
    {
        return isset(self::$writers[$alias]);
    }

    /**
     * @param string string $uri The uri where data will be written
     * @param string string $writerAlias The writer to use for this generator instance
     */
    public function __construct($uri = 'php://output', $writerAlias = 'xml')
    {
        $this->setUri($uri);
        $this->setWriter($writerAlias);

        $this->metadata = new ProductFeedMetadata();
        $this->validate = self::VALIDATE_NONE;
    }

    /**
     * @param string $uri
     *
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @param string $alias The writer alias to uses
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If the alias is not registered at class level
     */
    public function setWriter($alias)
    {
        if (!isset(self::$writers[$alias])) {
            throw new \InvalidArgumentException(sprintf('Writer alias "%s" is not registered', $alias));
        }

        $this->writer = $alias;

        return $this;
    }

    /**
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     *
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Determine if every product must be validated.
     * Possible values are:
     *
     * - self::VALIDATE_NONE      : No validation at all, invalid products may be written to the feed
     * - self::VALIDATE_EXCLUDE   : Validated and excluded from the final result if invalid. No error reported
     * - self::VALIDATE_EXCEPTION : An exception is thrown when the first invalid product is met
     *
     * @param int $flags
     *
     * @return ProductGenerator
     */
    public function setValidationFlags($flags)
    {
        $this->validate = (int) $flags;

        return $this;
    }

    /**
     * @param string $platformName
     * @param string $platformVersion
     *
     * @return $this
     */
    public function setPlatform($platformName, $platformVersion)
    {
        $this->metadata->setPlatform($platformName, $platformVersion);

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function addFilter(callable $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function addProcessor(callable $callback)
    {
        $this->processors[] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function addMapper(callable $callback)
    {
        $this->mappers[] = $callback;

        return $this;
    }

    public function open()
    {
        $this->metadata->setStartedAt(new \DateTimeImmutable());

        $this->writer = $this->createWriter();
        $this->writer->open($this->uri);
        $this->writer->setAttributes($this->attributes);
    }

    public function close()
    {
        $this->metadata->setFinishedAt(new \DateTimeImmutable());
        $this->writer->close($this->metadata);

        return new ProductFeedResult(
            $this->metadata->getStartedAt(),
            $this->metadata->getFinishedAt()
        );
    }

    public function appendProduct($iterable)
    {
        if (!$iterable instanceof \Traversable && !is_array($iterable)) {
            throw new \Exception(sprintf('cannot iterates over %s', gettype($iterable)));
        }

        $prototype = new Product\Product();
        foreach ($iterable as $item) {
            // Apply processors
            foreach ($this->processors as $processor) {
                $item = $processor($item);
            }

            // Apply filters
            foreach ($this->filters as $processor) {
                if (false === $processor($item)) {
                    $this->metadata->incrFiltered();
                    continue 2;
                }
            }

            // Apply mappers
            $product = clone $prototype;
            foreach ($this->mappers as $mapper) {
                $mapper($item, $product);
            }

            // The product does not match expected validation rules
            if ($this->validate && false === $product->isValid()) {
                if ($this->validate === self::VALIDATE_EXCEPTION) {
                    throw new Product\InvalidProductException(sprintf('Invalid product found at index %d, aborting', $product->id));
                }

                $this->metadata->incrInvalid();
                continue;
            }

            $this->writer->writeProduct($product);
            $this->metadata->incrWritten();
        }
    }

    /**
     * @param \Traversable|array $iterable
     *
     * @return ProductFeedResult
     *
     * @throws \Exception
     */
    public function write($iterable)
    {
        if (!$iterable instanceof \Traversable && !is_array($iterable)) {
            throw new \Exception(sprintf('cannot iterates over %s', gettype($iterable)));
        }

        $metadata = $this->metadata;
        $metadata->setStartedAt(new \DateTimeImmutable());

        $writer = $this->createWriter();
        $writer->open($this->uri);
        $writer->setAttributes($this->attributes);

        $prototype = new Product\Product();
        foreach ($iterable as $item) {
            // Apply processors
            foreach ($this->processors as $processor) {
                $item = $processor($item);
            }

            // Apply filters
            foreach ($this->filters as $processor) {
                if (false === $processor($item)) {
                    $metadata->incrFiltered();
                    continue 2;
                }
            }

            // Apply mappers
            $product = clone $prototype;
            foreach ($this->mappers as $mapper) {
                $mapper($item, $product);
            }

            // The product does not match expected validation rules
            if ($this->validate && false === $product->isValid()) {
                if ($this->validate === self::VALIDATE_EXCEPTION) {
                    throw new Product\InvalidProductException(sprintf('Invalid product found at index %d, aborting', $metadata->getTotalCount()));
                }

                $metadata->incrInvalid();
                continue;
            }

            $writer->writeProduct($product);
            $metadata->incrWritten();
        }

        $metadata->setFinishedAt(new \DateTimeImmutable());
        $writer->close($metadata);

        return new ProductFeedResult(
            $metadata->getStartedAt(),
            $metadata->getFinishedAt()
        );
    }

    /**
     * @return ProductFeedWriterInterface
     */
    protected function createWriter()
    {
        $writerClass = self::$writers[$this->writer];

        return new $writerClass();
    }

    public function getMetaData()
    {
        return $this->metadata;
    }
}
