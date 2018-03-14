<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Commands\AbstractCommand;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\CommandHandler;

class PollManagerAwareCommand extends AbstractCommand
{
    /**
     * polls keeper
     * @var PollManagerInterface
     */
    protected $pollManager;

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram.poll.manager');
    }
}