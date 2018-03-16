<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PollShowCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollShowCommand;
use Ducha\TelegramBot\Tests\TelegramData;

class PollShowCommandTest extends TestCase
{
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollShowCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;

    public function setUp()
    {
        $this->handler = $this->getCommandHandler();
        $this->command = new PollShowCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/pollshow';
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

        /* /pollshow 3 */
        $text = '/pollshow 3';
        $data['message']['text'] = $text;

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data), sprintf($warning2, 'true'));

        /* /pollshow */
        $text = '/pollshow';
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

//        $text = '/pollshow';
//        $data['message']['text'] = $text;
//        if ($this->command->isApplicable($data)){
//            $this->command->execute($data);
//        }


    }

}