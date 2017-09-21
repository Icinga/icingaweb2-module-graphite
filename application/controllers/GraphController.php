<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Graphite\GraphiteQuery;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\Web\Widget\GraphsTrait;
use Icinga\Web\Controller;
use Icinga\Web\UrlParams;

class GraphController extends Controller
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

    public function hostAction()
    {
        $this->service = false;

        $this->supplyImage();
    }

    public function serviceAction()
    {
        $this->supplyImage();
    }

    /**
     * Do all monitored object type independend actions
     */
    protected function supplyImage()
    {
        $this->filterParams = clone $this->getRequest()->getUrl()->getParams();

        foreach ($this->geometryParamsNames as $paramName) {
            $this->geometryParams[$paramName] = $this->filterParams->shift($paramName);
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
