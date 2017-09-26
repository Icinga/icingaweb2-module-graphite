<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Data\Selectable;
use Icinga\Module\Graphite\GraphiteWebClientInterface;

/**
 * Provides an interface to Graphite Web's metrics list
 */
class MetricsDataSource implements Selectable
{
    /**
     * HTTP interface to Graphite Web
     *
     * @var GraphiteWebClientInterface
     */
    private $client;

    /**
     * Constructor
     *
     * @param   GraphiteWebClientInterface  $client HTTP interface to Graphite Web
     */
    public function __construct(GraphiteWebClientInterface $client)
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
     * @return GraphiteWebClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
