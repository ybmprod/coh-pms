<?php
declare(strict_types=1);

/**
 * Lightweight class autoloader for the plain-PHP architecture.
 * Classes live in app/{core,models,services,controllers,helpers} and use
 * one-class-per-file with a matching filename.
 */
spl_autoload_register(static function (string $class): void {
    $baseDir = dirname(__DIR__);
    $directories = ['core', 'models', 'services', 'controllers', 'helpers'];

    foreach ($directories as $directory) {
        $file = $baseDir . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
