<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait;
use Icinga\Module\Graphite\GraphiteQuery;
use Icinga\Module\Graphite\GraphiteUtil;
use Icinga\Module\Graphite\Web\Widget\Graphs\Host as HostGraphs;
use Icinga\Module\Graphite\Web\Widget\Graphs\Service as ServiceGraphs;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Web\View;
use Icinga\Web\Widget\AbstractWidget;

abstract class Graphs extends AbstractWidget
{
    use GraphsTrait;

    /**
     * Graph image width
     *
     * @var string
     */
    protected $width = '300';

    /**
     * Graph image height
     *
     * @var string
     */
    protected $height = '150';

    /**
     * Graph range start
     *
     * @var string
     */
    protected $start;

    /**
     * Graph range end
     *
     * @var string
     */
    protected $end;

    /**
     * Whether to render as compact as possible
     *
     * @var bool
     */
    protected $compact = false;

    /**
     * The image links to be shown
     *
     * [$type => [$title => $url]]
     *
     * @var string[string][string]
     */
    protected $images = [];

    /**
     * Factory, based on the given object
     *
     * @param   MonitoredObject $object
     *
     * @return  static
     */
    public static function forMonitoredObject(MonitoredObject $object)
    {
        switch ($object->getType()) {
            case 'host':
                /** @var Host $object */
                return (new HostGraphs($object->getName()));

            case 'service':
                /** @var Service $object */
                return (new ServiceGraphs($object->getHost()->getName(), $object->getName()));
        }
    }

    /**
     * Process the given request using this widget
     *
     * @param   Request $request    The request to be processed
     *
     * @return  $this
     */
    public function handleRequest(Request $request = null)
    {
        if ($request === null) {
            $request = Icinga::app()->getRequest();
        }

        $this->handleGraphParams($request);
        $this->collectTemplates();
        $this->collectImages();

        return $this;
    }

    public function render()
    {
        /** @var View $view */
        $view = $this->view();
        $rendered = '';

        foreach ($this->images as $type => $images) {
            $rendered .= '<div class="images">';

            if (! $this->compact) {
                $rendered .= "<h3>{$view->escape(ucfirst($type))}</h3>{$view->partial(
                    'show/legend.phtml',
                    ['template' => $this->templates[$type]]
                )}";
            }

            foreach ($images as $url) {
                $rendered .= "<img src=\"$url\" class=\"graphiteImg\" alt=\"\" width=\"$this->width\" height=\"$this->height\" />";
            }

            $rendered .= '</div>';
        }

        return $rendered ?: "<p>{$view->escape($view->translate('No graphs found'))}</p>";
    }

    /**
     * Handle the given request's parameters
     *
     * @param   Request $request
     */
    protected function handleGraphParams(Request $request)
    {
        $params = $request->getUrl()->getParams();
        list($this->start, $this->end) = $this->getRangeFromTimeRangePicker($request);
        $this->width  = $params->shift('width', $this->width);
        $this->height = $params->shift('height', $this->height);
    }

    /**
     * Get time range parameters for Graphite from the URL
     *
     * @param   Request $request    The request to be used
     *
     * @return  string[]
     */
    protected function getRangeFromTimeRangePicker(Request $request)
    {
        $params = $request->getUrl()->getParams();
        $relative = $params->get(TimeRangePickerTrait::getRelativeRangeParameter());
        if ($relative !== null) {
            return ["-{$relative}s", null];
        }

        $absolute = TimeRangePickerTrait::getAbsoluteRangeParameters();
        return [$params->get($absolute['start'], '-1hours'), $params->get($absolute['end'])];
    }

    /**
     * Initialize {@link images}
     */
    protected function collectImages()
    {
        $this->collectGraphiteQueries();
        $imageBaseUrl = $this->getImageBaseUrl();

        foreach ($this->graphiteQueries as $templateName => $graphiteQuery) {
            /** @var GraphiteQuery $graphiteQuery */

            $searchPattern = $graphiteQuery->getSearchPattern();

            foreach ($graphiteQuery->listMetrics() as $metric) {
                $this->images[$templateName][] = $this->filterImageUrl(
                    $imageBaseUrl->with(GraphiteUtil::extractVars($metric, $searchPattern))
                )
                    ->setParam('start', $this->start)
                    ->setParam('end', $this->end)
                    ->setParam('width', $this->width)
                    ->setParam('height', $this->height);
            }
        }
    }

    /**
     * Get the base URL to a graph specifying just the monitored object kind
     *
     * @return Url
     */
    abstract protected function getImageBaseUrl();

    /**
     * Extend the {@link getImageBaseUrl()}'s result's parameters with the concrete monitored object
     *
     * @param   Url $url    The URL to extend
     *
     * @return  Url         The given URL
     */
    abstract protected function filterImageUrl(Url $url);

    /**
     * Get {@link compact}
     *
     * @return bool
     */
    public function getCompact()
    {
        return $this->compact;
    }

    /**
     * Set {@link compact}
     *
     * @param bool $compact
     *
     * @return $this
     */
    public function setCompact($compact = true)
    {
        $this->compact = $compact;
        return $this;
    }

    /**
     * Get the graph image width
     *
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the graph image width
     *
     * @param string $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get the graph image height
     *
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set the graph image height
     *
     * @param string $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }
}
