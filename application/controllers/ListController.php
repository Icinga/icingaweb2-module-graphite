<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\DataView\DataView;

class ListController extends Controller
{
    public function hostsAction()
    {
        $this->view->hosts = $hosts = $this->backend->select()->from('hoststatus', ['host_name', 'host_display_name']);
        $this->applyRestriction('monitoring/filter/objects', $hosts);
        $this->filterQuery($hosts);
        $this->setupPaginationControl($hosts);
        $this->setupLimitControl();
        $this->setupSortControl(['host_display_name' => mt('monitoring', 'Hostname')], $hosts);
    }

    public function servicesAction()
    {
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
}
