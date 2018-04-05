<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Ducha\TelegramBot\TelegramBot;
use Ducha\TelegramBot\ConfigLoader;

require_once 'autoload.php';

$configLoader = new ConfigLoader();

$bot = new TelegramBot($configLoader->getLogger());
$bot->setContainer($configLoader->getContainer());
$bot->execute();