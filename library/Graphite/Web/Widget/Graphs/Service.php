<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\Graphing\Template;
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

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/service');
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->host)->setParam('service.name',  $this->service);
    }

    protected function designedForMyMonitoredObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('service.name', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }

    protected function getMonitoredObjectFilter()
    {
        return ['host.name' => $this->host, 'service.name' =>  $this->service];
    }
}
