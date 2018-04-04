<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\Types\User;

class NewChatCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/new_chat';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('new_chat_command_description');
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

        $user = $message->getNewChatParticipant();

        if ($user instanceof User){
            $botId = $this->getBotId();
            // this bot was added to chat - send `hello message` to chat members
            if ($user->isIsBot() && $botId == $user->getId()){
                $lines = array(
                    HtmlFormatter::bold('Hello!'),
                    $this->translator->trans('telegram_bot_description', array(
                        '%poll_create_command_name%' => PollCreateCommand::getName(),
                        '%poll_start_command_name%' => PollStartCommand::getName(),
                        '%start_command_name%' => StartCommand::getName(),
                    )),
                );
                $text = implode("\n", $lines);
                $chatId = $message->getChatId();
                $this->telegram->sendMessage($chatId, $text);
            }
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
        if ($this->stringIsCommand($message->getText())){
            return false;
        }

        $user = $message->getNewChatParticipant();

        if ($user instanceof User){
            return true;
        }

        return false;
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