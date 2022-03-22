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
     * Regex for reverse resolving patterns
     *
     * @var string
     */
    protected $reverseResolvePattern;

    /**
     * Wildcards
     *
     * @var string[]
     */
    protected $wildCards;

    /**
     * The original raw template
     *
     * @var string
     */
    protected $orgTemplate;

    /**
     * Constructor
     *
     * @param   string  $template           The raw template
     * @param   string  $macroCharacter     Macros' start and end character
     */
    public function __construct($template, $macroCharacter = '$')
    {
        $this->orgTemplate = $template;
        $this->macroCharacter = $macroCharacter;
        $this->template = explode($macroCharacter, $template);
        foreach ($this->template as $key => $value) {
            if (preg_match('/([^:]+):(.+)/', $value, $match)) {
                $wildCardKey = $match[1];
                $this->template[$key] = $wildCardKey;
                $this->wildCards[$wildCardKey] = $match[2];
            }
        }

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
     * @param   string[]  $variables
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
                    // add wildcards to result before they are
                    // overwritten from Template::getFullCurves()
                    if (isset($this->wildCards[$part])) {
                        $result[] = ':' . $this->wildCards[$part];
                    }

                    $result[] = $this->macroCharacter;
                } else {
                    if (isset($this->wildCards[$part])) {
                        $result[] = $this->wildCards[$part];
                    } else {
                        $result[] = $default;
                    }
                }
            } else {
                $result[] = $part;
            }

            $macro = ! $macro;
        }

        return implode($result);
    }

    /**
     * Try to reverse-resolve the given string
     *
     * @param   string  $resolved       A result of {@link resolve()}
     *
     * @return  string[]|false    Variables as passed to {@link resolve()} if successful
     */
    public function reverseResolve($resolved)
    {
        $matches = [];
        if (! preg_match($this->getReverseResolvePattern(), $resolved, $matches)) {
            return false;
        }

        $macro = false;
        $macros = [];
        $currentCapturedSubPatternIndex = 0;

        foreach ($this->template as $part) {
            if ($macro && ! isset($macros[$part])) {
                $macros[$part] = ++$currentCapturedSubPatternIndex;
            }

            $macro = ! $macro;
        }

        $macros = array_flip($macros);

        $result = [];
        foreach ($matches as $index => $match) {
            if ($index > 0) {
                $result[$macros[$index]] = $match;
            }
        }

        return $result;
    }

    /**
     * Return the raw template string this instance was constructed from
     *
     * @return string
     */
    public function __toString()
    {
        return $this->orgTemplate;
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

    /**
     * Get {@link reverseResolvePattern}
     *
     * @return string
     */
    protected function getReverseResolvePattern()
    {
        if ($this->reverseResolvePattern === null) {
            $result = ['/\A']; // kind of string builder
            $macro = false;
            $macros = [];
            $currentCapturedSubPatternIndex = 0;

            foreach ($this->template as $part) {
                if ($macro) {
                    if (isset($macros[$part])) {
                        $result[] = '\g{';
                        $result[] = $macros[$part];
                        $result[] = '}';
                    } else {
                        $macros[$part] = ++$currentCapturedSubPatternIndex;
                        $result[] = '(.*)';
                    }
                } else {
                    $result[] = preg_quote($part, '/');
                }

                $macro = ! $macro;
            }

            $result[] = '\z/s';

            $this->reverseResolvePattern = implode($result);
        }

        return $this->reverseResolvePattern;
    }
}
