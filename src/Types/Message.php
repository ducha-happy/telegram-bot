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

class Message
{
    protected $data;

    /**
     * Message constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId()
    {
        return $this->data['message_id'];
    }

    /**
     * @return string
     */
    public function getText()
    {
        $text = '';
        if (isset($this->data['text'])){
            $text = $this->data['text'];
        }

        return $text;
    }

    /**
     * @return array
     */
    public function getChat()
    {
        return $this->data['chat'];
    }

    /**
     * @return mixed
     */
    public function getChatId()
    {
        return $this->data['chat']['id'];
    }

    /**
     * @return mixed
     */
    public function getChatType()
    {
        return $this->data['chat']['type'];
    }

    /**
     * @return bool|mixed
     */
    public function getReplyToMessage()
    {
        if (isset($this->data['reply_to_message'])){
            return $this->data['reply_to_message'];
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getLocation()
    {
        if (isset($this->data['location'])){
            return $this->data['location'];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getFromFirstName()
    {
        return $this->data['from']['first_name'];
    }

    public function getFrom()
    {
        return $this->data['from'];
    }

    /**
     * Get from['id']
     * @return mixed
     */
    public function getUserId()
    {
        $from = $this->getFrom();

        return $from['id'];
    }

    /**
     * @return User|null
     */
    public function getNewChatParticipant()
    {
        if (isset($this->data['new_chat_participant'])){
            $temp = $this->data['new_chat_participant'];

            return $this->getUser($temp);
        }

        return null;
    }

    /**
     * @return User|null
     */
    public function getLeftChatParticipant()
    {
        if (isset($this->data['left_chat_participant'])){
            $temp = $this->data['left_chat_participant'];

            return $this->getUser($temp);
        }

        return null;
    }

    /**
     * @param array $temp
     * @return User
     */
    protected function getUser($temp)
    {
        $user = new User($temp['id'], $temp['is_bot'], $temp['first_name']);
        if (isset($temp['last_name'])){
            $user->setLastName($temp['last_name']);
        }
        if (isset($temp['username'])){
            $user->setUsername($temp['username']);
        }
        if (isset($temp['language_code'])){
            $user->setLanguageCode($temp['language_code']);
        }

        return $user;
    }
}