<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait;
use Icinga\Module\Graphite\Graphing\GraphingTrait;
use Icinga\Module\Graphite\Graphing\Template;
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
    use GraphingTrait;

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
     * Additional CSS classes for the <div/>s around the images
     *
     * @var string[]
     */
    protected $classes = [];

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

        $params = $request->getUrl()->getParams();
        list($this->start, $this->end) = $this->getRangeFromTimeRangePicker($request);
        $this->width  = $params->shift('width', $this->width);
        $this->height = $params->shift('height', $this->height);

        return $this;
    }

    public function render()
    {
        /** @var View $view */
        $view = $this->view();
        $result = []; // kind of string builder
        $filter = $this->getMonitoredObjectFilter();
        $imageBaseUrl = $this->getImageBaseUrl();
        $templates = static::getAllTemplates()->getTemplates();
        $templateNames = array_keys($templates);

        $classes = $this->classes;
        $classes[] = 'images';
        $div = '<div class="' . implode(' ', $classes) . '">';

        shuffle($templateNames);

        foreach ($templateNames as $templateName) {
            if ($this->designedForMyMonitoredObjectType($templates[$templateName])) {
                $charts = $templates[$templateName]->getCharts(static::getMetricsDataSource(), $filter);
                if (! empty($charts)) {
                    $result[] = $div;

                    if (! $this->compact) {
                        $result[] = '<h3>';
                        $result[] = $view->escape(ucfirst($templateName));
                        $result[] = '</h3>';
                        $result[] = $view->partial('show/legend.phtml', ['template' => $templateName]);
                    }

                    foreach ($charts as $chart) {
                        $result[] = '<img src="';
                        $result[] = $this->filterImageUrl($imageBaseUrl->with($chart->getMetricVariables()))
                            ->setParam('template', $templateName)
                            ->setParam('start', $this->start)
                            ->setParam('end', $this->end)
                            ->setParam('width', $this->width)
                            ->setParam('height', $this->height);
                        $result[] = '" class="graphiteImg" alt="" width="';
                        $result[] = $this->width;
                        $result[] = '" height="';
                        $result[] = $this->height;
                        $result[] = '">';
                    }

                    $result[] = '</div>';
                }
            }
        }

        return empty($result) ? "<p>{$view->escape($view->translate('No graphs found'))}</p>" : implode($result);
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
     * Return whether the given template is designed for the type of the monitored object we display graphs for
     *
     * @param   Template    $template
     *
     * @return  bool
     */
    abstract protected function designedForMyMonitoredObjectType(Template $template);

    /**
     * Return a filter specifying the monitored object we display graphs for
     *
     * @return string[]
     */
    abstract protected function getMonitoredObjectFilter();

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

    /**
     * Get additional CSS classes for the <div/>s around the images
     *
     * @return string[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Set additional CSS classes for the <div/>s around the images
     *
     * @param string[] $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = $classes;

        return $this;
    }
}
