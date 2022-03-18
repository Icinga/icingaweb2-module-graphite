<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Monitoring\Object\Service as MonitoredService;
use Icinga\Web\Url;

class Service extends Graphs
{
    protected $objectType = 'service';

    /**
     * The service to render the graphs for
     *
     * @var MonitoredService
     */
    protected $object;

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/service');
    }

    protected function getGraphsListBaseUrl()
    {
        return Url::fromPath(
            'graphite/list/services',
            ['host' => $this->object->getHost()->getName(), 'service' => $this->object->getName()]
        );
    }

    protected function filterImageUrl(Url $url)
    {
        return $url
            ->setParam('host.name', $this->object->getHost()->getName())
            ->setParam('service.name', $this->object->getName());
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->object->getHost()->getName() . ':' . $this->object->getName();
    }

    protected function designedForObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('service_name_template', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }
}
