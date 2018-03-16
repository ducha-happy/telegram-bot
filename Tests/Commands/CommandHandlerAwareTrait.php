<?php

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
