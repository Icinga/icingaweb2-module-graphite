<?php

namespace Icinga\Module\Graphite\Forms;

use Icinga\Forms\ConfigForm as BaseConfigForm;
use Icinga\Module\Graphite\Web\Form\Validator\HttpUserValidator;

class ConfigForm extends BaseConfigForm
{
    public function init()
    {
        $this->setName('form_config_graphite');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElements([
            [
                'text',
                'graphite_web_url',
                [
                    'required'      => true,
                    'label'         => $this->translate('Graphite Web URL'),
                    'description'   => $this->translate('URL to your Graphite Web'),
                    'validators'    => ['UrlValidator']
                ]
            ],
            [
                'text',
                'graphite_web_user',
                [
                    'label'         => $this->translate('Graphite Web user'),
                    'description'   => $this->translate('A user with access to your Graphite Web'),
                    'validators'    => [new HttpUserValidator()]
                ]
            ],
            [
                'password',
                'graphite_web_password',
                [
                    'label'         => $this->translate('Graphite Web password'),
                    'description'   => $this->translate('The above user\'s password')
                ]
            ]
        ]);
    }
}
