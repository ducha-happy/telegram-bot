<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Redis;

use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Redis\PollManager;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Predis\Client as Redis;
use Ducha\TelegramBot\Storage\RedisStorage;
use PHPUnit\Framework\TestCase;

class PollManagerTest extends TestCase
{
    /**
     * @var PollManager
     */
    private $pollManager;
    /**
     * @var RedisStorage
     */
    private $storage;

    public function setUp()
    {
        $this->storage = new RedisStorage(new Redis());
        $this->pollManager = new PollManager($this->storage);
        StorageKeysHolder::setPrefix('telegram-test');
    }

    public function tearDown()
    {
        $this->pollManager = null;
        $this->storage->clear();
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