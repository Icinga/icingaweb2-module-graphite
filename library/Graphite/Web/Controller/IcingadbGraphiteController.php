<?php

/* Icinga Graphite Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Graphite\Web\Controller;

use Icinga\Application\Modules\Module;
use Icinga\Module\Graphite\ProvidedHook\Icingadb\IcingadbSupport;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\SearchControls;
use ipl\Orm\Query;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\SortControl;
use ipl\Web\Filter\QueryString;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class IcingadbGraphiteController extends CompatController
{
    use Auth;
    use Database;
    use SearchControls;

    /** @var bool Whether to use icingadb as the backend */
    protected $useIcingadbAsBackend;

    /** @var string[] Graph parameters */
    protected $graphParams = ['graphs_limit', 'graph_range', 'graph_start', 'graph_end', 'legacyParams'];

    /** @var Filter\Rule Filter from query string parameters */
    private $filter;

    protected function moduleInit()
    {
        $this->useIcingadbAsBackend = Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend();
    }

    /**
     * Get the filter created from query string parameters
     *
     * @return Filter\Rule
     */
    public function getFilter(): Filter\Rule
    {
        if ($this->filter === null) {
            $this->filter = QueryString::parse((string) $this->params);
        }

        return $this->filter;
    }

    /**
     * Create and return the LimitControl
     *
     * This automatically shifts the limit URL parameter from {@link $params}.
     *
     * @return LimitControl
     */
    public function createLimitControl(): LimitControl
    {
        $limitControl = new LimitControl(Url::fromRequest());
        $limitControl->setDefaultLimit($this->getPageSize(null));

        $this->params->shift($limitControl->getLimitParam());

        return $limitControl;
    }

    /**
     * Create and return the PaginationControl
     *
     * This automatically shifts the pagination URL parameters from {@link $params}.
     *
     * @return PaginationControl
     */
    public function createPaginationControl(Paginatable $paginatable): PaginationControl
    {
        $paginationControl = new PaginationControl($paginatable, Url::fromRequest());
        $paginationControl->setDefaultPageSize($this->getPageSize(null));
        $paginationControl->setAttribute('id', $this->getRequest()->protectId('pagination-control'));

        $this->params->shift($paginationControl->getPageParam());
        $this->params->shift($paginationControl->getPageSizeParam());

        return $paginationControl->apply();
    }

    /**
     * Create and return the SortControl
     *
     * This automatically shifts the sort URL parameter from {@link $params}.
     *
     * @param Query $query
     * @param array $columns Possible sort columns as sort string-label pairs
     *
     * @return SortControl
     */
    public function createSortControl(Query $query, array $columns): SortControl
    {
        $sortControl = SortControl::create($columns);

        $this->params->shift($sortControl->getSortParam());

        return $sortControl->apply($query);
    }
}
