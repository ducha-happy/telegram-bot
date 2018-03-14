<?php

namespace Ducha\TelegramBot\Commands;

class KillBotCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/killbot';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Kills the bot so that it can start only from server.' . "\n" .
            'The bot will be killed only if the command came from the administrator.';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $this->telegram->sendMessage($message->getChatId(), 'Ok i am dying!');
            $this->handler->getTelegramBot()->kill();
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
//    /**
//     * @inheritdoc
//     */
//    public function isHidden(array $data)
//    {
//        if ($this->hasMessage($data)){
//            $message = $this->getMessage($data);
//            if ($message->getChatId() != $this->handler->getTelegramBot()->getTelegramAdminChatId()){
//                return true;
//            }
//        }
//
//        return parent::isHidden($data);
//    }
}