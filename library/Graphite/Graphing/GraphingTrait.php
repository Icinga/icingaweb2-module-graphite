<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Graphite\Graphing;

use GuzzleHttp\Psr7\Uri;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;

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
            /** @var ConfigObject<string> $graphite */
            $graphite = $config->getSection('graphite');
            if (! isset($graphite->url)) {
                throw new ConfigurationError('Missing "graphite.url" in "%s"', $config->getConfigFile());
            }

            static::$metricsDataSource = new MetricsDataSource(
                (new GraphiteWebClient(new Uri($graphite->url)))
                    ->setUser($graphite->user)
                    ->setPassword($graphite->password)
                    ->setInsecure((bool) $graphite->insecure)
                    ->setTimeout(isset($graphite->timeout) ? intval($graphite->timeout) : 10)
            );
        }

        return static::$metricsDataSource;
    }
}
