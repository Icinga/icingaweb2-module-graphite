<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use Icinga\Web\Form;
use Icinga\Web\View;
use Zend_Form_Decorator_HtmlTag;
use Zend_Form_Element;
use Zend_Form_Element_Select;

class CommonForm extends Form
{
    use TimeRangePickerTrait;

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
    }

    public function createElements(array $formData)
    {
        /** @var View $view */
        $view = $this->getView();

        $this->addElement($this->reduceDecorators($this->createElement(
            'note',
            'custom_range',
            [
                'description'   => $this->translate('Specify custom time range'),
                'escape'        => false,
                'value'         => sprintf(
                    '<span class="subcontainer" id="form_timerangepickercommon_graphite-custom_range-%s">%s%s</span>',
                    md5($this->getRequest()->getUrl()->getAbsoluteUrl()),
                    $view->qlink(
                        null,
                        '#',
                        null,
                        [
                            'class' => 'button-link subcontainer-toggle',
                            'icon'  => 'service'
                        ]
                    ),
                    sprintf(
                        '<div class="subcontainer-content" data-icinga-url="%s"></div>',
                        $view->url(
                            'graphite/subcontainer/customtimerangepicker',
                            ['redirect' => $this->getRequest()->getUrl()->getAbsoluteUrl()]
                        )
                    )
                )
            ]
        )));

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
        $this->getRedirectUrl()->remove(array_values(static::getAbsoluteRangeParameters()));
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
            'autosubmit'    => true
        ]);

        return $this->reduceDecorators($element);
    }

    /**
     * Reduce the decorators set of the given element to the minimum
     *
     * @param   Zend_Form_Element   $element
     *
     * @return  Zend_Form_Element
     */
    protected function reduceDecorators(Zend_Form_Element $element)
    {
        $decorators = $element->getDecorators();

        return $element->setDecorators([
            'Zend_Form_Decorator_ViewHelper'    => $decorators['Zend_Form_Decorator_ViewHelper'],
            'Zend_Form_Decorator_HtmlTag'       => new Zend_Form_Decorator_HtmlTag([
                'tag'   => 'span',
                'title' => $element->getDescription()
            ])
        ]);
    }

    /**
     * Set this form's elements' default values based on the redirect URL's parameters
     */
    protected function urlToForm()
    {
        if ($this->preSelectDefault()) {
            return;
        }

        $params = $this->getRedirectUrl()->getParams();
        $seconds = $this->getRelativeSeconds($params);

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

            $params->remove(static::getRelativeRangeParameter());
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
                    static::getRelativeRangeParameter(),
                    (string) ((int) $formData[$unit] * $factor)
                );
                return;
            }
        }
    }

    /**
     * If no range is specified, pre-select "1 hour"
     *
     * @return  bool    Whether no range is specified
     */
    protected function preSelectDefault()
    {
        $params = $this->getRedirectUrl()->getParams();
        foreach (static::getAllRangeParameters() as $parameter) {
            if ($params->has($parameter)) {
                return false;
            }
        }

        $this->getElement('hours')->setValue('1');

        return true;
    }
}
