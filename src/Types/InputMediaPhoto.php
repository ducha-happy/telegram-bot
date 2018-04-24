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
 * Represents a photo to be sent.
 * @link https://core.telegram.org/bots/api#inputmediaphoto
 */
class InputMediaPhoto implements InputFile
{
    /**
     * Type of the result, must be photo
     * @var string
     */
    protected $type = 'photo';
    /**
     * File to send. Pass a file_id to send a file that exists on the Telegram servers (recommended),
     * pass an HTTP URL for Telegram to get a file from the Internet,
     * or pass "attach://<file_attach_name>" to upload a new one using multipart/form-data under <file_attach_name> name.
     * @var string
     */
    protected $media;
    /**
     * Optional. Caption of the photo to be sent, 0-200 characters
     * @var string
     */
    protected $caption;
    /**
     * Optional. Send Markdown or HTML, if you want Telegram apps to show bold, italic, fixed-width text or inline URLs in the media caption.
     * @var string
     */
    protected $parse_mode;

    public function __construct($media, $caption = null)
    {
        $this->media = $media;
        $this->caption = $caption;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @return mixed
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param string $parse_mode
     */
    public function setParseMode(string $parse_mode)
    {
        $this->parse_mode = $parse_mode;
    }

}