<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Module\Graphite\Graphing\Chart;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;
use Icinga\Web\Widget\AbstractWidget;
use RuntimeException;

class GraphImage extends AbstractWidget
{
    /**
     * The chart to be rendered
     *
     * @var Chart
     */
    protected $chart;

    /**
     * The rendered PNG image
     *
     * @var string|null
     */
    protected $rendered;

    /**
     * Constructor
     *
     * @param   Chart   $chart  The chart to be rendered
     */
    public function __construct(Chart $chart)
    {
        $this->chart = $chart;
    }

    /**
     * Render the graph lazily
     *
     * @return string
     */
    public function render()
    {
        if ($this->rendered === null) {
            $now = time();

            $from = (int) $this->chart->getFrom();
            if ($from < 0) {
                $from += $now;
            }

            $until = (string) $this->chart->getUntil();

            if ($until === '') {
                $until = $now;
            } else {
                $until = (int) $until;
                if ($until < 0) {
                    $until += $now;
                }
            }

            $variables = $this->chart->getMetricVariables();
            $template = $this->chart->getTemplate();
            $graphiteWebClient = $this->chart->getGraphiteWebClient();
            $params = (new UrlParams())->addValues([
                'from'                  => $from,
                'until'                 => $until,
                'bgcolor'               => $this->chart->getBackgroundColor() ?? 'black',
                'fgcolor'               => $this->chart->getForegroundColor() ?? 'white',
                'majorGridLineColor'    => $this->chart->getMajorGridLineColor() ?? '0000003F',
                'minorGridLineColor'    => $this->chart->getMinorGridLineColor() ?? 'black',
                'width'                 => $this->chart->getWidth(),
                'height'                => $this->chart->getHeight(),
                'hideLegend'            => (string) ! $this->chart->getShowLegend(),
                'tz'                    => date_default_timezone_get(),
                '_salt'                 => "$now.000",
                'vTitle'                => 'Percent',
                'lineMode'              => 'connected',
                'drawNullAsZero'        => 'false',
                'graphType'             => 'line',
                '_ext'                  => 'whatever.svg'
            ]);

            foreach ($template->getUrlParams() as $key => $value) {
                $params->set($key, $value->resolve($variables));
            }

            $metrics = $this->chart->getMetrics();

            foreach ($metrics as &$metric) {
                $metric = $graphiteWebClient->escapeMetricPath($metric);
            }
            unset($metric);

            $allVars = [];

            foreach ($template->getCurves() as $curveName => $curve) {
                if (!isset($metrics[$curveName])) {
                    continue;
                }

                $vars = $curve[0]->reverseResolve($metrics[$curveName]);

                if ($vars !== false) {
                    $allVars = array_merge($allVars, $vars);
                }
            }

            foreach ($metrics as $curveName => $metric) {
                $allVars['metric'] = $metric;
                $params->add('target', $template->getCurves()[$curveName][1]->resolve($allVars));
            }

            $url = Url::fromPath('/render')->setParams($params);
            $headers = [
                'Accept-language'   => 'en',
                'Content-type'      => 'application/x-www-form-urlencoded'
            ];

            for (;;) {
                try {
                    $this->rendered = $graphiteWebClient->request($url, 'GET', $headers);
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
        }

        return $this->rendered;
    }
}
