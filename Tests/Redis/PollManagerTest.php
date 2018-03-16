<?php

/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Redis/PollManagerTest.php
 */

namespace Ducha\TelegramBot\Tests\Redis;

use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Redis\PollManager;
use Predis\Client as Redis;
use Ducha\TelegramBot\Storage\RedisStorage;
use PHPUnit\Framework\TestCase;

class PollManagerTest extends TestCase
{
    /**
     * @var PollManager
     */
    private $pollManager;

    public function setUp()
    {
        $storage = new RedisStorage(new Redis());
        $this->pollManager = new PollManager($storage);
    }

    public function tearDown()
    {
        $this->pollManager = null;
    }

    public function testCRUD()
    {
        $text = '"%s" method must return %s but %s was given';
        $poll = $this->getTestPoll();
        $this->pollManager->addPoll($poll);

        $temp = $this->pollManager->getPoll($poll->getUserId());
        $this->assertTrue($temp instanceof Poll, sprintf($text, 'getPoll', 'a instance of ' . Poll::class, gettype($temp)));

        $temp = $this->pollManager->getPollById($poll->getId());
        $this->assertTrue($temp instanceof Poll, sprintf($text, 'getPollById', 'a instance of ' . Poll::class, gettype($temp)));

        $temp = $this->pollManager->getPollsByUserId($poll->getUserId());
        $this->assertArrayHasKey(0, $temp, sprintf($text, 'getPollsByUserId', ' array ', gettype($temp)));

        $temp = $this->pollManager->removePoll($poll->getId());
        $this->assertTrue($temp, sprintf($text, 'removePoll', 'true', gettype($temp)));
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