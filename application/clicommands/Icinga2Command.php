<?php

/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Graphite\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Graphite\Graphing\GraphingTrait;
use Icinga\Module\Graphite\Graphing\Template;
use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Module\Graphite\Web\Widget\Graphs;

class Icinga2Command extends Command
{
    use GraphingTrait;

    /**
     * Generate Icinga 2 host and service config based on the present graph templates
     *
     * The generated (fictive) monitored objects' checks yield random perfdata to be
     * written to Graphite as expected by the present graph templates of this module.
     * The generated Icinga 2 config can be used to simulate graphs generated based
     * on the graph templates.
     *
     * icingacli graphite icinga2 config
     */
    public function configAction()
    {
        $icinga2CfgObjPrefix = 'IW2_graphite_demo';
        $obscuredCheckCommandCustomVar = Graphs::getObscuredCheckCommandCustomVar();

        $result = [
            <<<EOT
object CheckCommand "$icinga2CfgObjPrefix" {
    command = [ "/usr/bin/printf" ]
    arguments = {
        "%s" = {{
            var res = " |"
            for (label => max in macro("\$$icinga2CfgObjPrefix\$")) {
                res += " '" + label + "'=" + (random() % max) + ";" + (max * 0.8) + ";" + (max * 0.9) + ";0;" + max
            }
            res
        }}
    }
}
EOT
            ,
            <<<EOT
object HostGroup "$icinga2CfgObjPrefix" {
    assign where host.vars.$icinga2CfgObjPrefix
}
EOT
            ,
            <<<EOT
object ServiceGroup "$icinga2CfgObjPrefix" {
    assign where service.vars.$icinga2CfgObjPrefix
}
EOT
            ,
            <<<EOT
object Host "{$icinga2CfgObjPrefix}_doesntmatchanycheckcommand" {
    check_command = "$icinga2CfgObjPrefix"
    check_interval = 30s
    vars.$obscuredCheckCommandCustomVar = "doesntmatchanycheckcommand"
    vars.$icinga2CfgObjPrefix = {
        "dummy1" = 100
        "dummy2" = 100
        "dummy3" = 100
        "dummy4" = 100
    }
}
EOT
            ,
            <<<EOT
apply Service "{$icinga2CfgObjPrefix}_doesntmatchanycheckcommand" {
    assign where host.vars.$icinga2CfgObjPrefix
    check_command = "$icinga2CfgObjPrefix"
    check_interval = 30s
    vars.$obscuredCheckCommandCustomVar = "doesntmatchanycheckcommand"
    vars.$icinga2CfgObjPrefix = {
        "dummy1" = 100
        "dummy2" = 100
        "dummy3" = 100
        "dummy4" = 100
    }
}
EOT
        ];

        foreach (static::getAllTemplates()->getAllTemplates() as $checkCommand => $templates) {
            $perfdata = [];

            foreach ($templates as $templateName => $template) {
                /** @var Template $template */

                $urlParams = $template->getUrlParams();

                switch (isset($urlParams['yUnitSystem']) ? $urlParams['yUnitSystem']->resolve([]) : 'none') {
                    case 'si':
                    case 'binary':
                        $max = 42000000;
                        break;

                    case 'sec':
                    case 'msec':
                        $max = 82800;
                        break;

                    default:
                        $max = 100;
                }

                foreach ($template->getCurves() as $curveName => $curve) {
                    /** @var MacroTemplate $metricFilter */
                    $metricFilter = $curve[0];

                    $macros = array_flip($metricFilter->getMacros());
                    $service = isset($macros['service_name_template']);

                    foreach ($macros as & $macro) {
                        $macro = ['dummy1', 'dummy2', 'dummy3', 'dummy4'];
                    }

                    $macros['host_name_template'] = [''];
                    $macros['service_name_template'] = [''];

                    foreach ($this->cartesianProduct($macros) as $macroValues) {
                        if (
                            preg_match(
                                '/\A\.[^.]+\.(.+)\.[^.]+\z/',
                                $metricFilter->resolve($macroValues),
                                $match
                            )
                        ) {
                            $perfdata[$match[1]] = $max;
                        }
                    }
                }
            }

            assert(isset($service), '$service not initialized in the loop');

            $monObj = $service
                ? [
                    "apply Service \"{$icinga2CfgObjPrefix}_{$checkCommand}\" {",
                    "    assign where host.vars.$icinga2CfgObjPrefix"
                ]
                : ["object Host \"{$icinga2CfgObjPrefix}_{$checkCommand}\" {"];

            $monObj[] = "    check_command = \"$icinga2CfgObjPrefix\"";
            $monObj[] = '    check_interval = 30s';
            $monObj[] = "    vars.$obscuredCheckCommandCustomVar = \"$checkCommand\"";
            $monObj[] = "    vars.$icinga2CfgObjPrefix = {";

            foreach ($perfdata as $label => $max) {
                $monObj[] = "        \"$label\" = $max";
            }

            $monObj[] = '    }';
            $monObj[] = '}';

            $result[] = implode("\n", $monObj);
        }

        echo implode("\n\n", $result) . "\n";
    }

    /**
     * Generate the cartesian product of the given array
     *
     * [
     *   'a' => ['b', 'c'],
     *   'd' => ['e', 'f']
     * ]
     *
     * [
     *   ['a' => 'b', 'd' => 'e'],
     *   ['a' => 'b', 'd' => 'f'],
     *   ['a' => 'c', 'd' => 'e'],
     *   ['a' => 'c', 'd' => 'f']
     * ]
     *
     * @param   array[] $input
     *
     * @return  array[]
     */
    protected function cartesianProduct(array &$input)
    {
        $results = [[]];

        foreach ($input as $key => & $values) {
            $nextStep = [];

            foreach ($results as & $result) {
                foreach ($values as $value) {
                    $nextStep[] = array_merge($result, [$key => $value]);
                }
            }
            unset($result);

            $results = & $nextStep;
            unset($nextStep);
        }
        unset($values);

        return $results;
    }
}
