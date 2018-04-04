<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Set error reporting to the max level.
 */
error_reporting(-1);

/*
 * Set UTC timezone.
 */
date_default_timezone_set('UTC');

$autoloader = __DIR__ . '/../vendor/autoload.php';

/*
 * Check that composer installation was done.
 */
if (!file_exists($autoloader)) {
    throw new Exception(
        'Please run "composer install" in root directory to setup unit test dependencies before running the tests'
    );
}

// Include the Composer autoloader.
require_once $autoloader;

/*
 * Unset global variables that are no longer needed.
 */
unset($autoloader);

spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'ducha\\telegrambot\\tests\\telegramdata' => '/TelegramData.php',
                'ducha\\telegrambot\\tests\\privateprotectedawaretrait' => '/PrivateProtectedAwareTrait.php',
                'ducha\\telegrambot\\tests\\helpers\\pollsurveyimitator' => '/Helpers/PollSurveyImitator.php',
                'ducha\\telegrambot\\tests\\helpers\\groupmanagerhelper' => '/Helpers/GroupManagerHelper.php',
                'ducha\\telegrambot\\tests\\commands\\commandhandlerawaretrait' => '/Commands/CommandHandlerAwareTrait.php',
                'ducha\\telegrambot\\tests\\commands\\abstractcommandtest' => '/Commands/AbstractCommandTest.php',
                'ducha\\telegrambot\\tests\\abstracttest' => '/AbstractTest.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);

