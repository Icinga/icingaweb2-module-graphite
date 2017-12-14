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
     * Send a HTTP request
     *
     * @param   RequestInterface    $request    Request to send
     * @param   array               $options    Request options
     *
     * @return  ResponseInterface               The response
     */
    public function send(RequestInterface $request, array $options = []);
}
