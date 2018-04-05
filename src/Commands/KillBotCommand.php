<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        return static::getTranslator()->trans('kill_bot_command_description');
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