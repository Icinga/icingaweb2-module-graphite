<?php

namespace Icinga\Module\Graphite\Forms;

use Icinga\Forms\ConfigForm as BaseConfigForm;
use Icinga\Module\Graphite\Web\Form\Validator\HttpUserValidator;
use Icinga\Module\Graphite\Web\Form\Validator\MacroTemplateValidator;

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
                    'description'   => $this->translate(
                        'A user with access to your Graphite Web via HTTP basic authentication'
                    ),
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
            ],
            [
                'text',
                'icinga_graphite_writer_host_name_template',
                [
                    'label'         => $this->translate('Host name template'),
                    'description'   => $this->translate(
                        'The value of your Icinga 2 GraphiteWriter\'s'
                        . ' attribute host_name_template (if specified)'
                    ),
                    'validators'    => [new MacroTemplateValidator()]
                ]
            ],
            [
                'text',
                'icinga_graphite_writer_service_name_template',
                [
                    'label'         => $this->translate('Service name template'),
                    'description'   => $this->translate(
                        'The value of your Icinga 2 GraphiteWriter\'s'
                        . ' attribute service_name_template (if specified)'
                    ),
                    'validators'    => [new MacroTemplateValidator()]
                ]
            ]
        ]);
    }
}
