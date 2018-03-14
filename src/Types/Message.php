<?php

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
}