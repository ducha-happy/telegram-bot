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

use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Tests\TelegramData;

class PollStartCommandTest extends AbstractCommandTest
{
    public function testIsApplicable()
    {
        $data = $this->data;
        $chat = &$data['message']['chat'];

        $chat['id'] = TelegramData::GROUP_CHAT_ID;
        foreach (array('group', 'supergroup') as $type){
            $chat['type'] = $type;
            $this->assertTrue($this->command->isApplicable($data),
                sprintf('For a %s chat the "isApplicable method" must return "true"', $type)
            );
        }

        $chat['id'] = TelegramData::PRIVATE_CHAT_ID;
        foreach (array('private', 'channel') as $type){
            $chat['type'] = $type;
            $this->assertFalse($this->command->isApplicable($data),
                sprintf('For a %s chat the "isApplicable method" method must return "false"', $type)
            );
        }
    }

    public function testGetPollAndSetArgumentsMethods()
    {
        $poll = $this->createTestPoll();
        $pollId = $userId = $poll->getId();
        $pollName = $poll->getName();
        $this->pollManager->removePoll($pollId);
        $this->pollManager->addPoll($poll);

        $this->invokeMethod($this->command, 'setArguments', array( array($pollId) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertInstanceOf(Poll::class, $result,
            sprintf('Arguments were filled - (int)id was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result))
        );

        $this->invokeMethod($this->command, 'setArguments', array( array((string)$pollId) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertInstanceOf(Poll::class, $result,
            sprintf('Arguments were filled - (string)id was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result))
        );

        $this->invokeMethod($this->command, 'setArguments', array( array($pollName) ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertInstanceOf(Poll::class, $result,
            sprintf('Arguments were filled - pollName was given as an argument - Result must be a instance of %s but %s was given', Poll::class, gettype($result))
        );

        $this->invokeMethod($this->command, 'setArguments', array( array() ));
        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertInstanceOf(Poll::class, $result,
            sprintf('Arguments were cleared - Result must be instance of %s', Poll::class)
        );

        $this->pollManager->removePoll($pollId);

        $result = $this->invokeMethod($this->command, 'getPoll', array($userId));
        $this->assertFalse($result, 'Poll was removed from storage - Result must be false');

        $this->storage->clear();
    }

    public function testCreatePollSurvey()
    {
        $poll = $this->createTestPoll();
        $pollId = $poll->getId();
        $this->pollManager->removePoll($pollId);
        $this->pollManager->addPoll($poll);

        $data = $this->data;
        $data['message']['from']['id'] = $poll->getUserId();
        $message = $this->command->getMessage($data);

        $result = $this->invokeMethod($this->command, 'hasAnyPollSurveyForChat', array($message->getChatId()));
        $this->assertFalse($result, 'hasAnyPollSurveyForChat must return false');

        $pollSurvey = new PollSurvey($message->getChatId(), $poll, $this->telegram, $this->storage, $this->handler);
        $pollSurvey->save();

        $result = $this->invokeMethod($this->command, 'hasAnyPollSurveyForChat', array($message->getChatId()));
        $this->assertTrue($result, 'hasAnyPollSurveyForChat must return true');

        $this->pollManager->removePoll($pollId);

        $this->storage->clear();
    }
}