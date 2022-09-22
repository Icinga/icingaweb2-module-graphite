<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use Icinga\Module\Graphite\Util\TimeRangePickerTools;
use Icinga\Web\Form;
use Zend_Form_Element_Select;

class CommonForm extends Form
{
    /**
     * The selectable units with themselves in seconds
     *
     * One month equals 30 days and one year equals 365.25 days. This should cover enough cases.
     *
     * @var int[]
     */
    protected $rangeFactors = [
        'minutes'   => 60,
        'hours'     => 3600,
        'days'      => 86400,
        'weeks'     => 604800,
        'months'    => 2592000,
        'years'     => 31557600
    ];

    /**
     * The elements' default values
     *
     * @var string[]|null
     */
    protected $defaultFormData;

    public function init()
    {
        $this->setName('form_timerangepickercommon_graphite');
        $this->setAttrib('data-base-target', '_self');
        $this->setAttrib('class', 'icinga-form icinga-controls inline');
    }

    public function createElements(array $formData)
    {
        $this->addElements([
            $this->createSelect(
                'minutes',
                $this->translate('Minutes'),
                $this->translate('Show the last … minutes'),
                [5, 10, 15, 30, 45],
                $this->translate('%d minute'),
                $this->translate('%d minutes')
            ),
            $this->createSelect(
                'hours',
                $this->translate('Hours'),
                $this->translate('Show the last … hours'),
                [1, 2, 3, 6, 12, 18],
                $this->translate('%d hour'),
                $this->translate('%d hours')
            ),
            $this->createSelect(
                'days',
                $this->translate('Days'),
                $this->translate('Show the last … days'),
                range(1, 6),
                $this->translate('%d day'),
                $this->translate('%d days')
            ),
            $this->createSelect(
                'weeks',
                $this->translate('Weeks'),
                $this->translate('Show the last … weeks'),
                range(1, 4),
                $this->translate('%d week'),
                $this->translate('%d weeks')
            ),
            $this->createSelect(
                'months',
                $this->translate('Months'),
                $this->translate('Show the last … months'),
                [1, 2, 3, 6, 9],
                $this->translate('%d month'),
                $this->translate('%d months')
            ),
            $this->createSelect(
                'years',
                $this->translate('Years'),
                $this->translate('Show the last … years'),
                range(1, 3),
                $this->translate('%d year'),
                $this->translate('%d years')
            )
        ]);

        $this->urlToForm();

        $this->defaultFormData = $this->getValues();
    }

    public function onSuccess()
    {
        $this->formToUrl();
        $this->getRedirectUrl()->remove(array_values(TimeRangePickerTools::getAbsoluteRangeParameters()));
    }

    /**
     * Create a common range picker for a specific time unit
     *
     * @param   string  $name
     * @param   string  $label
     * @param   string  $description
     * @param   int[]   $options
     * @param   string  $singular
     * @param   string  $plural
     *
     * @return  Zend_Form_Element_Select
     */
    protected function createSelect($name, $label, $description, array $options, $singular, $plural)
    {
        $multiOptions = ['' => $label];
        foreach ($options as $option) {
            $multiOptions[$option] = sprintf($option === 1 ? $singular : $plural, $option);
        }

        $element = $this->createElement('select', $name, [
            'label'         => $label,
            'description'   => $description,
            'multiOptions'  => $multiOptions,
            'title'         => $description,
            'autosubmit'    => true
        ]);

        $decorators = $element->getDecorators();
        $element->setDecorators([
            'Zend_Form_Decorator_ViewHelper'    => $decorators['Zend_Form_Decorator_ViewHelper']
        ]);

        return $element;
    }

    /**
     * Set this form's elements' default values based on the redirect URL's parameters
     */
    protected function urlToForm()
    {
        $params = $this->getRedirectUrl()->getParams();
        $seconds = TimeRangePickerTools::getRelativeSeconds($params);

        if (
            $seconds === null
            && count(array_intersect_key(
                $params->toArray(false),
                array_keys(TimeRangePickerTools::getAllRangeParameters())
            )) === 0
        ) {
            $seconds = TimeRangePickerTools::getDefaultRelativeTimeRange();
        }

        if ($seconds !== null) {
            if ($seconds !== false) {
                foreach ($this->rangeFactors as $unit => $factor) {
                    /** @var Zend_Form_Element_Select $element */
                    $element = $this->getElement($unit);

                    $options = $element->getMultiOptions();
                    unset($options['']);

                    foreach ($options as $option => $_) {
                        if ($seconds === $option * $factor) {
                            $element->setValue((string) $option);
                            return;
                        }
                    }
                }
            }

            $params->remove(TimeRangePickerTools::getRelativeRangeParameter());
        }
    }

    /**
     * Change the redirect URL's parameters based on this form's elements' values
     */
    protected function formToUrl()
    {
        $formData = $this->getValues();
        foreach ($this->rangeFactors as $unit => $factor) {
            if ($formData[$unit] !== '' && $formData[$unit] !== $this->defaultFormData[$unit]) {
                $this->getRedirectUrl()->setParam(
                    TimeRangePickerTools::getRelativeRangeParameter(),
                    (string) ((int) $formData[$unit] * $factor)
                );
                return;
            }
        }
    }
}
