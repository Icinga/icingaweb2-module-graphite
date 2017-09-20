<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Graphite\Forms\ConfigForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    public function indexAction()
    {
        $this->view->form = $form = new ConfigForm();
        $form->setIniConfig($this->Config())->handleRequest();
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backend');
    }
}
