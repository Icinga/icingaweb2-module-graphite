<?php

namespace Icinga\Module\Graphite\Util;

use InvalidArgumentException;

/**
 * A macro-based template for strings
 */
class MacroTemplate
{
    /**
     * Macros' start and end character
     *
     * @var string
     */
    protected $macroCharacter;

    /**
     * The parsed template
     *
     * @var string[]
     */
    protected $template;

    /**
     * Constructor
     *
     * @param   string  $template           The raw template
     * @param   string  $macroCharacter     Macros' start and end character
     */
    public function __construct($template, $macroCharacter = '$')
    {
        $this->macroCharacter = $macroCharacter;
        $this->template = explode($macroCharacter, $template);

        if (! (count($this->template) % 2)) {
            throw new InvalidArgumentException(
                'template contains odd number of ' . var_export($macroCharacter, true)
                    . 's: ' . var_export($template, true)
            );
        }
    }

    /**
     * Return a string based on this template with the macros resolved from the given variables
     *
     * @param   string[string]  $variables
     * @param   string          $default    The default value for missing variables.
     *                                      By default the macro just isn't replaced.
     *
     * @return  string
     */
    public function resolve(array $variables, $default = null)
    {
        $macro = false;
        $result = []; // kind of string builder

        foreach ($this->template as $part) {
            if ($macro) {
                if (isset($variables[$part])) {
                    $result[] = $variables[$part];
                } elseif ($part === '') {
                    $result[] = $this->macroCharacter;
                } elseif ($default === null) {
                    $result[] = $this->macroCharacter;
                    $result[] = $part;
                    $result[] = $this->macroCharacter;
                } else {
                    $result[] = $default;
                }
            } else {
                $result[] = $part;
            }

            $macro = ! $macro;
        }

        return implode($result);
    }

    /**
     * Return the raw template string this instance was constructed from
     *
     * @return string
     */
    public function __toString()
    {
        return implode($this->macroCharacter, $this->template);
    }

    /**
     * Return the macros of this template
     *
     * @return string[]
     */
    public function getMacros()
    {
        $macro = false;
        $macros = [];

        foreach ($this->template as $part) {
            if ($macro) {
                $macros[$part] = null;
            }

            $macro = ! $macro;
        }

        unset($macros['']);

        return array_keys($macros);
    }

    /**
     * Get macros' start and end character
     *
     * @return string
     */
    public function getMacroCharacter()
    {
        return $this->macroCharacter;
    }
}
