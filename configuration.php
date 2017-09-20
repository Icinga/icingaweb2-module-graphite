<?php

/** @var \Icinga\Application\Modules\Module $this */

/** @var \Icinga\Application\Modules\MenuItemContainer $section */
$section = $this->menuSection(N_('Graphite'), ['icon' => 'chart-area']);
$section->add(N_('Hosts'), ['url' => 'graphite/list/hosts']);
$section->add(N_('Services'), ['url' => 'graphite/list/services']);

$this->provideConfigTab('backend', array(
    'title' => $this->translate('Configure the Graphite Web backend'),
    'label' => $this->translate('Backend'),
    'url' => 'config'
));

