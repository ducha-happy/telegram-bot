<?php

namespace Ducha\TelegramBot\Commands;

class PolllistCommand extends PollManagerAwareCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/polllist';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Show poll list of a user. The command is available only for private chats.';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $chatId = $message->getChatId();
            $from = $message->getFrom();
            $polls = $this->pollManager->getPollsByUserId($from['id']);
            $lines = array();
            foreach ($polls as $poll){
                $lines[] = $poll->getId() . ' ' . $poll->getName();
            }
            if (count($lines) > 0){
                $text = implode("\n", $lines);
            }else{
//                $text = 'You do not have any polls! You can create them using the command ' . PollCreateCommand::getName();
                $text = 'У вас нет ни одного опроса! Вы можете создать их с помощью команды ' . PollCreateCommand::getName();
            }

            $this->telegram->sendMessage($chatId, $text);
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
                if ($this->isChatTypeAvailable($message->getChatType()) == false){
                    $text = implode("\n", array($this->getInListDescription(), $this->getWarning()));
                    $this->telegram->sendMessage($message->getChatId(), $text);
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isChatTypeAvailable($type)
    {
        return array_search($type, array('private')) !== false;
    }
}