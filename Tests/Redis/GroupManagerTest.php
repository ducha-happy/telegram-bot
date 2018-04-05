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

use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Tests\TelegramData;
use Ducha\TelegramBot\Types\Group;
use Ducha\TelegramBot\Types\Message;
use Predis\Client as Redis;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Redis\GroupManager;
use PHPUnit\Framework\TestCase;

class GroupManagerTest extends TestCase
{
    /**
     * @var GroupManager
     */
    private $groupManager;
    /**
     * @var RedisStorage
     */
    private $storage;

    public function setUp()
    {
        $this->storage = new RedisStorage(new Redis());
        $this->groupManager = new GroupManager($this->storage);
        StorageKeysHolder::setPrefix('telegram-test');
    }

    public function tearDown()
    {
        $this->storage->clear();
        $this->groupManager = null;
        $this->storage = null;
    }

    public function testInvalidArgumentException()
    {
        $chatId = 123456789;
        $this->expectException(\InvalidArgumentException::class);
        $this->groupManager->addGroup($chatId, 'testGroup');
    }

    public function testAddGroup()
    {
        $chatId = -123456789;
        $this->groupManager->addGroup($chatId, 'testGroup');
        $key = Group::getStorageKey($chatId);
        $group = $this->storage->get($key);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));
    }

    public function testGetAndRemoveGroup()
    {
        $chatId = -123456789;
        $this->groupManager->addGroup($chatId, 'testGroup');
        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));

        $this->groupManager->removeGroup($chatId);

        $group = $this->groupManager->getGroup($chatId);
        $this->assertFalse($group instanceof Group, sprintf('Group must not be instanceof %s but must be false', Group::class));
    }

    public function testLookAtMessage()
    {
        // left_chat_participant_data
        $data = TelegramData::$left_chat_participant_data;
        $message = new Message($data['message']);
        $chatId = $message->getChatId();

        $this->groupManager->removeGroup($chatId);

        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue(empty($group), 'var group must be empty');

        $this->groupManager->lookAtMessage($message);

        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));
        $this->assertFalse((bool)count($group), 'group must have no one user');

        $this->groupManager->removeGroup($chatId);
        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue(empty($group), 'var group must be empty');

        // new_chat_participant_data
        $data = TelegramData::$new_chat_participant_data;
        $message = new Message($data['message']);
        $chatId = $message->getChatId();

        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue(empty($group), 'var group must be empty');

        $this->groupManager->lookAtMessage($message);

        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));

        $this->assertTrue((bool)count($group), 'group must have at least one user');

        $this->groupManager->removeGroup($chatId);
        $group = $this->groupManager->getGroup($chatId);
        $this->assertTrue(empty($group), 'var group must be empty');
    }

}