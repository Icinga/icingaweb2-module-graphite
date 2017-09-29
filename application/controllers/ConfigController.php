<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Graphite\Forms\Config\AdvancedForm;
use Icinga\Module\Graphite\Forms\Config\BackendForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/modules');
        parent::init();
    }

    public function backendAction()
    {
        $this->view->form = $form = new BackendForm();
        $form->setIniConfig($this->Config())->handleRequest();
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backend');
    }

    public function advancedAction()
    {
        $this->view->form = $form = new AdvancedForm();
        $form->setIniConfig($this->Config())->handleRequest();
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('advanced');
    }
}
