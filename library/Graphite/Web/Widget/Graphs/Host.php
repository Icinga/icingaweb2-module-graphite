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
     * @param   string      $host                   The host to render the graphs of
     * @param   string      $checkCommand           The check command of the monitored object we display graphs for
     * @param   string|null $obscuredCheckCommand   The "real" check command (if any) of the monitored object
     *                                              we display graphs for
     */
    public function __construct($host, $checkCommand, $obscuredCheckCommand)
    {
        parent::__construct($checkCommand, $obscuredCheckCommand);

        $this->host = $host;
    }

    protected function getImageBaseUrl()
    {
        return Url::fromPath('graphite/graph/host');
    }

    protected function getDummyImageBaseUrl()
    {
        return Url::fromPath('graphite/graph-dummy/host');
    }

    protected function filterImageUrl(Url $url)
    {
        return $url->setParam('host.name', $this->host);
    }

    protected function getMonitoredObjectIdentifier()
    {
        return $this->host;
    }

    protected function getMonitoredObjectFilter()
    {
        return ['host.name' => $this->host];
    }

    protected function designedForMyMonitoredObjectType(Template $template)
    {
        foreach ($template->getCurves() as $curve) {
            if (in_array('host_name_template', $curve[0]->getMacros())) {
                return true;
            }
        }

        return false;
    }
}
