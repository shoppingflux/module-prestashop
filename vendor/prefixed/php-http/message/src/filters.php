<?php

// Register chunk filter if not found
if (!array_key_exists('chunk', stream_get_filters())) {
    stream_filter_register('chunk', 'ShoppingfeedPrefix\Http\Message\Encoding\Filter\Chunk');
}
