<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/StartCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Commands\StartCommand;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Redis\GroupManager;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Tests\TelegramData;


class StartCommandTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var StartCommand
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
        $this->command = new StartCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/start';
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

    public function testBotNamesFromConfigAndGetMe()
    {
        $this->telegram->setMode('prod');
        $response = $this->telegram->getMe();
        $botName1 = ''; $botId1 = '';
        if (isset($response['result'])){
            $botName1 = $response['result']['username'];
            $botId1 = $response['result']['id'];
        }
        $temp = $this->handler->getContainer()->getParameter('telegram_bot_link');
        $temp = explode('/', $temp);
        $botName2 = array_pop($temp);
        $this->assertEquals($botName1, $botName2, 'bot names is not equals');

        $temp = $this->handler->getContainer()->getParameter('telegram_bot_token');
        $temp = explode(':', $temp);
        $botId2 = array_shift($temp);
        $this->assertEquals($botId1, $botId2, 'bot ids is not equals');
    }

    public function testIsApplicable()
    {
        $data = $this->data;
        $warning1 = 'For a group chat the "isApplicable" method must return "false"!';
        $warning2 = 'For a private chat the "isApplicable" method must return "true"!';

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), $warning1);

        $data['message']['chat']['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data), $warning2);
    }

    public function testPollStatRemoveAction()
    {
        StorageKeysHolder::setPrefix('telegram');
        $key = StorageKeysHolder::getNotCompletedSurveyKey(-1001233109538, 10);
        var_dump(
            $key,
            $this->storage->exists($key),
            $this->storage->get($key)
        );
    }

}