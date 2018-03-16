<?php

use Ducha\TelegramBot\TelegramBot;
use Ducha\TelegramBot\ConfigLoader;

require_once 'autoload.php';

$configLoader = new ConfigLoader();

$bot = new TelegramBot($configLoader->getLogger());
$bot->setContainer($configLoader->getContainer());
$bot->execute();