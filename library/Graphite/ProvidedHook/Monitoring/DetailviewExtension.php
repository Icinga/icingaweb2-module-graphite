<?php

namespace Icinga\Module\Graphite\ProvidedHook\Monitoring;

use Icinga\Module\Graphite\EmbedGraphs;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;

class DetailviewExtension extends DetailviewExtensionHook
{
    use TimeRangePickerTrait;

    public function getHtmlForObject(MonitoredObject $object)
    {
        switch ($object->getType()) {
            case 'host':
                /** @var Host $object */
                return $this->getGeneric() . EmbedGraphs::host($object->getName());
            case 'service':
                /** @var Service $object */
                return $this->getGeneric() . EmbedGraphs::service($object->getHost()->getName(), $object->getName());
        }
    }

    /**
     * Get monitored object type independend HTML to use
     *
     * @return string
     */
    protected function getGeneric()
    {
        $this->handleTimeRangePickerRequest();
        return '<h2>' . mt('graphite', 'Graphs') . '</h2>' . $this->renderTimeRangePicker($this->getView());
    }
}
