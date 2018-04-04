<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/ListCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Tests\TelegramData;

class ListCommandTest extends AbstractCommandTest
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

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data),
            'For a private not admin chat the "isApplicable" method must return "true"!'
        );

        $chat['id'] = TelegramData::PRIVATE_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data),
            'For a private admin chat the "isApplicable" method must return "true"!'
        );
    }
}