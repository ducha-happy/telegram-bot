<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PollSurveyShowListCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Commands\PollStartCommand;
use Ducha\TelegramBot\Commands\PollSurveyShowListCommand;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollQuestion;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Redis\GroupManager;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Tests\TelegramData;


class PollSurveyShowListCommandTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollSurveyShowListCommand
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
        $this->command = new PollSurveyShowListCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/surveyshowlist';
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

    /**
     * Output test poll
     * @return Poll
     */
    public function getTestPoll()
    {
        $id = $this->storage->incr(StorageKeysHolder::getPollMaxIdPattern());

        $poll = new Poll($id, TelegramData::PRIVATE_CHAT_ID, 'testPoll', array(
            new PollQuestion('Are you happy?', array('Yes', 'No'))
        ));

        $this->pollManager->removePoll($id);
        $this->pollManager->addPoll($poll);

        $data = TelegramData::$data;
        $data['message']['chat'] = array (
            'id' => TelegramData::GROUP_CHAT_ID,
            'title' => 'Uma2',
            'type' => 'group',
            'all_members_are_administrators' => true,
        );
        $data['message']['text'] = PollStartCommand::getName() . ' ' . $id;

        $message = $this->command->getMessage($data);
        $this->groupManager->lookAtMessage($message);

        $survey = new PollSurvey(TelegramData::GROUP_CHAT_ID,  $poll,  $this->telegram,  $this->storage,  $this->handler);
        $survey->start($message);
        $survey->save();

        return $poll;
    }

    public function testExecute()
    {
        $this->assertEquals('telegram-test', StorageKeysHolder::getPrefix(), '');

        $storage = $this->storage;
        $storage->clear();

        $keys = $storage->keys(StorageKeysHolder::getPrefix().'*');
        $this->assertEmpty($keys, 'must be empty array');

        $poll = $this->getTestPoll();

        $keys = $storage->keys(StorageKeysHolder::getPrefix().'*');
        $this->assertNotEmpty($keys, 'must be not empty array');

        $this->assertTrue((bool)$storage->exists(StorageKeysHolder::getPollMaxIdPattern()), 'PollMaxId must exists in storage');

        $this->assertTrue((bool)$storage->exists(StorageKeysHolder::getPollKey($poll->getId())), 'Poll must exists in storage');

        $this->assertTrue((bool)$storage->exists(StorageKeysHolder::getUserPollsKey($poll->getUserId())), 'user polls list must exists in storage');

        $this->assertTrue((bool)$storage->exists(StorageKeysHolder::getGroupKey( TelegramData::GROUP_CHAT_ID ) ), 'Group must exists in storage');

        $this->assertTrue((bool)$storage->exists(StorageKeysHolder::getNotCompletedSurveyKey( TelegramData::GROUP_CHAT_ID, $poll->getId() ) ), 'Not Completed Survey must exists in storage');

        $this->invokeMethod($this->command, 'setUserPolls', array($poll->getUserId()));
        $result = $this->invokeMethod($this->command, 'getKeys');
        $this->assertNotEmpty($result, 'getKeys method must return not empty array');

        //var_dump($keys, $result);

        $storage->clear();

        $keys = $storage->keys(StorageKeysHolder::getPrefix().'*');
        $this->assertEmpty($keys, 'must be empty array');
    }

    public function testIsApplicable()
    {
        $data = $this->data;
        $warning1 = 'For a group chat the "isApplicable" method must return "%s"!';
        $warning2 = 'For a private not admin chat the "isApplicable" method must return "%s"!';

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data), sprintf($warning2, 'true'));
    }

}