<?php

namespace Icinga\Module\Graphite;

use Icinga\Module\Graphite\GraphiteWeb;
use Icinga\Web\Url;


class GraphiteQuery
{
    protected $web;

    protected $search;

    protected $searchPattern;

    public function __construct(GraphiteWeb $web)
    {
        $this->web = $web;
    }

    public function from($base, $pattern = null)
    {
        if (is_array($base)) {
            $key = key($base);
            if ($pattern === null) {
                $this->search = current($base);
            } else {
                $this->search = $this->replace($pattern, $key, current($base));
            }
        } else {
            // TODO: well... patterns might also work for non-aliases $base's
            $this->search = $base;
        }

        $this->searchPattern = $this->search;
        return $this;
    }

    public function getSearchPattern()
    {
        return $this->searchPattern;
    }

    protected function replace($string, $key, $replacement)
    {
        return preg_replace(
            '/\$' . preg_quote($key) . '(\.|$)/',
            $replacement . '\1',
            $string
        );
    }

    public function where($column, $search)
    {
        $this->search = $this->replace($this->search, $column, $search);
        return $this;
    }

    /**
     * Replace all variables ($some_thing) with an asterisk
     *
     * TODO: I'd opt for \w instead of [^\.]
     */
    protected function replaceRemainingVariables($string)
    {
        return preg_replace('/\$[^\.]+(\.|$)/', '*\1', $string);
    }

    /**
     * Create a filter string allowing us to filter metrics
     */
    protected function toFilterString()
    {
        return $this->replaceRemainingVariables($this->search);
    }

    protected function extractVars($string, $pattern)
    {
        $regexVar = '/\$(\w+)/';
        $vars = array();

        if (preg_match_all($regexVar, $pattern, $m)) {
            $varnames = $m[1];

            $parts = preg_split($regexVar, $pattern);
            foreach ($parts as $key => $val) {
                $parts[$key] = preg_quote($val, '/');
            }

            $regex = '/' . implode('([^\.]+?)', $parts) . '/';
            if (preg_match($regex, $string, $m)) {
                array_shift($m);
                $vars = array_combine($varnames, $m);
            }
        }

        return $vars;
    }

    public function getImages(GraphTemplate $template)
    {
        $charts = array();

        foreach ($this->listMetrics() as $metric) {
            $vars = $this->extractVars($metric, $this->getSearchPattern());
            $charts[] = new GraphiteChart($this->web, $template, $metric, $vars);
        }

        return $charts;
    }

    public function listMetrics()
    {
        return $this->web->listMetrics($this->toFilterString());
    }
}
