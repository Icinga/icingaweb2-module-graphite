<?php

namespace Icinga\Module\Graphite\Controllers;

use DateTimeZone;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Graphite\Graphing\GraphingTrait;
use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\UrlParams;

class GraphController extends MonitoringAwareController
{
    use GraphingTrait;

    /**
     * The URL parameters for the graph
     *
     * @var string[]
     */
    protected $graphParamsNames = ['start', 'end', 'width', 'height', 'legend', 'template'];

    /**
     * The URL parameters for metrics filtering
     *
     * @var UrlParams
     */
    protected $filterParams;

    /**
     * The URL parameters for the graph
     *
     * @var string[]
     */
    protected $graphParams = [];

    public function init()
    {
        parent::init();

        $this->filterParams = clone $this->getRequest()->getUrl()->getParams();

        foreach ($this->graphParamsNames as $paramName) {
            $this->graphParams[$paramName] = $this->filterParams->shift($paramName);
        }
    }

    public function hostAction()
    {
        $host = $this->applyMonitoringRestriction(
            $this->backend->select()->from('hoststatus', ['host_check_command'])
        )
            ->where('host_name', $this->filterParams->getRequired('host.name'))
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($host === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such host'));
        }

        $this->supplyImage($host->host_check_command);
    }

    public function serviceAction()
    {
        $service = $this->applyMonitoringRestriction(
            $this->backend->select()->from('servicestatus', ['service_check_command'])
        )
            ->where('host_name', $this->filterParams->getRequired('host.name'))
            ->where('service_description', $this->filterParams->getRequired('service.name'))
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($service === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such service'));
        }

        $this->supplyImage($service->service_check_command);
    }

    /**
     * Do all monitored object type independend actions
     *
     * @param   string  $checkCommand   The check command of the monitored object we supply an image for
     */
    protected function supplyImage($checkCommand)
    {
        $templates = $this->getAllTemplates()->getTemplates();
        if (! isset($templates[$this->graphParams['template']])) {
            throw new HttpNotFoundException($this->translate('No such template'));
        }

        $charts = $templates[$this->graphParams['template']]->getCharts(
            static::getMetricsDataSource(),
            array_map('rawurldecode', $this->filterParams->toArray(false)),
            $checkCommand
        );

        switch (count($charts)) {
            case 0:
                throw new HttpNotFoundException($this->translate('No such graph'));

            case 1:
                $timezoneDetect = new TimezoneDetect();
                $charts[0]
                    ->setFrom($this->graphParams['start'])
                    ->setUntil($this->graphParams['end'])
                    ->setWidth($this->graphParams['width'])
                    ->setHeight($this->graphParams['height'])
                    ->setShowLegend((bool) $this->graphParams['legend'])
                    ->setTimeZone(new DateTimeZone(
                        $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
                    ))
                    ->serveImage($this->getResponse());

            default:
                throw new HttpBadRequestException('%s', $this->translate(
                    'Graphite Web yields more than one metric for the given filter.'
                    . ' Please specify a more precise filter.'
                ));
        }
    }
}
