<?php

namespace Ducha\TelegramBot\Types;

use Ducha\TelegramBot\Storage\StorageKeysHolder;

class Group implements \Countable, \ArrayAccess
{
    /**
     * Identifier
     *
     * @var int
     */
    protected $id;
    /**
     * title of group
     *
     * @var string
     */
    protected $title;
    /**
     * Users
     *
     * @var array
     */
    protected $users;

    /**
     * @param int $id
     * @return string
     */
    public static function getStorageKey($id)
    {
        return StorageKeysHolder::getGroupKey($id);
    }

    /**
     * The group_id number must be negative
     *
     * @param int $id
     * @param string $title
     * @param array $users
     */
    public function __construct($id, $title, $users = array())
    {
        if (empty($id) || $id > 0){
            throw new \InvalidArgumentException('bad argument: the group_id number must be negative');
        }

        $this->id = $id;
        $this->title = $title;
        $this->users = $users;
    }

    /**
     * Test user argument: user array must have id key
     * @param array $user
     */
    protected function testUser(array $user)
    {
        if (!isset($user['id'])){
            throw new \InvalidArgumentException('bad argument: user array must have id key');
        }
    }

    /**
     * @param array $user
     * @return bool
     */
    protected function userIsNotBot(array $user)
    {
        $this->testUser($user);

        if ($user['is_bot'] == false){
            return true;
        }

        return false;
    }

    /**
     * @param array $user
     * @return bool
     */
    protected function userExists(array $user)
    {
        $this->testUser($user);

        $id = $user['id'];
        if (isset($this->users[$id])){
            return true;
        }

        return false;
    }

    protected function addUser(array $user)
    {
        $this->testUser($user);

        $this->users[$user['id']] = $user;
    }

    /**
     * @param integer $id
     * @return false|array
     */
    public function getUser($id)
    {
        if (isset($this->users[$id])){
            return $this->users[$id];
        }

        return false;
    }

    /**
     * @param $name
     * @return false|array
     */
    public function getUserByFirstName($name)
    {
        foreach ($this->users as $user){
            if ($user['first_name'] == $name){
                return $user;
            }
        }

        return false;
    }

    /**
     * @param $name
     * @return false|array
     */
    public function getUserByUsername($name)
    {
        foreach ($this->users as $user){
            if (isset($user['username']) && $user['username'] == $name){
                return $user;
            }
        }

        return false;
    }

    /**
     * How much users we have in the group?
     * @return int
     */
    public function count()
    {
        return count($this->users);
    }

    /**
     * @param int $offset user_id
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->users[$offset]);
    }

    /**
     * @param int $offset user_id
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->users[$offset];
    }

    /**
     * @param int $offset user_id
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->testUser($value);

        if ($this->userExists($value) == false && $this->userIsNotBot($value) == true){
            $this->users[$offset] = $value;
        }
    }

    /**
     * Remove user from group
     * @param int $offset user_id
     */
    public function offsetUnset($offset)
    {
        unset($this->users[$offset]);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}