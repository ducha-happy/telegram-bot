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
use Ducha\TelegramBot\Process;
use Ducha\TelegramBot\Storage\RedisStorage;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\Group;
use Ducha\TelegramBot\Types\InputMediaPhoto;
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
     * @var \Twig_Environment $twig
     */
    protected $twig;

    /**
     * RedisPollManager constructor.
     * @param RedisStorage $storage
     * @param TranslatorInterface $translator
     */
    public function __construct(RedisStorage $storage, TranslatorInterface $translator)
    {
        $this->storage = $storage;
        $this->translator = $translator;

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../app/templates');
        $this->twig = new \Twig_Environment($loader);
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
     * Get Group, Poll, PollSurvey objects from storage
     * @param int $chatId
     * @param int $pollId
     * @return array|false
     */
    protected function getObjects($chatId, $pollId)
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

        $key = static::getStatStorageKey($chatId, $pollId);
        $survey = $this->storage->get($key);
        if (!$survey instanceof PollSurvey){
            return false;
        }

        return array($group, $poll, $survey);
    }

    /**
     * Get Stat of a survey
     * @param int $chatId
     * @param int $pollId
     * @return string|false
     */
    public function getStat($chatId, $pollId)
    {
        $objects = $this->getObjects($chatId, $pollId);
        if (empty($objects)){
            return false;
        }

        list($group, $poll, $survey) = $objects;

        $texts = array(
            HtmlFormatter::bold($group->getTitle() . ' - ' . $poll->getName()),
            HtmlFormatter::bold(
                sprintf('%s: %s', $this->translator->trans('total_voting'), count($group))
            ),
            static::UNDERLINE
        );

        return $this->getResult($texts, $survey);
    }

    /**
     * Compress js string - remove comments and \n symbols
     * @param string $str
     * @return string
     */
    protected static function compress($str)
    {
        $pattern = "|\/\*[^\/\*]+\*\/|";
        $pattern2 = "|^\/\/|";

        $arr = array();

        if (preg_match($pattern, $str)){
            $str = preg_replace($pattern, "", $str);
            $temp = explode(chr(10), $str);
            foreach ($temp as $line){
                $line = trim($line);
                if (preg_match($pattern2, $line)){
                    $line = '';
                }
                if (!empty($line)){
                    $arr[] = $line;
                }
            }
        }

        return implode(" ", $arr);
    }

    /**
     * Get Stat of a survey in the form of a graph (chart) - need node and highcharts-export-server installed
     * @param int $chatId
     * @param int $pollId
     * @param string $nodePath
     * @return string|false
     */
    public function getChart($chatId, $pollId, $nodePath = null)
    {
        $objects = $this->getObjects($chatId, $pollId);
        if (empty($objects)){
            return false;
        }

        list($group, $poll, $survey) = $objects;

        $state = $survey->getState();

        $command = 'which node';
        exec($command, $output);
        if (empty($output) || count($state) > 1){ // chart can be only for one question - not many
            return false;
        }

        $question = $state[0];
        $counter = array();
        foreach ($question['replies'] as $reply){
            $replyText = $reply['text'];
            if (!isset($counter[$replyText])){
                $counter[$replyText] = 0;
            }
            $counter[$replyText]++;
        }
        $answers = $data = array();
        foreach ($counter as $replyText => $score) {
            $answers[] = $replyText;
            $data[] = $score;
        }

        $obj = new \stdClass();
        $obj->name = HtmlFormatter::bold($group->getTitle() . ' - ' . $poll->getName());
        $obj->dashStyle = 'Solid';
        $obj->data = $data;

        $chartFile = Process::getTempDir() . '/chart' . $chatId . '-' . $pollId . '.png';

        $parameters = array(
            'title'    => $this->translator->trans('voting_results'),
            'subtitle' => $question['title'],
            'series'   => json_encode([$obj]),
            'answers'  => json_encode($answers),
            'y_title'  => $this->translator->trans('voted'),
            'chart_png_file' => $chartFile
        );

        $content = $this->twig->render('highcharts.js.twig', $parameters);

        $content = self::compress($content);
        $content = str_replace('"', '\"', $content);
        $command = 'node -e "' .$content . '"';
        if (!empty($nodePath)){
            $command = 'NODE_PATH="'.$nodePath.'"; ' . $command;
        }
        exec($command);

        if (file_exists($chartFile)){
            return $chartFile;
        }

        return false;
    }

    /**
     * @param array $texts
     * @param PollSurvey $survey
     * @param int $variant
     * @return bool|string
     */
    protected function getResult($texts, $survey, $variant = 1)
    {
        $state = $survey->getState();

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