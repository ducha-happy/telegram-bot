<?php

namespace Ducha\TelegramBot\Storage;

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

    public static function getPrefix()
    {
        return self::$prefix;
    }

    public static function setPrefix($prefix)
    {
        self::$prefix = $prefix;
    }

    public static function getPollCreatePattern()
    {
        return implode('.', array(self::$prefix, self::$pollCreatePattern));
    }

    public static function getPollCreateKey($userId)
    {
        return sprintf(self::getPollCreatePattern(), $userId);
    }

    public static function getCompletedSurveyPattern()
    {
        return implode('.', array(self::$prefix, self::$completedSurveyPattern));
    }

    public static function getCompletedSurveyKey($chatId, $pollId)
    {
        return sprintf(self::getCompletedSurveyPattern(), $chatId, $pollId);
    }

    public static function getNotCompletedSurveyPattern()
    {
        return implode('.', array(self::$prefix, self::$notCompletedSurveyPattern));
    }

    public static function getNotCompletedSurveyKey($chatId, $pollId)
    {
        return sprintf(self::getNotCompletedSurveyPattern(), $chatId, $pollId);
    }

    public static function getPollPattern()
    {
        return implode('.', array(self::$prefix, self::$pollPattern));
    }

    public static function getPollKey($id)
    {
        return sprintf(self::getPollPattern(), $id);
    }

    public static function getGroupPattern()
    {
        return implode('.', array(self::$prefix, self::$groupPattern));
    }

    public static function getGroupKey($id)
    {
        return sprintf(self::getGroupPattern(), $id);
    }

    public static function getPollMaxIdPattern()
    {
        return implode('.', array(self::$prefix, self::$pollMaxIdPattern));
    }

    public static function getUserPollsPattern()
    {
        return implode('.', array(self::$prefix, self::$userPollsPattern));
    }

    public static function getSurveyReplyMessageIdPattern()
    {
        return implode('.', array(self::$prefix, self::$surveyReplyMessageIdPattern));
    }

    public static function getCompletedSurveyReplyMessageIdPattern()
    {
        return implode('.', array(self::$prefix, self::$completedSurveyReplyMessageIdPattern));
    }

    public static function getCompletedSurveyReplyMessageIdKey($userId)
    {
        return sprintf(self::getCompletedSurveyReplyMessageIdPattern(), $userId);
    }

    public static function getUserPollsKey($userId)
    {
        return sprintf(self::getUserPollsPattern(), $userId);
    }

    public static function getSurveyReplyMessageIdKey($userId)
    {
        return sprintf(self::getSurveyReplyMessageIdPattern(), $userId);
    }



}