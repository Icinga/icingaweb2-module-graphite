<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Module\Graphite\GraphiteQuery;
use Icinga\Module\Graphite\GraphiteWeb;
use Icinga\Module\Graphite\GraphiteWebClient;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\TemplateSet;
use Icinga\Module\Graphite\TemplateStore;

trait GraphsTrait
{
    /**
     * [$setName => $set]
     *
     * @var TemplateSet[string]
     */
    protected static $templateSets;

    /**
     * [$setName => [$templateName => $template]]
     *
     * @var GraphTemplate[string][string]
     */
    protected static $allTemplates = [];

    /**
     * @var GraphTemplate[string]
     */
    protected $templates = [];

    /**
     * @var GraphiteQuery[string]
     */
    protected $graphiteQueries = [];

    /**
     * Initialize {@link templates}
     */
    protected function collectTemplates()
    {
        if (static::$templateSets === null) {
            static::$templateSets = (new TemplateStore())->loadTemplateSets();
        }

        foreach (static::$templateSets as $setname => $set) {
            /** @var TemplateSet $set */

            if (array_key_exists('icingaHost', $set->getBasePatterns())) {
                if (! isset(static::$allTemplates[$setname])) {
                    static::$allTemplates[$setname] = $set->loadTemplates();
                }

                foreach (static::$allTemplates[$setname] as $templateName => $template) {
                    /** @var GraphTemplate $template */

                    if ($this->includeTemplate($template)) {
                        $this->templates[$templateName] = $template;
                    }
                }
            }
        }
    }

    /**
     * Initialize {@link graphiteQueries}
     */
    protected function collectGraphiteQueries()
    {
        $graphiteWeb = new GraphiteWeb(GraphiteWebClient::getInstance());
        foreach ($this->templates as $templateName => $template) {
            /** @var GraphTemplate $template */

            $this->graphiteQueries[$templateName] = $this->filterGraphiteQuery(
                $graphiteWeb->select()->from($template->getFilterString())
            );
        }
    }

    /**
     * Return whether to use the given template
     *
     * @param   GraphTemplate   $template
     *
     * @return  bool
     */
    abstract protected function includeTemplate(GraphTemplate $template);

    /**
     * Add filters to the given query so that only specific graphs are shown
     *
     * @param   GraphiteQuery   $query
     *
     * @return  GraphiteQuery           The given query
     */
    abstract protected function filterGraphiteQuery(GraphiteQuery $query);
}
