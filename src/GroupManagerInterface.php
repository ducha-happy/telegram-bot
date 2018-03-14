<?php

namespace Ducha\TelegramBot;

use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Types\Group;

interface GroupManagerInterface
{
    /**
     * @param $id
     * @param $title
     * @return Group
     */
    public function addGroup($id, $title);

    /**
     * @param $id
     * @return bool|Group
     */
    public function getGroup($id);

    /**
     * @param $id
     */
    public function removeGroup($id);

    /**
     * Find a real user. Is he in a group? Add if not
     * @param Message $message
     */
    public function lookAtMessage(Message $message);
}


