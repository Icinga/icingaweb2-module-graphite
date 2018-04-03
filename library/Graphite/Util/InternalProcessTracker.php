<?php

namespace Icinga\Module\Graphite\Util;
use Icinga\Authentication\Auth;
use Icinga\Security\SecurityException;

/**
 * A record about what happened during a specific action
 */
class InternalProcessTracker
{
    /**
     * Whether to record anything
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * How many '+'es to prepend to each new record
     *
     * @var int
     */
    private static $indentation = 1;

    /**
     * The recorded happenings
     *
     * @var string[]
     */
    private static $records = [];

    /**
     * Get whether recording is enabled
     *
     * @return bool
     */
    public static function enabled()
    {
        return self::$enabled;
    }

    /**
     * Enable recording
     *
     * @throws SecurityException
     */
    public static function enable()
    {
        if (! Auth::getInstance()->hasPermission('graphite/debug')) {
            throw new SecurityException('No permission for graphite/debug');
        }

        self::$enabled = true;
    }

    /**
     * Introduce a "sub-process"
     */
    public static function indent()
    {
        if (self::$enabled) {
            ++self::$indentation;
        }
    }

    /**
     * Record a happening
     *
     * Behaves like {@link sprintf()} if additional arguments given, but {@link var_export()}s the arguments first
     * (so always use %s instead of e.g. %d).
     *
     * @param   string  $format
     */
    public static function recordf($format)
    {
        if (self::$enabled) {
            if (func_num_args() > 1) {
                $args = [];
                foreach (array_slice(func_get_args(), 1) as $arg) {
                    $args[] = var_export($arg, true);
                }

                $format = vsprintf($format, $args);
            }

            self::$records[] = str_repeat('+', self::$indentation) . " $format";
        }
    }

    /**
     * Terminate a "sub-process"
     */
    public static function unindent()
    {
        if (self::$enabled) {
            --self::$indentation;
        }
    }

    /**
     * Dump everything recorded as plain text
     *
     * @return string
     */
    public static function dump()
    {
        return implode("\n", self::$records);
    }

    /**
     * Reset records
     */
    public static function clear()
    {
        if (self::$enabled) {
            self::$indentation = 1;
            self::$records = [];
        }
    }

    final private function __construct()
    {
    }
}
