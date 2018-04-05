<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Commands;

trait ArgumentsAwareTrait {

    /**
     * Keeps arguments of a command
     * @var array
     */
    protected $arguments;

    /**
     * @param array $arguments
     */
    protected function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
}