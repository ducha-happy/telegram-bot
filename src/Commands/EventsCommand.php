<?php

namespace Ducha\TelegramBot\Commands;

class EventsCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/events';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'show nearest events from the uma site';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $content = 'Events will be show here - this feature is not implemented yet';

            $this->telegram->sendMessage($message->getChatId(), $content);
        }
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            if ($this->stringIsCommand($message->getText())){
                return true;
            }
        }

        return false;
    }
}