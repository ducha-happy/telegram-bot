<?php

$autoloader = __DIR__ . '/../vendor/autoload.php';

/*
 * Check that composer installation was done.
 */
if (!file_exists($autoloader)) {
    throw new \Exception(
        'Please run "composer install" in root directory to have ability to run this application'
    );
}

// Include the Composer autoloader.
require_once $autoloader;