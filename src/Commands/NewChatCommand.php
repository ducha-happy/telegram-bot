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
        return 'This command processes adding of new chat participant';
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
                    'With my help you can create polls and conduct them in your groups!',
                    sprintf('So you can create a poll with "%s" and start that poll in any group where i live with "%s name" or "%s number".', PollCreateCommand::getName(), PollStartCommand::getName(), PollStartCommand::getName()),
                    sprintf('You can find all your polls and statistic for them with help of the command "%s".', StartCommand::getName())
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
        if ($message->getText() == self::getName()){
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