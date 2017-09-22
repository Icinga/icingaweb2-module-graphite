<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Graphite\GraphiteQuery;
use Icinga\Module\Graphite\GraphiteUtil;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;
use Icinga\Module\Graphite\Web\Widget\GraphsTrait;
use Icinga\Web\UrlParams;

class GraphController extends MonitoringAwareController
{
    use GraphsTrait;

    /**
     * The URL parameters for graph sizing
     *
     * @var string[]
     */
    protected $geometryParamsNames = ['start', 'end', 'width', 'height', 'legend'];

    /**
     * Whether we supply a service's graph
     *
     * @var bool
     */
    protected $service = true;

    /**
     * The URL parameters for metrics filtering
     *
     * @var UrlParams
     */
    protected $filterParams;

    /**
     * The URL parameters for graph sizing
     *
     * @var string[string]
     */
    protected $geometryParams = [];

    public function init()
    {
        parent::init();

        $this->filterParams = clone $this->getRequest()->getUrl()->getParams();

        foreach ($this->geometryParamsNames as $paramName) {
            $this->geometryParams[$paramName] = $this->filterParams->shift($paramName);
        }
    }

    public function hostAction()
    {
        $host = $this->applyMonitoringRestriction(
            $this->backend->select()->from('hoststatus', ['host_name'])
        )
            ->where('host_name', $this->filterParams->getRequired('hostname'))
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($host === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such host'));
        }

        $this->service = false;

        $this->supplyImage();
    }

    public function serviceAction()
    {
        $service = $this->applyMonitoringRestriction(
            $this->backend->select()->from('servicestatus', ['host_name', 'service_description'])
        )
            ->where('host_name', $this->filterParams->getRequired('hostname'))
            ->where('service_description', $this->filterParams->getRequired('service'))
            ->limit(1) // just to be sure to save a few CPU cycles
            ->fetchRow();

        if ($service === false) {
            throw new HttpNotFoundException('%s', $this->translate('No such service'));
        }

        $this->supplyImage();
    }

    /**
     * Do all monitored object type independend actions
     */
    protected function supplyImage()
    {
        $this->filterParams->set('hostname', GraphiteUtil::escape($this->filterParams->get('hostname')));
        if ($this->service) {
            $this->filterParams->set('service', GraphiteUtil::escape($this->filterParams->get('service')));
        }

        $this->collectTemplates();
        $this->collectGraphiteQueries();

        $charts = [];
        foreach ($this->graphiteQueries as $templateName => $graphiteQuery) {
            /** @var GraphiteQuery $graphiteQuery */

            $charts = array_merge($charts, $graphiteQuery->getImages($this->templates[$templateName]));
            if (count($charts) > 1) {
                throw new HttpBadRequestException('%s', $this->translate(
                    'Graphite Web yields more than one metric for the given filter.'
                    . ' Please specify a more precise filter.'
                ));
            }
        }

        if (empty($charts)) {
            throw new HttpNotFoundException('%s', $this->translate('No such graph'));
        }

        $image = $charts[0]
            ->setStart($this->geometryParams['start'])
            ->setUntil($this->geometryParams['end'])
            ->setWidth($this->geometryParams['width'])
            ->setHeight($this->geometryParams['height'])
            ->showLegend((bool) $this->geometryParams['legend'])
            ->fetchImage();

        $this->_helper->layout()->disableLayout();

        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="graph.png"');
        echo $image;
        exit;
    }

    protected function includeTemplate(GraphTemplate $template)
    {
        return (strpos($template->getFilterString(), '$service') !== false) === $this->service;
    }

    protected function filterGraphiteQuery(GraphiteQuery $query)
    {
        foreach ($this->filterParams->toArray() as list($key, $value)) {
            $query->where($key, $value);
        }

        return $query;
    }
}
