<?php

namespace Ducha\TelegramBot\Poll;

/**
 * StorageInterface.
 *
 * @author Andre Vlasov <areyouhappyihopeso@gmail.com>
 */
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