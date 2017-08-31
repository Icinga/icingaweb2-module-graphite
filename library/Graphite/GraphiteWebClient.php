<?php

namespace Icinga\Module\Graphite;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Pattern\Singleton;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;

class GraphiteWebClient implements GraphiteWebClientInterface
{
    use Singleton;

    /**
     * Base URL as configured
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Default headers
     *
     * @var string[string]
     */
    private $baseHeaders;

    public function request($url, $params = null, $method = 'GET', array $headers = [], $body = null)
    {
        $httpOptions = ['method' => $method, 'header' => ''];
        foreach (array_merge($this->baseHeaders, $headers) as $header => $headerValue) {
            $httpOptions['header'] .= "$header: $headerValue\r\n";
        }

        if ($body !== null) {
            $httpOptions['content'] = $body;
        }

        $url = Url::fromPath($this->baseUrl . ltrim($url, '/'));
        if ($params !== null) {
            $url->setParams($params);
        }

        // TODO(ak): use our CurlClient (one nice day)
        return file_get_contents($url->getAbsoluteUrl(), false, stream_context_create(['http' => $httpOptions]));
    }

    private function init()
    {
        $config = Config::module('graphite');
        $graphite = $config->getSection('graphite');
        $baseUrl = $graphite->web_url;
        if ($baseUrl === null) {
            throw new ConfigurationError('Missing graphite.web_url in "%s"', $config->getConfigFile());
        }

        $this->baseUrl = rtrim($baseUrl, '/') . '/';

        $this->baseHeaders = ['User-Agent' => 'icingaweb2-module-graphite'];
        if (isset($graphite->web_user) && isset($graphite->web_password)) {
            $this->baseHeaders['Authorization'] = 'Basic ' . base64_encode(
                "{$graphite->web_user}:{$graphite->web_password}"
            );
        }
    }
}
