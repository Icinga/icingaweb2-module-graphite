<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Graphite\Graphing;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\UriInterface;

/**
 * HTTP interface to Graphite Web
 */
class GraphiteWebClient
{
    /**
     * Base URL of every Graphite Web HTTP request
     *
     * @var UriInterface
     */
    protected $baseUri;

    /**
     * HTTP basic auth user for every Graphite Web HTTP request
     *
     * @var string|null
     */
    protected $user;

    /**
     * The above user's password
     *
     * @var string|null
     */
    protected $password;

    /**
     * Don't verify the remote's TLS certificate
     *
     * @var bool
     */
    protected $insecure = false;

    /**
     * Timeout for every Graphite Web HTTP request
     *
     * @var ?int
     */
    protected $timeout;

    /**
     * HTTP client
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Constructor
     *
     * @param UriInterface $baseUrl    Base URL of every Graphite Web HTTP request
     */
    public function __construct(UriInterface $baseUrl)
    {
        $this->httpClient = new Client();

        $this->setBaseUri($baseUrl);
    }

    /**
     * Send an HTTP request to the configured Graphite Web and return the response's body
     *
     * @param UriInterface $uri
     * @param string $method
     * @param string[] $headers
     * @param string $body
     *
     * @return  string
     */
    public function request(UriInterface $uri, $method = 'GET', array $headers = [], $body = null)
    {
        $headers['User-Agent'] = 'icingaweb2-module-graphite';
        if ($this->user !== null) {
            $headers['Authorization'] = 'Basic ' . base64_encode("{$this->user}:{$this->password}");
        }

        // TODO(ak): keep connections alive (TCP handshakes are a bit expensive and TLS handshakes are very expensive)
        return (string) $this->httpClient->send(
            new Request($method, $this->completeUri($uri), $headers, $body),
            [
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => ! $this->insecure
                ],
                'timeout' => $this->timeout ?? 10
            ]
        )->getBody();
    }

    /**
     * Complete the given relative URL according to the base URL
     *
     * @param UriInterface $uri
     *
     * @return UriInterface
     */
    public function completeUri(UriInterface $uri)
    {
        parse_str($this->baseUri->getQuery(), $originalParams);
        parse_str($uri->getQuery(), $newParams);

        $uri = $this->baseUri
            ->withPath(ltrim(rtrim($this->baseUri->getPath(), '/') . '/' . ltrim($uri->getPath(), '/'), '/'))
            ->withQuery(http_build_query(array_merge($originalParams, $newParams)));

        return $uri;
    }

    /**
     * Get the base URL of every Graphite Web HTTP request
     *
     * @return UriInterface
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * Set the base URL of every Graphite Web HTTP request
     *
     * @param UriInterface $baseUri
     *
     * @return $this
     */
    public function setBaseUri(UriInterface $baseUri)
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Get the HTTP basic auth user
     *
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the HTTP basic auth user
     *
     * @param null|string $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the HTTP basic auth password
     *
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the HTTP basic auth password
     *
     * @param null|string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get whether not to verify the remote's TLS certificate
     *
     * @return bool
     */
    public function getInsecure()
    {
        return $this->insecure;
    }

    /**
     * Set whether not to verify the remote's TLS certificate
     *
     * @param bool $insecure
     *
     * @return $this
     */
    public function setInsecure($insecure = true)
    {
        $this->insecure = $insecure;

        return $this;
    }

    /**
     * Get the HTTP request timeout
     *
     * @return ?int
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * Set the HTTP request timeout
     *
     * @param ?int $timeout
     *
     * @return $this
     */
    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
