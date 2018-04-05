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

use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\Group;
use Symfony\Component\Translation\TranslatorInterface;

class PollStatManager implements PollStatManagerInterface
{
    const UNDERLINE = '-----------------';

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var RedisStorage $storage
     */
    protected $storage;

    /**
     * RedisPollManager constructor.
     * @param RedisStorage $storage
     * @param TranslatorInterface $translator
     */
    public function __construct(RedisStorage $storage, TranslatorInterface $translator)
    {
        $this->storage = $storage;
        $this->translator = $translator;
    }

    /**
     * The key using to find a completed survey
     * @param int $chatId
     * @param int $pollId
     * @return string
     */
    public static function getStatStorageKey($chatId, $pollId)
    {
        return StorageKeysHolder::getCompletedSurveyKey($chatId, $pollId);
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
        $key = StorageKeysHolder::getPollKey($pollId);
        $poll = $this->storage->get($key);
        if (!$poll instanceof Poll){
            return false;
        }

        $key = StorageKeysHolder::getGroupKey($chatId);
        $group = $this->storage->get($key);
        if (!$group instanceof Group){
            return false;
        }

        $texts = array(
            HtmlFormatter::bold($group->getTitle() . ' - ' . $poll->getName()),
            HtmlFormatter::bold(
                sprintf('%s: %s', $this->translator->trans('total_voting'), count($group))
            ),
            static::UNDERLINE
        );

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
                $questionCounter = 0;
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
                        $lines[] = HtmlFormatter::bold(
                            sprintf('%s(%s):', $this->translator->trans('response'), $replyText)
                        );
                        foreach ($names as $name){
                            $lines[] = $name;
                        }
                    }
                    $lines[] = static::UNDERLINE;
                    $lines[] = HtmlFormatter::bold(
                        sprintf('%s: ', $this->translator->trans('total_voted'))
                    );
                    foreach ($counter as $replyText => $score) {
                        $lines[] = sprintf('%s - (%s)', $replyText, $score);
                    }
                    $questionCounter++;
                    if (count($state) > $questionCounter){
                        $lines[] = static::UNDERLINE;
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
                $questionCounter = 0;
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
                    $lines[] = HtmlFormatter::bold(
                        sprintf('%s:', $this->translator->trans('total_voted'))
                    );
                    foreach ($counter as $replyText => $score) {
                        $lines[] = sprintf('%s - (%s)', $replyText, $score);
                    }
                    $questionCounter++;
                    if (count($state) > $questionCounter){
                        $lines[] = static::UNDERLINE;
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