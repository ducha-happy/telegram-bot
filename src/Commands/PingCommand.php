<?php

namespace Ducha\TelegramBot\Commands;

class PingCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/ping';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('ping_command_description');
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $this->telegram->sendMessage($message->getChatId(), 'pong');
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