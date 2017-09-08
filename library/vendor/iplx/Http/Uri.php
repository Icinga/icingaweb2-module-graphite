<?php

namespace iplx\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    protected $scheme;

    protected $host;

    protected $port;

    protected $user;

    protected $password;

    protected $path;

    protected $query;

    protected $fragment;

    public function __construct($uri = null)
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException();
        }

        foreach ($parts as $component => $value) {
            $this->applyComponent($component, $value);
        }
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getAuthority()
    {
        // Weak type check to also check null
        if ($this->host == '') {
            return '';
        }

        $authority = $this->host;

        $userInfo = $this->getUserInfo();
        $port = $this->getPort();

        if ($userInfo) {
            $authority = "$userInfo@$authority";
        }

        if ($port !== null) {
            $authority .= ":$port";
        }

        return $authority;
    }

    public function getUserInfo()
    {
        $userInfo = $this->user;

        if ($this->password !== null) {
            $userInfo .= ":{$this->password}";
        }

        return $userInfo;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public function withScheme($scheme)
    {
        $uri = clone $this;
        $uri->applyComponent('scheme', $scheme);

        return $uri;
    }

    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->applyComponent('user', $user);
        $uri->applyComponent('password', $password);

        return $uri;
    }

    public function withHost($host)
    {
        $uri = clone $this;
        $uri->applyComponent('host', $host);

        return $uri;
    }

    public function withPort($port)
    {
        $uri = clone $this;
        $uri->applyComponent('port', $port);

        return $uri;
    }

    public function withPath($path)
    {
        $uri = clone $this;
        $uri->applyComponent('path', $path);

        return $uri;
    }

    public function withQuery($query)
    {
        $uri = clone $this;
        $uri->applyComponent('query', $query);

        return $uri;
    }

    public function withFragment($fragment)
    {
        $uri = clone $this;
        $uri->applyComponent('fragment', $fragment);

        return $uri;
    }

    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        $uri = '';

        // Weak type checks to also check null

        if ($scheme != '') {
            $uri = "$scheme:";
        }

        if ($authority != '') {
            $uri .= "//$authority";
        }

        if ($path != '') {
            if ($path[0] === '/') {
                if ($authority == '') {
                    $path = ltrim($path, '/');
                }
            } else {
                $path = "/$path";
            }

            $uri .= $path;
        }

        if ($query != '') {
            $uri .= "?$query";
        }

        if ($fragment != '') {
            $uri .= "#$fragment";
        }

        return $uri;
    }

    protected function applyComponent($name, $value)
    {
        switch ($name) {
            case 'scheme':
                $this->scheme = $value;
                break;

            case 'host':
                $this->host = $value;
                break;

            case 'port':
                $this->port = $value;
                break;

            case 'user':
                $this->user = $value;
                break;

            case 'pass':
                $this->password = $value;
                break;

            case 'path':
                $this->path = $value;
                break;

            case 'query':
                $this->query = $value;
                break;

            case 'fragment':
                $this->fragment = $value;
                break;
        }
    }
}
