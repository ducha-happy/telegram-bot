<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Redis;

use Ducha\TelegramBot\Storage\StorageKeysHolder;

class PollSurveyStatManager extends PollStatManager
{
    /**
     * The key using to find a completed survey
     * @param int|string $chatId
     * @param int|string $pollId
     * @return string
     */
    public static function getStatStorageKey($chatId, $pollId)
    {
        return StorageKeysHolder::getNotCompletedSurveyKey($chatId, $pollId);
    }

    /**
     * @param int $pollId
     * @return boolean
     */
    public function hasSurveys($pollId)
    {
        $pattern = self::getStatStorageKey('*', $pollId);
        $keys = $this->storage->keys($pattern);
        $keys = static::filterKeys($keys, $pattern);

        return !empty($keys);
    }

    public static function filterKeys($keys, $pattern)
    {
        $fKeys = array();
        $temp = explode('.', $pattern);
        $temp[3] = '-\d{1,}'; $temp[4] = '\d{1,}';
        $pattern = '|' . implode('\.', $temp) . '|';

        foreach ($keys as $key){
            if (preg_match($pattern, $key)){
                $fKeys[] = $key;
            }
        }

        return $fKeys;
    }
}