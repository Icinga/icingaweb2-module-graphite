<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Graphite\Web\Form\Validator;

use Icinga\Module\Graphite\Util\MacroTemplate;
use InvalidArgumentException;

/**
 * Validates Icinga-style macro templates
 */
class MacroTemplateValidator extends CustomErrorMessagesValidator
{
    protected function validate($value)
    {
        try {
            new MacroTemplate($value);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }
}
