<?php

namespace iplx\Http;

use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    /**
     * Case sensitive header names with lowercase header names as keys
     *
     * @var array
     */
    protected $headerNames = [];

    /**
     * Header values with lowercase header names as keys
     *
     * @var array
     */
    protected $headerValues = [];

    /**
     * The body of this request
     *
     * @var StreamInterface
     */
    protected $body;

    /**
     * Protocol version
     *
     * @var string
     */
    protected $protocolVersion;

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        $message = clone $this;
        $message->protocolVersion = $version;

        return $message;
    }

    public function getHeaders()
    {
        return array_combine($this->headerNames, $this->headerValues);
    }

    public function hasHeader($header)
    {
        return isset($this->headerValues[strtolower($header)]);
    }

    public function getHeader($header)
    {
        $header = strtolower($header);

        if (! isset($this->headerValues[$header])) {
            return [];
        }

        return $this->headerValues[$header];
    }

    public function getHeaderLine($name)
    {
        $name = strtolower($name);

        if (! isset($this->headerValues[$name])) {
            return '';
        }

        return implode(', ', $this->headerValues[$name]);
    }

    public function withHeader($name, $value)
    {
        $name = rtrim($name);

        $value = $this->normalizeHeaderValues($value);

        $normalized = strtolower($name);

        $message = clone $this;
        $message->headerNames[$normalized] = $name;
        $message->headerValues[$normalized] = $value;

        return $message;
    }

    public function withAddedHeader($name, $value)
    {
        $name = rtrim($name);

        $value = $this->normalizeHeaderValues($value);

        $normalized = strtolower($name);

        $message = clone $this;
        if (isset($message->headerNames[$normalized])) {
            $message->headerValues[$normalized] = array_merge($message->headerValues[$normalized], $value);
        } else {
            $message->headerNames[$normalized] = $name;
            $message->headerValues[$normalized] = $value;
        }

        return $message;
    }

    public function withoutHeader($name)
    {
        $normalized = strtolower(rtrim($name));

        $message = clone $this;
        unset($message->headerNames[$normalized]);
        unset($message->headerValues[$normalized]);

        return $message;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $message = clone $this;
        $message->body = $body;

        return $message;
    }

    protected function setHeaders(array $headers)
    {
        // Prepare header field names and header field values according to
        // https://tools.ietf.org/html/rfc7230#section-3.2.4
        $names = array_map('rtrim', array_keys($headers));
        $values = $this->normalizeHeaderValues($headers);

        $normalized = array_map('strtolower', $names);

        $this->headerNames = array_combine(
            $normalized,
            $names
        );

        $this->headerValues = array_combine(
            $normalized,
            $values
        );
    }

    protected function normalizeHeaderValues(array $values)
    {
        // Prepare header field names and header field values according to
        // https://tools.ietf.org/html/rfc7230#section-3.2.4
        return array_map(function ($value) {
            if (! is_array($value)) {
                $value = [$value];
            }

            return array_map(function ($value) {
                return trim($value, " \t");
            }, $value);
        }, $values);
    }
}
