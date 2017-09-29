<?php

namespace Icinga\Module\Graphite\Forms\Config;

use Icinga\Forms\ConfigForm;
use Icinga\Module\Graphite\Web\Form\Validator\MacroTemplateValidator;

class AdvancedForm extends ConfigForm
{
    public function init()
    {
        $this->setName('form_config_graphite_advanced');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {
        $this->addElements([
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
