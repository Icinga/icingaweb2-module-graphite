<?php

namespace Icinga\Module\Graphite;

/**
 * Utility class offering Graphite-related helpers
 */
class GraphiteUtil
{
    /**
     * Regex pattern matching $varname
     *
     * @var string
     */
    protected static $variablePattern = '/\$(\w+)/';

    /**
     * Extract a list of variable names from a given filter string
     *
     * Example
     * -------
     * <code>
     * $pattern = 'base.$group.$hostname.*.value';
     * echo implode(', ', GraphiteUtil::extractVariableNames($pattern));
     *
     * // Gives: group, hostname
     * </code>
     *
     * @param  string $pattern Filter pattern
     *
     * @return array
     */
    public static function extractVariableNames($pattern)
    {
        if (preg_match_all(self::$variablePattern, $pattern, $m)) {
            return $m[1];
        }

        return array();
    }

    /**
     * Extracts a key/value list with all variables filled in a given metric
     * for a given filter pattern
     *
     * Example
     * -------
     * <code>
     * $pattern = 'base.$group.$hostname.*.value';
     * $metric  = 'base.servers.www1.whatever.value';
     * print_r(GraphiteUtil::extractVars($metric, $pattern));
     * </code>
     *
     * Output
     * ------
     *     Array
     *     (
     *         [group] => servers
     *         [hostname] => www1
     *     )
     *
     * @param  string $metric  Metric string
     * @param  string $pattern Filter pattern
     *
     * @return array
     */
    public static function extractVars($metric, $pattern)
    {
        $vars = array();
        $varnames = self::extractVariableNames($pattern);

        if (! empty($varnames)) {
            $parts = preg_split(self::$variablePattern, $pattern);
            foreach ($parts as $key => $val) {
                $parts[$key] = preg_quote($val, '/');
            }

            $regex = '/^' . implode('([^\.]+?)', $parts) . '$/';
            if (preg_match($regex, $metric, $m)) {
                array_shift($m);
                $vars = array_combine($varnames, $m);
            }
        }

        return $vars;
    }

    public static function replace($string, $key, $replacement)
    {
        return preg_replace(
            '/\$' . preg_quote($key) . '(\.|$)/',
            $replacement . '\1',
            $string
        );
    }

    /**
     * Replace all variables ($some_thing) with an asterisk
     *
     * TODO: I'd opt for \w instead of [^\.]
     */
    public static function replaceRemainingVariables($string)
    {
        return preg_replace('/\$[^\.]+(\.|$)/', '*\1', $string);
    }

    public static function escape($string)
    {
        return preg_replace('/[^a-zA-Z0-9\*\-]/', '_', $string);
    }
}
