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

interface InputFile
{
    /**
     * Get type of media
     * @var string
     */
    public function getType();
    /**
     * Get content of media
     * @var string
     */
    public function getMedia();
}