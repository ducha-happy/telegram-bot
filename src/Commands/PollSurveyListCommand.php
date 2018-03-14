<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Types\Group;

class PollSurveyListCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/surveylist';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Show list of running surveys (show chat ids). This command is available only for admin chat.';
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        $text = 'In Baghdad, everything is calm. No surveys found. Sleep peacefully.';
        $storage = $this->storage;
        $groupManager = $this->handler->getGroupManager();

        $key = PollSurvey::getStorageKey(10);
        $pattern = str_replace(10, "", $key);
        $temp = $storage->keys($pattern . '*');

        if (!empty($temp)){
            $keys = array();
            foreach ($temp as $key){
                $keys[] = str_replace($pattern, "", $key);
            }
            $titles = array();
            foreach ($keys as $chatId){
                $group = $groupManager->getGroup($chatId);
                $title = ' ('.$chatId.') ';
                if ($group instanceof Group){
                    $title .= $group->getTitle();
                }
                $titles[] = $title;
            }

            $text = 'We have: ' . implode(', ', $titles);
        }

        $this->telegram->sendMessage($this->handler->getTelegramBot()->getTelegramAdminChatId(), $text);
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            if ($message->getText() == $this->getName()
                && $message->getChatId() == $this->handler->getTelegramBot()->getTelegramAdminChatId()
            ){
                return true;
            }
        }

        return false;
    }
}