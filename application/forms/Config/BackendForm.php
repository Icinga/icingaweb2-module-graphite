<?php

namespace Icinga\Module\Graphite\Forms\Config;

use Icinga\Forms\ConfigForm;
use Icinga\Module\Graphite\Web\Form\Validator\HttpUserValidator;

class BackendForm extends ConfigForm
{
    public function init()
    {
        $this->setName('form_config_graphite_backend');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElements([
            [
                'text',
                'graphite_url',
                [
                    'required'      => true,
                    'label'         => $this->translate('Graphite Web URL'),
                    'description'   => $this->translate('URL to your Graphite Web'),
                    'validators'    => ['UrlValidator']
                ]
            ],
            [
                'text',
                'graphite_user',
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
                'graphite_password',
                [
                    'renderPassword'    => true,
                    'label'             => $this->translate('Graphite Web password'),
                    'description'       => $this->translate('The above user\'s password')
                ]
            ],
            [
                'checkbox',
                'graphite_insecure',
                [
                    'label'         => $this->translate('Connect insecurely'),
                    'description'   => $this->translate('Check this to not verify the remote\'s TLS certificate')
                ]
            ],
            [
                'number',
                'graphite_timeout',
                [
                    'label'         => $this->translate('Request timeout'),
                    'description'   => $this->translate('The timeout for HTTP requests to Graphite Web'),
                    'min'           => 0,
                    'placeholder'   => 10
                ]
            ]
        ]);
    }
}
