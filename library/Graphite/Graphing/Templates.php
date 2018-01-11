<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Web\UrlParams;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Templates collection
 */
class Templates
{
    /**
     * All templates by their name
     *
     * @var Template[]
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
     *
     * @throws  ConfigurationError  If the configuration is invalid
     */
    public function loadDir($path)
    {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                    | RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        ) as $filepath => $fileinfo) {
            /** @var SplFileInfo $fileinfo */

            if ($fileinfo->isFile() && preg_match('/\A[^.].*\.ini\z/si', $fileinfo->getFilename())) {
                $this->loadIni($filepath);
            }
        }

        return $this;
    }

    /**
     * Load templates as configured in the given INI file
     *
     * @param   string  $path
     *
     * @return  $this
     *
     * @throws  ConfigurationError  If the configuration is invalid
     */
    public function loadIni($path)
    {
        /** @var string[][][] $templates */
        $templates = [];

        foreach (Config::fromIni($path) as $section => $options) {
            /** @var ConfigObject $options */

            $matches = [];
            if (! preg_match('/\A(.+)\.(graph|metrics_filters|urlparams|functions)\z/', $section, $matches)) {
                throw new ConfigurationError('Bad section name "%s" in file "%s"', $section, $path);
            }

            $templates[$matches[1]][$matches[2]] = $options->toArray();
        }

        foreach ($templates as $templateName => $template) {
            $checkCommands = isset($template['graph']['check_command'])
                ? array_unique(preg_split('/\s*,\s*/', $template['graph']['check_command'], -1, PREG_SPLIT_NO_EMPTY))
                : [];
            unset($template['graph']['check_command']);

            if (isset($template['graph'])) {
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
                        $standalone = array_keys($template['graph']);
                        sort($standalone);

                        throw new ConfigurationError(
                            'Bad options for template "%s" in file "%s": %s',
                            $templateName,
                            $path,
                            implode(', ', array_map(
                                function($option) {
                                    return "\"graph.$option\"";
                                },
                                $standalone
                            ))
                        );
                }
            }

            /** @var MacroTemplate[][] $curves */
            $curves = [];

            if (isset($template['metrics_filters'])) {
                foreach ($template['metrics_filters'] as $curve => $metricsFilter) {
                    try {
                        $curves[$curve][0] = new MacroTemplate($metricsFilter);
                    } catch (InvalidArgumentException $e) {
                        throw new ConfigurationError(
                            'Bad metrics filter "%s" for curve "%s" of template "%s" in file "%s": %s',
                            $metricsFilter,
                            $curve,
                            $templateName,
                            $path,
                            $e->getMessage()
                        );
                    }

                    if (count(array_intersect(
                        $curves[$curve][0]->getMacros(),
                        ['host_name_template', 'service_name_template']
                    )) !== 1) {
                        throw new ConfigurationError(
                            'Bad metrics filter "%s" for curve "%s" of template "%s" in file "%s": must include'
                            . ' either the macro $host_name_template$ or $service_name_template$, but not both',
                            $metricsFilter,
                            $curve,
                            $templateName,
                            $path
                        );
                    }

                    if (isset($template['functions'][$curve])) {
                        try {
                            $curves[$curve][1] = new MacroTemplate($template['functions'][$curve]);
                        } catch (InvalidArgumentException $e) {
                            throw new ConfigurationError(
                                'Bad function "%s" for curve "%s" of template "%s" in file "%s": %s',
                                $template['functions'][$curve],
                                $curve,
                                $templateName,
                                $path,
                                $e->getMessage()
                            );
                        }

                        if ($curves[$curve][1]->getMacros() !== ['metric']) {
                            throw new ConfigurationError(
                                'Bad function "%s" for curve "%s" of template "%s" in file "%s":'
                                . ' function definitions of templates must include the macro $metric$ and no other ones',
                                $template['functions'][$curve],
                                $curve,
                                $templateName,
                                $path
                            );
                        }

                        unset($template['functions'][$curve]);
                    } else {
                        $curves[$curve][1] = new MacroTemplate('$metric$');
                    }
                }
            }

            if (isset($template['functions'])) {
                switch (count($template['functions'])) {
                    case 0:
                        break;

                    case 1:
                        throw new ConfigurationError(
                            'Metrics filter for curve "%s" of template "%s" in file "%s" missing',
                            array_keys($template['functions'])[0],
                            $templateName,
                            $path
                        );

                    default:
                        $standalone = array_keys($template['functions']);
                        sort($standalone);

                        throw new ConfigurationError(
                            'Metrics filter for curves of template "%s" in file "%s" missing: "%s"',
                            $templateName,
                            $path,
                            implode('", "', $standalone)
                        );
                }
            }

            $urlParams = [];
            if (isset($template['urlparams'])) {
                foreach ($template['urlparams'] as $key => $value) {
                    try {
                        $urlParams[$key] = new MacroTemplate($value);
                    } catch (InvalidArgumentException $e) {
                        throw new ConfigurationError(
                            'Invalid URL parameter "%s" ("%s") for template "%s" in file "%s": %s',
                            $key,
                            $value,
                            $templateName,
                            $path,
                            $e->getMessage()
                        );
                    }
                }
            }

            $templates[$templateName] = empty($curves) ? null : (new Template())
                ->setCurves($curves)
                ->setUrlParams($urlParams)
                ->setCheckCommands($checkCommands);
        }

        foreach ($templates as $templateName => $template) {
            if ($template === null) {
                unset($this->templates[$templateName]);
            } else {
                $this->templates[$templateName] = $template;
            }
        }

        return $this;
    }

    /**
     * Get all loaded templates by their name
     *
     * @return Template[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }
}
