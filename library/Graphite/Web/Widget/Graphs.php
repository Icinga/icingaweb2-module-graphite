<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait;
use Icinga\Module\Graphite\Graphing\Chart;
use Icinga\Module\Graphite\Graphing\GraphingTrait;
use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Util\InternalProcessTracker as IPT;
use Icinga\Module\Graphite\Web\Widget\Graphs\Host as HostGraphs;
use Icinga\Module\Graphite\Web\Widget\Graphs\Service as ServiceGraphs;
use Icinga\Module\Graphite\Web\Widget\InlineGraphImage;
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
     * The Icinga custom variable with the "real" check command (if any) of monitored objects we display graphs for
     *
     * @var string
     */
    protected static $obscuredCheckCommandCustomVar;

    /**
     * The type of the monitored object to render the graphs of
     *
     * @var string
     */
    protected $monitoredObjectType;

    /**
     * The monitored object to render the graphs of
     *
     * @var MonitoredObject
     */
    protected $monitoredObject;

    /**
     * Graph image width
     *
     * @var string
     */
    protected $width = '350';

    /**
     * Graph image height
     *
     * @var string
     */
    protected $height = '200';

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
     * The check command of the monitored object we display graphs for
     *
     * @var string
     */
    protected $checkCommand;

    /**
     * The "real" check command (if any) of the monitored object we display graphs for
     *
     * E.g. the command executed remotely via check_by_ssh
     *
     * @var string|null
     */
    protected $obscuredCheckCommand;

    /**
     * Additional CSS classes for the <div/>s around the images
     *
     * @var string[]
     */
    protected $classes = [];

    /**
     * The amount of graphs to show
     *
     * @var int
     */
    protected $maxVisibleGraphs;

    /**
     * Whether to serve a transparent dummy image first and let the JS code load the actual graph
     *
     * @var bool
     */
    protected $preloadDummy = false;

    /**
     * Whether to render the graphs inline
     *
     * @var bool
     */
    protected $renderInline;

    /**
     * Whether to explicitly display that no graphs were found
     *
     * @var bool|null
     */
    protected $showNoGraphsFound;

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
                return new HostGraphs($object);

            case 'service':
                /** @var Service $object */
                return new ServiceGraphs($object);
        }
    }

    /**
     * Get the Icinga custom variable with the "real" check command (if any) of monitored objects we display graphs for
     *
     * @return string
     */
    public static function getObscuredCheckCommandCustomVar()
    {
        if (static::$obscuredCheckCommandCustomVar === null) {
            static::$obscuredCheckCommandCustomVar = Config::module('graphite')
                ->get('icinga', 'customvar_obscured_check_command', 'check_command');
        }

        return static::$obscuredCheckCommandCustomVar;
    }

    /**
     * Constructor
     *
     * @param   MonitoredObject $monitoredObject    The monitored object to render the graphs of
     */
    public function __construct(MonitoredObject $monitoredObject)
    {
        $this->monitoredObject = $monitoredObject;
        $this->checkCommand = $monitoredObject->{"{$this->monitoredObjectType}_check_command"};
        $this->renderInline = Url::fromRequest()->getParam('format') === 'pdf';
        $this->obscuredCheckCommand = $monitoredObject->{
            "_{$this->monitoredObjectType}_" . Graphs::getObscuredCheckCommandCustomVar()
        };
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

    /**
     * Render the graphs list
     *
     * @return string
     */
    protected function getGraphsList()
    {
        $result = []; // kind of string builder
        $imageBaseUrl = $this->preloadDummy ? $this->getDummyImageBaseUrl() : $this->getImageBaseUrl();
        $allTemplates = $this->getAllTemplates();
        $actualCheckCommand = $this->obscuredCheckCommand === null ? $this->checkCommand : $this->obscuredCheckCommand;
        $concreteTemplates = $allTemplates->getTemplates($actualCheckCommand);

        $excludedMetrics = [];

        foreach ($concreteTemplates as $concreteTemplate) {
            foreach ($concreteTemplate->getCurves() as $curve) {
                $excludedMetrics[] = $curve[0];
            }
        }

        IPT::recordf("Icinga check command: %s", $this->checkCommand);
        IPT::recordf("Obscured check command: %s", $this->obscuredCheckCommand);

        foreach ([
            ['template', $concreteTemplates, []],
            ['default_template', $allTemplates->getDefaultTemplates(), $excludedMetrics],
        ] as $templateSet) {
            list($urlParam, $templates, $excludeMetrics) = $templateSet;

            if ($urlParam === 'template') {
                IPT::recordf('Applying templates for check command %s', $actualCheckCommand);
            } else {
                IPT::recordf('Applying default templates, excluding previously used metrics');
            }

            IPT::indent();

            foreach ($templates as $templateName => $template) {
                if ($this->designedForMyMonitoredObjectType($template)) {
                    IPT::recordf('Applying template %s', $templateName);
                    IPT::indent();

                    $charts = $template->getCharts(
                        static::getMetricsDataSource(), $this->monitoredObject, [], $excludeMetrics
                    );

                    if (! empty($charts)) {
                        $currentGraphs = [];

                        foreach ($charts as $chart) {
                            /** @var Chart $chart */

                            $metricVariables = $chart->getMetricVariables();
                            $bestIntersect = -1;
                            $bestPos = count($result);

                            foreach ($result as $graphPos => & $graph) {
                                $currentIntersect = count(array_intersect_assoc($graph[1], $metricVariables));

                                if ($currentIntersect >= $bestIntersect) {
                                    $bestIntersect = $currentIntersect;
                                    $bestPos = $graphPos + 1;
                                }
                            }
                            unset($graph);

                            if ($this->renderInline) {
                                $chart->setFrom($this->start)
                                    ->setUntil($this->end)
                                    ->setWidth($this->width)
                                    ->setHeight($this->height)
                                    ->setShowLegend(! $this->compact);

                                $img = new InlineGraphImage($chart);
                            } else {
                                $imageUrl = $this->filterImageUrl($imageBaseUrl->with($metricVariables))
                                    ->setParam($urlParam, $templateName)
                                    ->setParam('start', $this->start)
                                    ->setParam('end', $this->end)
                                    ->setParam('width', $this->width)
                                    ->setParam('height', $this->height)
                                    ->setParam('cachebuster', time() * 65536 + mt_rand(0, 65535));

                                if (! $this->compact) {
                                    $imageUrl->setParam('legend', 1);
                                }

                                $img = '<img id="graphiteImg-'
                                    . md5((string) $imageUrl->without('cachebuster'))
                                    . "\" src=\"$imageUrl\" class=\"detach graphiteImg\" alt=\"\""
                                    . " width=\"$this->width\" height=\"$this->height\""
                                    . " style=\"min-width: {$this->width}px; min-height: {$this->height}px;\">";
                            }

                            $currentGraphs[] = [$img, $metricVariables, $bestPos];
                        }

                        foreach (array_reverse($currentGraphs) as $graph) {
                            list($img, $metricVariables, $bestPos) = $graph;
                            array_splice($result, $bestPos, 0, [[$img, $metricVariables]]);
                        }
                    }

                    IPT::unindent();
                } else {
                    IPT::recordf('Not applying template %s', $templateName);
                }
            }

            IPT::unindent();
        }

        if (! empty($result)) {
            foreach ($result as & $graph) {
                $graph = $graph[0];
            }
            unset($graph);

            $currentUrl = Icinga::app()->getRequest()->getUrl();
            $limit = (int) $currentUrl->getParam('graphs_limit', 50);
            $total = count($result);

            if ($limit < 1) {
                $limit = -1;
            }

            if ($limit !== -1 && $total > $limit) {
                $result = array_slice($result, 0, $limit);

                if (! $this->compact) {
                    /** @var View $view */
                    $view = $this->view();

                    $url = $this->getGraphsListBaseUrl();
                    TimeRangePickerTrait::copyAllRangeParameters($url->getParams(), $currentUrl->getParams());

                    $result[] = "<p class='load-more'>{$view->qlink(
                        sprintf($view->translate('Load all %d graphs'), $total),
                        $url->setParam('graphs_limit', '-1'),
                        null,
                        ['class' => 'action-link']
                    )}</p>";
                }
            }

            if ($this->maxVisibleGraphs && count($result) > $this->maxVisibleGraphs) {
                /** @var View $view */
                $view = $this->view();

                array_splice($result, $this->maxVisibleGraphs, 0, [sprintf(
                    '<input type="checkbox" id="toggle-%1$s" class="expandable-toggle">'
                        . '<label for="toggle-%1$s" class="link-button">'
                        . '<span class="expandable-expand-label">%2$s</span>'
                        . '<span class="expandable-collapse-label">%3$s</span>'
                        . '</label>'
                        . '<div class="expandable-content">',
                    $view->protectId($this->getMonitoredObjectIdentifier()),
                    $view->translate('Show More'),
                    $view->translate('Show Less')
                )]);

                $result[] = '</div>';
            }

            $classes = $this->classes;
            $classes[] = 'images';

            array_unshift($result, '<div class="' . implode(' ', $classes) . '">');
            $result[] = '</div>';
        }

        if ($this->renderInline) {
            foreach ($result as $html) {
                if ($html instanceof InlineGraphImage) {
                    // Errors should occur now or not at all
                    $html->render();
                }
            }
        }

        return implode($result);
    }

    public function render()
    {
        IPT::clear();

        try {
            $result = $this->getGraphsList();
        } catch (ConfigurationError $e) {
            $view = $this->view();

            return "<p>{$view->escape($e->getMessage())}</p>"
                . '<p>' . vsprintf(
                    $view->escape($view->translate('Please %scorrect%s the configuration of the Graphite module.')),
                    Auth::getInstance()->hasPermission('config/modules')
                        ? explode(
                            '$LINK_TEXT$',
                            $view->qlink('$LINK_TEXT$', 'graphite/config/backend', null, ['class' => 'action-link'])
                        )
                        : ['', '']
                ) . '</p>';
        }

        $view = $this->view();

        if ($result === '' && $this->getShowNoGraphsFound()) {
            $result = "<p>{$view->escape($view->translate('No graphs found'))}</p>";
        }

        if (IPT::enabled()) {
            $result .= "<h3>{$view->escape($view->translate('Graphs assembling process record'))}</h3>"
                . "<pre>{$view->escape(IPT::dump())}</pre>";
        }

        return $result;
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
            return ["-$relative", null];
        }

        $absolute = TimeRangePickerTrait::getAbsoluteRangeParameters();
        $start = $params->get($absolute['start']);
        return [
            $start === null ? -TimeRangePickerTrait::getDefaultRelativeTimeRange() : $start,
            $params->get($absolute['end'])
        ];
    }

    /**
     * Return a identifier specifying the monitored object we display graphs for
     *
     * @return  string
     */
    abstract protected function getMonitoredObjectIdentifier();

    /**
     * Get the base URL to a graph specifying just the monitored object kind
     *
     * @return Url
     */
    abstract protected function getImageBaseUrl();

    /**
     * Get the base URL to a dummy image specifying just the monitored object kind
     *
     * @return Url
     */
    abstract protected function getDummyImageBaseUrl();

    /**
     * Get the base URL to the monitored object's graphs list
     *
     * @return Url
     */
    abstract protected function getGraphsListBaseUrl();

    /**
     * Extend the {@link getImageBaseUrl()}'s result's parameters with the concrete monitored object
     *
     * @param   Url $url    The URL to extend
     *
     * @return  Url         The given URL
     */
    abstract protected function filterImageUrl(Url $url);

    /**
     * Return whether the given template is designed for the type of the monitored object we display graphs for
     *
     * @param   Template    $template
     *
     * @return  bool
     */
    abstract protected function designedForMyMonitoredObjectType(Template $template);

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

    /**
     * Get the amount of graphs to show
     *
     * @return  int
     */
    public function getMaxVisbileGraphs()
    {
        return $this->maxVisibleGraphs;
    }

    /**
     * Set the amount of graphs to show
     *
     * @param   int     $count
     *
     * @return  $this
     */
    public function setMaxVisibleGraphs($count)
    {
        $this->maxVisibleGraphs = (int) $count;
        return $this;
    }

    /**
     * Get whether to serve a transparent dummy image first and let the JS code load the actual graph
     *
     * @return bool
     */
    public function getPreloadDummy()
    {
        return $this->preloadDummy;
    }

    /**
     * Set whether to serve a transparent dummy image first and let the JS code load the actual graph
     *
     * @param bool $preloadDummy
     *
     * @return $this
     */
    public function setPreloadDummy($preloadDummy = true)
    {
        $this->preloadDummy = $preloadDummy;

        return $this;
    }

    /**
     * Get whether to render the graphs inline
     *
     * @return bool
     */
    public function getRenderInline()
    {
        return $this->renderInline;
    }

    /**
     * Set whether to render the graphs inline
     *
     * @param bool $renderInline
     *
     * @return $this
     */
    public function setRenderInline($renderInline = true)
    {
        $this->renderInline = $renderInline;

        return $this;
    }

    /**
     * Get whether to explicitly display that no graphs were found
     *
     * @return bool
     */
    public function getShowNoGraphsFound()
    {
        if ($this->showNoGraphsFound === null) {
            $this->showNoGraphsFound = ! Config::module('graphite')->get('ui', 'disable_no_graphs_found');
        }

        return $this->showNoGraphsFound;
    }

    /**
     * Set whether to explicitly display that no graphs were found
     *
     * @param bool $showNoGraphsFound
     *
     * @return $this
     */
    public function setShowNoGraphsFound($showNoGraphsFound = true)
    {
        $this->showNoGraphsFound = $showNoGraphsFound;

        return $this;
    }
}
