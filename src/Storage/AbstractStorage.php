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

/**
 * StorageInterface.
 *
 * @author Andre Vlasov <areyouhappyihopeso@gmail.com>
 */
abstract class AbstractStorage implements StorageInterface
{
    protected $id;
    protected $name;

    /**
     * form a storage key
     *
     * @param array $params
     * @return string
     */
    public function getStorageKey(array $params)
    {
        array_unshift($params, 'telegram');

        return implode(".", $params);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function get($key){ return false; }

    /**
     * @inheritdoc
     */
    public function set($key, $data){}

    /**
     * @inheritdoc
     */
    public function remove($key){}

    /**
     * Clear all session data in storage.
     */
    public function clear(){}
}
