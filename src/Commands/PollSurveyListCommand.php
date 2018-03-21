<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\CallbackQuery;
use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;
use Ducha\TelegramBot\Types\ReplyKeyboardRemove;

class PollSurveyListCommand extends AbstractCommand
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
    protected $showKeys;
    /**
     * @var array
     */
    protected $removeKeys;

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
        return '/surveylist';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Show list of running surveys. You even can remove some survey. This command is available only for admin chat.';
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
                if (array_key_exists($reply, $this->getShowKeys())){
                    $this->showResult($chatId, $callback);
                }elseif(array_key_exists($reply, $this->getRemoveKeys())){
                    $this->removeSurvey($chatId, $callback);
                }elseif($reply == 'list'){
                    $this->showMainMenu($chatId);
                }
            }
        }
    }

    /**
     * @param $chatId
     * @param CallbackQuery $callback
     */
    protected function removeSurvey($chatId, CallbackQuery $callback)
    {
        $data = explode(".", $callback->getData());
        if (count($data) != 3){
            throw new \LogicException('Callback data is not correct!');
        }

        $surveyChatId = $data[0];
        $pollId = $data[1];
        $key = implode('.', array($surveyChatId, $pollId));
        $showKeys = $this->getShowKeys();

        // remove survey
        $storage = $this->handler->getContainer()->get('ducha.telegram-bot.storage');
        $storage->remove(StorageKeysHolder::getNotCompletedSurveyKey($surveyChatId, $pollId));

        // send message to a chat with survey and remove all keyboards there
        $keyboard = new ReplyKeyboardRemove(true, false);
        $keyboard = json_encode($keyboard);
        $poll = $this->pollManager->getPollById($pollId);
        $text = sprintf('I am very sorry but this survey ("%s - %s") was removed by administrator.', $poll->getId(), $poll->getName());
        $this->telegram->sendMessage($surveyChatId, HtmlFormatter::bold($text), 'HTML', false, null, $keyboard);

        // edit message for administrator
        $text = sprintf('%s was removed.', $showKeys[$key]);
        $buttons = array(
            array(new InlineKeyboardButton('LIST OF SURVEYS', '', 'list'))
        );
        $keyboard = new InlineKeyboardMarkup($buttons);
        $keyboard = json_encode($keyboard);
        $replyMessageId = $this->getReplyMessageId($chatId);
        $this->telegram->editMessageText($chatId, $replyMessageId, '', $text);
        $this->telegram->editMessageReplyMarkup($chatId, $replyMessageId, '', $keyboard);
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

    protected function setKeys()
    {
        $pattern = sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), '*', '*');
        $keys = $this->storage->keys($pattern);
        $keys = $this->filterKeys($keys, $pattern);

        $this->showKeys = $this->removeKeys = array();
        foreach ($keys as $key){
            $temp = explode(".", $key);
            $pollId = array_pop($temp);
            $chatId = array_pop($temp);
            $poll = $this->pollManager->getPollById($pollId);
            $group = $this->groupManager->getGroup($chatId);
            $this->showKeys[$chatId.'.'.$pollId] = $group->getTitle() . ' - ' . $poll->getName();
            $this->removeKeys[ implode('.', array($chatId, $pollId, $this->getRemoveKeySuffix())) ] = 'remove';
        }
    }

    /**
     * @return array
     */
    protected function getShowKeys()
    {
        $this->setKeys();

        return $this->showKeys;
    }

    /**
     * @return array
     */
    protected function getRemoveKeys()
    {
        $this->setKeys();

        return $this->removeKeys;
    }

    protected function getRemoveKeySuffix()
    {
        return 'remove';
    }

    protected function showMainMenu($chatId, $start = false)
    {
        $replies = $this->getShowKeys();
        if (empty($replies)){
            $text = 'In Baghdad, everything is calm. Any uncompleted surveys were not found. Sleep peacefully. Try next time.';
            $this->telegram->sendMessage($chatId, $text);
        }else{
            $text = 'Which poll survey do you want to look at ? Or maybe remove ?';
            $buttons = array();
            foreach ($replies as $key => $value){
                $removeKey = implode('.', array($key, $this->getRemoveKeySuffix()));
                $removeValue = $this->removeKeys[$removeKey];
                $buttons[] = array(
                    new InlineKeyboardButton($value, '', $key),
                    new InlineKeyboardButton($removeValue, '', $removeKey)
                );
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

    /**
     * @param $chatId
     * @param CallbackQuery $callback
     */
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
            if ($message->getText() == $this->getName()
                && $message->getChatId() == $this->handler->getTelegramBot()->getTelegramAdminChatId()
            ){
                return true;
            }
        }

        if ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage(); $chatId = $message->getChatId();
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $reply = $callback->getData();
                if (array_key_exists($reply, $this->getShowKeys()) || array_key_exists($reply, $this->getRemoveKeys()) || $reply == 'list'){
                    return true;
                }
            }
        }

        return false;
    }
}