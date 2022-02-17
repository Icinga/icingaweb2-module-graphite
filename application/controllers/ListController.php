<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Application\Modules\Module;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;
use Icinga\Module\Graphite\Util\TimeRangePickerTools;
use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
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
        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            if ($hostName = $this->params->shift('host')) {
                $this->params->set('host.name', $hostName);
            }

            $this->redirectNow(Url::fromPath('graphite/hosts')->setParams($this->params));
        }

        $this->addTitleTab(
            'hosts',
            mt('monitoring', 'Hosts'),
            mt('monitoring', 'List hosts')
        );

        $hostsQuery = $this->applyMonitoringRestriction(
            $this->backend->select()->from('hoststatus', ['host_name'])
        );

        $hostsQuery->applyFilter(Filter::expression('host_perfdata', '!=', ''));

        $this->view->baseUrl = $baseUrl = Url::fromPath('monitoring/host/show');
        TimeRangePickerTools::copyAllRangeParameters(
            $baseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->filterQuery($hostsQuery);
        $this->setupPaginationControl($hostsQuery);
        $this->setupLimitControl();
        $this->setupSortControl(['host_display_name' => mt('monitoring', 'Hostname')], $hostsQuery);

        $hosts = [];
        foreach ($hostsQuery->peekAhead($this->view->compact) as $host) {
            $host = new Host($this->backend, $host->host_name);
            $host->fetch();
            $hosts[] = $host;
        }

        $this->handleTimeRangePickerRequest();
        $this->view->timeRangePicker = $this->renderTimeRangePicker($this->view);
        $this->view->hosts = $hosts;
        $this->view->hasMoreHosts = ! $this->view->compact && $hostsQuery->hasMore();

        $this->setAutorefreshInterval(30);
    }

    public function servicesAction()
    {
        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            if ($hostName = $this->params->shift('host')) {
                $this->params->set('host.name', $hostName);
            }

            if ($serviceName = $this->params->shift('service')) {
                $this->params->set('service.name', $serviceName);
            }

            $this->redirectNow(Url::fromPath('graphite/services')->setParams($this->params));
        }

        $this->addTitleTab(
            'services',
            mt('monitoring', 'Services'),
            mt('monitoring', 'List services')
        );

        $servicesQuery = $this->applyMonitoringRestriction(
            $this->backend->select()->from('servicestatus', ['host_name', 'service_description'])
        );

        $servicesQuery->applyFilter(Filter::expression('service_perfdata', '!=', ''));

        $this->view->hostBaseUrl = $hostBaseUrl = Url::fromPath('monitoring/host/show');
        TimeRangePickerTools::copyAllRangeParameters(
            $hostBaseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->view->serviceBaseUrl = $serviceBaseUrl = Url::fromPath('monitoring/service/show');
        TimeRangePickerTools::copyAllRangeParameters(
            $serviceBaseUrl->getParams(),
            $this->getRequest()->getUrl()->getParams()
        );

        $this->filterQuery($servicesQuery);
        $this->setupPaginationControl($servicesQuery);
        $this->setupLimitControl();
        $this->setupSortControl([
            'service_display_name'  => mt('monitoring', 'Service Name'),
            'host_display_name'     => mt('monitoring', 'Hostname')
        ], $servicesQuery);

        $services = [];
        foreach ($servicesQuery->peekAhead($this->view->compact) as $service) {
            $service = new Service($this->backend, $service->host_name, $service->service_description);
            $service->fetch();
            $services[] = $service;
        }

        $this->handleTimeRangePickerRequest();
        $this->view->timeRangePicker = $this->renderTimeRangePicker($this->view);
        $this->view->services = $services;
        $this->view->hasMoreServices = ! $this->view->compact && $servicesQuery->hasMore();

        $this->setAutorefreshInterval(30);
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
                TimeRangePickerTools::getAllRangeParameters()
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
