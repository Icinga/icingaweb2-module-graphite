<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Web\Url;

class ListController extends Controller
{
    public function hostsAction()
    {
        $this->addTitleTab(
            'hosts',
            mt('monitoring', 'Hosts'),
            mt('monitoring', 'List hosts')
        );

        $this->view->hosts = $hosts = $this->backend->select()->from('hoststatus', ['host_name', 'host_display_name']);
        $this->applyRestriction('monitoring/filter/objects', $hosts);
        $this->filterQuery($hosts);
        $this->setupPaginationControl($hosts);
        $this->setupLimitControl();
        $this->setupSortControl(['host_display_name' => mt('monitoring', 'Hostname')], $hosts);
    }

    public function servicesAction()
    {
        $this->addTitleTab(
            'services',
            mt('monitoring', 'Services'),
            mt('monitoring', 'List services')
        );

        $this->view->services = $services = $this->backend->select()->from('servicestatus', [
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name'
        ]);
        $this->applyRestriction('monitoring/filter/objects', $services);
        $this->filterQuery($services);
        $this->setupPaginationControl($services);
        $this->setupLimitControl();
        $this->setupSortControl([
            'service_display_name'  => mt('monitoring', 'Service Name'),
            'host_display_name'     => mt('monitoring', 'Hostname')
        ], $services);
    }

    /**
     * Apply filters on a DataView
     *
     * @param DataView  $dataView       The DataView to apply filters on
     */
    protected function filterQuery(DataView $dataView)
    {
        $this->setupFilterControl($dataView, null, null, ['format', 'stateType', 'addColumns', 'problems']);
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
