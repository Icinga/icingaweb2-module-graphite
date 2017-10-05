<?php

namespace iplx\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HTTP clients which send HTTP requests
 */
interface ClientInterface
{
    /**
     * Client version
     *
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * Send a HTTP request
     *
     * @param   RequestInterface    $request    Request to send
     *
     * @return  ResponseInterface               The response
     */
    public function send(RequestInterface $request);
}
