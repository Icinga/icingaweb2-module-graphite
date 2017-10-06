<?php

namespace Icinga\Module\Graphite\Web\Widget\Graphs;

use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Web\Url;

class Host extends Graphs
{
    /**
     * The host to render the graphs of
     *
     * @var string
     */
    protected $host;

    /**
     * Constructor
     *
     * @param   string  $host           The host to render the graphs of
     * @param   string  $checkCommand   The check command of the monitored object we display graphs for
     */
    public function __construct($host, $checkCommand)
    {
        parent::__construct($checkCommand);

        $this->host = $host;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/host');
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->host);
    }

    protected function designedForMyMonitoredObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('service.name', $curve[0]->getMacros())) {
                return false;
            }
        }

        return true;
    }

    protected function getMonitoredObjectFilter()
    {
        return ['host.name' => $this->host];
    }
}
