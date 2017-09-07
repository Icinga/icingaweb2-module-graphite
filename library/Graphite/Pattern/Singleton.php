<?php

namespace Icinga\Module\Graphite\Pattern;

use Exception;

/**
 * Everything needed for a singleton class
 */
trait Singleton
{
    /**
     * The only instance of a class
     *
     * @var static
     */
    private static $instance;

    /**
     * Initialize and return {@link instance}
     *
     * @return static
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    final private function __construct()
    {
        $this->init();
    }

    /**
     * Ensure that no one can clone the object
     */
    final private function __clone()
    {
        throw new Exception('Won\'t clone a singleton');
    }

    /**
     * Initializer
     *
     * May be overridden.
     */
    private function init()
    {
    }
}
