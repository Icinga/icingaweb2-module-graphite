<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Module\Graphite\Util\InternalProcessTracker as IPT;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use InvalidArgumentException;
use ipl\Orm\Model;

class Template
{
    /**
     * The configured icinga.graphite_writer_host_name_template
     *
     * @var MacroTemplate
     */
    protected static $hostNameTemplate;

    /**
     * The configured icinga.graphite_writer_service_name_template
     *
     * @var MacroTemplate
     */
    protected static $serviceNameTemplate;

    /**
     * All curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * [$curve => [$metricFilter, $function], ...]
     *
     * @var MacroTemplate[][]
     */
    protected $curves = [];

    /**
     * All curves to show in a chart by name with full Graphite Web metric filters and Graphite functions
     *
     * [$curve => [$metricFilter, $function], ...]
     *
     * @var MacroTemplate[][]
     */
    protected $fullCurves;

    /**
     * Additional URL parameters for rendering via Graphite Web
     *
     * [$key => $value, ...]
     *
     * @var MacroTemplate[]
     */
    protected $urlParams = [];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get all charts based on this template and applicable to the metrics
     * from the given data source restricted by the given filter
     *
     * @param   MetricsDataSource       $dataSource
     * @param   MonitoredObject|Model   $object    The object to render the graphs for
     * @param   string[]                $filter
     * @param   MacroTemplate[]         $excludeMetrics
     *
     * @return  Chart[]
     */
    public function getCharts(
        MetricsDataSource $dataSource,
        $object,
        array $filter,
        array &$excludeMetrics = []
    ) {
        $metrics = [];
        $metricsUsed = 0;
        $metricsExcluded = 0;

        foreach ($this->getFullCurves() as $curveName => $curve) {
            $fullMetricTemplate = $curve[0];

            $query = $dataSource->select()->setObject($object)->from($fullMetricTemplate);

            foreach ($filter as $key => $value) {
                $query->where($key, $value);
            }

            foreach ($query->fetchColumn() as $metric) {
                foreach ($excludeMetrics as $excludeMetric) {
                    if ($excludeMetric->reverseResolve($metric) !== false) {
                        ++$metricsExcluded;
                        continue 2;
                    }
                }

                $vars = $curve[0]->reverseResolve($metric);
                if ($vars !== false) {
                    $metrics[$curveName][$metric] = $vars;
                    ++$metricsUsed;
                }
            }
        }

        switch (count($metrics)) {
            case 0:
                $metricsCombinations = [];
                break;

            case 1:
                $metricsCombinations = [];

                foreach ($metrics as $curveName => & $curveMetrics) {
                    foreach ($curveMetrics as $metric => & $_) {
                        $metricsCombinations[] = [$curveName => $metric];
                    }
                    unset($_);
                }
                unset($curveMetrics);

                break;

            default:
                $possibleCombinations = [];
                $handledCurves = [];
                foreach ($metrics as $curveName1 => & $metrics1) {
                    $handledCurves[$curveName1] = true;

                    foreach ($metrics as $curveName2 => & $metrics2) {
                        if (! isset($handledCurves[$curveName2])) {
                            foreach ($metrics1 as $metric1 => & $vars1) {
                                foreach ($metrics2 as $metric2 => & $vars2) {
                                    if (count(array_intersect_assoc($vars1, $vars2))
                                        === count(array_intersect_key($vars1, $vars2))
                                    ) {
                                        $possibleCombinations[$curveName1][$curveName2][$metric1][$metric2] = true;
                                    }
                                }
                                unset($vars2);
                            }
                            unset($vars1);
                        }
                    }
                    unset($metrics2);
                }
                unset($metrics1);

                $metricsCombinations = [];
                $this->combineMetrics($metrics, $possibleCombinations, $metricsCombinations);
        }

        $charts = [];
        foreach ($metricsCombinations as $metricsCombination) {
            $charts[] = new Chart($dataSource->getClient(), $this, $metricsCombination);
        }

        IPT::recordf('Excluded %s metric(s)', $metricsExcluded);
        IPT::recordf('Combined %s metric(s) to %s chart(s)', $metricsUsed, count($charts));

        return $charts;
    }

