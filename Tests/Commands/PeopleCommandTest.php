<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PeopleCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Tests\TelegramData;

class PeopleCommandTest extends AbstractCommandTest
{
    public function testIsApplicable()
    {
        $data = $this->data;
        $chat = &$data['message']['chat'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'group';
        $this->assertTrue($this->command->isApplicable($data),
            'For a group chat the "isApplicable" method must return "true"!'
        );

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'supergroup';
        $this->assertTrue($this->command->isApplicable($data),
            'For a supergroup chat the "isApplicable" method must return "true"!'
        );

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data),
            'For a private chat the "isApplicable" method must return "false"!'
        );
    }
}