<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait as TimeRangePicker;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait as TimeRangePickerFormTrait;
use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;

class ListController extends MonitoringAwareController
{
    use TimeRangePickerTrait;

    public function init()
    {
        parent::init();
        $this->getTabs()
            ->extend(new OutputFormat([OutputFormat::TYPE_CSV, OutputFormat::TYPE_JSON]))
            ->extend(new DashboardAction())
            ->extend(new MenuAction());
    }

    public function hostsAction()
    {
        $this->addTitleTab(
            'hosts',
            mt('monitoring', 'Hosts'),
            mt('monitoring', 'List hosts')
        );

        $this->view->hosts = $hosts = $this->applyMonitoringRestriction(
            $this->backend->select()->from('hoststatus', [
                'host_name',
                'host_display_name',
                'host_check_command',
                '_host_check_command' => '_host_' . Graphs::getObscuredCheckCommandCustomVar()
            ])
        );

        $this->view->baseUrl = $baseUrl = Url::fromPath('monitoring/host/show');
        TimeRangePickerFormTrait::copyAllRangeParameters(
            $baseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->filterQuery($hosts);
        $this->setupPaginationControl($hosts);
        $this->setupLimitControl();
        $this->setupSortControl(['host_display_name' => mt('monitoring', 'Hostname')], $hosts);

        $this->handleTimeRangePickerRequest();
        $this->view->timeRangePicker = $this->renderTimeRangePicker($this->view);
    }

    public function servicesAction()
    {
        $this->addTitleTab(
            'services',
            mt('monitoring', 'Services'),
            mt('monitoring', 'List services')
        );

        $this->view->services = $services = $this->applyMonitoringRestriction(
            $this->backend->select()->from('servicestatus', [
                'host_name',
                'host_display_name',
                'service_description',
                'service_display_name',
                'service_check_command',
                '_service_check_command' => '_service_' . Graphs::getObscuredCheckCommandCustomVar()
            ])
        );

        $this->view->hostBaseUrl = $hostBaseUrl = Url::fromPath('monitoring/host/show');
        TimeRangePickerFormTrait::copyAllRangeParameters(
            $hostBaseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->view->serviceBaseUrl = $serviceBaseUrl = Url::fromPath('monitoring/service/show');
        TimeRangePickerFormTrait::copyAllRangeParameters(
            $serviceBaseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->filterQuery($services);
        $this->setupPaginationControl($services);
        $this->setupLimitControl();
        $this->setupSortControl([
            'service_display_name'  => mt('monitoring', 'Service Name'),
            'host_display_name'     => mt('monitoring', 'Hostname')
        ], $services);

        $this->handleTimeRangePickerRequest();
        $this->view->timeRangePicker = $this->renderTimeRangePicker($this->view);
    }

    /**
     * Apply filters on a DataView
     *
     * @param DataView  $dataView       The DataView to apply filters on
     */
    protected function filterQuery(DataView $dataView)
    {
        $this->setupFilterControl(
            $dataView,
            null,
            null,
            array_merge(
                ['format', 'stateType', 'addColumns', 'problems', 'graphs_limit'],
                TimeRangePicker::getAllRangeParameters()
            )
        );
        $this->handleFormatRequest($dataView);
    }

    /**
     * Add title tab
     *
     * @param   string  $action
     * @param   string  $title
     * @param   string  $tip
     */
    protected function addTitleTab($action, $title, $tip)
    {
        $this->getTabs()->add($action, [
            'title'     => $tip,
            'label'     => $title,
            'url'       => Url::fromRequest(),
            'active'    => true
        ]);
    }
}
