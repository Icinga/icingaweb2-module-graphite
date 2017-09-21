<?php

namespace Icinga\Module\Graphite\Controllers;

use DirectoryIterator;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait;
use Icinga\Module\Graphite\GraphiteChart;
use Icinga\Module\Graphite\GraphiteUtil;
use Icinga\Module\Graphite\GraphiteWeb;
use Icinga\Module\Graphite\GraphiteWebClient;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\TemplateStore;
use Icinga\Web\Controller;
use Icinga\Web\UrlParams;
use Icinga\Web\Widget;

class ShowController extends Controller
{
    protected $graphiteWeb;

    protected $templates;

    protected $templateSet;

    protected $template;

    protected $filters;

    protected $handledGraphParams = false;

    protected $templateStore;

    public function init()
    {
        $config = $this->Config();
        $this->templateStore = new TemplateStore();
        $graphite = $this->graphiteWeb = new GraphiteWeb(GraphiteWebClient::getInstance());
        $this->template = $this->view->template = $this->loadTemplate();
        $this->params->shift('r');
    }

    public function XXXserviceAction()
    {
        $this->handleDatasourceToggles();
        $this->handleGraphParams();
        $hostname = $this->view->hostname = $this->params->get('host');
        $service = $this->view->service = $this->params->get('service');
        if (! $hostname) {
            throw new NotFoundError('Host is required');
        }
        if (! $service) {
            throw new NotFoundError('Service is required');
        }
        $this->getTabs()->add('service', array(
            'label' => $this->translate('Graphite - Single service'),
            'url' => $this->getRequest()->getUrl()
        ))->activate('service');

        $imgs = array();
        $this->view->templates = array();

        foreach ($this->templateStore->loadTemplateSets() as $setname => $set) {

            $patterns = $set->getBasePatterns();
            if (! array_key_exists('icingaService', $patterns)) continue;

            foreach ($set->loadTemplates() as $key => $template) {

                if (strpos($template->getFilterString(), '$service') === false) continue;

                $this->view->templates[$key] = $template;

                $imgs[$key] = $this->graphiteWeb
                    ->select()
                    ->from($template->getFilterString())
                    ->where('hostname', $hostname)
                    ->where('service',  $service)
                    ->getImages($template);

                foreach ($imgs[$key] as $img) {
                    $this->applyGraphParams($img)
                         ->showLegend(! $this->params->get('hideLegend', false));
                }
            }
        }

        $this->view->images = $imgs;
    }

    protected function loadTemplate()
    {
        $this->handleTemplateParams();
        if (! $this->view->templateName) {
            return false;
        }

        $template = $this->templateStore->loadTemplate($this->view->templateName);
        foreach ($this->view->disabledDatasources as $key) {
            $template->getDatasource($key)->disable();
        }

        return $template;
    }

    protected function handleTemplateParams()
    {
        $this->view->templateName = $this->params->get('template');
        $this->view->disabledDatasources = $this->params->getValues('disabled');
    }

    /**
     * Get time range parameters for Graphite from the URL
     *
     * @return string[]
     */
    protected function getRangeFromTimeRangePicker()
    {
        $params = $this->getRequest()->getUrl()->getParams();
        $relative = $params->get(TimeRangePickerTrait::getRelativeRangeParameter());
        if ($relative !== null) {
            return ["-{$relative}s", null];
        }

        $absolute = TimeRangePickerTrait::getAbsoluteRangeParameters();
        return [$params->get($absolute['start'], '-1hours'), $params->get($absolute['end'])];
    }

    protected function handleGraphParams()
    {
        if ($this->handledGraphParams === false) {
            $this->handledGraphParams = true;
            $view = $this->view;
            list($view->start, $view->end) = $this->getRangeFromTimeRangePicker();
            $view->width  = $this->params->shift('width', '300');
            $view->height = $this->params->shift('height', '150');
        }

        return $this;
    }

    protected function applyGraphParams(GraphiteChart $chart)
    {
        $this->handleGraphParams();
        $view = $this->view;
        $chart->setStart($view->start)
              ->setUntil($view->end)
              ->setWidth($view->width)
              ->setHeight($view->height)
              // TODO: handle before
              ->showLegend(! $this->params->get('hideLegend', false));

        return $chart;
    }

    protected function handleDatasourceToggles()
    {
        $this->handleTemplateParams();
        $disabled = $this->view->disabledDatasources;

        if ($disable = $this->params->get('disableDatasource')) {
            $url = $this->getRequest()->getUrl()->without('disableDatasource');
            if (! in_array($disable, $disabled)) {
                $url->getParams()->add('disabled', $disable);

            }
            $this->redirectNow($url);
        }

        if ($enable = $this->params->get('enableDatasource')) {
            $url = $this->getRequest()->getUrl()->without('enableDatasource')->without('disabled');
            $params = $url->getParams();
            foreach ($disabled as $key) {
                if ($key !== $enable) {
                    $params->add('disabled', $key);
                }
            }
            
            $this->redirectNow($url);
        }
    }
}
