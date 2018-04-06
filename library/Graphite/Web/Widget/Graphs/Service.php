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
     * @param   string      $host                   The host to render the graphs of
     * @param   string      $service                The service to render the graphs of
     * @param   string      $checkCommand           The check command of the monitored object we display graphs for
     * @param   string|null $obscuredCheckCommand   The "real" check command (if any) of the monitored object
     *                                              we display graphs for
     */
    public function __construct($host, $service, $checkCommand, $obscuredCheckCommand)
    {
        parent::__construct($checkCommand, $obscuredCheckCommand);

        $this->host = $host;
        $this->service = $service;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/service');
    }

    protected function getDummyImageBaseUrl()
    {
        return Url::fromPath('graphite/graph-dummy/service');
    }

    protected function getGraphsListBaseUrl()
    {
        return Url::fromPath('graphite/list/services', ['host' => $this->host, 'service' => $this->service]);
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->host)->setParam('service.name',  $this->service);
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->host . ':' . $this->service;
    }

    protected function getMonitoredObjectFilter()
    {
        return ['host.name' => $this->host, 'service.name' =>  $this->service];
    }

    protected function designedForMyMonitoredObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('service_name_template', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }
}
