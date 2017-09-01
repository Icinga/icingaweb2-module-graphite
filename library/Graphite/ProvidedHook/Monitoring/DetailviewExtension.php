<?php

namespace Icinga\Module\Graphite\ProvidedHook\Monitoring;

use Icinga\Module\Graphite\EmbedGraphs;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;

class DetailviewExtension extends DetailviewExtensionHook
{
    public function getHtmlForObject(MonitoredObject $object)
    {
        switch ($object->getType()) {
            case 'host':
                /** @var Host $object */
                return $this->getHeader() . EmbedGraphs::host($object->getName());
            case 'service':
                /** @var Service $object */
                return $this->getHeader() . EmbedGraphs::service($object->getHost()->getName(), $object->getName());
        }
    }

    /**
     * Get HTML header to use
     *
     * @return string
     */
    protected function getHeader()
    {
        return '<h2>' . mt('graphite', 'Graphs') . '</h2>';
    }
}
