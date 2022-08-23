<?php

namespace Icinga\Module\Graphite\Web\Controller;

use ArrayIterator;
use Icinga\Application\Modules\Module;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\QueryException;
use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Monitoring\Data\CustomvarProtectionIterator;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Util\Json;
use Icinga\File\Csv;
use Icinga\Web\Controller;
use Icinga\Web\Url;

abstract class MonitoringAwareController extends Controller
{
    /** @var bool Whether to use icingadb as the backend */
    protected $useIcingadbAsBackend = false;

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
            $this->useIcingadbAsBackend = true;

            return;
        }

        $this->backend = MonitoringBackend::instance($this->_getParam('backend'));
        $this->view->url = Url::fromRequest();
    }


    protected function handleFormatRequest($query)
    {
        $desiredContentType = $this->getRequest()->getHeader('Accept');
        if ($desiredContentType === 'application/json') {
            $desiredFormat = 'json';
        } elseif ($desiredContentType === 'text/csv') {
            $desiredFormat = 'csv';
        } else {
            $desiredFormat = strtolower($this->params->get('format', 'html'));
        }

        if ($desiredFormat !== 'html' && ! $this->params->has('limit')) {
            $query->limit();  // Resets any default limit and offset
        }

        switch ($desiredFormat) {
            case 'sql':
                echo '<pre>'
                    . htmlspecialchars(wordwrap($query->dump()))
                    . '</pre>';
                exit;
            case 'json':
                $response = $this->getResponse();
                $response
                    ->setHeader('Content-Type', 'application/json')
                    ->setHeader('Cache-Control', 'no-store')
                    ->setHeader(
                        'Content-Disposition',
                        'inline; filename=' . $this->getRequest()->getActionName() . '.json'
                    )
                    ->appendBody(
                        Json::sanitize(
                            iterator_to_array(
                                new CustomvarProtectionIterator(
                                    new ArrayIterator($query->fetchAll())
                                )
                            )
                        )
                    )
                    ->sendResponse();
                exit;
            case 'csv':
                $response = $this->getResponse();
                $response
                    ->setHeader('Content-Type', 'text/csv')
                    ->setHeader('Cache-Control', 'no-store')
                    ->setHeader(
                        'Content-Disposition',
                        'attachment; filename=' . $this->getRequest()->getActionName() . '.csv'
                    )
                    ->appendBody((string) Csv::fromQuery(new CustomvarProtectionIterator($query)))
                    ->sendResponse();
                exit;
        }
    }

    /**
     * Apply a restriction of the authenticated on the given filterable
     *
     * @param   string      $name       Name of the restriction
     * @param   Filterable  $filterable Filterable to restrict
     *
     * @return  Filterable  The filterable having the restriction applied
     */
    protected function applyRestriction($name, Filterable $filterable)
    {
        $filterable->applyFilter($this->getRestriction($name));
        return $filterable;
    }

    /**
     * Get a restriction of the authenticated
     *
     * @param   string $name        Name of the restriction
     *
     * @return  Filter              Filter object
     * @throws  ConfigurationError  If the restriction contains invalid filter columns
     */
    protected function getRestriction($name)
    {
        $restriction = Filter::matchAny();
        $restriction->setAllowedFilterColumns(array(
            'host_name',
            'hostgroup_name',
            'instance_name',
            'service_description',
            'servicegroup_name',
            function ($c) {
                return preg_match('/^_(?:host|service)_/i', $c);
            }
        ));
        foreach ($this->getRestrictions($name) as $filter) {
            if ($filter === '*') {
                return Filter::matchAll();
            }
            try {
                $restriction->addFilter(Filter::fromQueryString($filter));
            } catch (QueryException $e) {
                throw new ConfigurationError(
                    $this->translate(
                        'Cannot apply restriction %s using the filter %s. You can only use the following columns: %s'
                    ),
                    $name,
                    $filter,
                    implode(', ', array(
                        'instance_name',
                        'host_name',
                        'hostgroup_name',
                        'service_description',
                        'servicegroup_name',
                        '_(host|service)_<customvar-name>'
                    )),
                    $e
                );
            }
        }

        if ($restriction->isEmpty()) {
            return Filter::matchAll();
        }

        return $restriction;
    }
}
