<?php

$section = $this->menuSection(mt('monitoring', 'History'));
$section->add($this->translate('Graphite'))->setUrl('graphite/show/overview');

