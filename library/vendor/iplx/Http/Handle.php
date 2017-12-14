<?php

namespace iplx\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Internal cURL handle representation
 */
class Handle
{
    /**
     * cURL handle
     *
     * @var resource
     */
    public $handle;

    /**
     * Response body
     *
     * @var StreamInterface
     */
    public $responseBody;

    /**
     * Received response headers
     *
     * @var array
     */
    public $responseHeaders = [];
}
