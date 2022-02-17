<?php

namespace Icinga\Module\Graphite\Web\Controller;

use Icinga\Application\Modules\Module;
use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\DataView\DataView;

abstract class MonitoringAwareController extends Controller
{
    /**
     * Restrict the given monitored object query for the currently authenticated user
     *
     * @param   DataView    $dataView
     *
     * @return  DataView                The given data view
     */
    protected function applyMonitoringRestriction(DataView $dataView)
    {
        $this->applyRestriction('monitoring/filter/objects', $dataView);

        return $dataView;
    }

    protected function moduleInit()
    {
        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            return;
        }

        parent::moduleInit();
    }
}
