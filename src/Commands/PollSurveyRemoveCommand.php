<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Types\Group;

class PollSurveyRemoveCommand extends AbstractCommand
{
    use ArgumentsAwareTrait;

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/surveyremove';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Remove survey for a chat. This command is available only for admin chat.' . "\n" .
            'Format: ' . static::getName() . ' chat_id';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        $chatId = $this->arguments[0];
        $storage = $this->storage;

        #TODO must be changed - need pollId for key

        $key = PollSurvey::getStorageKey($chatId);
        $storage->remove($key);

        $groupManager = $this->handler->getGroupManager();
        $group = $groupManager->getGroup($chatId);
        $title = ' ('.$chatId.') ';
        if ($group instanceof Group){
            $title .= $group->getTitle();
        }

        $this->telegram->sendMessage($this->handler->getTelegramBot()->getTelegramAdminChatId(), sprintf('Ok. PollSurvey %s was removed', $title));
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            if ($message->getChatId() == $this->handler->getTelegramBot()->getTelegramAdminChatId()){

                $text = $message->getText();
                $temp = $this->combOut($text);

                if (!empty($temp)) {
                    if (count($temp) > 1) {
                        $args = $temp;
                        array_shift($args);
                        $this->setArguments($args);
                        if ($temp[0] == $this->getName() && preg_match("|^-\d+$|", $this->arguments[0])){
                            return true;
                        }
                    }

                    if ($temp[0] == $this->getName()){
                        $text = $this->getInListDescription();
                        $this->telegram->sendMessage($message->getChatId(), $text);
                    }
                }
            }
        }

        return false;
    }
}