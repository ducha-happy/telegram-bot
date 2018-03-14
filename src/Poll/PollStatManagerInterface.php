<?php

namespace Ducha\TelegramBot\Poll;

/**
 * StorageInterface.
 *
 * @author Andre Vlasov <areyouhappyihopeso@gmail.com>
 */
interface PollStatManagerInterface
{
    /**
     * Get Stat by chatId and pollId
     * @param int $chatId
     * @param int $pollId
     * @return array
     */
    function getStat($chatId, $pollId);

    /**
     * Set Stat
     * @param int $chatId
     * @param int $pollId
     * @param PollSurvey $result
     */
    function setStat($chatId, $pollId, $result);

    /**
     * Remove Stat by chatId and pollId
     * @param int $chatId
     * @param int $pollId
     */
    function removeStat($chatId, $pollId);
}