<?php

namespace Icinga\Module\Graphite\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Provides an easy way to implement validators with custom error messages
 *
 * TODO(ak): move to framework(?)
 */
abstract class CustomErrorMessagesValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = ['CUSTOM_ERROR' => ''];
    }

    public function isValid($value)
    {
        $errorMessage = $this->validate($value);
        if ($errorMessage === null) {
            return true;
        }

        $this->setMessage($errorMessage, 'CUSTOM_ERROR');
        $this->_error('CUSTOM_ERROR');
        return false;
    }

    /**
     * Validate the given value and return an error message if it's invalid
     *
     * @param   string  $value
     *
     * @return  string|null
     */
    abstract protected function validate($value);
}
