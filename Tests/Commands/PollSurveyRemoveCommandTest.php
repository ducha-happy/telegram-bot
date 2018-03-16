<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PollSurveyRemoveCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollSurveyRemoveCommand;
use Ducha\TelegramBot\Tests\TelegramData;

class PollSurveyRemoveCommandTest extends TestCase
{
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollSurveyRemoveCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;

    public function setUp()
    {
        $this->handler = $this->getCommandHandler();
        $this->command = new PollSurveyRemoveCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/surveyremove';
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->command = null;
    }

    public function testIsApplicable()
    {
        $data = $this->data;
        $warning1 = '(%s) For a group chat the "isApplicable" method must return "%s"!';
        $warning2 = '(%s) For a private not admin chat the "isApplicable" method must return "%s"!';
        $warning3 = '(%s) For a private admin chat the "isApplicable" method must return "%s"!';

        foreach (array("", " -123456") as $temp){
            $data['message']['text'] .= $temp;
            $text = $data['message']['text'];

            $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
            $data['message']['chat']['type'] = 'group';
            $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, $text, 'false'));

            $data['message']['chat']['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
            $data['message']['chat']['type'] = 'private';
            $this->assertFalse($this->command->isApplicable($data), sprintf($warning2, $text, 'true'));

            $data['message']['chat']['id'] = TelegramData::PRIVATE_ADMIN_CHAT_ID;
            $data['message']['chat']['type'] = 'private';
            if (preg_match("|123456|", $data['message']['text'])){
                $this->assertTrue($this->command->isApplicable($data), sprintf($warning3, $text, 'true'));
            }else{
                $this->assertFalse($this->command->isApplicable($data), sprintf($warning3, $text, 'true'));
            }
        }
    }

}