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

class ReplyKeyboardRemove implements \JsonSerializable
{
    use JsonSerializer;
    /**
     * Requests clients to remove the custom keyboard (user will not be able to summon this keyboard;
     * if you want to hide the keyboard from sight but keep it accessible, use one_time_keyboard in ReplyKeyboardMarkup)
     * @var true
     */
    protected $remove_keyboard;
    /**
     * Optional. Use this parameter if you want to remove the keyboard for specific users only.
     * Targets:
     * 1) users that are @mentioned in the text of the Message object;
     * 2) if the bot's message is a reply (has reply_to_message_id), sender of the original message.
     * @var bool
     */
    protected $selective;

    public function __construct($remove_keyboard = true, $selective = false)
    {
        $this->remove_keyboard = $remove_keyboard;
        $this->selective = $selective;
    }
}