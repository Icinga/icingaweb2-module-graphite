<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Graphite\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Zend_Form_Decorator_Interface;

/**
 * Wrap a decorator and use it only for rendering
 */
class Proxy extends Zend_Form_Decorator_Abstract
{
    /**
     * The actual decorator being proxied
     *
     * @var Zend_Form_Decorator_Interface
     */
    protected $actualDecorator;

    public function render($content)
    {
        return $this->actualDecorator->render($content);
    }

    /**
     * Get {@link actualDecorator}
     *
     * @return Zend_Form_Decorator_Interface
     */
    public function getActualDecorator()
    {
        return $this->actualDecorator;
    }

    /**
     * Set {@link actualDecorator}
     *
     * @param Zend_Form_Decorator_Interface $actualDecorator
     *
     * @return $this
     */
    public function setActualDecorator($actualDecorator)
    {
        $this->actualDecorator = $actualDecorator;
        return $this;
    }
}
