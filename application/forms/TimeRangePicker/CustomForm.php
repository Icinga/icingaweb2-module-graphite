<?php

namespace Icinga\Module\Graphite\Forms\TimeRangePicker;

use DateInterval;
use DateTime;
use DateTimeZone;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;

class CustomForm extends Form
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
     * The time zone of all dates and times
     *
     * @var DateTimeZone
     */
    protected $timeZone;

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

        $this->urlToForm('start');
        $this->urlToForm('end');
    }

    public function onSuccess()
    {
        $this->formToUrl('start', '00:00');
        $this->formToUrl('end', '23:59', 'PT59S');
        $this->getRedirectUrl()->remove('graph_range');
    }

    /**
     * Set this form's elements' default values based on the redirect URL's parameters
     *
     * @param   string  $part   Either 'start' or 'end'
     */
    protected function urlToForm($part)
    {
        $params = $this->getRedirectUrl()->getParams();
        $timestamp = $params->get("graph_$part");

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
     * Change the redirect URL's parameters based on this form's elements' values
     *
     * @param   string  $part           Either 'start' or 'end'
     * @param   string  $defaultTime    Default if no time given
     * @param   string  $addInterval    Add this interval to the result
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
                ($date === '' ? (new DateTime())->setTimezone($this->getTimeZone())->format('Y-m-d') : $date)
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
}
