<?php

namespace Icinga\Module\Graphite;

use DirectoryIterator;
use Icinga\Application\Icinga;
use Icinga\Exception\NotFoundError;

class TemplateStore
{
    protected $basedir;

    public function __construct($basedir = null)
    {
        if ($basedir !== null) {
            $this->basedir = $basedir;
        }
    }

    public function enumTemplateSets()
    {
        $enum = array();
        foreach ($this->loadTemplateSets() as $key => $set) {
            $enum[$key] = $set->getTitle();
        }

        return $enum;
    }

    public function getTemplateSets()
    {
        return $this->loadTemplateSets();
    }

    public function enumTemplates()
    {
        $enum = array();
        foreach ($this->loadTemplateSets() as $set) {
            $enum[$set->getTitle()] = $set->enumTemplates();
        }

        return $enum;
    }

    public function loadTemplate($name)
    {
        list($set, $name) = preg_split('~/~', $name, 2);

        return $this->loadTemplateSets($set)->loadTemplate($name);
    }    

    public function loadTemplateSets($name = null)
    {
        $dir = $this->getDir();
        $sets = array();

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;
            if (! $file->isDir()) continue;
            $setname = $file->getFilename();
            $iniFilename = $file->getPathName() . '/templateset.ini';
            if (! is_readable($iniFilename)) continue;

            $sets[$setname] = new TemplateSet($iniFilename);
        }

        if ($name !== null) {
            if (! array_key_exists($name, $sets)) {
                throw new NotFoundError(
                    'The desired template set "%s" doesn\'t exist',
                    $name
                );
            }

            return $sets[$name];
        }

        ksort($sets);
        return $sets;
    }

    protected function getDir($suffix = null)
    {
        $this->detectBasedir();
        if ($suffix === null) {
            return $this->basedir;
        } else {
            return $this->basedir . '/' . $suffix;
        }
    }

    protected function detectBasedir()
    {
        if ($this->basedir === null) {
            $this->basedir = Icinga::app()
                ->getModuleManager()
                ->getModule('graphite')
                ->getConfigDir()
                . '/templates';
        }
    }
}
