<?php

namespace Icinga\Module\Graphite\Graphing;

use FilesystemIterator;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Web\UrlParams;
use InvalidArgumentException;
use SplFileInfo;

/**
 * Templates collection
 */
class Templates
{
    /**
     * The configured icinga.graphite_writer_host_name_template
     *
     * @var MacroTemplate
     */
    protected $hostNameTemplate;

    /**
     * The configured icinga.graphite_writer_service_name_template
     *
     * @var MacroTemplate
     */
    protected $serviceNameTemplate;

    /**
     * All templates by their name
     *
     * @var Template[string]
     */
    protected $templates = [];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Load templates as configured inside the given directory
     *
     * @param   string  $path
     *
     * @return  $this
     */
    public function loadDir($path)
    {
        $result = $this->fromFileSystem(new SplFileInfo($path))[1];
        if ($result !== null) {
            $this->treeToFlat($result, $this->templates);
        }

        return $this;
    }

    /**
     * Create and return templates as configured inside the given filesystem node
     *
     * Directories are traversed recursively
     *
     * @param   SplFileInfo $root
     *
     * @return  array
     */
    protected function fromFileSystem(SplFileInfo $root)
    {
        if ($root->isLink()) {
            $realpath = $root->getRealPath();
            if ($realpath === false) {
                return [null, null];
            }

            $filename = $root->getFilename();
            $root = new SplFileInfo($realpath);
        }

        if ($root->isFile()) {
            $matches = [];
            if (preg_match('/\A([^.].*)\.ini\z/si', isset($filename) ? $filename : $root->getFilename(), $matches)) {
                $result = $this->fromIni($root->getPathname());
                if (! empty($result)) {
                    return [$matches[1], $result];
                }
            }

            return [null, null];
        }

        if ($root->isDir()) {
            $results = [];

            foreach (new FilesystemIterator($root->getPathname()) as $fileinfo) {
                /** @var SplFileInfo $fileinfo */

                list($name, $result) = $this->fromFileSystem($fileinfo);
                if ($name !== null) {
                    $results[$name] = $result;
                }
            }

            if (! empty($results)) {
                return [isset($filename) ? $filename : $root->getFilename(), $results];
            }
        }

        return [null, null];
    }

    /**
     * Flatten the given subtree and append the result to the given results
     *
     * @param   string[]            $basePath
     * @param   array               $subTree
     * @param   Template[string]    $results
     */
    protected function treeToFlat(array $subTree, array & $results, array $basePath = [])
    {
        foreach ($subTree as $key => $value) {
            if (is_array($value)) {
                $subPath = $basePath;
                $subPath[] = $key;
                $this->treeToFlat($value, $results, $subPath);
            } else {
                $results[implode('/', array_map('rawurlencode', $basePath))] = $value;
            }
        }
    }

