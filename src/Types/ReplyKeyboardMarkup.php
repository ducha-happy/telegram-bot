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

class ReplyKeyboardMarkup implements \JsonSerializable
{
    use JsonSerializer;
    /**
     * Array of button rows, each represented by an Array of KeyboardButton objects
     * @var array
     */
    protected $keyboard;
    /**
     * Optional. Requests clients to resize the keyboard vertically for optimal fit (e.g., make the keyboard smaller if there are just two rows of buttons).
     * Defaults to false, in which case the custom keyboard is always of the same height as the app's standard keyboard.
     * @var bool
     */
    protected $resize_keyboard;
    /**
     * Optional. Requests clients to hide the keyboard as soon as it's been used.
     * The keyboard will still be available, but clients will automatically display the usual letter-keyboard in the chat –
     * the user can press a special button in the input field to see the custom keyboard again. Defaults to false.
     * @var bool
     */
    protected $one_time_keyboard;
    /**
     * Optional. Use this parameter if you want to show the keyboard to specific users only.
     * Targets:
     * 1) users that are @mentioned in the text of the Message object;
     * 2) if the bot's message is a reply (has reply_to_message_id), sender of the original message.
     * @var bool
     */
    protected $selective;

    public function __construct(array $rows, $resize_keyboard = false, $one_time_keyboard = false, $selective = false)
    {
        foreach ($rows as $row){
            foreach ($row as $button) {
                if (!is_string($button)){
                    throw new \LogicException('The button of the keyboard must be string');
                }
            }
        }

        $this->keyboard = $rows;
        $this->resize_keyboard = $resize_keyboard;
        $this->one_time_keyboard = $one_time_keyboard;
        $this->selective = $selective;
    }
}