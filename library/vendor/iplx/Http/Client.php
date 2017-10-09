<?php

namespace iplx\Http;

use RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client that uses cURL
 */
class Client implements ClientInterface
{
    /**
     * cURL handles for reusing connections
     *
     * @var resource[]
     */
    protected $handles = [];

    /**
     * Return user agent
     *
     * @return  string
     */
    protected function getAgent()
    {
        $defaultAgent = 'ipl/' . static::VERSION;
        $defaultAgent .= ' curl/' . curl_version()['version'];
        $defaultAgent .= ' PHP/' . PHP_VERSION;

        return $defaultAgent;
    }

    /**
     * Create and return a cURL handle based on the given request
     *
     * @param   RequestInterface    $request
     *
     * @return  Handle
     *
     * @throws  RuntimeException
     */
    public function createHandle(RequestInterface $request)
    {
        $uri = $request->getUri();
        $server = "{$uri->getScheme()}://{$uri->getHost()}:{$uri->getPort()}";

        if (isset($this->handles[$server])) {
            $ch = $this->handles[$server];
        } else {
            $this->handles[$server] = $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION  => 1,
                CURLOPT_RETURNTRANSFER  => 1
            ]);
        }

        // Bypass Expect: 100-continue timeouts
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (strtolower($name) === 'expect') {
                continue;
            }
            $headers[] = $name . ': ' . implode(', ', $values);
        }

        $options = [
            CURLOPT_URL             => (string) $uri->withFragment(''),
            CURLOPT_CUSTOMREQUEST   => $request->getMethod(),
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_USERAGENT       => $this->getAgent()
        ];

        if ($request->getProtocolVersion()) {
            $protocolVersion = null;
            switch ($request->getProtocolVersion()) {
                case '2.0':
                    if (version_compare(phpversion(), '7.0.7', '<')) {
                        throw new RuntimeException('You need at least PHP 7.0.7 to use HTTP 2.0');
                    }
                    $protocolVersion = CURL_HTTP_VERSION_2;
                    break;
                case '1.1':
                    $protocolVersion = CURL_HTTP_VERSION_1_1;
                    break;
                default:
                    $protocolVersion = CURL_HTTP_VERSION_1_0;
            }

            $options[CURLOPT_HTTP_VERSION] = $protocolVersion;
        }

        if ($request->getBody()) {
            $options[CURLOPT_POSTFIELDS] = (string) $request->getBody();
        }

        $handle = new Handle();

        $options[CURLOPT_HEADERFUNCTION] = function($ch, $header) use ($handle) {
            $size = strlen($header);

            if (! trim($header) || strpos($header, 'HTTP/') === 0) {
                return $size;
            }

            list($key, $value) = explode(': ', $header, 2);
            $handle->responseHeaders[$key] = rtrim($value, "\r\n");

            return $size;
        };

        curl_setopt_array($ch, $options);

        $handle->handle = $ch;

        return $handle;
    }

    /**
     * Execute a cURL handle and return the response as response object
     *
     * @param   Handle  $handle
     *
     * @return  ResponseInterface
     *
     * @throws  RuntimeException
     */
    public function executeHandle(Handle $handle)
    {
        $ch = $handle->handle;

        $body = curl_exec($ch);

        if ($body === false) {
            throw new RuntimeException(curl_error($ch));
        }

        $response = new Response(curl_getinfo($ch, CURLINFO_HTTP_CODE), $handle->responseHeaders, $body);

        curl_close($ch);

        return $response;
    }

    public function send(RequestInterface $request)
    {
        $handle = $this->createHandle($request);

        $response = $this->executeHandle($handle);

        return $response;
    }
}
