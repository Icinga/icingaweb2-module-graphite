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
     * The request target
     *
     * @var string|null
     */
    protected $requestTarget;

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

        $this->provideHostHeader();
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $requestTarget = $this->uri->getPath();

        // Weak type checks to also check null

        if ($requestTarget == '') {
            $requestTarget = '/';
        }

        if ($this->uri->getQuery() != '') {
            $requestTarget .= '?' . $this->uri->getQuery();
        }

        return $requestTarget;
    }

    public function withRequestTarget($requestTarget)
    {
        $request = clone $this;
        $request->requestTarget = $requestTarget;

        return $request;
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
        $request = clone $this;
        $request->uri = $uri;

        if (! $preserveHost) {
            $this->provideHostHeader(true);
        }

        return $this;
    }

    protected function provideHostHeader($force = false)
    {
        if ($this->hasHeader('host')) {
            if (! $force) {
                return;
            }

            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
        }

        $host = $this->uri->getHost();

        // Weak type check to also check null
        if ($host == '') {
            $host = '';
        } else {
            $port = $this->uri->getPort();

            if ($port !== null) {
                $host .= ":$port";
            }
        }

        $this->headerNames['host'] = $header;
        $this->headerValues['host'] = [$host];
    }
}
