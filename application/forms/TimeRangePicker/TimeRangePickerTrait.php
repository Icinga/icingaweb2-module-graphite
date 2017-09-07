<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

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
     * @return string[string]
     */
    public static function getAbsoluteRangeParameters()
    {
        return ['start' => 'graph_start', 'end' => 'graph_end'];
    }

    /**
     * @return string
     */
    public static function getRangeCustomizationParameter()
    {
        return 'graph_range_custom';
    }

    /**
     * @return string[]
     */
    public static function getAllRangeParameters()
    {
        return array_values(array_merge([static::getRelativeRangeParameter()], static::getAbsoluteRangeParameters()));
    }

    /**
     * @return string[]
     */
    public static function getAllParameters()
    {
        return array_values(array_merge(static::getAllRangeParameters(), [static::getRangeCustomizationParameter()]));
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
}
