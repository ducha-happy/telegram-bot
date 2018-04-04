<?php

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Commands\AbstractCommand;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\Commands\CommandInterface;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Redis\GroupManager;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Tests\TelegramData;

abstract class AbstractCommandTestOld extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    protected $handler;
    /**
     * @var AbstractCommand
     */
    protected $command;
    /**
     * @var array
     */
    protected $data;
    /**
     * @var PollManagerInterface
     */
    protected $pollManager;
    /**
     * @var GroupManager
     */
    protected $groupManager;
    /**
     * @var RedisStorage
     */
    protected $storage;
    /**
     * Telegram Bot Api
     *
     * @var Telegram
     */
    protected $telegram;

    public function getCommandClass()
    {
        $class = get_class($this);
        $class = preg_replace('|Test$|', '', $class);
        $temp = explode('\\', $class);
        $temp = array_filter($temp, function($value){
            return ($value == 'Tests')? false : true;
        });

        return '\\' . implode('\\', $temp);
    }

    public function setUp()
    {
        StorageKeysHolder::setPrefix('telegram-test');

        $this->handler = $this->getCommandHandler();
        $class = $this->getCommandClass();

        if (class_exists($class) == false){
            throw new \LogicException(sprintf('Class %s does not exists', $class));
        }

        $this->command = new $class($this->handler);

        if (!$this->command instanceof CommandInterface){
            throw new \LogicException(sprintf('Class %s must be instance of %s', $class, CommandInterface::class));
        }

        $this->data = TelegramData::$data;
        $this->data['message']['text'] = $class::getName();
        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram-bot.poll.manager');
        $this->groupManager = $this->handler->getContainer()->get('ducha.telegram-bot.group.manager');
        $this->storage = $this->handler->getContainer()->get('ducha.telegram-bot.storage');
        $this->telegram = $this->handler->getTelegramBot()->getTelegram();
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->command = null;
        $this->storage = null;
        $this->telegram = null;
        $this->pollManager = null;
        $this->groupManager = null;
    }

}