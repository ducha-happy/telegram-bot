<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Redis\PollStatManager;
use Ducha\TelegramBot\Types\CallbackQuery;
use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;

class PollStatCommand extends AbstractCommand
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

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->groupManager = $this->handler->getGroupManager();
        $this->pollManager = $this->handler->getPollManager();
        $this->statManager = $this->handler->getPollStatManager();
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/pollstat';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Show list of completed polls for a chat.';
    }

    /**
     * @return array
     */
    protected function getKeys()
    {
        $temp = explode('.', PollStatManager::getStatStorageKey(1, 1)); // arguments are not important here
        array_pop($temp); array_pop($temp);
        $pattern = implode('.', $temp);
        $keys = $this->storage->keys($pattern.'*');
        $lines = array();
        foreach ($keys as $key){
            $temp = explode(".", $key);
            $pollId = array_pop($temp);
            $chatId = array_pop($temp);
            $poll = $this->pollManager->getPollById($pollId);
            $group = $this->groupManager->getGroup($chatId);
            $lines[$chatId.'.'.$pollId] = $group->getTitle() . ' - ' . $poll->getName();
        }

        return $lines;
    }

    protected function showMainMenu($chatId, $start = false)
    {
        $text = 'Which poll do you want to look at';
        $replies = $this->getKeys();

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

    protected function showResult($chatId, CallbackQuery $callback)
    {
        $data = explode(".", $callback->getData());
        if (count($data) != 2){
            throw new \LogicException('Callback data is not correct!');
        }

        $text = $this->statManager->getStat($data[0], $data[1]);
        $buttons = array(
            array(new InlineKeyboardButton('LIST OF POLLS', '', 'list'))
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
            $this->showMainMenu($message->getChatId(), true);
        }elseif ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage();
            $from = $callback->getFrom();
            $chatId = $from['id'];
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $reply = $callback->getData();
                if (array_search($reply, array_keys($this->getKeys())) !== false){
                    $this->showResult($chatId, $callback);
                }elseif($reply == 'list'){
                    $this->showMainMenu($chatId);
                }
            }
        }
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
                if (array_search($reply, array_keys($this->getKeys())) !== false || $reply == 'list'){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int $chatId private chat
     * @return string
     */
    protected function getStorageKey($chatId)
    {
        return sprintf('telegram.poll.completed.%s', $chatId);
    }

    /**
     * @param int $chatId private chat
     * @return int|null
     */
    protected function getReplyMessageId($chatId)
    {
        return $this->storage->get($this->getStorageKey($chatId));
    }

    /**
     * @param int $chatId private chat
     * @param int $messageId for which
     */
    protected function setReplyMessageId($chatId, $messageId)
    {
        $this->storage->set($this->getStorageKey($chatId), $messageId);
    }

    /**
     * @inheritdoc
     */
    public function isChatTypeAvailable($type)
    {
        return array_search($type, array('private')) !== false;
    }
}