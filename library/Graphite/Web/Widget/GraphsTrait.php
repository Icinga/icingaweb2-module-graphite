<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Application\Config;
use Icinga\Module\Graphite\Graphing\Templates;

trait GraphsTrait
{
    /**
     * All loaded templates
     *
     * @var Templates
     */
    protected static $allTemplates;

    /**
     * Load and get all templates
     *
     * @return Templates
     */
    protected static function getAllTemplates()
    {
        if (static::$allTemplates === null) {
            static::$allTemplates = (new Templates())
                ->loadDir(Config::resolvePath('modules/graphite/templates'));
        }

        return static::$allTemplates;
    }
}
