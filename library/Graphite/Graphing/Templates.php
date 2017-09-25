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

class Templates
{
    /**
     * Create and return templates with their paths as configured inside the given directory
     *
     * @param   string  $path
     *
     * @return  Template[string]
     */
    public static function fromDir($path)
    {
        $result = static::fromFileSystem(new SplFileInfo($path))[1];
        if ($result === null) {
            return [];
        }

        $results = [];
        static::treeToFlat($result, $results);
        return $results;
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
    protected static function fromFileSystem(SplFileInfo $root)
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
                $result = static::fromIni($root->getPathname());
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

                list($name, $result) = static::fromFileSystem($fileinfo);
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
    protected static function treeToFlat(array $subTree, array & $results, array $basePath = [])
    {
        foreach ($subTree as $key => $value) {
            if (is_array($value)) {
                $subPath = $basePath;
                $subPath[] = $key;
                static::treeToFlat($value, $results, $subPath);
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
    protected static function fromIni($path)
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

            if (count(array_intersect($metricsFilter->getMacros(), ['icinga_host', 'icinga_service'])) !== 1) {
                throw new ConfigurationError(
                    'Bad metrics filter ("%s") for template "%s" in file "%s":'
                    . ' must include either the macro $icinga_host$ or $icinga_service$, but not both',
                    $template['graph']['metrics_filter'],
                    $templateName,
                    $path
                );
            }

            unset($template['graph']['metrics_filter']);

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
}
