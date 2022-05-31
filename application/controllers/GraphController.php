<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Application\Modules\Module;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Graphite\Graphing\GraphingTrait;
use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;
use Icinga\Module\Graphite\Util\IcingadbUtils;
use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;
use Icinga\Module\Graphite\Web\Widget\Graphs;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\UrlParams;
use Icinga\Module\Icingadb\Model\Service as IcingadbService;
use Icinga\Module\Icingadb\Model\Host as IcingadbHost;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;

class GraphController extends MonitoringAwareController
{
    use GraphingTrait;

    /**
     * The URL parameters for the graph
     *
     * @var string[]
     */
    protected $graphParamsNames = [
        'start', 'end',
        'width', 'height',
        'legend',
        'template', 'default_template',
        'bgcolor', 'fgcolor',
        'majorGridLineColor', 'minorGridLineColor'
    ];

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
        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            $this->icingadbHost();
            return;
        }

        $hostName = $this->filterParams->getRequired('host.name');
        $checkCommandColumn = '_host_' . Graphs::getObscuredCheckCommandCustomVar();
        $host = $this->applyMonitoringRestriction(
            $this->backend->select()->from('hoststatus', ['host_check_command', $checkCommandColumn])
        )
            ->where('host_name', $hostName)
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($host === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such host'));
        }

        $this->supplyImage(new Host($this->backend, $hostName), $host->host_check_command, $host->$checkCommandColumn);
    }

    public function serviceAction()
    {
        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            $this->icingadbService();
            return;
        }

        $hostName = $this->filterParams->getRequired('host.name');
        $serviceName = $this->filterParams->getRequired('service.name');
        $checkCommandColumn = '_service_' . Graphs::getObscuredCheckCommandCustomVar();
        $service = $this->applyMonitoringRestriction(
            $this->backend->select()->from('servicestatus', ['service_check_command', $checkCommandColumn])
        )
            ->where('host_name', $hostName)
            ->where('service_description', $serviceName)
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($service === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such service'));
        }

        $this->supplyImage(
            new Service($this->backend, $hostName, $serviceName),
            $service->service_check_command,
            $service->$checkCommandColumn
        );
    }

    private function icingadbService()
    {
        $hostName = $this->filterParams->getRequired('host.name');
        $serviceName = $this->filterParams->getRequired('service.name');
        $icingadbUtils = IcingadbUtils::getInstance();
        $query = IcingadbService::on($icingadbUtils->getDb())
            ->with('state')
            ->with('host');

        $query->filter(Filter::all(
            Filter::equal('service.name', $serviceName),
            Filter::equal('service.host.name', $hostName)
        ));

        $icingadbUtils->applyRestrictions($query);

        /** @var IcingadbService $service */
        $service = $query->first();

        if ($service === null) {
            throw new HttpNotFoundException($this->translate('No such service'));
        }

        $checkCommandColumn = $service->vars[Graphs::getObscuredCheckCommandCustomVar()] ?? null;

        $this->supplyImage(
            $service,
            $service->checkcommand_name,
            $checkCommandColumn
        );
    }

    private function icingadbHost()
    {
        $hostName = $this->filterParams->getRequired('host.name');
        $icingadbUtils = IcingadbUtils::getInstance();
        $query = IcingadbHost::on($icingadbUtils->getDb())->with('state');
        $query->filter(Filter::equal('host.name', $hostName));

        $icingadbUtils->applyRestrictions($query);

        /** @var IcingadbHost $host */
        $host = $query->first();

        if ($host === null) {
            throw new HttpNotFoundException($this->translate('No such host'));
        }

        $checkCommandColumn = $host->vars[Graphs::getObscuredCheckCommandCustomVar()] ?? null;

        $this->supplyImage(
            $host,
            $host->checkcommand_name,
            $checkCommandColumn
        );
    }

    /**
     * Do all monitored object type independend actions
     *
     * @param MonitoredObject|Model     $object                 The object to render the graphs for
     * @param string                    $checkCommand           The check command of the object we supply an image for
     * @param string|null               $obscuredCheckCommand   The "real" check command (if any) of the  object we
     *                                                          display graphs for
     */
    protected function supplyImage($object, $checkCommand, $obscuredCheckCommand)
    {
        if (isset($this->graphParams['default_template'])) {
            $urlParam = 'default_template';
            $templates = $this->getAllTemplates()->getDefaultTemplates();
        } else {
            $urlParam = 'template';
            $templates = $this->getAllTemplates()->getTemplates(
                $obscuredCheckCommand === null ? $checkCommand : $obscuredCheckCommand
            );
        }

        if (! isset($templates[$this->graphParams[$urlParam]])) {
            throw new HttpNotFoundException($this->translate('No such template'));
        }

        $charts = $templates[$this->graphParams[$urlParam]]->getCharts(
            static::getMetricsDataSource(),
            $object,
            array_map('rawurldecode', $this->filterParams->toArray(false))
        );

        switch (count($charts)) {
            case 0:
                throw new HttpNotFoundException($this->translate('No such graph'));

            case 1:
                $charts[0]
                    ->setFrom($this->graphParams['start'])
                    ->setUntil($this->graphParams['end'])
                    ->setWidth($this->graphParams['width'])
                    ->setHeight($this->graphParams['height'])
                    ->setBackgroundColor($this->graphParams['bgcolor'])
                    ->setForegroundColor($this->graphParams['fgcolor'])
                    ->setMajorGridLineColor($this->graphParams['majorGridLineColor'])
                    ->setMinorGridLineColor($this->graphParams['minorGridLineColor'])
                    ->setShowLegend((bool) $this->graphParams['legend'])
                    ->serveImage($this->getResponse());

                // not falling through, serveImage exits
            default:
                throw new HttpBadRequestException('%s', $this->translate(
                    'Graphite Web yields more than one metric for the given filter.'
                    . ' Please specify a more precise filter.'
                ));
        }
    }
}
