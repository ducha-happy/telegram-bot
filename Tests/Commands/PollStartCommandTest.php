<?php

/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PollStartCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollStartCommand;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Tests\PrivateProtectedAwareTrait;
use Ducha\TelegramBot\Tests\TelegramData;

class PollStartCommandTest extends TestCase
{
    use PrivateProtectedAwareTrait;
    use CommandHandlerAwareTrait;

    /**
     * @var PollManagerInterface
     */
    private $pollManager;
    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollStartCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;

    public function setUp()
    {
        $this->handler = $this->getCommandHandler();
        $this->command = new PollStartCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/pollstart';

        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram.poll.manager');
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->command = null;
        $this->pollManager = null;
    }

    public function testIsApplicable()
    {
        $data = $this->data;

        $warning1 = 'Error! For a group chat the "isApplicable method" must return "%s"!';
        $warning2 = 'Error! For a private chat the "isApplicable method" method must return "%s"!';

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertTrue($this->command->isApplicable($data), sprintf($warning1, 'true'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning2, 'false'));
    }

    public function testGetPoll()
    {
        $poll = $this->getTestPoll();
        $pollId = $userId = $poll->getId();
        $pollName = $poll->getName();
        $this->pollManager->removePoll($pollId);
        $this->pollManager->addPoll($poll);

        $this->invokeMethod($this->command, 'setArguments', array( array($pollId) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertEquals(true, $result instanceof Poll, sprintf('Arguments were filled - (int)id was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result)));

        $this->invokeMethod($this->command, 'setArguments', array( array((string)$pollId) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertEquals(true, $result instanceof Poll, sprintf('Arguments were filled - (string)id was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result)));

        $this->invokeMethod($this->command, 'setArguments', array( array($pollName) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertEquals(true, $result instanceof Poll, sprintf('Arguments were filled - pollName was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result)));

        $this->invokeMethod($this->command, 'setArguments', array( array() ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertEquals(true, $result instanceof Poll, sprintf('Arguments were cleared - Result must be instance of %s', Poll::class));

        $this->pollManager->removePoll($pollId);

        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertFalse($result, 'Poll was removed from storage - Result must be false');
    }

    public function testCreatePollSurvey()
    {
        $poll = $this->getTestPoll();
        $pollId = $userId = $poll->getId();
        $this->pollManager->removePoll($pollId);
        $this->pollManager->addPoll($poll);

        $data = $this->data;
        $data['message']['from']['id'] = $poll->getUserId();
        $message = $this->command->getMessage($data);

        $result = $this->invokeMethod($this->command, 'createPollSurvey', array($message));
        $this->assertEquals(true, $result instanceof PollSurvey, sprintf('Arguments were cleared - Result must be instance of %s but %s was given', PollSurvey::class, gettype($result)));

        $this->pollManager->removePoll($pollId);

        $result = $this->invokeMethod($this->command, 'createPollSurvey', array($message));
        $this->assertFalse($result, sprintf('Poll was removed from storage - Result must be false but %s was given', gettype($result)) );
    }

    public function testIsChatTypeAvailable()
    {
        $message = '"isChatTypeAvailable" method with a "%s" value as argument must return %s';
        $type = 'group';
        $this->assertTrue($this->command->isChatTypeAvailable($type), sprintf($message, $type, 'true'));
        $type = 'private';
        $this->assertFalse($this->command->isChatTypeAvailable($type), sprintf($message, $type, 'false'));
        $type = 'supergroup';
        $this->assertTrue($this->command->isChatTypeAvailable($type), sprintf($message, $type, 'false'));
        $type = 'channel';
        $this->assertFalse($this->command->isChatTypeAvailable($type), sprintf($message, $type, 'false'));
    }

    /**
     * Output test poll
     * @return Poll
     */
    public function getTestPoll()
    {
        $pollId = $userId = 999999;

        return new Poll($pollId, $userId, 'testPoll');
    }

}