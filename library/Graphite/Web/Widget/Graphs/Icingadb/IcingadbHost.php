<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Web\Url;
use Icinga\Module\Icingadb\Model\Host;

class IcingadbHost extends Graphs
{
    protected $objectType = 'host';

    /**
     * The Icingadb host to render the graphs for
     *
     * @var Host
     */
    protected $object;

    protected function getGraphsListBaseUrl()
    {
        return Url::fromPath('graphite/hosts', ['host.name' => $this->object->name]);
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->object->name);
    }

    public function createHostTitle()
    {
        return $this->object->name;
    }

    public function getObjectType()
    {
        return $this->objectType;
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->object->name;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/host');
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
