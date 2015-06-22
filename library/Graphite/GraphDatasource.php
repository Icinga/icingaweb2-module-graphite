<?php

namespace Icinga\Module\Graphite;

use Icinga\Web\Url;

class GraphDatasource
{
    protected static $cssColors = array(
        'black'     => 'rgb(0,0,0)',
        'white'     => 'rgb(255,255,255)',
        'blue'      => 'rgb(100,100,255)',
        'green'     => 'rgb(0,200,0)',
        'red'       => 'rgb(200,0,50)',
        'yellow'    => 'rgb(255,255,0)',
        'orange'    => 'rgb(255, 165, 0)',
        'purple'    => 'rgb(200,100,255)',
        'brown'     => 'rgb(150,100,50)',
        'aqua'      => 'rgb(0,150,150)',
        'gray'      => 'rgb(175,175,175)',
        'grey'      => 'rgb(175,175,175)',
        'magenta'   => 'rgb(255,0,255)',
        'pink'      => 'rgb(255,100,100)',
        'gold'      => 'rgb(200,200,0)',
        'rose'      => 'rgb(200,150,200)',
        'darkblue'  => 'rgb(0,0,255)',
        'darkgreen' => 'rgb(0,255,0)',
        'darkred'   => 'rgb(255,0,0)',
        'darkgray'  => 'rgb(111,111,111)',
        'darkgrey'  => 'rgb(111,111,111)'
    );

    protected $path;

    protected $color;

    protected $alias;

    protected $scale;

    protected $scaleToSeconds;

    protected $stacked = false;

    protected $enabled = true;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    public function setScale($scale)
    {
        $this->scale = $scale;
        return $this;
    }

    public function setScaleToSeconds($seconds)
    {
        $this->scaleToSeconds = $seconds;
        return $this;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function setStacked()
    {
        $this->stacked = true;
        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function disable()
    {
        $this->enabled = false;
        return $this;
    }

    public function getName()
    {
        if ($this->alias !== null) {
            return $this->alias;
        }
        return $this->path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getColorCss()
    {
        $color = $this->getColor();
        if (array_key_exists($color, self::$cssColors)) {
            return self::$cssColors[$color];
        }

        return $color;
    }

    protected function func()
    {
        $args = func_get_args();
        $function = array_shift($args);
        return sprintf($function . '(%s)', implode(',', $args));
    }

    public function addToUrl(Url $url, $metric)
    {
        $target = $metric . '.' . $this->path;
        if ($this->color !== null) {
            $target = $this->func('color', $target, "'" . $this->color . "'");
        }
        if ($this->scaleToSeconds !== null) {
            $target = $this->func('scaleToSeconds', $target, $this->scaleToSeconds);
        }
        if ($this->scale !== null) {
            $target = $this->func('scale', $target, $this->scale);
        }
        if ($this->alias !== null) {
            $target = $this->func('alias', $target, "'" . $this->alias . "'");
        }
        if ($this->stacked) {
            $target = $this->func('stacked', $target);
        }

        return $url->getParams()->add('target', $target);
    }
}
