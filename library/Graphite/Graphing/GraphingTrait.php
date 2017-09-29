<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Application\Config;
use Icinga\Module\Graphite\GraphiteWebClient;

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
            static::$allTemplates = (new Templates())
                ->loadDir(Config::resolvePath('modules/graphite/templates'));
        }

        return static::$allTemplates;
    }

    /**
     * Get metrics data source
     *
     * @return MetricsDataSource
     */
    public static function getMetricsDataSource()
    {
        if (static::$metricsDataSource === null) {
            static::$metricsDataSource = new MetricsDataSource(GraphiteWebClient::getInstance());
        }

        return static::$metricsDataSource;
    }
}
