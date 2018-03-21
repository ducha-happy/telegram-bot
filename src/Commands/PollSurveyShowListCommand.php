<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\CallbackQuery;
use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;

class PollSurveyShowListCommand extends AbstractCommand
{
    /**
     * @var PollManagerInterface
     */
    protected $pollManager;
    /**
     * @var GroupManagerInterface
     */
    protected $groupManager;
    /**
     * @var PollStatManagerInterface
     */
    protected $statManager;
    /**
     * @var array
     */
    protected $userPolls;

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->groupManager = $this->handler->getGroupManager();
        $this->pollManager = $this->handler->getPollManager();
        $this->statManager = $this->handler->getPollSurveyStatManager();
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/surveyshowlist';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Show list of running and completed surveys. This command is available only for private chats and show polls(surveys) of those users.';
    }

    protected function filterKeys($keys, $pattern)
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

    /**
     * @return array
     */
    protected function getKeys()
    {
        $pattern = sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), '*', '*');
        $keys = $this->storage->keys($pattern);
        $keys = $this->filterKeys($keys, $pattern);

        $lines = array();
        foreach ($keys as $key){
            $temp = explode(".", $key);
            $pollId = array_pop($temp);
            if (!empty($this->userPolls)){
                if (array_search($pollId, $this->userPolls) !== false){
                    $chatId = array_pop($temp);
                    $poll = $this->pollManager->getPollById($pollId);
                    $group = $this->groupManager->getGroup($chatId);
                    $lines[$chatId.'.'.$pollId] = $group->getTitle() . ' - ' . $poll->getName();
                }
            }
        }

        return $lines;
    }

    protected function showMainMenu($chatId, $start = false)
    {
        $replies = $this->getKeys();
        if (empty($replies)){
            $text = 'You don`t have any uncompleted poll surveys now. Try next time.';
            $this->telegram->sendMessage($chatId, $text);
        }else{
            $text = 'Which poll survey do you want to look at ?';
            $buttons = array();
            foreach ($replies as $key => $value){
                $buttons[] = array(new InlineKeyboardButton($value, '', $key));
            }
            $keyboard = new InlineKeyboardMarkup($buttons);
            $keyboard = json_encode($keyboard);
            $replyMessageId = $this->getReplyMessageId($chatId);
            if (empty($replyMessageId) || $start){
                $response = $this->telegram->sendMessage($chatId, $text,  'HTML', false, null, $keyboard);
                $this->setReplyMessageId($chatId, $response['result']['message_id']);
            }else{
                $this->telegram->editMessageText($chatId, $replyMessageId, '', $text);
                $this->telegram->editMessageReplyMarkup($chatId, $replyMessageId, '', $keyboard);
            }
        }
    }

    protected function showResult($chatId, CallbackQuery $callback)
    {
        $data = explode(".", $callback->getData());
        if (count($data) != 2){
            throw new \LogicException('Callback data is not correct!');
        }

        $text = $this->statManager->getStat($data[0], $data[1]);
        $buttons = array(
            array(new InlineKeyboardButton('LIST OF SURVEYS', '', 'list'))
        );
        $keyboard = new InlineKeyboardMarkup($buttons);
        $keyboard = json_encode($keyboard);
        $replyMessageId = $this->getReplyMessageId($chatId);
        $this->telegram->editMessageText($chatId, $replyMessageId, '', $text);
        $this->telegram->editMessageReplyMarkup($chatId, $replyMessageId, '', $keyboard);
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $this->setUserPolls($message->getUserId());
            $this->showMainMenu($message->getChatId(), true);
        }elseif ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage();
            $from = $callback->getFrom();
            $chatId = $from['id'];
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $this->setUserPolls($chatId);
                $reply = $callback->getData();
                if (array_search($reply, array_keys($this->getKeys())) !== false){
                    $this->showResult($chatId, $callback);
                }elseif($reply == 'list'){
                    $this->showMainMenu($chatId);
                }
            }
        }
    }

    protected function setUserPolls($userId)
    {
        $this->userPolls = $this->storage->get(StorageKeysHolder::getUserPollsKey($userId));
    }

    /**
     * @param int $chatId private chat
     * @return int|null
     */
    protected function getReplyMessageId($chatId)
    {
        return $this->storage->get(StorageKeysHolder::getSurveyReplyMessageIdKey($chatId));
    }

    /**
     * @param int $chatId private chat
     * @param int $messageId for which
     */
    protected function setReplyMessageId($chatId, $messageId)
    {
        $this->storage->set(StorageKeysHolder::getSurveyReplyMessageIdKey($chatId), $messageId);
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            if ($this->stringIsCommand($message->getText())){
                if ($this->isChatTypeAvailable($message->getChatType())){
                    return true;
                }else{
                    $this->telegram->sendMessage($message->getChatId(), $this->getInListDescription());
                }
            }
        }

        if ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage(); $chatId = $message->getChatId();
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $reply = $callback->getData();
                // array_search($reply, array_keys($this->getKeys())) !== false
                if (array_key_exists($reply, $this->getKeys()) || $reply == 'list'){
                    return true;
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
        return array_search($type, array('private')) !== false;
    }
}