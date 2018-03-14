<?php

namespace Ducha\TelegramBot\Types;

class CallbackQuery
{
    /**
     * Unique identifier for this query
     * @var string
     */
    protected $id;
    /**
     * Sender
     * @var User
     */
    protected $from;
    /**
     * Optional. Message with the callback button that originated the query. Note that message content and message date will not be available if the message is too old
     * @var Message
     */
    protected $message;
    /**
     * Optional. Identifier of the message sent via the bot in inline mode, that originated the query.
     * @var string
     */
    protected $inline_message_id;
    /**
     * Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent. Useful for high scores in games.
     * @var string
     */
    protected $chat_instance;
    /**
     * Optional. Data associated with the callback button. Be aware that a bad client can send arbitrary data in this field.
     * @var string
     */
    protected $data;
    /**
     * Optional. Short name of a Game to be returned, serves as the unique identifier for the game
     * @var string
     */
    protected $game_short_name;

    public function __construct($id, $from, $message = null, $inline_message_id = null, $chat_instance, $data = null, $game_short_name = null)
    {
        $this->id = $id;
        $this->from = $from;
        $this->message = $message;
        $this->inline_message_id = $inline_message_id;
        $this->chat_instance = $chat_instance;
        $this->data = $data;
        $this->game_short_name = $game_short_name;
    }

    /**
     * @return string
     */
    public function getChatInstance()
    {
        return $this->chat_instance;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return User
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getGameShortName()
    {
        return $this->game_short_name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getInlineMessageId()
    {
        return $this->inline_message_id;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }
}