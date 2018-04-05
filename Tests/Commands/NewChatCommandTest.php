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

use Ducha\TelegramBot\Commands\NewChatCommand;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Redis\GroupManager;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Tests\TelegramData;

class NewChatCommandTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var NewChatCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;
    /**
     * @var PollManagerInterface
     */
    private $pollManager;
    /**
     * @var GroupManager
     */
    private $groupManager;
    /**
     * @var RedisStorage
     */
    private $storage;
    /**
     * Telegram Bot Api
     *
     * @var Telegram
     */
    protected $telegram;

    public function setUp()
    {
        StorageKeysHolder::setPrefix('telegram-test');

        $this->handler = $this->getCommandHandler();
        $this->command = new NewChatCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = NewChatCommand::getName();
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

    public function testIsApplicable()
    {
        $data = $this->data;

        $this->assertFalse($this->command->isApplicable($data),
            'For a obviously required to perform command the "isApplicable" method must return "false"!'
        );

        $data = TelegramData::$new_chat_participant_data;

        $this->assertTrue($this->command->isApplicable($data),
            'For a message with a new_chat_participant key the "isApplicable" method must return "true"!'
        );
    }

}