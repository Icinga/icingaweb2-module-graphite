<?php

/** @var \Icinga\Application\Modules\Module $this */

require_once $this->getLibDir() . '/vendor/Psr/Loader.php';
require_once $this->getLibDir() . '/vendor/iplx/Loader.php';

$this->provideHook('monitoring/DetailviewExtension');
$this->provideHook('icingadb/IcingadbSupport');
$this->provideHook('icingadb/HostDetailExtension');
$this->provideHook('icingadb/ServiceDetailExtension');
