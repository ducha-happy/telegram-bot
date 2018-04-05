<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Types;

use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;
use PHPUnit\Framework\TestCase;

class InlineKeyboardMarkupTest extends TestCase
{
    public function testThatGetInlineKeyboardReturnArray()
    {
        $rows = array(
            array(new InlineKeyboardButton('Test1', '', 'test1')),
            array(new InlineKeyboardButton('Test2', '', 'test2'))
        );

        $keyboard = new InlineKeyboardMarkup($rows);
        $rows = $keyboard->getInlineKeyboard();
        $this->assertTrue(is_array($rows), 'var is array - that is not true');
    }
}
