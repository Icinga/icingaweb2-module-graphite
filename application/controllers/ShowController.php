<?php

use Icinga\Exception\NotFoundError;
use Icinga\Module\Graphite\GraphiteWeb;
use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Web\Controller;
use Icinga\Web\Widget;
use \DirectoryIterator;

class Graphite_ShowController extends Controller
{
    protected $baseUrl;

    protected $graphiteWeb;

    protected $templates;

    protected $filters;

    public function init()
    {
        $this->baseUrl = $this->Config()->get('global', 'web_url');
        $graphite = $this->graphiteWeb = new GraphiteWeb($this->baseUrl);
    }

    public function overviewAction()
    {
        $this->view->disabledDatasources = $disabled = $this->params->getValues('disabled');

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

        $this->view->tabs->add('overview', array(
            'label' => $this->translate('Graphite - Overview'),
            'url'   => $this->getRequest()->getUrl()
        ))->activate('overview');

        $this->view->filterColumn = $this->params->get('filterColumn');
        $this->view->filterValue  = $this->params->get('filterValue');
        $this->view->templateName = $this->params->get('templateName');

        $optional = array(null => '- please choose -');

        $this->view->templates = $optional;
        foreach ($this->loadTemplates() as $type => $template) {
            $this->view->templates[$type] = $template->getTitle();
        }
        asort($this->view->templates);

        $base = $this->Config()->get('global', 'host_pattern');

        $varnames = $this->graphiteWeb->select()->from($base)->extractVariableNames($base);
        $varnames = array_combine($varnames, $varnames);

        $this->view->filterColumns = $optional + $varnames;

        if ($this->view->filterColumn) {
            if (array_key_exists($this->view->filterColumn, $this->view->filterColumns)) {

                $this->view->filterValues = $optional + $this->graphiteWeb->select()
                    ->from($base)
                    ->listDistinct($this->view->filterColumn);
            }
        }

        if (! $this->view->filterColumn || ! $this->view->filterValue || ! $this->view->templateName) {
            return;
        }

        $imgs = array();

        $template = $this->loadTemplate($this->view->templateName);
        foreach ($disabled as $key) {
            $template->getDatasource($key)->disable();
        }

        // $template->setTitle('$hostname: ' . $template->getTitle());
        $template->setTitle('$hostname');
        $this->view->template = $template;

        $query = $this->graphiteWeb
            ->select()
            ->from(
                array('host' => $base),
                $template->getFilterString()
            )
            ->where($this->view->filterColumn, $this->view->filterValue);

        // TODO: $max  = $query->getMaxValue();
        $imgs = $query->getImages($template);

        foreach ($imgs as $img) {
            $img->setStart($this->params->get('start', '-1hours'))
                ->setWidth($this->params->get('width', '300'))
                ->setHeight($this->params->get('height', '150'))
                ->showLegend(false);
        }

        $this->view->images = $imgs;
    }

    public function hostAction()
    {
        $hostname = $this->view->hostname = $this->params->get('host');
        if (! $hostname) {
            throw new NotFoundError('Host is required');
        }
        $this->tabs()->activate('host');
        $hosts = $this->Config()->get('global', 'host_pattern');
        $imgs = array();

        foreach ($this->loadTemplates() as $type => $template) {

            $imgs[$type] = $this->graphiteWeb
                ->select()
                ->from(
                    array('host' => $hosts),
                    $template->getFilterString()
                )
                ->where('hostname', $hostname)
                ->getImages($template);

            foreach ($imgs[$type] as $img) {
                $img->setStart($this->params->get('start', '-1hours'))
                    ->setWidth($this->params->get('width', '300'))
                    ->setHeight($this->params->get('height', '200'))
                    ->showLegend(! $this->params->get('hideLegend', false));
            }
        }

        $this->view->images = $imgs;
    }

    protected function getTemplatePath($templateName = null)
    {
        $path = $this->Module()->getConfigDir() . '/templates';

        if ($templateName !== null) {
            $path .= '/' . $templateName . '.conf';
        }

        return $path;
    }

    protected function loadTemplate($name)
    {
        return $this->loadTemplates($name);
    }

    protected function loadTemplates($name = null)
    {
        $dir = $this->getTemplatePath();
        $templates = array();

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;
            $filename = $file->getFilename();
            if (substr($filename, -5) === '.conf') {
                $tname = substr($filename, 0, -5);
                if ($name !== null) {
                    if ($name !== $tname) continue;
                }
                $templates[$tname] = GraphTemplate::load(
                    file_get_contents($file->getPathname())
                );
            }
        }

        if ($name !== null) {
            if (! array_key_exists($name, $templates)) {
                throw new NotFoundError(
                    'The desired template "%s" doesn\'t exist',
                    $name
                );
            }

            return $templates[$name];
        }

        ksort($templates);
        return $templates;
    }

    protected function tabs()
    {
        return $this->view->tabs = Widget::create('tabs')->add('host', array(
            'label' => $this->translate('Graphite - Single Host'),
            'url' => $this->getRequest()->getUrl()
        ));
    }
}
