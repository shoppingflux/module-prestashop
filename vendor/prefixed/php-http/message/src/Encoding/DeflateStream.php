<?php

namespace ShoppingfeedPrefix\Http\Message\Encoding;

use ShoppingfeedPrefix\Clue\StreamFilter as Filter;
use ShoppingfeedPrefix\Psr\Http\Message\StreamInterface;

/**
 * Stream deflate (RFC 1951).
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class DeflateStream extends FilteredStream
{
    /**
     * @param int $level
     */
    public function __construct(StreamInterface $stream, $level = -1)
    {
        parent::__construct($stream, ['window' => -15, 'level' => $level]);

        // @deprecated will be removed in 2.0
        $this->writeFilterCallback = Filter\fun($this->writeFilter(), ['window' => -15]);
    }

    protected function readFilter(): string
    {
        return 'zlib.deflate';
    }

    protected function writeFilter(): string
    {
        return 'zlib.inflate';
    }
}
