<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Web\Response;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;
use RuntimeException;

class Chart
{
    /**
     * Used to render the chart
     *
     * @var GraphiteWebClient
     */
    protected $graphiteWebClient;

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
        $now = time();

        $from = (int) $this->from;
        if ($from < 0) {
            $from += $now;
        }

        if ((string) $this->until === '') {
            $until = $now;
        } else {
            $until = (int) $this->until;
            if ($until < 0) {
                $until += $now;
            }
        }

        $params = (new UrlParams())->addValues([
            'from'                  => $from,
            'until'                 => $until,
            'width'                 => $this->width,
            'height'                => $this->height,
            'hideLegend'            => (string) ! $this->showLegend,
            'tz'                    => date_default_timezone_get(),
            '_salt'                 => time() . '.000',
            'vTitle'                => 'Percent',
            'lineMode'              => 'connected',
            'drawNullAsZero'        => 'false',
            'graphType'             => 'line',
            'majorGridLineColor'    => '#0000003F',
            'minorGridLineColor'    => '#00000000',
            '_ext'                  => 'whatever.svg'
        ]);

        $variables = $this->getMetricVariables();
        foreach ($this->template->getUrlParams() as $key => $value) {
            $params->set($key, $value->resolve($variables));
        }

        foreach ($this->metrics as $curveName => $metric) {
            $params->add('target', $this->template->getCurves()[$curveName][1]->resolve([
                'metric' => $this->graphiteWebClient->escapeMetricPath($metric)
            ]));
        }

        $url = Url::fromPath('/render')->setParams($params);
        $headers = [
            'Accept-language'   => 'en',
            'Content-type'      => 'application/x-www-form-urlencoded'
        ];

        for (;;) {
            try {
                $image = $this->graphiteWebClient->request($url, 'POST', $headers);
            } catch (RuntimeException $e) {
                if (preg_match('/\b500\b/', $e->getMessage())) {
                    // A 500 Internal Server Error, probably because of
                    // a division by zero because of a too low time range to render.

                    $until = (int) $url->getParam('until');
                    $diff = $until - (int) $url->getParam('from');

                    // Try to render a higher time range, but give up
                    // once our default (1h) has been reached (non successfully).
                    if ($diff < 3600) {
                        $url->setParam('from', $until - $diff * 2);
                        continue;
                    }
                }

                throw $e;
            }

            break;
        }

        $response
            ->setHeader('Content-Type', 'image/png', true)
            ->setHeader('Content-Disposition', 'inline; filename="graph.png"', true)
            ->setHeader('Cache-Control', null, true)
            ->setHeader('Expires', null, true)
            ->setHeader('Pragma', null, true)
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
        $curves = $this->template->getCurves();
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
