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

/**
 * This object represents one button of an inline keyboard. You must use exactly one of the optional fields.
 */
class InlineKeyboardButton implements \JsonSerializable
{
    use JsonSerializer;

    /**
     * Label text on the button
     * @var string
     */
    protected $text;
    /**
     * Optional. HTTP url to be opened when button is pressed
     * @var string
     */
    protected $url;
    /**
     * Optional. Data to be sent in a callback query to the bot when button is pressed, 1-64 bytes
     * @var string
     */
    protected $callback_data;
    /**
     * Optional. If set, pressing the button will prompt the user to select one of their chats,
     * open that chat and insert the bot‘s username and the specified inline query in the input field.
     * Can be empty, in which case just the bot’s username will be inserted.
     * @var string
     */
    protected $switch_inline_query;
    /**
     * Optional. If set, pressing the button will insert the bot‘s username and the specified inline query in the current chat's input field.
     * Can be empty, in which case only the bot’s username will be inserted.
     * @var string
     */
    protected $switch_inline_query_current_chat;
    /**
     * Optional. Description of the game that will be launched when the user presses the button.
     * @var CallbackGame
     */
    protected $callback_game;
    /**
     * Optional. Specify True, to send a Pay button.
     * @var string
     */
    protected $pay;

    public function __construct($text, $url = null, $callback_data = null, $switch_inline_query = null, $switch_inline_query_current_chat = null, $callback_game = null, $pay = null)
    {
        if (!is_null($callback_game)){
            throw new \InvalidArgumentException('Sorry, but CallbackGame is not implemented yet');
        }
        $this->text = $text;
        $this->url = $url;
        $this->callback_data = $callback_data;
        $this->switch_inline_query = $switch_inline_query;
        $this->switch_inline_query_current_chat = $switch_inline_query_current_chat;
        $this->callback_game = $callback_game;
        $this->pay = $pay;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getPay()
    {
        return $this->pay;
    }

    /**
     * @return null
     */
    public function getCallbackData()
    {
        return $this->callback_data;
    }

    /**
     * @return null
     */
    public function getCallbackGame()
    {
        return $this->callback_game;
    }

    /**
     * @return null
     */
    public function getSwitchInlineQuery()
    {
        return $this->switch_inline_query;
    }

    /**
     * @return null
     */
    public function getSwitchInlineQueryCurrentChat()
    {
        return $this->switch_inline_query_current_chat;
    }

    /**
     * @param null $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @param null $callback_data
     */
    public function setCallbackData($callback_data)
    {
        $this->callback_data = $callback_data;
    }
}
