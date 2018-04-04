<?php

namespace Ducha\TelegramBot\Poll;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Storage\StorageInterface;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Types\ReplyKeyboardRemove;
use Symfony\Component\Translation\TranslatorInterface;

class PollSurvey implements \Serializable
{
    /**
     * Keeps Poll object
     *
     * @var Poll
     */
    protected $poll;

    /**
     * Keeps chat_id where Poll Survey was started
     * @var int
     */
    protected $chat_id;

    /**
     * Keeps init message id
     * @var int
     */
    protected $init_message_id;

    /**
     * Keeps replies on questions
     * @var array
     */
    protected $state;

    /**
     * Telegram bot api
     * @var Telegram
     */
    protected $telegram;

    /**
     * Storage
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Group Manager
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * Poll Stat Manager
     * @var PollStatManagerInterface
     */
    protected $statManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * PollSurvey constructor.
     *
     * @param string $chatId
     * @param Poll $poll
     * @param Telegram $telegram
     * @param StorageInterface $storage
     * @param CommandHandler $handler
     */
    public function __construct($chatId, Poll $poll, Telegram $telegram, StorageInterface $storage, CommandHandler $handler)
    {
        $this->chat_id = $chatId;
        $this->poll = $poll;
        $this->setServices($telegram, $storage, $handler);
    }

    /**
     * Get PollSurvey instance from storage.
     *
     * @param int $chatId
     * @param int $pollId
     * @param Telegram $telegram
     * @param StorageInterface $storage
     * @param CommandHandler $handler
     * @return PollSurvey|false
     */
    public static function getInstance($chatId, $pollId, Telegram $telegram, StorageInterface $storage, CommandHandler $handler)
    {
        $temp = $storage->get(self::getStorageKey($chatId, $pollId));
        if ($temp instanceof PollSurvey){
            $temp->setServices($telegram, $storage, $handler);

            return $temp;
        }

        return false;
    }

    /**
     * @param Telegram $telegram
     * @param StorageInterface $storage
     * @param CommandHandler $handler
     */
    protected function setServices(Telegram $telegram, StorageInterface $storage, CommandHandler $handler)
    {
        $this->telegram = $telegram;
        $this->storage = $storage;
        $this->statManager = $handler->getPollStatManager();
        $this->groupManager = $handler->getGroupManager();
        $this->translator = $handler->getContainer()->get('ducha.telegram-bot.translator');

        if (property_exists($this, 'poll_id')){
            $key = StorageKeysHolder::getPollKey($this->poll_id);
            $this->poll = $this->storage->get($key);
        }

        if (!$this->poll instanceof Poll){
            throw new \LogicException(sprintf('Property poll must be instance of class %s', Poll::class));
        }
    }

    public function getState()
    {
        return $this->state;
    }

    /**
     * @param PollQuestion $question
     * @param int $id
     * @return bool
     */
    protected function setQuestionMessageId(PollQuestion $question, $id)
    {
        foreach ($this->state as &$item){
            if ($item['title'] == $question->getTitle()){
                $item['message_id'] = $id;
                return true;
            }
        }
        
        return false;
    }

    public function getInitMessageId()
    {
        return $this->init_message_id;
    }

    public function getChatId()
    {
        return $this->chat_id;
    }

    public function serialize()
    {
        return serialize(array(
             $this->chat_id,
             $this->init_message_id,
             $this->state,
             $this->poll->getId()
        ));

    }

    public function unserialize($serialized)
    {
        list(
            $this->chat_id,
            $this->init_message_id,
            $this->state,
            $this->poll_id
            ) = unserialize($serialized);

        return $this;
    }

    /**
     * Finds out that all questions have all replies from all users in the chat
     *
     * @return bool
     */
    protected function haveAllReplies()
    {
        foreach ($this->state as $item){
            if (!isset($item['completed'])){
                return false;
            }
        }

        return true;
    }

    /**
     * Question array in state
     * If count(replies) == count(users of group) then this question will be marked as completed
     *
     * @param array $item by reference
     */
    protected function haveAllRepliesFor(&$item)
    {
        $group = $this->groupManager->getGroup($this->chat_id);
        if ($group == false || count($item['replies']) == count($group)){
            $item['completed'] = true;
        }
    }

