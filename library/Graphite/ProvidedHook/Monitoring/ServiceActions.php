<?php

namespace Icinga\Module\Graphite\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\ServiceActionsHook;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Url;

class ServiceActions extends ServiceActionsHook
{
    public function getActionsForService(Service $service)
    {
        return array(
            'Graphite' => Url::fromPath(
                'graphite/show/service',
                array(
                    'host'    => $service->host_name,
                    'service' => $service->service_description,
                ))
        );
    }
}
