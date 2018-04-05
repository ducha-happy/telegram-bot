<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Storage;

interface StorageInterface
{
    /**
     * Returns the storage ID.
     *
     * @return string The storage ID or empty
     */
    public function getId();

    /**
     * Sets the storage ID.
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Returns the storage name.
     *
     * @return mixed The storage name
     */
    public function getName();

    /**
     * Sets the storage name.
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Get data from storage by key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Test that the key exists
     * @param $key
     * @return bool
     */
    public function exists($key);

    /**
     * Save data in storage
     *
     * @param string $key
     * @param string $data
     */
    public function set($key, $data);

    /**
     * Remove data from storage
     *
     * @param string $key
     */
    public function remove($key);

    /**
     * Remove all data from storage.
     */
    public function clear();

    /**
     * Gets all keys according to the pattern
     *
     * @param string $pattern
     * @return mixed
     */
    public function keys($pattern);
}
