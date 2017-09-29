<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Data\Selectable;

/**
 * Provides an interface to Graphite Web's metrics list
 */
class MetricsDataSource implements Selectable
{
    /**
     * HTTP interface to Graphite Web
     *
     * @var GraphiteWebClient
     */
    private $client;

    /**
     * Constructor
     *
     * @param   GraphiteWebClient   $client HTTP interface to Graphite Web
     */
    public function __construct(GraphiteWebClient $client)
    {
        $this->client = $client;
    }

    /**
     * Initiate a new query
     *
     * @return MetricsQuery
     */
    public function select()
    {
        return new MetricsQuery($this);
    }

    /**
     * Get the client passed to the constructor
     *
     * @return GraphiteWebClient
     */
    public function getClient()
    {
        return $this->client;
    }
}
