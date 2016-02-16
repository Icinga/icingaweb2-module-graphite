<?php

namespace Icinga\Module\Graphite;

use DirectoryIterator;

class TemplateSet
{
    protected $name;

    protected $title;

    protected $basedir;

    protected $patterns = array();

    public function __construct($configfile)
    {
        $this->basedir = dirname($configfile);
        $this->name = basename($this->basedir);

        $config = parse_ini_file($configfile, true);

        if (isset($config['templateset']['name'])) {
            $this->title = $config['templateset']['name'];
        }

        if (isset($config['patterns'])) {
            $this->patterns = $config['patterns'];
        }
    }

    public function getBasePatterns()
    {
        return $this->patterns;
    }

    public function getTitle()
    {
        if ($this->title === null) {
            return $this->name;
        }

        return $this->title;
    }

    public function getName()
    {
        return $this->name;
    }

    public function enumTemplates()
    {
        $enum = array();
        return $this->extendEnumTemplates($enum);
    }

    public function extendEnumTemplates(& $enum)
    {
        foreach ($this->loadTemplates() as $key => $template) {
            $enum[sprintf('%s/%s', $this->name, $key)] = $template->getTitle();
        }

        return $enum;
    }

    public function loadTemplate($name)
    {
        return $this->loadTemplates($name);
    }

    public function loadTemplates($name = null)
    {
        $dir = $this->basedir;
        $templates = array();

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;
            $filename = $file->getFilename();
            if (substr($filename, -5) === '.conf') {
                $tname = substr($filename, 0, -5);
                if ($name !== null) {
                    if ($name !== $tname) continue;
                }
                $templates[$tname] = GraphTemplate::load(
                    file_get_contents($file->getPathname())
                );
            }
        }

        if ($name !== null) {
            if (! array_key_exists($name, $templates)) {
                throw new NotFoundError(
                    'The desired template "%s" doesn\'t exist',
                    $name
                );
            }

            return $templates[$name];
        }

        ksort($templates);
        return $templates;
    }
}
