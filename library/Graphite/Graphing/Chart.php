<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Module\Graphite\Web\Widget\GraphImage;
use Icinga\Web\Response;

class Chart
{
    /**
     * Used to render the chart
     *
     * @var GraphiteWebClient
     */
    protected $graphiteWebClient;

    /** @var ?string This chart's background color */
    protected $bgcolor;

    /** @var ?string This chart's foreground color */
    protected $fgcolor;

    /** @var ?string This chart's major grid line color */
    protected $majorGridLineColor;

    /** @var ?string This chart's minor grid line color */
    protected $minorGridLineColor;

    /**
     * This chart's base
     *
     * @var Template
     */
    protected $template;

    /**
     * Target metrics by curve name
     *
     * @var string[]
     */
    protected $metrics;

    /**
     * The chart's begin
     *
     * @var string
     */
    protected $from = '-14400';

    /**
     * The chart's end
     *
     * @var string
     */
    protected $until;

    /**
     * The chart's width
     *
     * @var int
     */
    protected $width = 350;

    /**
     * The chart's height
     *
     * @var int
     */
    protected $height = 200;

    /**
     * Whether to show the chart's legend
     *
     * @var bool
     */
    protected $showLegend = true;

    /**
     * Constructor
     *
     * @param   GraphiteWebClient   $graphiteWebClient  Used to render the chart
     * @param   Template            $template           This chart's base
     * @param   string[]            $metrics            Target metrics by curve name
     */
    public function __construct(GraphiteWebClient $graphiteWebClient, Template $template, array $metrics)
    {
        $this->graphiteWebClient = $graphiteWebClient;
        $this->template = $template;
        $this->metrics = $metrics;
    }

    /**
     * Let Graphite Web render this chart and serve the result immediately to the user agent (via the given response)
     *
     * Does not return.
     *
     * @param   Response    $response
     */
    public function serveImage(Response $response)
    {
        $image = new GraphImage($this);

        // Errors should occur now or not at all
        $image->render();

        $response
            ->setHeader('Content-Type', 'image/png', true)
            ->setHeader('Content-Disposition', 'inline; filename="graph.png"', true)
            ->setHeader('Cache-Control', '', true)
            ->setHeader('Expires', '', true)
            ->setHeader('Pragma', '', true)
            ->setBody($image)
            ->sendResponse();

        exit;
    }

    /**
     * Extract the values of the template's metrics filters' variables from the target metrics
     *
     * @return string[]
     */
    public function getMetricVariables()
    {
        /** @var MacroTemplate[][] $curves */
        $curves = $this->template->getFullCurves();
        $variables = [];

        foreach ($this->metrics as $curveName => $metric) {
            $vars = $curves[$curveName][0]->reverseResolve($metric);
            if ($vars !== false) {
                $variables = array_merge($variables, $vars);
            }
        }

        return $variables;
    }

    /**
     * Get Graphite Web client
     *
     * @return GraphiteWebClient
     */
    public function getGraphiteWebClient()
    {
        return $this->graphiteWebClient;
    }

    /**
     * Get this chart's background color
     *
     * @return string|null
     */
    public function getBackgroundColor(): ?string
    {
        return $this->bgcolor;
    }

    /**
     * Set this chart's background color
     *
     * @param string|null $color
     *
     * @return $this
     */
    public function setBackgroundColor(?string $color): self
    {
        $this->bgcolor = $color;

        return $this;
    }

    /**
     * Get this chart's foreground color
     *
     * @return string|null
     */
    public function getForegroundColor(): ?string
    {
        return $this->fgcolor;
    }

    /**
     * Set this chart's foreground color
     *
     * @param string|null $color
     *
     * @return $this
     */
    public function setForegroundColor(?string $color): self
    {
        $this->fgcolor = $color;

        return $this;
    }

    /**
     * Get this graph's major grid line color
     *
     * @return string|null
     */
    public function getMajorGridLineColor(): ?string
    {
        return $this->majorGridLineColor;
    }

    /**
     * Set this graph's major grid line color
     *
     * @param string|null $color
     *
     * @return $this
     */
    public function setMajorGridLineColor(?string $color): self
    {
        $this->majorGridLineColor = $color;

        return $this;
    }

    /**
     * Get this graph's minor grid line color
     *
     * @return string|null
     */
    public function getMinorGridLineColor(): ?string
    {
        return $this->minorGridLineColor;
    }

    /**
     * Set this graph's minor grid line color
     *
     * @param string|null $color
     *
     * @return $this
     */
    public function setMinorGridLineColor(?string $color): self
    {
        $this->minorGridLineColor = $color;

        return $this;
    }

    /**
     * Get template
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get metrics
     *
     * @return string[]
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * Get begin
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set begin
     *
     * @param string $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get end
     *
     * @return string
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * Set end
     *
     * @param string $until
     *
     * @return $this
     */
    public function setUntil($until)
    {
        $this->until = $until;

        return $this;
    }

    /**
     * Get width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set width
     *
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set height
     *
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get whether to show the chart's legend
     *
     * @return bool
     */
    public function getShowLegend()
    {
        return $this->showLegend;
    }

    /**
     * Set whether to show the chart's legend
     *
     * @param bool $showLegend
     *
     * @return $this
     */
    public function setShowLegend($showLegend)
    {
        $this->showLegend = $showLegend;

        return $this;
    }
}