    /**
     * Processes a message
     * @param Message $message
     */
    public function go(Message $message)
    {
        $replyToMessage = $message->getReplyToMessage();
        $from = $message->getFrom();
        
        if ($replyToMessage !== false){
            foreach ($this->state as &$item){
                if ($item['message_id'] == $replyToMessage['message_id'] && !isset($item['completed'])){
                    $item['replies'][ $from['id'] ] = array('from' => $from, 'text' => $message->getText());
                    $this->haveAllRepliesFor($item);
                    $this->storage->set(self::getStorageKey($this->chat_id, $this->poll->getId()), $this);

                    $keyboard = new ReplyKeyboardRemove(true, true);
                    $keyboard = json_encode($keyboard);
                    $username = '';
                    if (isset($from['username'])){
                        $username = $from['username'];
                    }else{
                        if (isset($from['first_name'])){
                            $username = $from['first_name'];
                        }
                    }

                    //$text = sprintf('ok @%s', $username);
                    $text = sprintf('ok %s', $username);

                    $this->telegram->sendMessage($this->chat_id, $text, 'HTML', false, $message->getId(), $keyboard);

                    if ($this->haveAllReplies()){
                        $this->end();
                    }else{
                        if (isset($item['completed'])){
                            $this->sendQuestion();
                        }
                    }
                    return;
                }
            }
        }
    }

    /**
     * @param Message $message
     */
    public function start(Message $message)
    {
        $this->init_message_id = $message->getId();

        foreach ($this->poll->getQuestions() as $question){
            $this->state[] = array(
                'title'      => $question->getTitle(),
                'message_id' => null,
                'replies'    => array()
            );
        }

        $this->sendQuestion();
    }

    /**
     * The key using in a process of survey
     * @param int $chatId
     * @param int $pollId
     * @return string
     */
    public static function getStorageKey($chatId, $pollId)
    {
        return sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), $chatId, $pollId);
    }

    /**
     * Finish PollSurvey
     */
    protected function end()
    {
        $keyboard = new ReplyKeyboardRemove(true, false);
        $keyboard = json_encode($keyboard);

        $this->statManager->setStat($this->chat_id, $this->poll->getId(), $this);
        $this->storage->remove(self::getStorageKey($this->chat_id, $this->poll->getId()));
        $result = $this->statManager->getStat($this->chat_id, $this->poll->getId());

        $text = HtmlFormatter::bold($this->translator->trans('poll_survey_finished'));

        if (!empty($result)){
            $text .= "\n" . $result;
        }
        $this->telegram->sendMessage($this->chat_id, $text, 'HTML', false, null, $keyboard);
    }

    /**
     * @param PollQuestion $question
     * @return bool
     */
    protected function hasReply(PollQuestion $question)
    {
        foreach ($this->state as $item){
            if ($item['title'] == $question->getTitle()){
                if (isset($item['completed'])){
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get PollQuestion
     * @return bool|PollQuestion
     */
    protected function getQuestion()
    {
       foreach ($this->poll->getQuestions() as $question){
           if (!$this->hasReply($question)){
               return $question;
           }
       }

       return false;
    }

    /**
     * send Question to users , 
     * save response set message_id for that question, 
     * save PollSurvey in storage
     */
    protected function sendQuestion()
    {
        $question = $this->getQuestion();
        if ($question instanceof PollQuestion){
            $response = $this->telegram->sendMessage($this->chat_id, $question->getTitle(), 'HTML', false, null, $question->getMarkup());
            if ($response){
                $this->setQuestionMessageId($question, $response['result']['message_id']);
                $this->storage->set(self::getStorageKey($this->chat_id, $this->poll->getId()), $this);
            }
        }
    }

    /**
     * Save survey in storage
     */
    public function save()
    {
        $this->storage->set(self::getStorageKey($this->chat_id, $this->poll->getId()), $this);
    }

    /**
     * Get raw survey from storage
     * @return mixed
     */
    public function get()
    {
        return $this->storage->get(self::getStorageKey($this->chat_id, $this->poll->getId()));
    }

    /**
     * @return bool|\Ducha\TelegramBot\Types\Group
     */
    public function getGroup()
    {
        return $this->groupManager->getGroup($this->chat_id);
    }

    /**
     * @return Poll
     */
    public function getPoll()
    {
        return $this->poll;
    }
   
}
