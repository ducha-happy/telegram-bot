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
 * This object represents one button of the reply keyboard. For simple text buttons String can be used instead of this object to specify text of the button. Optional fields are mutually exclusive.
 * @link https://core.telegram.org/bots/api#keyboardbutton
 */
class KeyboardButton implements \JsonSerializable
{
    use JsonSerializer;

    /**
     * Label text on the button
     * @var string
     */
    protected $text;

    /**
     * Optional.  If True, the user's phone number will be sent as a contact when the button is pressed. Available in private chats only
     * @var boolean
     */
    protected $request_contact;
    /**
     * Optional. If True, the user's current location will be sent when the button is pressed. Available in private chats only
     * @var boolean
     */
    protected $request_location;

    // Note: request_contact and request_location options will only work in Telegram versions released after 9 April, 2016. Older clients will ignore them.

    /**
     * KeyboardButton constructor.
     * @param string $text
     * @param bool $request_contact
     * @param bool $request_location
     */
    public function __construct($text, $request_contact = false, $request_location = false)
    {
        $this->text = $text;
        $this->request_contact = $request_contact;
        $this->request_location = $request_location;
    }

}