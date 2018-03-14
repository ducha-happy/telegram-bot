<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Poll\Poll;

class PollRemoveCommand extends PollManagerAwareCommand
{
    use ArgumentsAwareTrait;

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/pollremove';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Remove a poll from poll`s user list. The command is available only for private chats. ' .  "\n" . 'Format: /pollremove number ';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $id = $this->arguments[0];
            $poll = $this->pollManager->getPollById($id);
            $text = sprintf('Sorry. Can not remove poll with number %s', $id);
            if ($poll instanceof Poll){
                $userId = $poll->getUserId();
                if ($userId == $message->getUserId()){
                    $this->pollManager->removePoll($id);
                    $text = sprintf('Ok. Your poll with number %s is removed!', $id);
                }
            }
            $this->telegram->sendMessage($message->getChatId(), $text);
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
            $text = $message->getText();
            $temp = $this->combOut($text);

            if (!empty($temp)) {
                if (count($temp) > 1) {
                    $args = $temp;
                    array_shift($args);
                    $this->setArguments($args);

                    if ($this->stringIsCommand($temp[0]) && preg_match("|^\d+$|", $this->arguments[0])){
                        if ($this->isChatTypeAvailable($message->getChatType()) == false){
                            $this->telegram->sendMessage($message->getChatId(), $this->getWarning());
                            return false;
                        }
                        return true;
                    }
                }

                if ($this->stringIsCommand($temp[0])){
                    $text = $this->getInListDescription();
                    if ($this->isChatTypeAvailable($message->getChatType()) == false){
                        $text .= "\n" . $this->getWarning();
                    }
                    $this->telegram->sendMessage($message->getChatId(), $text);
                }
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