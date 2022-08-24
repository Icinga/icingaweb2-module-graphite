<?php

/** @var \Icinga\Application\Modules\Module $this */

use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;

require_once $this->getLibDir() . '/vendor/Psr/Loader.php';
require_once $this->getLibDir() . '/vendor/iplx/Loader.php';

$this->provideHook('monitoring/DetailviewExtension');
$this->provideHook('icingadb/IcingadbSupport');
$this->provideHook('icingadb/HostDetailExtension');
$this->provideHook('icingadb/ServiceDetailExtension');

if (! $this->exists('icingadb') || ! IcingadbSupport::useIcingaDbAsBackend()) {
    $this->addRoute('graphite/monitoring-graph/host', new Zend_Controller_Router_Route(
        'graphite/graph/host',
        [
            'controller'    => 'monitoring-graph',
            'action'        => 'host',
            'module'        => 'graphite'
        ]
    ));
    $this->addRoute('graphite/monitoring-graph/service', new Zend_Controller_Router_Route(
        'graphite/graph/service',
        [
            'controller'    => 'monitoring-graph',
            'action'        => 'service',
            'module'        => 'graphite'
        ]
    ));
}
