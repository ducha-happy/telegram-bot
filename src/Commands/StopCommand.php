<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;

class StopCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/stop';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('stop_command_description');
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $this->telegram->sendMessage($message->getChatId(), 'Ok i am stopping!');
            $this->handler->setMode(CommandHandler::SLEEPING_STATE);
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
            if ($message->getText() == $this->getName() &&
                $message->getChatId() == $this->handler->getTelegramBot()->getTelegramAdminChatId()){
                return true;
            }
        }

        return false;
    }
}