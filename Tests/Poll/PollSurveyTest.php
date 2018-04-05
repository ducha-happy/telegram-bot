<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Poll;

use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Tests\AbstractTest;
use Ducha\TelegramBot\Tests\TelegramData;

class PollSurveyTest extends AbstractTest
{
    public function testHaveAllReplies()
    {
        $this->storage->clear();

        $survey = $this->createTestSurvey();
        $pollId = $survey->getPoll()->getId();
        $chatId = $survey->getChatId();
        $result = PollSurvey::getInstance($chatId, $pollId, $this->telegram, $this->storage, $this->handler);
        $this->assertInstanceOf(PollSurvey::class, $result,
            sprintf('In current context, the method "%s" must return instance of "%s" ', 'PollSurvey::getInstance', PollSurvey::class)
        );

        $data = TelegramData::$data;
        $from = $data['message']['from'];

        $group = $this->groupManager->getGroup($survey->getChatId());
        $this->assertEquals(1, count($group), 'Count(group) must return 1');

        foreach ($survey->state as &$item){
            $item['replies'][ $from['id'] ] = array('from' => $from, 'text' => 'a response on a question');
            $this->invokeMethod($survey, 'haveAllRepliesFor', array(&$item));
            $this->assertTrue(isset($item['completed']), 'Method "haveAllRepliesFor" don`t work as expected');
            break;
        }

        $result = $this->invokeMethod($survey, 'haveAllReplies', array());
        $this->assertTrue($result, 'Method "haveAllReplies" must return true');

        $this->storage->clear();
    }
}