<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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