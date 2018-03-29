<?php

namespace Ducha\TelegramBot\Redis;

use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Types\Group;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Types\User;

/**
 * Control chats of a group type where users are writing their messages.
 * Is intended to collect users.
 */
class GroupManager implements GroupManagerInterface
{
    protected $storage;

    /**
     * GroupManager constructor.
     * @param RedisStorage $storage
     */
    public function __construct(RedisStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param $id
     * @param $title
     * @return Group
     */
    public function addGroup($id, $title)
    {
        $group = new Group($id, $title);
        $key = Group::getStorageKey($id);
        $this->storage->set($key, $group);

        return $group;
    }

    /**
     * @param $id
     * @return bool|Group
     */
    public function getGroup($id)
    {
        $key = Group::getStorageKey($id);

        return $this->storage->get($key);
    }

    /**
     * @param $id
     */
    public function removeGroup($id)
    {
        $key = Group::getStorageKey($id);

        $this->storage->remove($key);
    }

    /**
     * Find a real user. Is he in a group? Add if not
     * @param Message $message
     */
    public function lookAtMessage(Message $message)
    {
        $user = $message->getFrom();
        $userId = $user['id'];
        $chat = $message->getChat();

        $group = false;

        if (array_search($chat['type'], array('group', 'supergroup')) !== false){
            $group = $this->getGroup($chat['id']);
            if ($group == false){
                $group = $this->addGroup($chat['id'], $chat['title']);
            }
        }

        if ($group != false){
            $groupChange = false;
            if (!isset($group[$userId])){
                $group[$userId] = $user;
                $groupChange = true;
            }

            // add new participant to a group
            $participant = $message->getNewChatParticipant();
            if ($participant instanceof User){
                if ($participant->isIsBot() == false){
                    $participantId = $participant->getId();
                    if (!isset($group[$participantId])){
                        $group[$participantId] = $participant->toArray();
                        $groupChange = true;
                    }
                }
            }

            // remove participant from the group
            $participant = $message->getLeftChatParticipant();
            if ($participant instanceof User){
                if ($participant->isIsBot() == false){
                    $participantId = $participant->getId();
                    if (isset($group[$participantId])){
                        unset($group[$participantId]);
                        $groupChange = true;
                    }
                }
            }

            if ($groupChange){
                $key = Group::getStorageKey($chat['id']);
                $this->storage->set($key, $group);
            }
        }
    }
}