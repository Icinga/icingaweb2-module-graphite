<?php

namespace Icinga\Module\Graphite\Forms;

use DateInterval;
use DateTime;
use DateTimeZone;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Zend_Form_Element_Checkbox;

class TimeRangePickerForm extends Form
{
    /**
     * @var string
     */
    protected $dateTimeFormat = 'Y-m-d\TH:i';

    /**
     * @var string
     */
    protected $timestamp = '/^(?:0|-?[1-9]\d*)$/';

    /**
     * The selectable units with themselves in seconds
     *
     * One month equals 30 days and one year equals 365.25 days. This should cover enough cases.
     *
     * @var int[string]
     */
    protected $rangeFactors = [
        'years'     => 31557600,
        'months'    => 2592000,
        'weeks'     => 604800,
        'days'      => 86400,
        'hours'     => 3600,
        'minutes'   => 60
    ];

    /**
     * Whether this form has been requested for the first time
     *
     * @var bool
     */
    protected $initialRequest = false;

    /**
     * Whether this form asks for a custom date/time range
     *
     * @var bool
     */
    protected $customRange;

    /**
     * The elements' default values
     *
     * @var string[string]|null
     */
    protected $defaultFormData;

    /**
     * The URL to redirect to
     *
     * @var Url
     */
    protected $url;

