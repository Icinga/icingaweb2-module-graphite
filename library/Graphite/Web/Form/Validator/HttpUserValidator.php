<?php

namespace Icinga\Module\Graphite\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validates http basic authn user names
 *
 * TODO(ak): move to Icinga Web 2
 */
class HttpUserValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = ['HAS_COLON' => mt('graphite', 'The username must not contain colons.')];
    }

    public function isValid($value)
    {
        $hasColon = false !== strpos($value, ':');
        if ($hasColon) {
            $this->_error('HAS_COLON');
        }
        return ! $hasColon;
    }
}
