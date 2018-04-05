<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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