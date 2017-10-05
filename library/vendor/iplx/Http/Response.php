<?php

namespace iplx\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * A HTTP response
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /**
     * Status code of the response
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Response status reason phrase
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Create a new HTTP response
     *
     * @param   int     $statusCode         Response status code
     * @param   array   $headers            Response headers
     * @param   string  $body               Response body
     * @param   string  $protocolVersion    Protocol version
     * @param   string  $reasonPhrase       Response status reason phrase
     */
    public function __construct($statusCode = 200, array $headers = [], $body = null, $protocolVersion = '1.1', $reasonPhrase = '')
    {
        $this->statusCode = $statusCode;
        $this->setHeaders($headers);
        $this->body = Stream::create($body);
        $this->protocolVersion = $protocolVersion;
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $response = clone $this;
        $response->statusCode = $code;
        $response->reasonPhrase = $reasonPhrase;

        return $response;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
