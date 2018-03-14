<?php

namespace Ducha\TelegramBot\Redis;

use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Types\Group;

class PollStatManager implements PollStatManagerInterface
{
    const UNDERLINE = '-----------------';

    protected $storage;

    /**
     * RedisPollManager constructor.
     * @param RedisStorage $storage
     */
    public function __construct(RedisStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * The key using to find a completed survey
     * @param int $chatId
     * @param int $pollId
     * @return string
     */
    public static function getStatStorageKey($chatId, $pollId)
    {
//        return sprintf('telegram.polls.stat.%s.%s', $chatId, $pollId);
        return sprintf('telegram.poll.survey.completed.%s.%s', $chatId, $pollId);
    }

    public function setStat($chatId, $pollId, $result)
    {
        $key = static::getStatStorageKey($chatId, $pollId);
        $this->storage->set($key, $result);
    }

    public function removeStat($chatId, $pollId)
    {
        $key = static::getStatStorageKey($chatId, $pollId);
        $this->storage->remove($key);
    }

    /**
     * Get Stat of a survey
     * @param int $chatId
     * @param int $pollId
     * @return string|false
     */
    public function getStat($chatId, $pollId)
    {
        $key = Poll::getStorageKey($pollId);
        $poll = $this->storage->get($key);
        if (!$poll instanceof Poll){
            return false;
        }

        $key = Group::getStorageKey($chatId);
        $group = $this->storage->get($key);
        if (!$group instanceof Group){
            return false;
        }

        $texts = array(HtmlFormatter::bold($group->getTitle() . ' - ' . $poll->getName()), static::UNDERLINE);

        $key = static::getStatStorageKey($chatId, $pollId);
        $survey = $this->storage->get($key);

        if (!$survey instanceof PollSurvey){
            return false;
        }

        return $this->getResult($texts, $survey->getState());
    }

    /**
     * @param array $texts
     * @param array $state
     * @param int $variant
     * @return bool|string
     */
    protected function getResult($texts, $state, $variant = 1)
    {
        switch ($variant){
            case 1:
                foreach ($state as $question){
                    $lines = array(HtmlFormatter::bold($question['title']), static::UNDERLINE);
                    $counter = $temp = array();
                    foreach ($question['replies'] as $reply){
                        $replyText = $reply['text'];
                        if (!isset($counter[$replyText])){
                            $counter[$replyText] = 0;
                        }                        
                        $counter[$replyText]++;
                        $temp[$replyText][] = $reply['from']['first_name'];
                    }
                    foreach ($temp as $replyText => $names){
                        $lines[] = HtmlFormatter::bold(sprintf('Ответ(%s):', $replyText));
                        foreach ($names as $name){
                            $lines[] = $name;
                        }
                    }
                    $lines[] = static::UNDERLINE;
                    $lines[] = HtmlFormatter::bold(sprintf('Total voted: %s', count($counter)));
                    foreach ($counter as $replyText => $score) {
                        $lines[] = sprintf('%s - (%s)', $replyText, $score);
                    }
                    if (count($lines) > 3){
                        $texts[] = implode("\n", $lines);
                    }
                }
                if (!empty($texts)){
                    return implode("\n", $texts);
                }
                break;
            default:
                foreach ($state as $question){
                    $lines = array(HtmlFormatter::bold($question['title']), static::UNDERLINE);
                    $counter = array();
                    foreach ($question['replies'] as $reply){
                        $replyText = $reply['text'];
                        if (!isset($counter[$replyText])){
                            $counter[$replyText] = 0;
                        }
                        $lines[] = sprintf('%s - (%s)', $reply['from']['first_name'], $replyText);
                        $counter[$replyText]++;
                    }
                    $lines[] = static::UNDERLINE;
                    $lines[] = HtmlFormatter::bold(sprintf('Total voted: %s', count($counter)));
                    foreach ($counter as $replyText => $score) {
                        $lines[] = sprintf('%s - (%s)', $replyText, $score);
                    }
                    if (count($lines) > 3){
                        $texts[] = implode("\n", $lines);
                    }
                }
                if (!empty($texts)){
                    return implode("\n", $texts);
                }
                break;
        }

        return false;
    }

}