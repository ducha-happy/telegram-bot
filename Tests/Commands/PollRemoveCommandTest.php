<?php
/**
 * phpunit57 -v -c src/Ducha/TelegramBot/phpunit.xml.dist src/Ducha/TelegramBot/Tests/Commands/PollRemoveCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollRemoveCommand;
use Sas\CommonBundle\Command\TelegramBotCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ducha\TelegramBot\Tests\TelegramData;

class PollRemoveCommandTest extends WebTestCase
{
    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollRemoveCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;

    public function setUp()
    {
        static::$kernel = static::createKernel(array());
        static::$kernel->boot();

        $container = static::$kernel->getContainer();

        $bot = new TelegramBotCommand();
        $bot->setContainer($container);
        $bot->setTelegram();
        $bot->getTelegram()->setMode('test');
        $bot->setPredis();

        $this->handler = new CommandHandler($container, $bot);
        $this->command = new PollRemoveCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/pollremove';
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->command = null;
    }


    public function testIsApplicable()
    {
        $data = $this->data;
        $warning1 = 'Error! For a group chat the "isApplicable method" must return "%s"!';
        $warning2 = 'Error! For a private chat the "isApplicable method" method must return "%s"!';

        /* /pollremove 3 */
        $text = '/pollremove 3';
        $data['message']['text'] = $text;

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data), sprintf($warning2, 'true'));

        /* /pollremove */
        $text = '/pollremove';
        $data['message']['text'] = $text;

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning2, 'false'));

        /* just simple message  */
        $text = 'just simple message';
        $data['message']['text'] = $text;

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning2, 'false'));

//        $text = '/pollremove 3';
//        $data['message']['text'] = $text;
//        if ($this->command->isApplicable($data)){
//            $this->command->execute($data);
//        }


    }

}