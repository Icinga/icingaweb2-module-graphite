<?php

namespace Icinga\Module\Graphite;

use Icinga\Module\Monitoring\Web\Hook\HostActionsHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Url;

class HostActions extends HostActionsHook
{
    public function getActionsForHost(Host $host)
    {
        return array(
            'Graphite' => Url::fromPath('graphite/show/host', array('host' => $host->host_name))
        );
    }
}
