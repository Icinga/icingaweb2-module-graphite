<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Graphite\Web\FakeSchemeRequest;
use Icinga\Web\Url;

trait GraphingTrait
{
    /**
     * All loaded templates
     *
     * @var Templates
     */
    protected static $allTemplates;

    /**
     * Metrics data source
     *
     * @var MetricsDataSource
     */
    protected static $metricsDataSource;

    /**
     * Load and get all templates
     *
     * @return Templates
     */
    protected static function getAllTemplates()
    {
        if (static::$allTemplates === null) {
            $allTemplates = (new Templates())->loadDir(
                Icinga::app()
                    ->getModuleManager()
                    ->getModule('graphite')
                    ->getBaseDir() . DIRECTORY_SEPARATOR . 'templates'
            );

            $path = Config::resolvePath('modules/graphite/templates');
            if (file_exists($path)) {
                $allTemplates->loadDir($path);
            }

            static::$allTemplates = $allTemplates;
        }

        return static::$allTemplates;
    }

    /**
     * Get metrics data source
     *
     * @return MetricsDataSource
     *
     * @throws ConfigurationError
     */
    public static function getMetricsDataSource()
    {
        if (static::$metricsDataSource === null) {
            $config = Config::module('graphite');
            $graphite = $config->getSection('graphite');
            if (! isset($graphite->url)) {
                throw new ConfigurationError('Missing "graphite.url" in "%s"', $config->getConfigFile());
            }

            static::$metricsDataSource = new MetricsDataSource(
                (new GraphiteWebClient(Url::fromPath($graphite->url, [], new FakeSchemeRequest())))
                    ->setUser($graphite->user)
                    ->setPassword($graphite->password)
                    ->setInsecure($graphite->insecure)
            );
        }

        return static::$metricsDataSource;
    }
}
