<?php

namespace Icinga\Module\Graphite\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Graphite\Web\Controller\IcingadbGraphiteController;
use Icinga\Module\Graphite\Web\Controller\TimeRangePickerTrait;
use Icinga\Module\Graphite\Web\Widget\IcingadbGraphs;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Web\Control\SearchBar\ObjectSuggestions;
use Icinga\Web\Url;
use ipl\Html\HtmlString;
use ipl\Stdlib\Filter;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

class ServicesController extends IcingadbGraphiteController
{
    use TimeRangePickerTrait;

    public function indexAction()
    {
        if (! $this->useIcingadbAsBackend) {
            $params = urldecode($this->params->get('legacyParams'));
            $this->redirectNow(Url::fromPath('graphite/list/services')->setQueryString($params));
        }

        // shift graph params to avoid exception
        $graphRange = $this->params->shift('graph_range');
        $baseFilter = $graphRange ? Filter::equal('graph_range', $graphRange) : null;
        foreach ($this->graphParams as $param) {
            $this->params->shift($param);
        }

        $this->addTitleTab(t('Services'));

        $db = $this->getDb();

        $services = Service::on($db)->with(['state', 'host']);
        $services->filter(Filter::equal('state.performance_data', '*'));

        $this->applyRestrictions($services);

        $limitControl = $this->createLimitControl();
        $paginationControl = $this->createPaginationControl($services);
        $sortControl = $this->createSortControl($services, [
            'service.display_name' => t('Servicename'),
            'host.display_name' => t('Hostname')
        ]);

        $searchBar = $this->createSearchBar(
            $services,
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

        $services->filter($filter);

        $this->addControl($paginationControl);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);
        $this->handleTimeRangePickerRequest();
        $this->addControl(HtmlString::create($this->renderTimeRangePicker($this->view)));

        $this->addContent(
            (new IcingadbGraphs($services->execute()))
                ->setBaseFilter($baseFilter)
        );

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate();
        }
    }

    public function completeAction()
    {
        $suggestions = new ObjectSuggestions();
        $suggestions->setModel(Service::class);
        $suggestions->forRequest(ServerRequest::fromGlobals());
        $this->getDocument()->add($suggestions);
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(
            Service::on($this->getDb()),
            array_merge(
                [LimitControl::DEFAULT_LIMIT_PARAM, SortControl::DEFAULT_SORT_PARAM],
                $this->graphParams
            )
        );

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }
}
