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

interface PollManagerInterface
{
    /**
     * Get Polls by user_id
     * @param int $userId
     * @return array
     */
    function getPollsByUserId($userId);
    /**
     * Get Poll by id
     * @param int $id
     * @return Poll|false
     */
    function getPollById($id);
    /**
     * Get Poll by $userId
     * @param int $userId
     * @param string $name
     * @return Poll|false
     */
    function getPoll($userId, $name = null);
    /**
     * Save Poll in a storage
     * @param Poll $poll
     */
    function addPoll(Poll $poll);
    /**
     * Remove Poll from a storage
     * @param int $id
     * @return bool
     */
    function removePoll($id);
}