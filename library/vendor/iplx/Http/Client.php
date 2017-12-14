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
     * Client version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Maximum number of internal cURL handles
     *
     * @var int
     */
    const MAX_HANDLES = 4;

    /**
     * Internal cURL handles
     *
     * @var array
     */
    protected $handles;

    /**
     * Return user agent
     *
     * @return  string
     */
    protected function getAgent()
    {
        $defaultAgent = 'ipl/' . self::VERSION;
        $defaultAgent .= ' curl/' . curl_version()['version'];
        $defaultAgent .= ' PHP/' . PHP_VERSION;

        return $defaultAgent;
    }

    /**
     * Create and return a cURL handle based on the given request
     *
     * @param   RequestInterface    $request
     * @param   array               $options
     *
     * @return  Handle
     *
     * @throws  RuntimeException
     */
    protected function createHandle(RequestInterface $request, array $options)
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[] = $name . ': ' . implode(', ', $values);
        }

        $curlOptions = [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_FAILONERROR     => true,
            CURLOPT_USERAGENT       => $this->getAgent()
        ];

        if (isset($options['curl'])) {
            $curlOptions += $options['curl'];
        }

        $curlOptions += [
            CURLOPT_CUSTOMREQUEST   => $request->getMethod(),
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => false,
            CURLOPT_URL             => (string) $request->getUri()->withFragment('')
        ];

        if (! $request->hasHeader('Accept')) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Accept:';
        }

        if (! $request->hasHeader('Content-Type')) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type:';
        }

        if (! $request->hasHeader('Expect')) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
        }

        if ($request->getBody()->getSize() !== 0) {
            $curlOptions[CURLOPT_UPLOAD] = true;

            $body = $request->getBody();
            if ($body->isSeekable()) {
                $body->seek(0);
            }

            $curlOptions[CURLOPT_READFUNCTION] = function ($ch, $infile, $length) use ($body) {
                return $body->read($length);
            };
        }

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

            $curlOptions[CURLOPT_HTTP_VERSION] = $protocolVersion;
        }

        $handle = new Handle();

        $curlOptions[CURLOPT_HEADERFUNCTION] = function($ch, $header) use ($handle) {
            $size = strlen($header);

            if (! trim($header) || strpos($header, 'HTTP/') === 0) {
                return $size;
            }

            list($key, $value) = explode(': ', $header, 2);
            $handle->responseHeaders[$key] = rtrim($value, "\r\n");

            return $size;
        };

        $handle->responseBody = Stream::open();

        $curlOptions[CURLOPT_WRITEFUNCTION] = function ($ch, $string) use ($handle) {
            return $handle->responseBody->write($string);
        };

        $ch = $this->handles ? array_pop($this->handles) : curl_init();

        curl_setopt_array($ch, $curlOptions);

        $handle->handle = $ch;

        return $handle;
    }

    /**
     * Execute a cURL handle and return the response
     *
     * @param   Handle  $handle
     *
     * @return  ResponseInterface
     *
     * @throws  RuntimeException
     */
    protected function executeHandle(Handle $handle)
    {
        $ch = $handle->handle;

        $success = curl_exec($ch);

        if ($success === false) {
            throw new RuntimeException(curl_error($ch));
        }

        $response = new Response(
            curl_getinfo($ch, CURLINFO_HTTP_CODE), $handle->responseHeaders, $handle->responseBody
        );

        if (count($this->handles) >= self::MAX_HANDLES) {
            curl_close($ch);
        } else {
            curl_reset($ch);

            $this->handles[] = $ch;
        }

        return $response;
    }

    public function send(RequestInterface $request, array $options = [])
    {
        $handle = $this->createHandle($request, $options);

        $response = $this->executeHandle($handle);

        return $response;
    }
}
