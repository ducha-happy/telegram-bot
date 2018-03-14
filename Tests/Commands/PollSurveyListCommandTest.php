<?php
/**
 * phpunit57 -v -c src/Ducha/TelegramBot/phpunit.xml.dist src/Ducha/TelegramBot/Tests/Commands/PollSurveyListCommandTest.php
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Commands\PollSurveyListCommand;
use Ducha\TelegramBot\Poll\PollSurvey;
use Sas\CommonBundle\Command\TelegramBotCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ducha\TelegramBot\Tests\TelegramData;

class PollSurveyListCommandTest extends WebTestCase
{
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
        static::$kernel = static::createKernel(array());
        static::$kernel->boot();

        $container = static::$kernel->getContainer();

        $bot = new TelegramBotCommand();
        $bot->setContainer($container);
        $bot->setTelegram();
        $bot->getTelegram()->setMode('test');
        $bot->setPredis();

        $this->handler = new CommandHandler($container, $bot);
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

        $key = PollSurvey::getStorageKey(10);
        $pattern = str_replace(10, "", $key);
        $temp = $storage->keys($pattern . '*');
        $this->assertTrue(is_array($temp), 'temp is not array');

        if (!empty($temp)){
            $keys = array();
            foreach ($temp as $key){
                $keys[] = str_replace($pattern, "", $key);
            }
            //implode(',', $keys);
        }
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