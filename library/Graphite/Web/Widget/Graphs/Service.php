<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Monitoring\Object\Service as MonitoredService;
use Icinga\Web\Url;

class Service extends Graphs
{
    protected $monitoredObjectType = 'service';

    /**
     * The service to render the graphs of
     *
     * @var MonitoredService
     */
    protected $monitoredObject;

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
        return Url::fromPath(
            'graphite/list/services',
            ['host' => $this->monitoredObject->getHost()->getName(), 'service' => $this->monitoredObject->getName()]
        );
    }

    protected function filterImageUrl(Url $url)
    {
        return $url
            ->setParam('host.name', $this->monitoredObject->getHost()->getName())
            ->setParam('service.name',  $this->monitoredObject->getName());
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->monitoredObject->getHost()->getName() . ':' . $this->monitoredObject->getName();
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
