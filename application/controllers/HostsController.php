<?php

/* Icinga Graphite Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Graphite\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Graphite\Web\Controller\IcingadbGraphiteController;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Graphite\Web\Widget\IcingadbGraphs;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Web\Url;
use ipl\Html\HtmlString;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class HostsController extends IcingadbGraphiteController
{
    use TimeRangePickerTrait;

    public function indexAction()
    {
        if (! $this->useIcingadbAsBackend) {
            $params = urldecode($this->params->get('legacyParams'));
            $this->redirectNow(Url::fromPath('graphite/list/hosts')->setQueryString($params));
        }

        // shift graph params to avoid exception
        $graphRange = $this->params->shift('graph_range');
        $baseFilter = $graphRange ? Filter::equal('graph_range', $graphRange) : null;
        foreach ($this->graphParams as $param) {
            $this->params->shift($param);
        }

        $this->addTitleTab(t('Hosts'));

        $db = $this->getDb();

        $hosts = Host::on($db)->with(['state']);
        $hosts->filter(Filter::equal('state.performance_data', '*'));

        $this->applyRestrictions($hosts);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($hosts);
        $sortControl = $this->createSortControl($hosts, ['host.display_name' => t('Hostname')]);

        $searchBar = $this->createSearchBar(
            $hosts,
            array_merge(
                [$limitControl->getLimitParam(), $sortControl->getSortParam()],
                $this->graphParams
            )
        );

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();
                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $this->applyRestrictions($hosts);
        $hosts->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);
        $this->handleTimeRangePickerRequest();
        $this->addControl(HtmlString::create($this->renderTimeRangePicker($this->view)));

        $this->addContent(
            (new IcingadbGraphs($hosts->execute()))
                ->setBaseFilter($baseFilter)
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Host::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(
            Host::on($this->getDb()),
            array_merge(
                [LimitControl::DEFAULT_LIMIT_PARAM, SortControl::DEFAULT_SORT_PARAM],
                $this->graphParams
            )
        );

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
