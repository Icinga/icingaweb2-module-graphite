<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Monitoring\Object\Host as MonitoredHost;
use Icinga\Web\Url;

class Host extends Graphs
{
    protected $objectType = 'host';

    /**
     * The host to render the graphs of
     *
     * @var MonitoredHost
     */
    protected $object;

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/host');
    }

    protected function getGraphsListBaseUrl()
    {
        return Url::fromPath('graphite/list/hosts', ['host' => $this->object->getName()]);
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->object->getName());
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->object->getName();
    }

    protected function designedForObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('host_name_template', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }
}
