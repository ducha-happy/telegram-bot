<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Storage;

/**
 * Class - Storage Keys Holder
 *
 * @method static getCompletedSurveyPattern
 * @method static getCompletedSurveyKey($chatId, $pollId)
 * @method static getNotCompletedSurveyPattern
 * @method static getNotCompletedSurveyKey($chatId, $pollId)
 * @method static getPollPattern
 * @method static getPollKey($pollId)
 * @method static getPollCreatePattern
 * @method static getPollCreateKey($chatId)
 * @method static getGroupPattern
 * @method static getGroupKey($groupId)
 * @method static getPollMaxIdPattern
 * @method static getPollMaxIdKey
 * @method static getUserPollsPattern
 * @method static getUserPollsKey($userId)
 * @method static getSurveyReplyMessageIdPattern
 * @method static getSurveyReplyMessageIdKey($chatId)
 * @method static getCompletedSurveyReplyMessageIdPattern
 * @method static getCompletedSurveyReplyMessageIdKey
 * @method static getMenuReplyMessageIdPattern
 * @method static getMenuReplyMessageIdKey($chatId)
 */
class StorageKeysHolder
{
    private static $prefix                                    = 'telegram';
    private static $completedSurveyPattern                    = 'poll.survey.completed.%s.%s';
    private static $notCompletedSurveyPattern                 = 'poll.survey.%s.%s';
    private static $pollPattern                               = 'poll.%s';
    private static $pollCreatePattern                         = 'poll.create.%s';
    private static $groupPattern                              = 'group.%s';
    private static $pollMaxIdPattern                          = 'poll.maxId';
    private static $userPollsPattern                          = 'polls.%s';
    private static $surveyReplyMessageIdPattern               = 'poll.survey.ReplyMessageId.%s';
    private static $completedSurveyReplyMessageIdPattern      = 'poll.completed.ReplyMessageId.%s';
    private static $menuReplyMessageIdPattern                 = 'menu.ReplyMessageId.%s';

    public static function getPrefix()
    {
        return self::$prefix;
    }

    public static function setPrefix($prefix)
    {
        self::$prefix = $prefix;
    }

    /**
     * @param string $method
     * @param mixed $args
     * @return string|null
     */
    public static function __callStatic($method, $args)
    {
        $vars = get_class_vars(StorageKeysHolder::class);
        $methods = array();
        foreach ($vars as $key => $value){
            if (preg_match('|Pattern$|', $key)){
                $methods['get' . ucfirst($key)] = function() use ($key) {
                    return implode('.', array(self::$prefix, self::$$key));
                };
                $methods['get' . ucfirst(str_replace('Pattern', 'Key', $key))] = function() use ($args, $key) {
                    $pattern = call_user_func(array(StorageKeysHolder::class, 'get' . ucfirst($key)));
                    return self::sprintf_array($pattern, $args);
                };
            }
        }

        if (isset($methods[$method])){
            return call_user_func_array($methods[$method], $args);
        }
    }

    /**
     * @param string $str
     * @param array $args
     * @return string
     */
    public static function sprintf_array($str, $args){
        $temp = explode('.', $str);
        $placeHolderCount = count(array_keys($temp, '%s'));
        if ($placeHolderCount != count($args)){
            throw new \InvalidArgumentException(
                sprintf('Number of items in the second argument don`t corresponds to number of place holders in the first one. %s must be but there is %s ', $placeHolderCount, count($args))
            );
        }
        foreach ($temp as $key => $value){
            if ($value == '%s'){
                $temp[$key] = array_shift($args);
            }
        }
        return implode('.', $temp);
    }
}