<?php

namespace Icinga\Module\Graphite\Forms;

use Icinga\Forms\ConfigForm as BaseConfigForm;

class ConfigForm extends BaseConfigForm
{
    public function init()
    {
        $this->setName('form_config_graphite');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'graphite_web_url',
            array(
                'required'      => true,
                'label'         => $this->translate('Graphite Web URL'),
                'description'   => $this->translate('URL to your Graphite Web'),
                'validators'    => ['UrlValidator']
            )
        );
    }
}
