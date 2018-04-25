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

use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Tests\TelegramData;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;

class StartCommandTest extends AbstractCommandTest
{
    public function testBotNamesFromConfigAndGetMe()
    {
        $this->telegram->setMode('prod');
        $response = $this->telegram->getMe();
        if (!is_array($response)){
            $this->markTestSkipped(sprintf('PHPUnit skip "%s" because Telegram->getMe response is not correct!', __METHOD__));
        }

        $botName1 = ''; $botId1 = '';
        if (isset($response['result'])){
            $botName1 = $response['result']['username'];
            $botId1 = $response['result']['id'];
        }
        $temp = $this->handler->getContainer()->getParameter('telegram_bot_link');
        $temp = explode('/', $temp);
        $botName2 = array_pop($temp);
        $this->assertEquals($botName1, $botName2, 'bot names is not equals');

        $temp = $this->handler->getContainer()->getParameter('telegram_bot_token');
        $temp = explode(':', $temp);
        $botId2 = array_shift($temp);
        $this->assertEquals($botId1, $botId2, 'bot ids is not equals');
    }

    public function testIsApplicable()
    {
        $data = $this->data;
        $chat = &$data['message']['chat'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        foreach (array('group', 'supergroup') as $type){
            $chat['type'] = $type;
            $this->assertFalse($this->command->isApplicable($data),
                sprintf('For a %s chat the "isApplicable" method must return "false"!', $type)
            );
        }

        $chat['id'] = TelegramData::PRIVATE_NOT_ADMIN_CHAT_ID;
        $chat['type'] = 'private';
        $this->assertTrue($this->command->isApplicable($data),
            'For a private chat the "isApplicable" method must return "true"!'
        );
    }

    public function testGetAllPollsMenuKeyboardMethod()
    {
        $result = $this->invokeMethod($this->command, 'GetAllPollsMenuKeyboard', array(123456));
        $this->assertInstanceOf(InlineKeyboardMarkup::class, $result,
            sprintf('Method "%s" must return instance of "%s"', 'getAllPollsMenuKeyboard', InlineKeyboardMarkup::class)
        );
    }

    public function testPollStatRemoveActionMethod()
    {
        $this->storage->clear();

        $survey = $this->createTestSurvey();
        $pollId = $survey->getPoll()->getId();
        $chatId = $survey->getChatId();
        $userId = $survey->getPoll()->getUserId();
        $result = PollSurvey::getInstance($chatId, $pollId, $this->telegram, $this->storage, $this->handler);
        $this->assertInstanceOf(PollSurvey::class, $result,
            sprintf('In current context, the method "%s" must return instance of "%s" ', 'PollSurvey::getInstance', PollSurvey::class)
        );

        $this->invokeMethod($this->command, 'pollStatRemoveAction', array($userId, $pollId, $chatId));
        $result = PollSurvey::getInstance($chatId, $pollId, $this->telegram, $this->storage, $this->handler);
        $this->assertFalse($result,
            sprintf('In current context, the method "%s" must return false', 'PollSurvey::getInstance')
        );

        $result = $this->command->getPollContent($survey->getPoll());
        $this->assertNotEmpty($result,
            sprintf('In current context, the method "%s" must not return an empty string', 'getPollContent')
        );

        $this->storage->clear();
    }

}