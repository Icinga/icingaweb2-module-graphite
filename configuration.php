<?php

/** @var \Icinga\Application\Modules\Module $this */

/** @var \Icinga\Application\Modules\MenuItemContainer $section */

use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;

$section = $this->menuSection(N_('Graphite'), ['icon' => 'chart-area']);

if ($this::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
    $section->add(N_('Hosts'), ['url' => 'graphite/hosts']);
    $section->add(N_('Services'), ['url' => 'graphite/services']);
} else {
    $section->add(N_('Hosts'), ['url' => 'graphite/list/hosts']);
    $section->add(N_('Services'), ['url' => 'graphite/list/services']);
}

$this->provideConfigTab('backend', array(
    'title' => $this->translate('Configure the Graphite Web backend'),
    'label' => $this->translate('Backend'),
    'url' => 'config/backend'
));

$this->provideConfigTab('advanced', array(
    'title' => $this->translate('Advanced configuration'),
    'label' => $this->translate('Advanced'),
    'url' => 'config/advanced'
));

$this->providePermission('graphite/debug', $this->translate('Allow debugging directly via the web UI'));
