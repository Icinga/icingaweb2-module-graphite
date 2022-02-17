<?php

namespace Icinga\Module\Graphite\ProvidedHook\Icingadb;

use Icinga\Application\Icinga;

use Icinga\Module\Graphite\Util\InternalProcessTracker as IPT;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Icingadb\Hook\ServiceDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;

class ServiceDetailExtension extends ServiceDetailExtensionHook
{
    use TimeRangePickerTrait;

    public function getHtmlForObject(Service $service): ValidHtml
    {
        if (Icinga::app()->getRequest()->getUrl()->getParam('graph_debug')) {
            IPT::enable();
        }

        $graphs = (string) Graphs::forIcingadbObject($service)
            ->setWidth(440)
            ->setHeight(220)
            ->setClasses(['object-detail-view'])
            ->setPreloadDummy()
            ->setShowNoGraphsFound(false)
            ->handleRequest();

        if (! empty($graphs)) {
            $this->handleTimeRangePickerRequest();

            $header = Html::tag('h2', [], 'Graphs');
            $timepicker = HtmlString::create($this->renderTimeRangePicker(Icinga::app()->getViewRenderer()->view));
            $graphs = HtmlString::create($graphs);

            return HtmlString::create($header . $timepicker . $graphs);
        }

        return HtmlString::create('');
    }
}
