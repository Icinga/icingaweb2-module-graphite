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
     * HTTP interface to Graphite Web
     *
     * @var GraphiteWebClientInterface
     */
    private $client;

    /**
     * Construct a new graphite webapp instance
     *
     * @param   GraphiteWebClientInterface  $client HTTP interface to Graphite Web
     */
    public function __construct(GraphiteWebClientInterface $client)
    {
        $this->client = $client;
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
     * Get {@link client}
     *
     * @return GraphiteWebClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Retrieve a list of metrics fitting the given filter
     *
     * @return array
     */
    public function listMetrics($filter)
    {
        $res = json_decode($this->client->request('metrics/expand', ['query' => $filter]));
        natsort($res->results);
        return array_values($res->results);
    }
}
