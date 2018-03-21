<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Commands/PollSurveyListCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Storage\StorageKeysHolder;
use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollSurveyListCommand;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Tests\TelegramData;

class PollSurveyListCommandTest extends TestCase
{
    use CommandHandlerAwareTrait;

    /**
     * @var CommandHandler
     */
    private $handler;
    /**
     * @var PollSurveyListCommand
     */
    private $command;
    /**
     * @var array
     */
    private $data;

    public function setUp()
    {
        $this->handler = $this->getCommandHandler();
        $this->command = new PollSurveyListCommand($this->handler);
        $this->data = TelegramData::$data;
        $this->data['message']['text'] = '/surveylist';
    }

    public function tearDown()
    {
        $this->handler = null;
        $this->command = null;
    }

    public function testExecute()
    {
        $storage = $this->command->getStorage();

        //$keys = $storage->keys(StorageKeysHolder::getNotCompletedSurveyKey('*', '*'));
        $keys = $storage->keys(StorageKeysHolder::getPrefix().'*');

        var_dump($keys);


    }

    public function testIsApplicable()
    {
        $data = $this->data;
        $warning1 = 'For a group chat the "isApplicable" method must return "%s"!';
        $warning2 = 'For a private not admin chat the "isApplicable" method must return "%s"!';
        $warning3 = 'For a private admin chat the "isApplicable" method must return "%s"!';

        $data['message']['chat']['id'] = TelegramData::GROUP_CHAT_ID;
        $data['message']['chat']['type'] = 'group';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning1, 'false'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertFalse($this->command->isApplicable($data), sprintf($warning2, 'true'));

        $data['message']['chat']['id'] = TelegramData::PRIVATE_ADMIN_CHAT_ID;
        $data['message']['chat']['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data), sprintf($warning3, 'true'));
    }

}