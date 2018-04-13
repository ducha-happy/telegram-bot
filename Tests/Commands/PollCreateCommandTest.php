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

use Ducha\TelegramBot\Commands\PollCreateCommand;
use Ducha\TelegramBot\Tests\TelegramData;
use Ducha\TelegramBot\Translation\Translator;

class PollCreateCommandTest extends AbstractCommandTest
{
    public function testIsApplicable()
    {
        $data = $this->data;
        $chat = &$data['message']['chat'];
        $text = &$data['message']['text'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data),
            'For a group chat the "isApplicable" method must return "false"!'
        );

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        $chat['type'] = 'supergroup';
        $this->assertFalse($this->command->isApplicable($data),
            'For a supergroup chat the "isApplicable" method must return "false"!'
        );

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data),
            'For a private chat the "isApplicable" method must return "true"!'
        );

        $method = 'getArguments';
        $this->assertTrue(method_exists($this->command, $method), sprintf('Method "%s" of class %s must exists', $method, get_class($this->command)));

        $text = '/pollcreate';
        $args = $this->command->getArguments();
        $this->assertEmpty($args, 'args must be empty');

        $text = '/pollcreate 18';
        $this->command->isApplicable($data);
        $args = $this->command->getArguments();
        $this->assertNotEmpty($args, 'args must be not empty');
    }

    public function testSomeMethods()
    {
        $method = 'messageTextIsValid';

        $str = '/sometext';
        $result = $this->invokeMethod($this->command, $method, array($str));
        $this->assertFalse($result, sprintf('Method "%s" of class "%s" must return "false"', $method, get_class($this->command)));

        $str = 'sometext';
        $result = $this->invokeMethod($this->command, $method, array($str));
        $this->assertTrue($result, sprintf('Method "%s" of class "%s" must return "true"', $method, get_class($this->command)));


        $method = 'repliesIsValid';

        $str = 'sometext, asdasd, /asdasd';
        $result = $this->invokeMethod($this->command, $method, array($str));
        $this->assertFalse($result, sprintf('Method "%s" of class "%s" must return "false"', $method, get_class($this->command)));

        $str = 'sometext, asdasd, asdasd';
        $result = $this->invokeMethod($this->command, $method, array($str));
        $this->assertTrue($result, sprintf('Method "%s" of class "%s" must return "true"', $method, get_class($this->command)));


        $translator = PollCreateCommand::getInstance(Translator::class);
        $this->assertInstanceOf(Translator::class, $translator, sprintf('translator must be instance of %s but "" was given', Translator::class, gettype($translator)));
    }
}