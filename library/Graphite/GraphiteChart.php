<?php

namespace Icinga\Module\Graphite;

use Icinga\Web\Url;

class GraphiteChart
{
    protected $web;

    protected $metric;

    protected $from = '-4hours';

    protected $showLegend = true;

    protected $height = 200;

    protected $width = 300;

    public function __construct(GraphiteWeb $web, GraphTemplate $template, $metric, $vars)
    {
        $this->web = $web;
        $this->template = $template;
        $this->metric = $metric;
        $this->vars = $vars;
    }

    public function getTitle()
    {
        return $this->template->getTitle($this->vars);
    }

    public function setStart($start)
    {
        $this->from = $start;
        return $this;
    }

    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    public function showLegend($show = true)
    {
        $this->showLegend = (bool) $show;
        return $this;
    }

    public function setMetrics($metrics = array())
    {
    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    protected function getParams()
    {
        return array(
            'height'         => $this->height,
            'width'          => $this->width,
            '_salt'          => time() . '.000',
            'from'           => $this->from,
            // 'graphOnly'      => (string) ! $this->showLegend,
            'hideLegend'     => (string) ! $this->showLegend,
            'hideGrid'       => 'true',
            'vTitle'         => 'Percent',
            'lineMode'       => 'connected', // staircase, slope
            'xFormat'        => '%a %H:%M',
            'drawNullAsZero' => 'false',
            'graphType'      => 'line', // pie
            'tz'             => 'Europe/Berlin',
            // 'hideAxes'    => 'true',
            // 'hideYAxis'   => 'true',
            // 'format'      => 'svg',
            // 'pieMode'     => 'average',
        );
    }

    public function getUrl()
    {
        $urlPattern = '/^' . preg_quote(Url::fromPath('/'), '/') . '/';
        $url = Url::fromPath('/render', $this->getParams());
        $this->template->extendUrl($url, $this->metric, $this->vars);
        $url->getParams()->add('_ext', 'whatever.svg');
        $url = preg_replace($urlPattern, $this->web->getBaseUrl() . '/', $url);
        return $url;
    }

    public function fetchImage()
    {
        $options = array(
            'http'=>array(
            'method'=>"POST",
            'header'=>
                "Accept-language: en\r\n".
                "Content-type: application/x-www-form-urlencoded\r\n",
//                'content'=> $data
            )
        );

        $context = stream_context_create($options);
        return file_get_contents($this->getUrl(), false, $context);
    }
}
