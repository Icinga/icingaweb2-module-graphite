<?php

spl_autoload_register(function ($class) {
    $prefix = 'iplx\\';
    $len = strlen($prefix);

    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);

    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
