<?php

namespace Ducha\TelegramBot\Tests;

use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollQuestion;
use Ducha\TelegramBot\Tests\Commands\CommandHandlerAwareTrait;
use Ducha\TelegramBot\Tests\Helpers\PollSurveyImitator;
use Ducha\TelegramBot\Types\Group;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Types\User;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Redis\GroupManager;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\CommandHandler;

abstract class AbstractTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    protected $handler;
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

    public function setUp()
    {
        StorageKeysHolder::setPrefix('telegram-test');
        $this->handler = $this->getCommandHandler();
        $this->data = TelegramData::$data;
        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram-bot.poll.manager');
        $this->groupManager = $this->handler->getContainer()->get('ducha.telegram-bot.group.manager');
        $this->storage = $this->handler->getContainer()->get('ducha.telegram-bot.storage');
        $this->telegram = $this->handler->getTelegramBot()->getTelegram();
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->storage = null;
        $this->telegram = null;
        $this->pollManager = null;
        $this->groupManager = null;
    }

    /**
     * Create test user
     * @return User
     */
    public function createTestUser()
    {
        return new User(999999, false, 'testUser', null, 'test_user');
    }

    /**
     * Create test poll

     * @return Poll
     */
    public function createTestPoll()
    {
        $user = $this->createTestUser();
        $userId = $user->getId();

        $questions = array(
            new PollQuestion('Question1', explode(',', 'yes,no'))
        );

        $poll = new Poll(999999, $userId, 'testPoll', $questions);

        $this->pollManager->addPoll($poll);

        return $poll;
    }

    /**
     * Create test group

     * @return Group
     */
    public function createTestGroup()
    {
        $group = $this->groupManager->addGroup(-999999, 'testGroup');

        return $group;
    }

    /**
     * Create test PollSurvey

     * @return PollSurveyImitator
     */
    public function createTestSurvey()
    {
        $chat = $this->createTestGroup();
        $chatId = $chat->getId();
        $poll = $this->createTestPoll();
        $survey = new PollSurveyImitator($chatId, $poll, $this->telegram, $this->storage, $this->handler);
        $data = TelegramData::$data;
        $chat = array (
            'id' => $chat->getId(),
            'title' => $chat->getTitle(),
            'type' => 'group',
        );
        $data['message']['chat'] = $chat;
        $message = new Message($data['message']);
        $this->groupManager->lookAtMessage($message);
        $survey->start($message);
        $survey->save();

        return $survey;
    }
}