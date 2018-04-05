<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Types;

class User
{
    /**
     * Unique identifier for this user or bot
     * @var int
     */
    protected $id;
    /**
     * True, if this user is a bot
     * @var bool
     */
    protected $is_bot;
    /**
     * User‘s or bot’s first name
     * @var string
     */
    protected $first_name;
    /**
     * Optional. User‘s or bot’s last name
     * @var string
     */
    protected $last_name;
    /**
     * Optional. User‘s or bot’s username
     * @var string
     */
    protected $username;
    /**
     * Optional. User‘s or bot’s username
     * https://en.wikipedia.org/wiki/IETF_language_tag
     * Optional. IETF language tag of the user's language
     * @var string
     */
    protected $language_code;

    public function __construct($id, $is_bot, $first_name, $last_name = null, $username = null, $language_code = null)
    {
        $this->id = $id;
        $this->is_bot = $is_bot;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->username = $username;
        $this->language_code = $language_code;
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
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @return bool
     */
    public function isIsBot()
    {
        return $this->is_bot;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->language_code;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param bool $is_bot
     */
    public function setIsBot($is_bot)
    {
        $this->is_bot = $is_bot;
    }

    /**
     * @param string $language_code
     */
    public function setLanguageCode($language_code)
    {
        $this->language_code = $language_code;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

}