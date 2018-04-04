<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/KillBotCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Tests\TelegramData;

class KillBotCommandTest extends AbstractCommandTest
{
    public function testIsApplicable()
    {
        $data = $this->data;
        $chat = &$data['message']['chat'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data),
            'For a group chat the "isApplicable" method must return "false"!'
        );

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data),
            'For a private not admin chat the "isApplicable" method must return "false"!'
        );

        $chat['id'] = TelegramData::PRIVATE_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data),
            'For a private admin chat the "isApplicable" method must return "true"!'
        );
    }
}