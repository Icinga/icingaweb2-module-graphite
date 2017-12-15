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
                'number',
                'ui_default_time_range',
                [
                    'label'         => $this->translate('Default time range'),
                    'description'   => $this->translate('The default time range for graphs'),
                    'min'           => 1,
                    'value'         => 1
                ]
            ],
            [
                'select',
                'ui_default_time_range_unit',
                [
                    'label'         => $this->translate('Default time range unit'),
                    'description'   => $this->translate('The above range\'s unit'),
                    'multiOptions'  => [
                        'minutes'   => $this->translate('Minutes'),
                        'hours'     => $this->translate('Hours'),
                        'days'      => $this->translate('Days'),
                        'weeks'     => $this->translate('Weeks'),
                        'months'    => $this->translate('Months'),
                        'years'     => $this->translate('Years')
                    ],
                    'value'         => 'hours'
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
