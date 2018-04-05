<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\ConfigLoader;
use Ducha\TelegramBot\TelegramBot;

trait CommandHandlerAwareTrait {

    /**
     *
     * @return CommandHandler
     */
    public function getCommandHandler()
    {
        $configLoader = new ConfigLoader();
        $container = $configLoader->getContainer();

        $bot = new TelegramBot($configLoader->getLogger());
        $bot->setContainer($container);
        $bot->setTelegram();
        $bot->getTelegram()->setMode('test');

        return new CommandHandler($container, $bot);
    }

}
