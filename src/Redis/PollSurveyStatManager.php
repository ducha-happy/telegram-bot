<?php

namespace Ducha\TelegramBot\Redis;

use Ducha\TelegramBot\Storage\StorageKeysHolder;

class PollSurveyStatManager extends PollStatManager
{
    /**
     * The key using to find a completed survey
     * @param int $chatId
     * @param int $pollId
     * @return string
     */
    public static function getStatStorageKey($chatId, $pollId)
    {
        return StorageKeysHolder::getNotCompletedSurveyKey($chatId, $pollId);
    }
}