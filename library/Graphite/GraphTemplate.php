<?php

namespace Icinga\Module\Graphite;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\GraphDatasource;
use Icinga\Web\Url;

/**
 * TODO: allow same metric multiple times
 *       wording: metric vs datasource
 *       allow a template to generate multiple (different) graphs (e.g. traffic & errors)
 *
 */
class GraphTemplate
{
    protected $datasources = array();

    protected $attributes = array();

    protected $filterString;

    /**
     * Use one of the static constructors instead
     */
    protected function __construct()
    {
    }

    /**
     * Load a template from a given string
     */
    public static function load($string)
    {
        $tmpl = new static;
        $tmpl->parse($string);
        return $tmpl;
    }

    public function getFilterString()
    {
        return $this->filterString;
    }

    protected function parse($string)
    {
        $lines = preg_split('/\n/', $string);
        foreach ($lines as $line) {
            $line = trim($line, ' ');
            if ($line === '') continue;
            if ($line[0] === '#') continue;
            if (preg_match('/^(\w+)\s*=\s*(.+)$/', $line, $m)) {
                if ($m[1] === 'filter') {
                    $this->filterString = $m[2];
                } else {
                    $this->attributes[$m[1]] = $m[2];
                }
                continue;
            }

            if (! preg_match('/^([^:\s]+)\s*:\s*(.+)$/', $line, $m)) {
                throw new ConfigurationError('Got invalid template line: %s', $line);
            }

            $ds = new GraphDatasource($m[1]);
            $params = preg_split('/\s*,\s*/', $m[2]);
            $props = array();

            foreach ($params as $p) {
                list($k, $v) = preg_split('/\s*=\s*/', $p, 2);
                $func = 'set' . ucfirst($k);
                $ds->$func($v);
            }

            $this->datasources[$m[1]] = $ds;
        }
    }

    /**
     * Fill the given vars into the given string
     */
    protected function fillVars($string, $vars)
    {
        $regexes = array();
        $values = array();

        foreach ($vars as $k => $v) {
            $regexes[] = '/' . preg_quote('$' . $k, '/') . '/';
            $values[] = $v;
        }

        return preg_replace(
            $regexes,
            $values,
            $string
        );
    }

    public function getTitle($vars)
    {
        return $this->fillVars($this->attributes['title'], $vars);
    }

    /**
     * Extend the given URL and add all configured data sources based on the
     * given metric string
     */
    public function extendUrl(Url $url, $metric, $vars)
    {
        $params = $url->getParams();
        foreach ($this->attributes as $k => $v) {
            $params->add($k, $this->fillVars($v, $vars));
        }
        foreach ($this->datasources as $ds) {
            $ds->addToUrl($url, $metric);
        }

        return $url;
    }
}
