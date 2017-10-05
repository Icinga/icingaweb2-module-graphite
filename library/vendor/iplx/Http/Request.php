<?php

namespace iplx\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * A HTTP request
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * HTTP method of the request
     *
     * @var string
     */
    protected $method;

    /**
     * URI of the request
     *
     * @var UriInterface
     */
    protected $uri;

    /**
     * Create a new HTTP request
     *
     * @param   string      $method             HTTP method
     * @param   string      $uri                URI
     * @param   array       $headers            Request headers
     * @param   string      $body               Request body
     * @param   string      $protocolVersion    Protocol version
     */
    public function __construct($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $this->method = $method;
        $this->uri = new Uri($uri);
        $this->setHeaders($headers);
        $this->body = Stream::create($body);
        $this->protocolVersion = $protocolVersion;
    }

    public function getRequestTarget()
    {
        // TODO: Implement getRequestTarget() method.
    }

    public function withRequestTarget($requestTarget)
    {
        // TODO: Implement withRequestTarget() method.
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $request = clone $this;
        $request->method = $method;

        return $this;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        // TODO: Implement withUri() method.
    }
}
