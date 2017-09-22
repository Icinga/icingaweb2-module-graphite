<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\GraphiteQuery;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Web\Url;

class Service extends Graphs
{
    /**
     * The host to render the graphs of
     *
     * @var string
     */
    protected $host;

    /**
     * The service to render the graphs of
     *
     * @var string
     */
    protected $service;

    /**
     * Constructor
     *
     * @param   string  $host       The host to render the graphs of
     * @param   string  $service    The service to render the graphs of
     */
    public function __construct($host, $service)
    {
        $this->host = $host;
        $this->service = $service;
    }

    protected function filterGraphiteQuery(GraphiteQuery $query)
    {
        return $query
            ->where('hostname', $this->host)
            ->where('service',  $this->service);
    }

    protected function includeTemplate(GraphTemplate $template)
    {
        return strpos($template->getFilterString(), '$service') !== false;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/service');
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('hostname', $this->host)->setParam('service',  $this->service);
    }
}
