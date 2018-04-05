<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Poll;

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