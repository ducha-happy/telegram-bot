<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Poll/PollSurveyTest.php
 */

namespace Ducha\TelegramBot\Tests\Poll;

use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollStartCommand;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollQuestion;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Tests\Helpers\GroupManagerHelper;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use Ducha\TelegramBot\Tests\TelegramData;
use Ducha\TelegramBot\Commands\PollSurveyListCommand;
use Ducha\TelegramBot\Storage\StorageInterface;
use Ducha\TelegramBot\Tests\Helpers\PollSurveyHelper;
use Ducha\TelegramBot\Tests\Commands\CommandHandlerAwareTrait;

class PollSurveyTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;

    /**
     * Storage where a command can save your data
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Manager to monitor your group users
     *
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * Telegram Bot Api
     *
     * @var Telegram
     */
    protected $telegram;
    /**
     * PollSurvey
     *
     * @var PollSurveyHelper
     */
    protected $survey;

    public function setUp()
    {
        $this->handler = $this->getCommandHandler();

        $command = new PollSurveyListCommand($this->handler);
        $this->storage = $command->getStorage();

        $this->telegram = $this->handler->getTelegramBot()->getTelegram();
        $data = TelegramData::$data;
        $testId = 99999;
        $data['message']['text'] = PollStartCommand::getName() . ' ' . $testId;
        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $message = $command->getMessage($data);

        $userId = TelegramData::PRIVATE_CHAT_ID;
        $poll = new Poll($testId, $userId, 'testPoll', array(
            new PollQuestion('Are you happy?', array('Yes', 'No'))
        ));

        $this->groupManager = new GroupManagerHelper();

        $groupId = -123456789;

        $group = $this->groupManager->addGroup($groupId, 'testGroup');
        $group[$userId] = TelegramData::$data['message']['from'];

        $this->survey = new PollSurveyHelper($groupId,  $poll,  $this->telegram,  $this->storage,  $this->handler);
        $this->survey->start($message);
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->storage = null;
        $this->groupManager = null;
        $this->telegram = null;
        $this->survey = null;
    }

    public function testHaveAllRepliesFor()
    {
        $group = $this->groupManager->getGroup($this->survey->getChatId());
        $this->assertEquals(1, count($group), 'Count(group) must return 1');
        $this->assertEquals(-123456789, $this->survey->getChatId(), 'getChatId must be equals -123456789 ');

        $data = TelegramData::$data;
        $from = $data['message']['from'];

        foreach ($this->survey->state as &$item){
            $item['replies'][ $from['id'] ] = array('from' => $from, 'text' => 'a response on a question');
            $this->invokeMethod($this->survey, 'haveAllRepliesFor', array(&$item));
            $this->assertTrue(isset($item['completed']), 'Method "haveAllRepliesFor" don`t work as expected');
            break;
        }
    }

    public function testHaveAllReplies()
    {
        $result = $this->invokeMethod($this->survey, 'haveAllReplies', array());
        $this->assertFalse($result, 'Method "haveAllReplies" must return false');

        foreach ($this->survey->state as &$item){
            if (!isset($item['completed'])){
                $item['completed'] = 1;
            }
        }

        $result = $this->invokeMethod($this->survey, 'haveAllReplies', array());
        $this->assertTrue($result, 'Method "haveAllReplies" must return true');
    }

}