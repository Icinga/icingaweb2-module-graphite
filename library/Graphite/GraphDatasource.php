<?php

namespace Icinga\Module\Graphite;

use Icinga\Web\Url;

class GraphDatasource
{
    protected $path;

    protected $color;

    protected $alias;

    protected $scale;

    protected $scaleToSeconds;

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

    public function getName()
    {
        if ($this->alias !== null) {
            return $this->alias;
        }
        return $this->path;
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

        return $url->getParams()->add('target', $target);
    }
}
