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
