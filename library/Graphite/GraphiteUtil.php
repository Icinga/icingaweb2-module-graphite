<?php

namespace Icinga\Module\Graphite;

class GraphiteUtil
{
    protected static $variablePattern = '/\$(\w+)/';

    public static function extractVariableNames($pattern)
    {
        if (preg_match_all(self::$variablePattern, $pattern, $m)) {
            return $m[1];
        }

        return array();
    }

    public static function extractVars($string, $pattern)
    {
        $vars = array();
        $varnames = self::extractVariableNames($pattern);

        if (! empty($varnames)) {
            $parts = preg_split(self::$variablePattern, $pattern);
            foreach ($parts as $key => $val) {
                $parts[$key] = preg_quote($val, '/');
            }

            $regex = '/' . implode('([^\.]+?)', $parts) . '/';
            if (preg_match($regex, $string, $m)) {
                array_shift($m);
                $vars = array_combine($varnames, $m);
            }
        }

        return $vars;
    }
}
