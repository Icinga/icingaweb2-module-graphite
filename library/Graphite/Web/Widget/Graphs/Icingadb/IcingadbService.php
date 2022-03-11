<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Web\Url;
use \Icinga\Module\Icingadb\Model\Service;

class IcingadbService extends Graphs
{
    protected $objectType = 'service';

    /**
     * The icingadb service to render the graphs for
     *
     * @var Service
     */
    protected $object;

    protected function getGraphsListBaseUrl()
    {
        return Url::fromPath(
            'graphite/services',
            ['service.name' => $this->object->name, 'host.name' => $this->object->host->name]
        );
    }

    protected function filterImageUrl(Url $url)
    {
        return $url
            ->setParam('host.name', $this->object->host->name)
            ->setParam('service.name', $this->object->name);
    }

    public function createHostTitle()
    {
        return $this->object->host->name;
    }

    public function createServiceTitle()
    {
        return ' : ' . $this->object->name;
    }

    public function getObjectType()
    {
        return $this->objectType;
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->object->host->name . ':' . $this->object->name;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/service');
    }

    protected function designedforObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('service_name_template', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }
}
