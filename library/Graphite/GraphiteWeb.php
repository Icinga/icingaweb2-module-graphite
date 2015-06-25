<?php

namespace Icinga\Module\Graphite;

use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\GraphiteQuery;

/**
 * Graphite web app
 *
 * Simple class, start point for all your queries
 */
class GraphiteWeb
{
    /**
     * Graphite webapp base url
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Construct a new graphite webapp instance
     *
     * @param $baseUrl string Graphite webapp base url
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Initiate a new query object
     *
     * @return GraphiteQuery
     */
    public function select()
    {
        return new GraphiteQuery($this);
    }

    /**
     * Retrieve out base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Retrieve a list of metrics fitting the given filter
     *
     * @return array
     */
    public function listMetrics($filter)
    {
        $res = json_decode(
            file_get_contents(
                $this->baseUrl . '/metrics/expand?query=' . $filter
            )
        );
        natsort($res->results);
        return array_values($res->results);
    }
}
