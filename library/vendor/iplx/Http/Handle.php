<?php

namespace iplx\Http;

/**
 * cURL handle representation
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
     * Received response headers
     *
     * @var array
     */
    public $responseHeaders = [];
}
