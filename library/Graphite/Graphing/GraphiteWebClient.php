<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Web\Url;
use iplx\Http\Client;
use iplx\Http\ClientInterface;
use iplx\Http\Request;

/**
 * HTTP interface to Graphite Web
 */
class GraphiteWebClient
{
    /**
     * Base URL of every Graphite Web HTTP request
     *
     * @var Url
     */
    protected $baseUrl;

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
     * HTTP client
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Constructor
     *
     * @param   Url $baseUrl    Base URL of every Graphite Web HTTP request
     */
    public function __construct(Url $baseUrl)
    {
        $this->httpClient = new Client();

        $this->setBaseUrl($baseUrl);
    }

    /**
     * Send an HTTP request to the configured Graphite Web and return the response's body
     *
     * @param   Url         $url
     * @param   string      $method
     * @param   string[]    $headers
     * @param   string      $body
     *
     * @return  string
     */
    public function request(Url $url, $method = 'GET', array $headers = [], $body = null)
    {
        $headers['User-Agent'] = 'icingaweb2-module-graphite';
        if ($this->user !== null) {
            $headers['Authorization'] = 'Basic ' . base64_encode("{$this->user}:{$this->password}");
        }

        $url = Url::fromPath(rtrim($this->baseUrl->getAbsoluteUrl(), '/') . '/' . ltrim($url->getPath(), '/'))
            ->setParams($url->getParams())
            ->getAbsoluteUrl();

        return (string) $this->httpClient->send(new Request($method, $url, $headers, $body))->getBody();
    }

    /**
     * Get the base URL of every Graphite Web HTTP request
     *
     * @return Url
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set the base URL of every Graphite Web HTTP request
     *
     * @param Url $baseUrl
     *
     * @return $this
     */
    public function setBaseUrl(Url $baseUrl)
    {
        $this->baseUrl = $baseUrl;

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
}
