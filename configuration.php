<?php

/** @var \Icinga\Application\Modules\Module $this */

$this->menuSection(N_('Graphite'), ['icon' => 'chart-area'])->setUrl('graphite/show/overview');

$this->provideConfigTab('backend', array(
    'title' => $this->translate('Configure the Graphite Web backend'),
    'label' => $this->translate('Backend'),
    'url' => 'config'
));

