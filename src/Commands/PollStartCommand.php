<?php
/*
 * Start a poll
 * format: 1) /pollstart 2) /pollstart name
 */
namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollSurvey;
use Ducha\TelegramBot\Storage\StorageKeysHolder;

class PollStartCommand extends AbstractCommand
{
    use ArgumentsAwareTrait;

    /**
     * polls keeper
     * @var PollManagerInterface
     */
    protected $pollManager;

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram-bot.poll.manager');
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/pollstart';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Start poll. The command is available only in group chat. ' . "\n" .
            ' Formats: 1) ' . static::getName() . ' number ; 2) ' . static::getName() . ' string ; 3) ' . static::getName();
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $poll = $this->getPoll($message->getUserId());
            if ($poll instanceof Poll){
                $pollSurvey = PollSurvey::getInstance($message->getChatId(), $poll->getId(), $this->telegram, $this->storage, $this->handler);
                if ($pollSurvey instanceof PollSurvey){
                    $this->telegram->sendMessage($message->getChatId(), 'Poll Survey already goes in this chat. Try once more later.');
                }else{
                    if ($this->hasAnyPollSurveyForChat($message->getChatId())){
                        $this->telegram->sendMessage($message->getChatId(), 'Other Poll Survey already goes in this chat. Try once more later.');
                    }else{
                        $pollSurvey = new PollSurvey($message->getChatId(), $poll, $this->telegram, $this->storage, $this->handler);
                        $pollSurvey->start($message);
                    }
                }
            }else{
                $this->telegram->sendMessage($message->getChatId(),
                 'Can not find any poll. Seems you need to create a poll, the one at least.' . $this->getWarning()
                );
            }
        }
    }

    /**
     * @param int $chatId
     * @return boolean
     */
    protected function hasAnyPollSurveyForChat($chatId)
    {
        $pattern = sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), $chatId, '*');
        $keys = $this->storage->keys($pattern);

        return !empty($keys);
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $text = $message->getText();
            $temp = $this->combOut($text);

            if (!empty($temp) && $this->stringIsCommand($temp[0])){
                if ($this->isChatTypeAvailable($message->getChatType())){
                    if (count($temp) > 1){
                        $args = $temp; array_shift($args);
                        $this->setArguments($args);
                    }
                    return true;
                }else{
                    $from = $message->getFrom();
                    $this->telegram->sendMessage($from['id'], $this->getInListDescription());
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isChatTypeAvailable($type)
    {
        return array_search($type, array('group', 'supergroup')) !== false;
    }

    /**
     * @param int $userId
     * @return bool|Poll
     */
    protected function getPoll($userId)
    {
        $pollName = null;
        if (isset($this->arguments[0])){
            $temp = $this->arguments[0];
            if (preg_match("|^\d+$|", $temp)){
                $id = $this->arguments[0];
            }else{
                $pollName = $this->arguments[0];
            }
        }

        if (isset($id)){
            $poll = $this->pollManager->getPollById($id);
            if ($poll instanceof Poll){
                if ($poll->getUserId() == $userId){
                    return $poll;
                }
            }
        }

        return $this->pollManager->getPoll($userId, $pollName);
    }
}