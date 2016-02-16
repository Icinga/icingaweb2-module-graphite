<?php

namespace Icinga\Module\Graphite;

use Icinga\Module\Graphite\GraphiteWeb;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;

/**
 * Graphite query
 * ==============
 *
 * Usage
 * -----
 * Constructor expects a GraphiteWeb instance. To make things easier, this
 * ...
 * <code>
 * $query = $graphiteweb->select();
 * </code>
 *
 * Filtering
 * ---------
 * <code>
 * $query->from('base.$host.$service.$metric')
 *       ->where('host', 'www1')
 *       ->where('service', 'ping');
 * </code>
 *
 */
class GraphiteQuery
{
    protected $web;

    protected $search;

    protected $searchPattern;

    /**
     * Construct a new query
     *
     * @param GraphiteWeb $web Graphite webapp instance
     */
    public function __construct(GraphiteWeb $web)
    {
        $this->web = $web;
    }

    /**
     * Set the base pattern for your query
     *
     * @return self
     */
    public function from($base, $pattern = null)
    {
        if (is_array($base)) {
            $key = key($base);
            if ($pattern === null) {
                $this->search = current($base);
            } else {
                $this->search = GraphiteUtil::replace($pattern, $key, current($base));
            }
        } else {
            // TODO: well... patterns might also work for non-aliases $base's
            $this->search = $base;
        }

        $this->searchPattern = $this->search;
        return $this;
    }

    /**
     * Add a filter
     *
     * @param string $colum  Virtual column we are going to filter for
     * @param string $search Search string
     *
     * @return self
     */
    public function where($column, $search)
    {
        $this->search = GraphiteUtil::replace(
            $this->search,
            $column,
            GraphiteUtil::escape($search)
        );

        return $this;
    }

    /**
     * TODO: rename to getCharts
     */
    public function getImages(GraphTemplate $template)
    {
        $charts = array();

        foreach ($this->listMetrics() as $metric) {
            $vars = GraphiteUtil::extractVars($metric, $this->getSearchPattern());
            $charts[] = new GraphiteChart($this->web, $template, $metric, $vars);
        }

        return $charts;
    }

    public function getWrappedImageLinks(GraphTemplate $template, $params)
    {
        $links = array();
        if ($params instanceof UrlParams) {
            $urlParams = $params;
        } else {
            $urlParams = new UrlParams();
            foreach ($params as $k => $v) {
                if (is_array($v)) {
                    $urlParams->addValues($k, $v);
                } else {
                    $urlParams->add($k, $v);
                }
            }
        }

        foreach ($this->listMetrics() as $metric) {
            $params = clone($urlParams);
            $vars = GraphiteUtil::extractVars($metric, $this->getSearchPattern());
            $params->mergeValues($vars);
            $url = Url::fromPath('graphite/show/graph')->setParams($params);

            $links[] = $url;
        }

        return $links;
    }

    /**
     * List all metrics fitting this query
     *
     */
    public function listMetrics($filterString = null)
    {
        if ($filterString === null) {
            $filterString = $this->toFilterString();
        }

        $metrics = $this->web->listMetrics($filterString);
        asort($metrics);
        return $metrics;
    }

    /**
     * Retrieve a distinct list of values fitting a given placeholder in our
     * search pattern
     *
     * Example
     * -------
     * This example retrieves all distinct services available on any host
     * belonging to our customer "Icinga".
     *
     * <code>
     * $icingaHosts = $graphite
     *     ->select()
     *     ->from('base.$customer.$host.$service.$metric')
     *     ->where('customer', 'icinga')
     *     ->listMetrics('service');
     * </code>
     *
     * @param  string $placeholder The placeholder we are interested in
     *
     * @return array
     */
    public function listDistinct($placeholder)
    {
        $search = $this->getSearchPattern();
        $totalLength = strlen($search);
        $varLength = strlen($placeholder) + 1;
        $pos = 0;
        $found = false;

        while (false !== ($pos = strpos($search, '$' . $placeholder, $pos + 1))) {
            if ($pos + $varLength === $totalLength) {
                $found = $search;
                break;
            }
            if ($search[$pos + $varLength] === '.') {
                $found = substr($search, 0, $pos + $varLength);
                break;
            }
        }

        if ($found === false) {
            return array();
        }

        $pattern = GraphiteUtil::replaceRemainingVariables($found);
        $metrics = $this->listMetrics($pattern);
        $distinct = array();

        foreach ($metrics as & $metric) {
            $parts = explode('.', $metric);
            $value = end($parts);
            $distinct[$value] = $value;
        }

        ksort($distinct);
        return $distinct;
    }

    /**
     * Get our search pattern
     *
     * TODO: example
     *
     * @return string
     */
    public function getSearchPattern()
    {
        return $this->searchPattern;
    }

    /**
     * Create a filter string allowing us to filter metrics
     */
    protected function toFilterString()
    {
        return GraphiteUtil::replaceRemainingVariables($this->search);
    }
}
