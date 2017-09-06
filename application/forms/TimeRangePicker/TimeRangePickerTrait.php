<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use Icinga\Web\UrlParams;

trait TimeRangePickerTrait
{
    /**
     * Extract the relative time range (if any) from the given URL parameters
     *
     * @param   UrlParams   $params
     *
     * @return  bool|int|null
     */
    protected function getRelativeSeconds(UrlParams $params)
    {
        $seconds = $params->get('graph_range');
        if ($seconds === null) {
            return null;
        }

        return preg_match('/^(?:0|[1-9]\d*)$/', $seconds) ? (int) $seconds : false;
    }
}
