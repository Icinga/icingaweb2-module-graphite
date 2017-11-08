<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Graphite\Forms\TimeRangePicker\CustomForm;
use Icinga\Web\Controller;

class SubcontainerController extends Controller
{
    public function customtimerangepickerAction()
    {
        $this->view->form = $form = new CustomForm();
        $form->setRedirectUrl($this->getRequest()->getUrl()->getParams()->getRequired('redirect'))
            ->handleRequest();
    }
}
