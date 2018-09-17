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

class RouteCommandTest extends AbstractCommandTest
{
    public function testIsApplicable()
    {
        //$this->markTestSkipped(sprintf('PHPUnit skip "%s" because so i need now!', __METHOD__));

        $data = $this->data;
        $chat = &$data['message']['chat'];
        $textOrig = $data['message']['text'];
        $text = &$data['message']['text'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data),
            'For a group chat the "isApplicable" method must return "false"!'
        );

        $chat['type'] = 'supergroup';
        $this->assertFalse($this->command->isApplicable($data),
            'For a supergroup chat the "isApplicable" method must return "false"!'
        );

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data),
            'In that case for a private chat the "isApplicable" method must return "false"!'
        );

        $text .= ' 56.8214256,60.6361983';

        $this->assertTrue($this->command->isApplicable($data),
            'In that case for a private chat the "isApplicable" method must return "true"!'
        );

        $text = $textOrig . ' 56.8214256';
        $this->assertFalse($this->command->isApplicable($data),
            'In that case for a private chat the "isApplicable" method must return "false"!'
        );
    }

    public function testGetGoogleRoute()
    {
        //$this->markTestSkipped(sprintf('PHPUnit skip "%s" because so i need now!', __METHOD__));

        $origin = '56.752442,60.802001';
        $destination = '56.831967,60.573392';
        $parameters = array($origin, $destination);

        $result = $this->invokeMethod($this->command, 'getGoogleRoute', $parameters);
        $this->assertTrue(is_array($result), 'result must be array');
        $this->assertTrue(isset($result['distance']), 'the resulting array must have "distance key"');
        $this->assertTrue(isset($result['duration']), 'the resulting array must have "duration key"');
        $this->assertTrue(isset($result['png']), 'the resulting array must have "png key"');
    }
}