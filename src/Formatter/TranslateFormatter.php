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

final class TranslateFormatter
{
    /**
     * String is divided on pieces by \n
     * and empty spaces is removed in the end and beginning of each item
     * Empty item is removed too
     * The remaining elements are glued back and returned
     * @param $str
     * @return string
     */
    public static function format($str)
    {
        $temp = explode("\n", $str);
        $arr = array();
        foreach ($temp as $value){
            $value = trim($value);
            if (!empty($value)){
                $arr[] = $value;
            }
        }

        return implode("\n", $arr);
    }
}