    /**
     * Whether {@link url} has been changed
     *
     * @var bool
     */
    protected $urlHasBeenChanged = false;

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
        $this->setName('form_timerangepicker_graphite');
    }

    public function createElements(array $formData)
    {
        if ($this->initialRequest) {
            $this->customRange = true;
            if (! $this->getUrl()->hasParam('graph_end')) {
                $timestamp = $this->getUrl()->getParam('graph_start');
                if ($timestamp === null || preg_match($this->timestamp, $timestamp) && (int) $timestamp <= 0) {
                    $this->customRange = false;
                }
            }
        } else {
            $this->customRange = isset($formData['custom']) && $formData['custom'];
        }

        if ($this->customRange) {
            $this->addElement($this->getCustomCheckbox()->setChecked(true));

            $this->addElements([
                [
                    'date',
                    'start_date',
                    [
                        'label'         => $this->translate('Start Date'),
                        'description'   => $this->translate('Start date of the date/time range')
                    ]
                ],
                [
                    'time',
                    'start_time',
                    [
                        'label'         => $this->translate('Start Time'),
                        'description'   => $this->translate('Start time of the date/time range')
                    ]
                ],
                [
                    'date',
                    'end_date',
                    [
                        'label'         => $this->translate('End Date'),
                        'description'   => $this->translate('End date of the date/time range')
                    ]
                ],
                [
                    'time',
                    'end_time',
                    [
                        'label'         => $this->translate('End Time'),
                        'description'   => $this->translate('End time of the date/time range')
                    ]
                ]
            ]);

            $this->setSubmitLabel($this->translate('Update'));

            $this->urlToCustom('start');
            $this->urlToCustom('end');
        } else {
            $this->addElements([
                [
                    'select',
                    'minutes',
                    [
                        'label'         => $this->translate('Minutes'),
                        'description'   => $this->translate('Show the last … minutes'),
                        'multiOptions'  => $this->generateMultiOptions([5, 10, 15, 30, 45]),
                        'autosubmit'    => true
                    ]
                ],
                [
                    'select',
                    'hours',
                    [
                        'label'         => $this->translate('Hours'),
                        'description'   => $this->translate('Show the last … hours'),
                        'multiOptions'  => $this->generateMultiOptions([1, 2, 3, 6, 12, 18]),
                        'autosubmit'    => true
                    ]
                ],
                [
                    'select',
                    'days',
                    [
                        'label'         => $this->translate('Days'),
                        'description'   => $this->translate('Show the last … days'),
                        'multiOptions'  => $this->generateMultiOptions(range(1, 6)),
                        'autosubmit'    => true
                    ]
                ],
                [
                    'select',
                    'weeks',
                    [
                        'label'         => $this->translate('Weeks'),
                        'description'   => $this->translate('Show the last … weeks'),
                        'multiOptions'  => $this->generateMultiOptions(range(1, 4)),
                        'autosubmit'    => true
                    ]
                ],
                [
                    'select',
                    'months',
                    [
                        'label'         => $this->translate('Months'),
                        'description'   => $this->translate('Show the last … months'),
                        'multiOptions'  => $this->generateMultiOptions([1, 2, 3, 6, 9]),
                        'autosubmit'    => true
                    ]
                ],
                [
                    'select',
                    'years',
                    [
                        'label'         => $this->translate('Years'),
                        'description'   => $this->translate('Show the last … years'),
                        'multiOptions'  => $this->generateMultiOptions(range(1, 3)),
                        'autosubmit'    => true
                    ]
                ]
            ]);

            $this->addElement($this->getCustomCheckbox());

            $this->urlToCommon();

            $this->defaultFormData = $this->getValues();
        }
    }

    public function onRequest()
    {
        $this->initialRequest = true;
    }

    public function onSuccess()
    {
        if ($this->customRange) {
            $this->customToUrl('start', '00:00');
            $this->customToUrl('end', '23:59', 'PT59S');
        } else {
            $this->commonToUrl();
        }

        if ($this->urlHasBeenChanged) {
            $this->setRedirectUrl($this->getUrl());
        } else {
            return false;
        }
    }

    /**
     * @return Zend_Form_Element_Checkbox
     */
    protected function getCustomCheckbox()
    {
        return $this->createElement('checkbox', 'custom', [
            'label'         => $this->translate('Custom'),
            'description'   => $this->translate('Provide a custom date/time range'),
            'autosubmit'    => true
        ]);
    }

    /**
     * Generate an array suitable for a selection form element's multiOptions
     *
     * E.g.: $this->generateMultiOptions([15, 30, 45]) === ['' => '-', '15' => '15', '30' => '30', '45' => '45']
     *
     * @param   array   $options
     *
     * @return  string[string]
     */
    protected function generateMultiOptions(array $options)
    {
        $result = ['' => '-'];
        foreach ($options as $option) {
            $result[$option] = (string) $option;
        }
        return $result;
    }

    /**
     * Set this form's elements' default values based on {@link url}'s parameters
     */
    protected function urlToCommon()
    {
        $timestamp = $this->getUrl()->getParam('graph_start');
        if ($timestamp !== null) {
            if (preg_match($this->timestamp, $timestamp)) {
                $timestamp = (int) $timestamp;

                if ($timestamp <= 0) {
                    $seconds = - $timestamp;

                    foreach ($this->rangeFactors as $unit => $factor) {
                        /** @var \Zend_Form_Element_Select $element */
                        $element = $this->getElement($unit);

                        $options = $element->getMultiOptions();
                        unset($options['']);
                        krsort($options);

                        foreach ($options as $option => $_) {
                            if ($seconds >= $option * $factor) {
                                $element->setValue((string) $option);
                                return;
                            }
                        }
                    }

                    $this->getElement('minutes')->setValue('5');
                }
            } else {
                $this->getUrl()->remove('graph_start');
                $this->urlHasBeenChanged = true;
            }
        }
    }

    /**
     * Set this form's elements' default values based on {@link url}'s parameters
     *
     * @param   string  $part   Either 'start' or 'end'
     */
    protected function urlToCustom($part)
    {
        $timestamp = $this->getUrl()->getParam("graph_$part");
        if ($timestamp !== null) {
            if (preg_match($this->timestamp, $timestamp)) {
                $timestamp = (int) $timestamp;
                if ($timestamp < 0) {
                    $timestamp += $this->getNow()->getTimestamp();
                }

                list($date, $time) = explode(
                    'T',
                    DateTime::createFromFormat('U', $timestamp)
                        ->setTimezone($this->getTimeZone())
                        ->format($this->dateTimeFormat)
                );

                $this->getElement("{$part}_date")->setValue($date);
                $this->getElement("{$part}_time")->setValue($time);
            } else {
                $this->getUrl()->remove("graph_$part");
                $this->urlHasBeenChanged = true;
            }
        }
    }

    /**
     * Change {@link url}'s parameters based on this form's elements' values
     */
    protected function commonToUrl()
    {
        $formData = $this->getValues();
        foreach ($this->rangeFactors as $unit => $factor) {
            if ($formData[$unit] !== '' && $formData[$unit] !== $this->defaultFormData[$unit]) {
                $params = $this->getUrl()->getParams();
                $params->set('graph_start', (string) - ((int) $formData[$unit] * $factor));
                $params->remove('graph_end');
                $this->urlHasBeenChanged = true;
                return;
            }
        }
    }

    /**
     * Change {@link url}'s parameters based on this form's elements' values
     *
     * @param   string  $part           Either 'start' or 'end'
     * @param   string  $defaultTime    Default if no time given
     * @param   string  $addInterval    Add this interval to the result
     */
    protected function customToUrl($part, $defaultTime, $addInterval = null)
    {
        $date = $this->getValue("{$part}_date");
        $time = $this->getValue("{$part}_time");
        if (! ($date === '' && $time === '')) {
            $dateTime = DateTime::createFromFormat(
                $this->dateTimeFormat,
                ($date === '' ? $this->getNow()->format('Y-m-d') : $date) . 'T' . ($time === '' ? $defaultTime : $time),
                $this->getTimeZone()
            );

            if ($dateTime === false) {
                $this->getElement("{$part}_date")->setValue('');
                $this->getElement("{$part}_time")->setValue('');

                $this->getUrl()->remove("graph_$part");
            } else {
                if ($dateTime->getTimestamp() < 1) {
                    $dateTime = DateTime::createFromFormat('U', '1')->setTimezone($this->getTimeZone());
                }

                if ($addInterval !== null) {
                    $dateTime->add(new DateInterval($addInterval));
                }

                $this->getUrl()->setParam("graph_$part", $dateTime->format('U'));
            }

            $this->urlHasBeenChanged = true;
        }
    }

    /**
     * Get {@link url}
     *
     * @return Url
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest();
        }

        return $this->url;
    }

    /**
     * Set {@link url}
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url)
    {
        $this->url = $url;
        $this->urlHasBeenChanged = false;
        return $this;
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
            $this->now = (new DateTime('now'))->setTimezone($this->getTimeZone());
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
    public function setNow(DateTime $now)
    {
        $this->now = $now;
        return $this;
    }
}
