<?php

namespace Icinga\Module\Graphite;

use Icinga\Application\Icinga;
use Icinga\Web\Url;
use Icinga\Web\View;

class EmbedGraphs
{
    /**
     * Embed all graphs of the given host (but none of its services)
     *
     * @param   string  $host
     *
     * @return  string
     */
    public static function host($host)
    {
        return static::url(static::getView()->href('graphite/show/host', [
            'host'  => $host,
            'view'  => 'compact'
        ]));
    }

    /**
     * Embed all graphs of the given service of the given host
     *
     * @param   string  $host
     * @param   string  $service
     *
     * @return  string
     */
    public static function service($host, $service)
    {
        return static::url(static::getView()->href('graphite/show/service', [
            'host'      => $host,
            'service'   => $service,
            'view'      => 'compact'
        ]));
    }

    /**
     * Return a <div/> which causes the framework JS to embed the given URL
     *
     * @param   Url $url
     *
     * @return  string
     */
    protected static function url(Url $url)
    {
        // TODO(ak): EL says "<div class="container" data-icinga-url="..." /> is enough",
        // but this seems not to work for me
        return '<div class="container" data-base-target="_main" data-last-update="-1" data-icinga-refresh="15"'
            . ' data-icinga-url="' . $url . '"></div>';
    }

    /**
     * Get the current response view
     *
     * @return View
     */
    protected static function getView()
    {
        return Icinga::app()->getViewRenderer()->view;
    }
}
