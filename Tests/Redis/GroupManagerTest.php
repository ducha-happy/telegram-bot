<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Redis/GroupManagerTest.php
 */

namespace Ducha\TelegramBot\Tests\Redis;

use Ducha\TelegramBot\Types\Group;
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
    }

    public function tearDown()
    {
        $this->groupManager = null;
        $this->storage = null;
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->groupManager->addGroup(123456789, 'testGroup');
    }

    public function testAddGroup()
    {
        $this->groupManager->addGroup(-123456789, 'testGroup');
        $key = $this->storage->getStorageKey(array('group', -123456789));
        $group = $this->storage->get($key);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));
    }

    public function testGetAndRemoveGroup()
    {
        $this->groupManager->addGroup(-123456789, 'testGroup');
        $group = $this->groupManager->getGroup(-123456789);
        $this->assertTrue($group instanceof Group, sprintf('Group must be instanceof %s', Group::class));

        $this->groupManager->removeGroup(-123456789);

        $group = $this->groupManager->getGroup(-123456789);
        $this->assertFalse($group instanceof Group, sprintf('Group must not be instanceof %s but must be false', Group::class));
    }

}