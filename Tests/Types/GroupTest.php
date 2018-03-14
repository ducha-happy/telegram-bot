<?php

namespace Ducha\TelegramBot\Tests\Types;

use Ducha\TelegramBot\Types\Group;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testCreatingObject()
    {
        $group = new Group(-7, 'Salut');

        $message = 'A error took place while creating object!';

        $this->assertEquals('Salut', $group->getTitle(), $message);
        $this->assertEquals(-7, $group->getId(), $message);
    }

    public function testCannotBeCreatedWithPositiveId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $group = new Group(7, 'Salut');
    }

    public function testCannotBeCreatedWithZeroId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $group = new Group(0, 'Salut');
    }

    public function testImplementInterfaces()
    {
        $interfaces = class_implements(Group::class);

        $this->assertTrue(array_search(\ArrayAccess::class, $interfaces) !== false, sprintf('Class `%s` must implement `%s` interface', Group::class, \ArrayAccess::class));
        $this->assertTrue(array_search(\Countable::class, $interfaces) !== false, sprintf('Class `%s` must implement `%s` interface', Group::class, \Countable::class));
    }

    public function testAddUser()
    {
        $message = 'A error took place while adding a user to group!';

        $group = new Group(-7, 'Salut');
        $group[374780075] = array(
            "id"            => 374780075,
            "is_bot"        => false,
            "first_name"    => "Ducha",
            "username"      => "ducha_v",
            "language_code" => "en-US"
        );

        $this->assertTrue(is_array($group[374780075]), $message);
        $this->assertEquals(1, count($group), $message);
    }
}
