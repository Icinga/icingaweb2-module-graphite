<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;

trait TimeRangePickerTrait
{
    /**
     * @return string
     */
    public static function getRelativeRangeParameter()
    {
        return 'graph_range';
    }

    /**
     * @return string[]
     */
    public static function getAbsoluteRangeParameters()
    {
        return ['start' => 'graph_start', 'end' => 'graph_end'];
    }

    /**
     * @return string[]
     */
    public static function getAllRangeParameters()
    {
        return array_values(array_merge([static::getRelativeRangeParameter()], static::getAbsoluteRangeParameters()));
    }

    /**
     * Copy {@link getAllRangeParameters()} from one {@link UrlParams} instance to another
     *
     * @param   UrlParams|null  $copy   Defaults to a new instance
     * @param   UrlParams|null  $origin Defaults to the current request's params
     *
     * @return  UrlParams               The copy
     */
    public static function copyAllRangeParameters(UrlParams $copy = null, UrlParams $origin = null)
    {
        if ($origin === null) {
            $origin = Url::fromRequest()->getParams();
        }
        if ($copy === null) {
            $copy = new UrlParams();
        }

        foreach (TimeRangePickerTrait::getAllRangeParameters() as $param) {
            $value = $origin->get($param);
            if ($value !== null) {
                $copy->set($param, $value);
            }
        }

        return $copy;
    }

    /**
     * Extract the relative time range (if any) from the given URL parameters
     *
     * @param   UrlParams   $params
     *
     * @return  bool|int|null
     */
    protected function getRelativeSeconds(UrlParams $params)
    {
        $seconds = $params->get(static::getRelativeRangeParameter());
        if ($seconds === null) {
            return null;
        }

        return preg_match('/^(?:0|[1-9]\d*)$/', $seconds) ? (int) $seconds : false;
    }

    /**
     * Get the default relative time range for graphs
     *
     * @return int
     *
     * @throws ConfigurationError
     */
    public static function getDefaultRelativeTimeRange()
    {
        $rangeFactors = [
            'minutes'   => 60,
            'hours'     => 3600,
            'days'      => 86400,
            'weeks'     => 604800,
            'months'    => 2592000,
            'years'     => 31557600
        ];

        $config = Config::module('graphite');
        $unit = $config->get('ui', 'default_time_range_unit', 'hours');

        if (! isset($rangeFactors[$unit])) {
            throw new ConfigurationError(
                'Bad ui.default_time_range_unit %s in file %s',
                var_export($unit, true),
                var_export($config->getConfigFile(), true)
            );
        }

        return (int) $config->get('ui', 'default_time_range', 1) * $rangeFactors[$unit];
    }
}
