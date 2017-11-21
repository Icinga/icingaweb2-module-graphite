<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Util\MacroTemplate;
use InvalidArgumentException;

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
     * Additional URL parameters for rendering via Graphite Web
     *
     * [$key => $value, ...]
     *
     * @var MacroTemplate[]
     */
    protected $urlParams = [];

    /**
     * The check command this template is designed for
     *
     * @var string|null
     */
    protected $checkCommand;

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
     * @param   MetricsDataSource   $dataSource
     * @param   string[]            $filter
     * @param   string              $checkCommand   The check command of the monitored object we fetch charts for
     *
     * @return  Chart[]
     */
    public function getCharts(MetricsDataSource $dataSource, array $filter, $checkCommand)
    {
        $metrics = [];
        foreach ($this->curves as $curveName => $curve) {
            $query = $dataSource->select()->from($curve[0]->resolve([
                'host_name_template'    => static::getHostNameTemplate()->resolve([
                    'host.check_command'    => $checkCommand,
                    ''                      => '$$'
                ]),
                'service_name_template' => static::getServiceNameTemplate()->resolve([
                    'service.check_command' => $checkCommand,
                    ''                      => '$$'
                ]),
                ''                      => '$$'
            ]));

            foreach ($filter as $key => $value) {
                $query->where($key, $value);
            }

            foreach ($query->fetchColumn() as $metric) {
                $vars = $curve[0]->reverseResolve($metric);
                if ($vars !== false) {
                    $metrics[$curveName][$metric] = $vars;
                }
            }
        }

        if (empty($metrics)) {
            return [];
        }

        $intersectingVariables = [];
        foreach ($metrics as $curveName1 => $_) {
            foreach ($metrics as $curveName2 => $_) {
                if ($curveName1 !== $curveName2 && ! isset($intersectingVariables[$curveName2][$curveName1])) {
                    $vars = array_intersect(
                        $this->curves[$curveName1][0]->getMacros(),
                        $this->curves[$curveName2][0]->getMacros()
                    );
                    if (! empty($vars)) {
                        $intersectingVariables[$curveName1][$curveName2] = $vars;
                    }
                }
            }
        }

        $iterState = [];
        foreach ($metrics as $curveName => $metric) {
            $iterState[$curveName] = [0, array_keys($metric)];
        }

        $metricsCombinations = [];
        $currentMetrics = [];
        do {
            foreach ($metrics as $curveName => $metric) {
                $currentMetrics[$curveName] = $iterState[$curveName][1][ $iterState[$curveName][0] ];
            }

            $acceptCombination = true;
            foreach ($intersectingVariables as $curveName1 => $intersectingWith) {
                foreach ($intersectingWith as $curveName2 => $vars) {
                    foreach ($vars as $key) {
                        if ($metrics[$curveName1][ $currentMetrics[$curveName1] ][$key]
                            !== $metrics[$curveName2][ $currentMetrics[$curveName2] ][$key]) {
                            $acceptCombination = false;
                            break 3;
                        }
                    }
                }
            }

            if ($acceptCombination) {
                $metricsCombinations[] = $currentMetrics;
            }

            $overflow = true;
            foreach ($iterState as $curveName => & $iterSubState) {
                if (isset($iterSubState[1][ ++$iterSubState[0] ])) {
                    $overflow = false;
                    break;
                } else {
                    $iterSubState[0] = 0;
                }
            }

            unset($iterSubState);
        } while (! $overflow);

        $charts = [];
        foreach ($metricsCombinations as $metricsCombination) {
            $charts[] = new Chart($dataSource->getClient(), $this, $metricsCombination);
        }

        return $charts;
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
     * Get the check command this template is designed for
     *
     * @return string|null
     */
    public function getCheckCommand()
    {
        return $this->checkCommand;
    }

    /**
     * Set the check command this template is designed for
     *
     * @param string|null $checkCommand
     *
     * @return $this
     */
    public function setCheckCommand($checkCommand)
    {
        $this->checkCommand = $checkCommand;

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
