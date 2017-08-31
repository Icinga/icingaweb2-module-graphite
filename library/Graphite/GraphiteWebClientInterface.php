<?php

namespace Icinga\Module\Graphite;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Pattern\Singleton;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;

/**
 *  HTTP interface to Graphite Web
 */
interface GraphiteWebClientInterface
{
    /**
     * Send an HTTP request to the configured Graphite Web and return the response's body
     *
     * @param   string                      $url
     * @param   UrlParams|string[string]    $params
     * @param   string                      $method
     * @param   string[string]              $headers
     * @param   string                      $body
     *
     * @return  string
     */
    public function request($url, $params = null, $method = 'GET', array $headers = [], $body = null);
}