    /**
     * Create and return templates as configured in the given INI file
     *
     * @param   string  $path
     *
     * @return  Template[string]
     *
     * @throws  ConfigurationError  If the configuration is invalid
     */
    protected function fromIni($path)
    {
        $templates = [];

        foreach (Config::fromIni($path) as $section => $options) {
            /** @var ConfigObject $options */

            $matches = [];
            if (! preg_match('/\A(.+)\.(graph|urlparams|functions)\z/', $section, $matches)) {
                throw new ConfigurationError('Bad section name "%s" in file "%s"', $section, $path);
            }

            $templates[$matches[1]][$matches[2]] = $options->toArray();
        }

        foreach ($templates as $templateName => & $template) {
            if (! isset($template['graph']['metrics_filter'])) {
                throw new ConfigurationError(
                    'Metrics filter for template "%s" in file "%s" missing', $templateName, $path
                );
            }

            if (! isset($template['graph']['check_command'])) {
                throw new ConfigurationError(
                    'Icinga check command for template "%s" in file "%s" missing', $templateName, $path
                );
            }

            try {
                $metricsFilter = new MacroTemplate($template['graph']['metrics_filter']);
            } catch (InvalidArgumentException $e) {
                throw new ConfigurationError(
                    'Bad metrics filter ("%s") for template "%s" in file "%s": %s',
                    $template['graph']['metrics_filter'],
                    $templateName,
                    $path,
                    $e->getMessage()
                );
            }

            if (count(array_intersect(
                $metricsFilter->getMacros(),
                ['host_name_template', 'service_name_template']
            )) !== 1) {
                throw new ConfigurationError(
                    'Bad metrics filter ("%s") for template "%s" in file "%s":'
                    . ' must include either the macro $host_name_template$ or $service_name_template$, but not both',
                    $template['graph']['metrics_filter'],
                    $templateName,
                    $path
                );
            }

            $metricsFilter = new MacroTemplate($metricsFilter->resolve([
                'host_name_template'    => $this->getHostNameTemplate()->resolve([
                    'host.check_command'    => $template['graph']['check_command'],
                    ''                      => '$$'
                ]),
                'service_name_template' => $this->getServiceNameTemplate()->resolve([
                    'service.check_command' => $template['graph']['check_command'],
                    ''                      => '$$'
                ]),
                ''                      => '$$'
            ]));

            unset($template['graph']['metrics_filter']);
            unset($template['graph']['check_command']);

            switch (count($template['graph'])) {
                case 0:
                    break;

                case 1:
                    throw new ConfigurationError(
                        'Bad option for template "%s" in file "%s": "graph.%s"',
                        $templateName,
                        $path,
                        array_keys($template['graph'])[0]
                    );

                default:
                    $unknown = array_keys($template['graph']);
                    sort($unknown);

                    throw new ConfigurationError(
                        'Bad options for template "%s" in file "%s": %s',
                        $templateName,
                        $path,
                        implode(', ', array_map(
                            function($option) {
                                return "\"graph.$option\"";
                            },
                            $unknown
                        ))
                    );
            }

            $urlParams = new UrlParams();
            if (isset($template['urlparams'])) {
                $urlParams->addValues($template['urlparams']);
            }

            if (isset($template['functions'])) {
                $functions = [];
                foreach ($template['functions'] as $functionName => $function) {
                    try {
                        $functions[$functionName] = new MacroTemplate($function);
                    } catch (InvalidArgumentException $e) {
                        throw new ConfigurationError(
                            'Bad definition of function "%s" ("%s") for template "%s" in file "%s": %s',
                            $functionName,
                            $function,
                            $templateName,
                            $path,
                            $e->getMessage()
                        );
                    }

                    if ($functions[$functionName]->getMacros() !== ['metric']) {
                        throw new ConfigurationError(
                            'Bad function "%s" ("%s") of template "%s" in file "%s":'
                            . ' function definitions of templates must include the macro $metric$ and no other ones',
                            $functionName,
                            $function,
                            $templateName,
                            $path
                        );
                    }
                }
            } else {
                $functions = ['value' => new MacroTemplate('$metric$')];
            }

            $template = new Template($metricsFilter, $urlParams, $functions);
        }

        return $templates;
    }

    /**
     * Get {@link hostNameTemplate}
     *
     * @return MacroTemplate
     */
    protected function getHostNameTemplate()
    {
        if ($this->hostNameTemplate === null) {
            $config = Config::module('graphite');
            $template = $config->get(
                'icinga',
                'graphite_writer_host_name_template',
                'icinga2.$host.name$.host.$host.check_command$'
            );

            try {
                $this->hostNameTemplate = new MacroTemplate($template);
            } catch (InvalidArgumentException $e) {
                throw new ConfigurationError(
                    'Bad icinga.graphite_writer_host_name_template in "%s": %s',
                    $config->getConfigFile(),
                    $e->getMessage()
                );
            }
        }

        return $this->hostNameTemplate;
    }

    /**
     * Get {@link serviceNameTemplate}
     *
     * @return MacroTemplate
     */
    protected function getServiceNameTemplate()
    {
        if ($this->serviceNameTemplate === null) {
            $config = Config::module('graphite');
            $template = $config->get(
                'icinga',
                'graphite_writer_service_name_template',
                'icinga2.$host.name$.services.$service.name$.$service.check_command$'
            );

            try {
                $this->serviceNameTemplate = new MacroTemplate($template);
            } catch (InvalidArgumentException $e) {
                throw new ConfigurationError(
                    'Bad icinga.graphite_writer_service_name_template in "%s": %s',
                    $config->getConfigFile(),
                    $e->getMessage()
                );
            }
        }

        return $this->serviceNameTemplate;
    }

    /**
     * Get all loaded templates by their name
     *
     * @return Template[string]
     */
    public function getTemplates()
    {
        return $this->templates;
    }
}
