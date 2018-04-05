<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Formatter;

/**
 * @link https://core.telegram.org/bots/api#html-style
 */
final class HtmlFormatter
{
    public static function bold($str){
        return sprintf('<b>%s</b>', $str);
    }

    public static function italic($str){
        return sprintf('<i>%s</i>', $str);
    }

    public static function code($str){
        return sprintf('<code>%s</code>', $str);
    }

    public static function pre($str){
        return sprintf('<pre>%s</pre>', $str);
    }

    public static function link($url, $text){
        return sprintf('<a href="%s">%s</a>', $url, $text);
    }

}