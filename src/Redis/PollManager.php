<?php

namespace Ducha\TelegramBot\Redis;

use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\Poll;

class PollManager implements PollManagerInterface
{
    protected $storage;

    /**
     * RedisPollManager constructor.
     * @param RedisStorage $storage
     */
    public function __construct(RedisStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritdoc
     */
    public function getPoll($userId, $name = null)
    {
        $polls = $this->getPollsByUserId($userId);
        foreach ($polls as $poll){
            if (is_null($name)){
                return $poll;
            }else{
                if ($poll->getName() == $name){
                    return $poll;
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getPollById($id)
    {
        $key = $this->storage->getStorageKey(array('poll', $id));

        return $this->storage->get($key);
    }

    /**
     * @inheritdoc
     */
    public function getPollsByUserId($userId)
    {
        $polls = array();
        $key = $this->storage->getStorageKey(array('polls', $userId));
        $list = $this->storage->getList($key);
        foreach ($list as $id){
            $key = $this->storage->getStorageKey(array('poll', $id));
            $polls[] = $this->storage->get($key);
        }

        return $polls;
    }

    /**
     * @inheritdoc
     */
    public function addPoll(Poll $poll)
    {
        $userId = $poll->getUserId();
        $polls = $this->getPollsByUserId($userId);

        foreach ($polls as $item){
            if ($item->getName() == $poll->getName()){
                throw new \LogicException('Can not add the poll because a poll with the same name already exists!');
            }
        }

        $id = $poll->getId();
        $this->storage->set($this->storage->getStorageKey(array('poll', $id)), $poll);
        $this->storage->addToList($this->storage->getStorageKey(array('polls', $userId)), $id);
    }

    /**
     * @inheritdoc
     */
    public function removePoll($id)
    {
        $poll = $this->getPollById($id);
        if ($poll instanceof Poll){
            $userId = $poll->getUserId();
            $key = $this->storage->getStorageKey(array('polls', $userId));
            $this->storage->removeFromList($key, $id);

            $key = $this->storage->getStorageKey(array('poll', $id));
            $this->storage->remove($key);
            $this->storage->clear(PollStatManager::getStatStorageKey('*', $id));

            return true;
        }
        return false;
    }
}