<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Poll\PollSurvey;

class PollCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/poll';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'This command processes a poll';
    }

    /**
     * Get PollSurvey from storage
     *
     * @param Message $message
     * @return PollSurvey|false
     */
    protected function getPollSurvey(Message $message)
    {
        $chatId = $message->getChatId();

        $pattern = sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), $chatId, '*');
        $keys = $this->storage->keys($pattern);

        if (!empty($keys)){
            $key = $keys[0];
            $temp = explode('.', $key);
            $pollId = $temp[count($temp)-1];

            return PollSurvey::getInstance($chatId, $pollId, $this->telegram, $this->storage, $this->handler);
        }

        return false;
    }

    /**
     * @param array $data
     * @return void
     */
    public function execute(array $data)
    {
        // a message must be
        if (!$this->hasMessage($data)){
            return;
        }

        $message = $this->getMessage($data);

        // the command must not be executed obviously
        if ($message->getText() == self::getName()){
            return;
        }

        $ps = $this->getPollSurvey($message);
        if ($ps instanceof PollSurvey){
            $ps->go($message);
        }
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        // a message must be
        if (!$this->hasMessage($data)){
            return false;
        }

        $message = $this->getMessage($data);

        // the command must not be executed obviously
        if ($message->getText() == self::getName()){
            return false;
        }

        if ($message->getReplyToMessage() === false){
            return false;
        }

        return true;
    }

    /**
     * Don`t show this command in the list
     *
     * @param array $data
     * @return true
     */
    public function isHidden(array $data)
    {
        return true;
    }
}