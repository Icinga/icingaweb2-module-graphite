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

    /** @var string[] Preserved graph parameters */
    protected $preservedParams = ['graphs_limit', 'graph_range', 'graph_start', 'graph_end', 'legacyParams', 'format'];

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
}
