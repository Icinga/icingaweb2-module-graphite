<?php

namespace Icinga\Module\Graphite\Web\Controller;

use Icinga\Module\Graphite\Forms\TimeRangePicker\CommonForm;
use Icinga\Module\Graphite\Forms\TimeRangePicker\CustomForm;
use Icinga\Module\Graphite\Forms\TimeRangePicker\TimeRangePickerTrait as TimeRangePicker;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Web\View;

trait TimeRangePickerTrait
{
    /**
     * @var CommonForm
     */
    protected $timeRangePickerCommonForm;

    /**
     * @var CustomForm
     */
    protected $timeRangePickerCustomForm;

    /**
     * Process the given request using the forms
     *
     * @param   Request $request    The request to be processed
     *
     * @return  Request             The request supposed to be processed
     */
    protected function handleTimeRangePickerRequest(Request $request = null)
    {
        $this->getTimeRangePickerCommonForm()->handleRequest($request);
        return $this->getTimeRangePickerCustomForm()->handleRequest($request);
    }

    /**
     * Render all needed forms and links
     *
     * @param   View    $view
     *
     * @return  string
     */
    protected function renderTimeRangePicker(View $view)
    {
        $result = $this->getTimeRangePickerCommonForm();
        $url = Url::fromRequest();
        $relevantParams = TimeRangePicker::getAllRangeParameters();

        foreach ($relevantParams as $param) {
            if ($url->hasParam($param)) {
                $result .= $view->qlink(
                    $view->translate('Clear', 'TimeRangePicker'),
                    $url->without($relevantParams),
                    null,
                    ['class' => 'button-link']
                );
                break;
            }
        }

        if ($url->hasParam(TimeRangePicker::getRangeCustomizationParameter())) {
            $result .= $this->getTimeRangePickerCustomForm();
        } else {
            $result .= $view->qlink(
                $view->translate('Custom', 'TimeRangePicker'),
                $url->with(TimeRangePicker::getRangeCustomizationParameter(), '1'),
                null,
                ['class' => 'button-link']
            );
        }

        return '<div class="timerangepicker-forms">' . $result . '</div>';
    }

    /**
     * Get {@link timeRangePickerCommonForm}
     *
     * @return CommonForm
     */
    public function getTimeRangePickerCommonForm()
    {
        if ($this->timeRangePickerCommonForm === null) {
            $this->timeRangePickerCommonForm = new CommonForm();
        }

        return $this->timeRangePickerCommonForm;
    }

    /**
     * Set {@link timeRangePickerCommonForm}
     *
     * @param CommonForm $timeRangePickerCommonForm
     *
     * @return $this
     */
    public function setTimeRangePickerCommonForm(CommonForm $timeRangePickerCommonForm)
    {
        $this->timeRangePickerCommonForm = $timeRangePickerCommonForm;
        return $this;
    }

    /**
     * Get {@link timeRangePickerCustomForm}
     *
     * @return CustomForm
     */
    public function getTimeRangePickerCustomForm()
    {
        if ($this->timeRangePickerCustomForm === null) {
            $this->timeRangePickerCustomForm = new CustomForm();
        }

        return $this->timeRangePickerCustomForm;
    }

    /**
     * Set {@link timeRangePickerCustomForm}
     *
     * @param CustomForm $timeRangePickerCustomForm
     *
     * @return $this
     */
    public function setTimeRangePickerCustomForm(CustomForm $timeRangePickerCustomForm)
    {
        $this->timeRangePickerCustomForm = $timeRangePickerCustomForm;
        return $this;
    }
}
