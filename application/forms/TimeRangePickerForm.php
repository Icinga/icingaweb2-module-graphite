<?php

namespace Icinga\Module\Graphite\Forms;

use DateInterval;
use DateTime;
use DateTimeZone;
use Icinga\Exception\NotImplementedError;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
use Icinga\Web\Url;

class TimeRangePickerForm extends Form
{
    /**
     * @var string
     */
    protected $dateTimeFormat = 'Y-m-d\TH:i';

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
        $this->setSubmitLabel($this->translate('Update'));
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

        $this->urlToForm('start');
        $this->urlToForm('end');
    }

    public function onSuccess()
    {
        $this->formToUrl('start', '00:00');
        $this->formToUrl('end', '23:59', 'PT59S');

        if ($this->urlHasBeenChanged) {
            $this->setRedirectUrl($this->getUrl());
        } else {
            return false;
        }
    }

    /**
     * Set this form's elements' default values based on {@link url}'s parameters
     *
     * @param   string  $part   Either 'start' or 'end'
     */
    protected function urlToForm($part)
    {
        $timestamp = $this->getUrl()->getParam("graph_$part", '');
        if (preg_match('/^(?:0|-?[1-9]\d*)$/', $timestamp)) {
            $timestamp = (int) $timestamp;
            if ($timestamp > 0) {
                list($date, $time) = explode(
                    'T',
                    DateTime::createFromFormat('U', $timestamp)
                        ->setTimezone($this->getTimeZone())
                        ->format($this->dateTimeFormat)
                );

                $this->getElement("{$part}_date")->setValue($date);
                $this->getElement("{$part}_time")->setValue($time);
            } else {
                // TODO(ak): relative to now
                throw new NotImplementedError('');
            }
        } else {
            $this->getElement("{$part}_date")->setValue('');
            $this->getElement("{$part}_time")->setValue('');

            $this->getUrl()->remove("graph_$part");

            $this->urlHasBeenChanged = true;
        }
    }

    /**
     * Change {@link url}'s parameters based on this form's elements' values
     *
     * @param   string  $part           Either 'start' or 'end'
     * @param   string  $defaultTime    Default if no time given
     * @param   string  $addInterval    Add this interval to the result
     */
    protected function formToUrl($part, $defaultTime, $addInterval = null)
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
