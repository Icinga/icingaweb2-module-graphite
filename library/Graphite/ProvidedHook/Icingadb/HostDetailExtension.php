<?php

namespace Icinga\Module\Graphite\ProvidedHook\Icingadb;

use Icinga\Application\Icinga;
use Icinga\Module\Graphite\Util\InternalProcessTracker as IPT;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Icingadb\Hook\HostDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;

class HostDetailExtension extends HostDetailExtensionHook
{
    use TimeRangePickerTrait;

    public function getHtmlForObject(Host $host): ValidHtml
    {
        if (Icinga::app()->getRequest()->getUrl()->getParam('graph_debug')) {
            IPT::enable();
        }

        $graphs = (string) Graphs::forIcingadbObject($host)
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
            $graphColorRegistry = Html::tag('div', ['class' => 'graphite-graph-color-registry']);
            $graphs = HtmlString::create($graphs);

            return HtmlString::create($header . $timepicker . $graphColorRegistry . $graphs);
        }

        return HtmlString::create('');
    }
}
