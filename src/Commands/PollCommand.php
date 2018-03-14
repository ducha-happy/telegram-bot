<?php

namespace Ducha\TelegramBot\Commands;

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

        return PollSurvey::getInstance($chatId, $this->telegram, $this->storage, $this->handler);
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