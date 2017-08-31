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
