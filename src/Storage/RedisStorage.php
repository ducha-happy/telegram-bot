<?php

/*
 * This file is part of the ducha/telegram-bot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Storage;

use Predis\Client as Redis;

/**
 * StorageInterface.
 *
 * @author Andre Vlasov <areyouhappyihopeso@gmail.com>
 */
class RedisStorage extends AbstractStorage
{
    protected $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function incr($key)
    {
        return $this->redis->incr($key);
    }

    public function decr($key)
    {
        return $this->redis->decr($key);
    }

    public function addToSet($key, $values)
    {
        $this->redis->sadd($key, $values);
    }

    public function existsInSet($key, $value)
    {
        return $this->redis->sismember($key, $value);
    }

    public function getSameValuesInSets($key1, $key2)
    {
        // sinter
        return $this->redis->sinter($key1, $key2);
    }

    // sismember

    public function addToListIfKeyExists($key, $value){
        $this->redis->lpushx($key, $value);
    }

    public function addToList($key, $value){
        $this->redis->lpush($key, $value);
    }

    public function removeFromList($key, $value){
        $list = $this->getList($key);
        foreach ($list as $index => $val){
            if ($value == $val){
                $this->redis->lrem($key, $index, $value);
            }
        }
    }

    public function getList($key){
        return $this->redis->lrange($key, 0, -1);
    }

    public function countList($key){
        return $this->redis->llen($key);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        switch ($this->redis->type($key)){
            case 'list':
                $data = $this->getList($key);
                break;
            default:
                $data = $this->redis->get($key);
                $temp = @unserialize($data);
                if ($temp !== false){
                    $data = $temp;
                }
                break;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $data)
    {
        if (is_array($data) || is_object($data)){
            $data = serialize($data);
        }

        $this->redis->set($key, $data);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        $this->redis->del($key);
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        return $this->redis->exists($key);
    }

    /**
     * @inheritdoc
     */
    public function clear($pattern = null)
    {
        if (empty($pattern)){
            $pattern = StorageKeysHolder::getPrefix().'*';
        }
        $keys = $this->keys($pattern);
        foreach ($keys as $key){
            $this->remove($key);
        }
    }

    /**
     * @param string $pattern example pattern*, *pattern*, *pattern, pattern
     * @return mixed
     */
    public function keys($pattern)
    {
        return $this->redis->keys($pattern);
    }
}
