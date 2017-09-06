<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use DateInterval;
use DateTime;
use DateTimeZone;
use Icinga\Module\Graphite\Web\Form\Decorator\Proxy;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;

class CustomForm extends Form
{
    use TimeRangePickerTrait;

    /**
     * @var string
     */
    protected $dateTimeFormat = 'Y-m-d\TH:i';

    /**
     * @var string
     */
    protected $timestamp = '/^(?:0|-?[1-9]\d*)$/';

    /**
     * The time zone of all dates and times
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * Right now
     *
     * @var DateTime
     */
    protected $now;

    public function init()
    {
        $this->setName('form_timerangepickercustom_graphite');
    }

    public function createElements(array $formData)
    {
        $this->addElements([
            [
                'date',
                'start_date',
                [
                    'label'         => $this->translate('Start'),
                    'description'   => $this->translate('Start of the date/time range')
                ]
            ],
            [
                'time',
                'start_time',
                [
                    'label'         => $this->translate('Start'),
                    'description'   => $this->translate('Start of the date/time range')
                ]
            ],
            [
                'date',
                'end_date',
                [
                    'label'         => $this->translate('End'),
                    'description'   => $this->translate('End of the date/time range')
                ]
            ],
            [
                'time',
                'end_time',
                [
                    'label'         => $this->translate('End'),
                    'description'   => $this->translate('End of the date/time range')
                ]
            ]
        ]);

        $this->groupDateTime('start');
        $this->groupDateTime('end');

        $this->setSubmitLabel($this->translate('Update'));

        $this->urlToForm('start', $this->getRelativeTimestamp());
        $this->urlToForm('end');
    }

    public function onSuccess()
    {
        $start = $this->formToUrl('start', '00:00');
        $end = $this->formToUrl('end', '23:59', 'PT59S');
        if ($start > $end) {
            $this->getRedirectUrl()->getParams()
                ->set('graph_start', $end)
                ->set('graph_end', $start);
        }

        $this->getRedirectUrl()->remove('graph_range');
    }

    /**
     * Add display group for a date and a time input belonging together
     *
     * @param   string  $part   Either 'start' or 'end'
     */
    protected function groupDateTime($part)
    {
        $this->addDisplayGroup(["{$part}_date", "{$part}_time"], $part);
        $group = $this->getDisplayGroup($part);

        foreach ($group->getElements() as $element) {
            /** @var \Zend_Form_Element $element */

            $elementDecorators = $element->getDecorators();
            $element->setDecorators([
                'Zend_Form_Decorator_ViewHelper' => $elementDecorators['Zend_Form_Decorator_ViewHelper']
            ]);
        }

        $decorators = [];
        foreach ($elementDecorators as $key => $decorator) {
            if ($key === 'Zend_Form_Decorator_ViewHelper') {
                $decorators['Zend_Form_Decorator_FormElements'] = $group->getDecorators()['Zend_Form_Decorator_FormElements'];
            } else {
                $decorators[$key] = (new Proxy())->setActualDecorator($decorator->setElement($element));
            }
        }

        $group->setDecorators($decorators);
    }

    /**
     * Set this form's elements' default values based on the redirect URL's parameters
     *
     * @param   string  $part               Either 'start' or 'end'
     * @param   int     $defaultTimestamp   Fallback
     */
    protected function urlToForm($part, $defaultTimestamp = null)
    {
        $params = $this->getRedirectUrl()->getParams();
        $timestamp = $params->get("graph_$part", $defaultTimestamp);

        if ($timestamp !== null) {
            if (preg_match($this->timestamp, $timestamp)) {
                list($date, $time) = explode(
                    'T',
                    DateTime::createFromFormat('U', $timestamp)
                        ->setTimezone($this->getTimeZone())
                        ->format($this->dateTimeFormat)
                );

                $this->getElement("{$part}_date")->setValue($date);
                $this->getElement("{$part}_time")->setValue($time);
            } else {
                $params->remove("graph_$part");
            }
        }
    }

    /**
     * Get the relative range start (if any) set by {@link CommonForm}
     *
     * @return int|null
     */
    protected function getRelativeTimestamp()
    {
        $seconds = $this->getRelativeSeconds($this->getRedirectUrl()->getParams());
        return is_int($seconds) ? $this->getNow()->getTimestamp() - $seconds : null;
    }

    /**
     * Change the redirect URL's parameters based on this form's elements' values
     *
     * @param   string  $part           Either 'start' or 'end'
     * @param   string  $defaultTime    Default if no time given
     * @param   string  $addInterval    Add this interval to the result
     *
     * @return  int|null                The updated timestamp (if any)
     */
    protected function formToUrl($part, $defaultTime, $addInterval = null)
    {
        $date = $this->getValue("{$part}_date");
        $time = $this->getValue("{$part}_time");
        $params = $this->getRedirectUrl()->getParams();

        if ($date === '' && $time === '') {
            $params->remove("graph_$part");
        } else {
            $dateTime = DateTime::createFromFormat(
                $this->dateTimeFormat,
                ($date === '' ? $this->getNow()->format('Y-m-d') : $date)
                    . 'T' . ($time === '' ? $defaultTime : $time),
                $this->getTimeZone()
            );

            if ($dateTime === false) {
                $params->remove("graph_$part");
            } else {
                if ($addInterval !== null) {
                    $dateTime->add(new DateInterval($addInterval));
                }

                $params->set("graph_$part", $dateTime->format('U'));
                return $dateTime->getTimestamp();
            }
        }
    }

    /**
     * Get {@link timeZone}
     *
     * @return DateTimeZone
     */
    public function getTimeZone()
    {
        if ($this->timeZone === null) {
            $timezoneDetect = new TimezoneDetect();
            $this->timeZone = new DateTimeZone(
                $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
            );
        }

        return $this->timeZone;
    }

    /**
     * Set {@link timeZone}
     *
     * @param DateTimeZone $timeZone
     *
     * @return $this
     */
    public function setTimeZone(DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     * Get {@link now}
     *
     * @return DateTime
     */
    public function getNow()
    {
        if ($this->now === null) {
            $this->now = (new DateTime())->setTimezone($this->getTimeZone());
        }

        return $this->now;
    }

    /**
     * Set {@link now}
     *
     * @param DateTime $now
     *
     * @return $this
     */
    public function setNow($now)
    {
        $this->now = $now;
        return $this;
    }
}