    /**
     * Fill the given metrics combinations from the given metrics as restricted by the given possible combinations
     *
     * @param   string[][][]    $metrics
     * @param   bool[][][][]    $possibleCombinations
     * @param   string[][]      $metricsCombinations
     * @param   string[]        $currentCombination
     */
    protected function combineMetrics(
        array &$metrics,
        array &$possibleCombinations,
        array &$metricsCombinations,
        array $currentCombination = []
    ) {
        if (empty($currentCombination)) {
            foreach ($metrics as $curveName => & $curveMetrics) {
                foreach ($curveMetrics as $metric => & $_) {
                    $this->combineMetrics(
                        $metrics,
                        $possibleCombinations,
                        $metricsCombinations,
                        [$curveName => $metric]
                    );
                }
                unset($_);

                break;
            }
            unset($curveMetrics);
        } elseif (count($currentCombination) === count($metrics)) {
            $metricsCombinations[] = $currentCombination;
        } else {
            foreach ($metrics as $nextCurveName => & $_) {
                if (! isset($currentCombination[$nextCurveName])) {
                    break;
                }
            }
            unset($_);

            $allowedNextCurveMetricsPerCurrentCurveName = [];
            foreach ($currentCombination as $currentCurveName => $currentCurveMetric) {
                $allowedNextCurveMetricsPerCurrentCurveName[$currentCurveName]
                    = $possibleCombinations[$currentCurveName][$nextCurveName][$currentCurveMetric];
            }

            $allowedNextCurveMetrics = $allowedNextCurveMetricsPerCurrentCurveName[$currentCurveName];
            unset($allowedNextCurveMetricsPerCurrentCurveName[$currentCurveName]);

            foreach ($allowedNextCurveMetricsPerCurrentCurveName as & $allowedMetrics) {
                $allowedNextCurveMetrics = array_intersect_key($allowedNextCurveMetrics, $allowedMetrics);
            }
            unset($allowedMetrics);

            foreach ($allowedNextCurveMetrics as $allowedNextCurveMetric => $_) {
                $nextCombination = $currentCombination;
                $nextCombination[$nextCurveName] = $allowedNextCurveMetric;

                $this->combineMetrics($metrics, $possibleCombinations, $metricsCombinations, $nextCombination);
            }
        }
    }

    /**
     * Get curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * @return MacroTemplate[][]
     */
    public function getCurves()
    {
        return $this->curves;
    }

    /**
     * Get curves to show in a chart by name with full Graphite Web metric filters and Graphite functions
     *
     * @return MacroTemplate[][]
     */
    public function getFullCurves()
    {
        if ($this->fullCurves === null) {
            $curves = $this->curves;

            foreach ($curves as &$curve) {
                $curve[0] = new MacroTemplate($curve[0]->resolve([
                    'host_name_template'    => static::getHostNameTemplate(),
                    'service_name_template' => static::getServiceNameTemplate(),
                    ''                      => '$$'
                ]));
            }
            unset($curve);

            $this->fullCurves = $curves;
        }

        return $this->fullCurves;
    }

    /**
     * Set curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * @param MacroTemplate[][] $curves
     *
     * @return $this
     */
    public function setCurves(array $curves)
    {
        $this->curves = $curves;

        return $this;
    }

    /**
     * Get additional URL parameters for Graphite Web
     *
     * @return MacroTemplate[]
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * Set additional URL parameters for Graphite Web
     *
     * @param MacroTemplate[]  $urlParams
     *
     * @return $this
     */
    public function setUrlParams(array $urlParams)
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * Get {@link hostNameTemplate}
     *
     * @return MacroTemplate
     *
     * @throws  ConfigurationError  If the configuration is invalid
     */
    protected static function getHostNameTemplate()
    {
        if (static::$hostNameTemplate === null) {
            $config = Config::module('graphite');
            $template = $config->get(
                'icinga',
                'graphite_writer_host_name_template',
                'icinga2.$host.name$.host.$host.check_command$'
            );

            try {
                static::$hostNameTemplate = new MacroTemplate($template);
            } catch (InvalidArgumentException $e) {
                throw new ConfigurationError(
                    'Bad icinga.graphite_writer_host_name_template in "%s": %s',
                    $config->getConfigFile(),
                    $e->getMessage()
                );
            }
        }

        return static::$hostNameTemplate;
    }

    /**
     * Get {@link serviceNameTemplate}
     *
     * @return MacroTemplate
     *
     * @throws  ConfigurationError  If the configuration is invalid
     */
    protected static function getServiceNameTemplate()
    {
        if (static::$serviceNameTemplate === null) {
            $config = Config::module('graphite');
            $template = $config->get(
                'icinga',
                'graphite_writer_service_name_template',
                'icinga2.$host.name$.services.$service.name$.$service.check_command$'
            );

            try {
                static::$serviceNameTemplate = new MacroTemplate($template);
            } catch (InvalidArgumentException $e) {
                throw new ConfigurationError(
                    'Bad icinga.graphite_writer_service_name_template in "%s": %s',
                    $config->getConfigFile(),
                    $e->getMessage()
                );
            }
        }

        return static::$serviceNameTemplate;
    }
}
