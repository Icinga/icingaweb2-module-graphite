<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Module\Graphite\Util\MacroTemplate;

class Template
{
    /**
     * All curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * [$curve => [$metricFilter, $function], ...]
     *
     * @var MacroTemplate[][]
     */
    protected $curves = [];

    /**
     * Additional URL parameters for rendering via Graphite Web
     *
     * [$key => $value, ...]
     *
     * @var string[]
     */
    protected $urlParams = [];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * @return MacroTemplate[][]
     */
    public function getCurves()
    {
        return $this->curves;
    }

    /**
     * Set curves to show in a chart by name with Graphite Web metric filters and Graphite functions
     *
     * @param MacroTemplate[][] $curves
     *
     * @return $this
     */
    public function setCurves(array $curves)
    {
        $this->curves = $curves;

        return $this;
    }

    /**
     * Get additional URL parameters for Graphite Web
     *
     * @return string[]
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * Set additional URL parameters for Graphite Web
     *
     * @param string[]  $urlParams
     *
     * @return $this
     */
    public function setUrlParams(array $urlParams)
    {
        $this->urlParams = $urlParams;

        return $this;
    }
}
