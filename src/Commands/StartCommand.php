<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Ducha\TelegramBot\Redis\PollSurveyStatManager;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;
use Ducha\TelegramBot\Types\ReplyKeyboardRemove;

class StartCommand extends AbstractCommand
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
     * @var PollSurveyStatManager
     */
    protected $surveyStatManager;

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->groupManager = $this->handler->getGroupManager();
        $this->pollManager = $this->handler->getPollManager();
        $this->surveyStatManager = $this->handler->getPollSurveyStatManager();
        $this->statManager = $this->handler->getPollStatManager();
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/start';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return 'Start menu for a user to manage their polls. This command is available only for private chat.';
    }

    protected static function ucFirstEveryWord($key)
    {
        $temp = explode('_', $key);
        $isChange = array_walk($temp, function(&$value){
            $value = ucfirst($value);
        });
        if (!$isChange){
            throw new \InvalidArgumentException(sprintf('array_walk function did not work - error in method "%s", file "%s" ', __METHOD__, __FILE__));
        }

        return implode('', $temp);
    }

    /**
     * @param $action
     * @param $chatId
     * @param $reply
     */
    protected function makeAction($action, $chatId, $reply)
    {
        $parameters = array($chatId);
        $callback_array = explode('.', $reply);

        switch($action){
            case 'poll_create_action':
                $methodName = lcfirst(self::ucFirstEveryWord($action)); //pollCreateAction
                break;
            case 'poll_remove_action':
            case 'poll_show_action':
                $methodName = lcfirst(self::ucFirstEveryWord($action)); //pollShowAction
                $parameters[] = $callback_array[1]; //pollId
                break;
            case 'poll_show_stat_action':
            case 'poll_stat_remove_action':
                $methodName = lcfirst(self::ucFirstEveryWord($action)); //pollStatRemoveAction, pollShowStatAction
                $parameters[] = $callback_array[2]; //pollId
                $parameters[] = $callback_array[1]; //statChatId
                break;
            case 'poll_show_stat_action_uncompleted':
                $temp = explode('_', $action);
                $lastItem = array_pop($temp);
                $methodName = lcfirst(self::ucFirstEveryWord(implode('_', $temp))); //pollShowStatAction
                $parameters[] = $callback_array[2]; //pollId
                $parameters[] = $callback_array[1]; //statChatId
                $parameters[] = $lastItem;
                break;
        }

        if (empty($methodName) || !method_exists($this, $methodName)){
            throw new \LogicException(sprintf('Something is wrong - it is having error in method "%s" in file "%s" ', __METHOD__, __FILE__));
        }

        call_user_func_array(array($this, $methodName), $parameters);
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $this->showMenu('main_menu', $message->getChatId(), null, true);
        }elseif ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage();
            $from = $callback->getFrom();
            $chatId = $from['id'];
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $reply = $callback->getData();
                $points = $this->getCallbackPoints();
                foreach ($points as $key => $point){
                    if (preg_match($point['pattern'], $reply)){
                        if (preg_match('|_menu$|', $key)){
                            $this->showMenu($key, $chatId, $reply);
                        }else{
                            $this->makeAction($key, $chatId, $reply);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get info about poll
     * 
     * @param int $chatId
     * @param int $pollId
     */
    protected function pollShowAction($chatId, $pollId)
    {
        $poll = $this->pollManager->getPollById($pollId);
        $text = sprintf('Sorry. Can not find the poll with number %s', $pollId);
        if ($poll instanceof Poll){
            $userId = $poll->getUserId();
            if ($userId == $chatId){
                $lines = array(
                    $poll->getContent(),
                    sprintf('You can start this poll in any group where i live with "%s %s"', PollStartCommand::getName(), $pollId)
                );
                $text = implode("\n", $lines);
            }
        }

        $keyboard = $this->getAllPollsMenuKeyboard($chatId);
        $keyboard = json_encode($keyboard);

        $this->sendResponse($chatId, $text, $keyboard);
    }

    /**
     * Get poll statistic
     * 
     * @param int $chatId
     * @param int $pollId
     * @param int $statChatId
     * @param bool $uncompleted
     */
    protected function pollShowStatAction($chatId, $pollId, $statChatId, $uncompleted = null)
    {
        $poll = $this->pollManager->getPollById($pollId);
        $text = sprintf('Sorry. Can not find the stat for the poll with number %s', $pollId);
        if ($poll instanceof Poll){
            $userId = $poll->getUserId();
            if ($userId == $chatId){
                if ($uncompleted){
                    $text = $this->surveyStatManager->getStat($statChatId, $pollId);
                }else{
                    $text = $this->statManager->getStat($statChatId, $pollId);
                }
            }
        }

        $points = $this->getCallbackPoints();
        $pollShowStatMenuPoint = $points['poll_show_stat_menu'];

        $keyboard = $this->getAllPollsMenuKeyboard($chatId);
        $rows = $keyboard->getInlineKeyboard();

        array_unshift(
            $rows,
            array(new InlineKeyboardButton($pollShowStatMenuPoint['caption'], '', sprintf($pollShowStatMenuPoint['callback_data'], $pollId)))
        );

        $keyboard->setInlineKeyboard($rows);

        $keyboard = json_encode($keyboard);

        $this->sendResponse($chatId, $text, $keyboard);
    }

    protected function hasSurveyFor($pollId)
    {
        return $this->surveyStatManager->hasSurveys($pollId);
    }

    /**
     * @param int $userId
     * @return InlineKeyboardMarkup
     */
    protected function getAllPollsMenuKeyboard($userId)
    {
        $points = $this->getCallbackPoints();
        $allPollsPoint = $points['all_polls_menu'];
        $mainMenuPoint = $points['main_menu'];

        $rows = array(
            array(new InlineKeyboardButton($allPollsPoint['caption'], '', sprintf($allPollsPoint['callback_data'], $userId))),
            array(new InlineKeyboardButton($mainMenuPoint['caption'], '', sprintf($mainMenuPoint['callback_data'], $userId)))
        );
        $keyboard = new InlineKeyboardMarkup($rows);

        return $keyboard;
    }

    protected function pollRemoveAction($chatId, $pollId)
    {
        $poll = $this->pollManager->getPollById($pollId);
        $text = sprintf('Sorry. Can not remove poll with number %s', $pollId);
        if ($poll instanceof Poll){
            if ($chatId == $poll->getUserId()){
                //test that not survey for that poll
                if (!$this->hasSurveyFor($pollId)){
                    $this->pollManager->removePoll($pollId);
                    $text = sprintf('Ok. Your poll with number %s is removed!', $pollId);
                }else{
                    $text = sprintf('Sorry. Can not remove poll with number %s because there is a survey for that poll - remove the survey first', $pollId);
                }
            }
        }

        $keyboard = $this->getAllPollsMenuKeyboard($chatId);
        $keyboard = json_encode($keyboard);

        $this->sendResponse($chatId, $text, $keyboard);
    }

    protected function pollCreateAction($chatId)
    {
        $text = sprintf('To create a poll use this command "%s"', PollCreateCommand::getName());

        $keyboard = $this->getAllPollsMenuKeyboard($chatId);
        $keyboard = json_encode($keyboard);

        $this->sendResponse($chatId, $text, $keyboard);
    }

    /**
     * @param int $chatId
     * @param int $pollId
     * @param int $statChatId
     */
    protected function pollStatRemoveAction($chatId, $pollId, $statChatId)
    {
        $isRemove = false;
        $poll = $this->pollManager->getPollById($pollId);
        $group = $this->groupManager->getGroup($statChatId);

        $key = StorageKeysHolder::getCompletedSurveyKey($statChatId, $pollId);
        if ($this->storage->exists($key)){
            $this->storage->remove($key);
            $isRemove = true;
        }

        $key = StorageKeysHolder::getNotCompletedSurveyKey($statChatId, $pollId);
        if ($this->storage->exists($key)){
            $this->storage->remove($key);
            $isRemove = true;

            // send message to a chat with survey and remove all keyboards there
            $keyboard = new ReplyKeyboardRemove(true, false);
            $keyboard = json_encode($keyboard);
            $text = sprintf('I am very sorry but this survey ("%s - %s") was removed by owner.', $poll->getId(), $poll->getName());
            $this->telegram->sendMessage($statChatId, HtmlFormatter::bold($text), 'HTML', false, null, $keyboard);
        }

        if ($isRemove){
            $text = sprintf('The stat for ("%s - %s") was removed.', $group->getTitle(), $poll->getName());
        }else{
            $text = 'Ok. But nothing was removed.';
        }

        $keyboard = $this->getAllPollsMenuKeyboard($chatId);
        $keyboard = json_encode($keyboard);

        $this->sendResponse($chatId, $text, $keyboard);
    }

    /**
     * @param array $lines
     * @param array $keys
     * @param array $parameters
     * @return array
     */
    protected function getPollShowStatMenuItems($lines, $keys, $parameters)
    {
        $points = $this->getCallbackPoints();
        $pollStatRemovePointKey = 'poll_stat_remove_action';
        $pollStatRemovePoint = $points[$pollStatRemovePointKey];
        $pollShowStatPoint = $points['poll_show_stat_action'];

        foreach ($keys as $key){
            $temp = explode(".", $key);
            $pollId = array_pop($temp);
            $chatId = array_pop($temp);
            $group = $this->groupManager->getGroup($chatId);
            $showReply = implode('.', array_replace($parameters, array($parameters[0], $chatId, $pollId)));
            $removeReply = implode('.', array_replace($parameters, array($pollStatRemovePointKey, $chatId, $pollId)));
            $lines[] = array(
                'show'   => array(
                    'caption' => $pollShowStatPoint['caption'] . ' (' . $group->getTitle() . ')',
                    'reply' => $showReply,
                ),
                'remove' => array(
                    'caption' => $pollStatRemovePoint['caption'],
                    'reply' => $removeReply,
                ),
            );
        }

        return $lines;
    }

    /**
     * @param  int $pollId
     * @return array
     */
    protected function getPollShowStatMenu($pollId)
    {
        $points = $this->getCallbackPoints();
        $mainMenuPoint = $points['main_menu'];
        $allPollsPoint = $points['all_polls_menu'];
        $pollShowStatPoint = $points['poll_show_stat_action'];
        $pollShowStatUncompletedPoint = $points['poll_show_stat_action_uncompleted'];

        $poll = $this->pollManager->getPollById($pollId);
        $text = sprintf('Select the statistic for your poll (%s - %s) ', $poll->getId(), $poll->getName());
        $text = HtmlFormatter::bold($text);

        $pattern = sprintf(StorageKeysHolder::getCompletedSurveyPattern(), '*', $pollId);
        $keys = $this->storage->keys($pattern);

        $lines = array();
        $parameters = explode('.', $pollShowStatPoint['callback_data']);
        $lines = $this->getPollShowStatMenuItems($lines, $keys, $parameters);

        $pattern = sprintf(StorageKeysHolder::getNotCompletedSurveyPattern(), '*', $pollId);
        $keys = $this->storage->keys($pattern);
        $keys = PollSurveyStatManager::filterKeys($keys, $pattern);

        $parameters = explode('.', $pollShowStatUncompletedPoint['callback_data']);
        $lines = $this->getPollShowStatMenuItems($lines, $keys, $parameters);

        $rows = array();
        foreach ($lines as $item){
            $rows[] = array(
                new InlineKeyboardButton($item['show']['caption'], '', $item['show']['reply']),
                new InlineKeyboardButton($item['remove']['caption'], '', $item['remove']['reply']),
            );
        }
        $rows[] = array(
            new InlineKeyboardButton($allPollsPoint['caption'], '', sprintf($allPollsPoint['callback_data'], $poll->getUserId())),
            new InlineKeyboardButton($mainMenuPoint['caption'], '', sprintf($mainMenuPoint['callback_data'], $poll->getUserId())),
        );

        $keyboard = new InlineKeyboardMarkup($rows);
        $keyboard = json_encode($keyboard);

        return array($text, $keyboard);
    }

    /**
     * @param  int $chatId
     * @return array
     */
    protected function getAllPollsMenu($chatId)
    {
        $points = $this->getCallbackPoints();
        $mainMenuPoint = $points['main_menu'];
        $pollShowPoint = $points['poll_show_action'];
        $pollRemovePoint = $points['poll_remove_action'];
        $pollShowStatMenuPoint = $points['poll_show_stat_menu'];

        $text = 'Select your poll and action what you want.';
        $polls = $this->pollManager->getPollsByUserId($chatId);

        if (empty($polls)){
            $text = sprintf('It seems you have not any polls yet. To create a poll, You can use "%s" command', PollCreateCommand::getName());
        }
        //$text = HtmlFormatter::bold($text);
        $rows = array();
        foreach ($polls as $poll){
            if (!$poll instanceof Poll){
                throw new \LogicException(sprintf('Something is wrong - it is having error in method "%s" in file "%s" ', __METHOD__, __FILE__));
            }
            $pollId = $poll->getId();
            $pollTitle = $poll->getId() . ' - ' . $poll->getName();
            $rows[] = array(
                new InlineKeyboardButton($pollTitle, '', sprintf($pollShowPoint['callback_data'], $pollId)),
                new InlineKeyboardButton($pollShowStatMenuPoint['caption'], '', sprintf($pollShowStatMenuPoint['callback_data'], $pollId)),
                new InlineKeyboardButton($pollRemovePoint['caption'], '', sprintf($pollRemovePoint['callback_data'], $pollId))
            );
        }
        $rows[] = array(
            new InlineKeyboardButton($mainMenuPoint['caption'], '', $mainMenuPoint['callback_data']),
        );

        $keyboard = new InlineKeyboardMarkup($rows);
        $keyboard = json_encode($keyboard);

        return array($text, $keyboard);
    }

    /**
     * @param int $chatId chatId is the same as userId
     * @return array
     */
    protected function getMainMenu($chatId)
    {
        $points = $this->getCallbackPoints();
        $allPollsPoint = $points['all_polls_menu'];
        $createPollPoint = $points['poll_create_action'];
        
        $text = 'Select a menu point what do you want me to do for you!';
        $rows = array(
            array(
                new InlineKeyboardButton($allPollsPoint['caption'], '', sprintf($allPollsPoint['callback_data'], $chatId)) //chatId is the same as userId
            ),
            array(
                new InlineKeyboardButton($createPollPoint['caption'], '', $createPollPoint['callback_data'])
            )
        );

        $keyboard = new InlineKeyboardMarkup($rows);
        $keyboard = json_encode($keyboard);

        return array($text, $keyboard);
    }

    /**
     * MainMenu MyPollsMenu
     * @param string $key
     * @param int $chatId
     * @param string $reply
     * @param bool $start
     */
    protected function showMenu($key, $chatId, $reply = null, $start = false)
    {
        $methodName = 'get'.self::ucFirstEveryWord($key);
        if (!method_exists($this, $methodName)){
            throw new \LogicException(sprintf('Something is wrong - it is having error in method "%s" in file "%s" ', __METHOD__, __FILE__));
        }

        $parameters = array();
        $callback_array = explode('.', $reply);

        switch($key){
            case 'main_menu':
            case 'all_polls_menu':
                $parameters = array($chatId);
                break;
            case 'poll_show_stat_menu':
                $parameters[] = $callback_array[1]; //pollId
                break;
        }

        list($text, $keyboard) = call_user_func_array(array($this, $methodName), $parameters);

        $this->sendResponse($chatId, $text, $keyboard, $start);
    }

    /**
     * @param int $chatId
     * @param string $text
     * @param InlineKeyboardMarkup $keyboard
     * @param bool $start
     */
    protected function sendResponse($chatId, $text, $keyboard, $start = false)
    {
        $replyMessageId = $this->getReplyMessageId($chatId);
        if (empty($replyMessageId) || $start){
            $response = $this->telegram->sendMessage($chatId, $text,  'HTML', false, null, $keyboard);
            $this->setReplyMessageId($chatId, $response['result']['message_id']);
        }else{
            $this->telegram->editMessageText($chatId, $replyMessageId, '', $text);
            $this->telegram->editMessageReplyMarkup($chatId, $replyMessageId, '', $keyboard);
        }
    }

    /**
     * @param int $chatId private chat
     * @return int|null
     */
    protected function getReplyMessageId($chatId)
    {
        return $this->storage->get(StorageKeysHolder::getMenuReplyMessageIdKey($chatId));
    }

    /**
     * @param int $chatId private chat
     * @param int $messageId for which
     */
    protected function setReplyMessageId($chatId, $messageId)
    {
        $this->storage->set(StorageKeysHolder::getMenuReplyMessageIdKey($chatId), $messageId);
    }

    /**
     * @return array
     */
    protected function getCallbackPoints()
    {
        return array(
            'main_menu' => array(
                'caption' => 'Main Menu',
                'pattern' => '|^main_menu$|',
                'callback_data' => 'main_menu',
            ),
            'all_polls_menu' => array(
                'caption' => 'All Polls',
                'pattern' => '|^all_polls_menu\.\d+$|', //userId
                'callback_data' => 'all_polls_menu.%s',
            ),
            'poll_show_stat_menu' => array(
                'caption' => 'Poll Show Stat Menu',
                'pattern' => '|^poll_show_stat_menu\.\d+$|', //pollId
                'callback_data' => 'poll_show_stat_menu.%s',
            ),
            'poll_create_action' => array(
                'caption' => 'Poll Create',
                'pattern' => '|^poll_create_action$|',
                'callback_data' => 'poll_create_action',
            ),
            'poll_remove_action' => array(
                'caption' => 'Poll Remove',
                'pattern' => '|^poll_remove_action\.\d+$|', //pollId
                'callback_data' => 'poll_remove_action.%s',
            ),
            'poll_show_action' => array(
                'caption' => 'Poll Show',
                'pattern' => '|^poll_show_action\.\d+$|', //pollId
                'callback_data' => 'poll_show_action.%s',
            ),
            'poll_show_stat_action' => array(
                'caption' => 'Poll Show Stat',
                'pattern' => '|^poll_show_stat_action\.-\d+\.\d+$|', //chatId pollId
                'callback_data' => 'poll_show_stat_action.%s.%s',
            ),
            'poll_show_stat_action_uncompleted' => array(
                'caption' => 'Poll Show Stat Uncompleted',
                'pattern' => '|^poll_show_stat_action\.-\d+\.\d+\.uncompleted$|', //chatId pollId
                'callback_data' => 'poll_show_stat_action.%s.%s.uncompleted',
            ),
            'poll_stat_remove_action' => array(
                'caption' => 'Poll Stat Remove',
                'pattern' => '|^poll_stat_remove_action\.-\d+\.\d+(\.uncompleted){0,1}$|', //chatId pollId
                'callback_data' => 'poll_stat_remove_action.%s.%s',
            ),
        );
    }

    /**
     * @return array
     */
    protected function getCallbackReplyPatterns()
    {
        $points = $this->getCallbackPoints();
        $patterns = array();
        foreach ($points as $point){
            $patterns[] = $point['pattern'];
        }
        
        return $patterns;
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);

            if ($this->stringIsCommand($message->getText()) && $this->isChatTypeAvailable($message->getChatType())){
                return true;
            }

            if ($this->stringIsCommand($message->getText())){
                $lines = array(
                    StartCommand::getDescription(),
                    sprintf('Go to @%s and use there %s', $this->getBotName(), StartCommand::getName())
                );
                $text = implode("\n", $lines);

                $this->telegram->sendMessage($message->getChatId(), $text);
            }


        }

        if ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage(); $chatId = $message->getChatId();
            if ($this->getReplyMessageId($chatId) == $message->getId()){
                $reply = $callback->getData();
                $patterns = $this->getCallbackReplyPatterns();
                foreach ($patterns as $pattern){
                    if (preg_match($pattern, $reply)){
                        return true;
                    }
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