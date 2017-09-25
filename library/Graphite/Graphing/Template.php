<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Web\UrlParams;

class Template
{
    /**
     * Graphite Web metrics filter
     *
     * @var MacroTemplate
     */
    protected $filter;

    /**
     * Additional URL parameters for Graphite Web
     *
     * @var UrlParams
     */
    protected $urlParams;

    /**
     * Perfdata curves to show in a chart with Graphite functions to apply to them
     *
     * @var MacroTemplate[string]
     */
    protected $functions;

    /**
     * Constructor
     */
    public function __construct(MacroTemplate $filter, UrlParams $urlParams, array $functions)
    {
        $this->setFilter($filter)->setUrlParams($urlParams)->setFunctions($functions);
    }

    /**
     * Get the Graphite Web metrics filter
     *
     * @return MacroTemplate
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the Graphite Web metrics filter
     *
     * @param MacroTemplate $filter
     *
     * @return $this
     */
    public function setFilter(MacroTemplate $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get additional URL parameters for Graphite Web
     *
     * @return UrlParams
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * Set additional URL parameters for Graphite Web
     *
     * @param UrlParams $urlParams
     *
     * @return $this
     */
    public function setUrlParams(UrlParams $urlParams)
    {
        $this->urlParams = $urlParams;
        return $this;
    }

    /**
     * Get the perfdata curves to show in a chart with Graphite functions to apply to them
     *
     * @return MacroTemplate[string]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * Set the perfdata curves to show in a chart with Graphite functions to apply to them
     *
     * @param MacroTemplate[string] $functions
     *
     * @return $this
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }
}
