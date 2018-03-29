<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/Types/InlineKeyboardMarkupTest.php
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
