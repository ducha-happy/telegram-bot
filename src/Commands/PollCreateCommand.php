<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Formatter\HtmlFormatter;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollQuestion;
use Ducha\TelegramBot\Redis\PollManager;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\InlineKeyboardButton;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;
use Ducha\TelegramBot\Types\Message;

class PollCreateCommand extends AbstractCommand
{
    use ArgumentsAwareTrait;

    const STATE_NAME = 1;
    const STATE_QUESTION = 2;
    const STATE_REPLY = 3;
    const STATE_PAUSE = 4;
    const STATE_COMPLETE = 5;

    /**
     * polls keeper
     * @var PollManager
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
        return '/pollcreate';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('poll_create_command_description', array(
            '%format1%' => self::getName(),
            '%format2%' => sprintf('%s name', self::getName()),
        ));
    }

    /**
     * @param Message $message
     * @return int
     */
    protected function getChatId(Message $message)
    {
        $from = $message->getFrom();
        
        return $from['id'];
    }

    protected function savePoll($chatId, $poll)
    {
        $this->storage->set($this->getStorageKey($chatId), $poll);
    }

    /**
     * @param $chatId
     * @param $poll
     */
    protected function askName($chatId, $poll)
    {
        $poll['state'] = self::STATE_NAME;
        $this->savePoll($chatId, $poll);
        $this->telegram->sendMessage($chatId, 'Введите имя опроса');
    }

    /**
     * @param $chatId
     * @param $poll
     */
    protected function askQuestion($chatId, $poll)
    {
        $poll['state'] = self::STATE_QUESTION;
        $this->savePoll($chatId, $poll);
        $this->telegram->sendMessage($chatId, 'Введите вопрос');
    }

    /**
     * @param $chatId
     * @param $poll
     */
    protected function askReply($chatId, $poll)
    {
        $poll['state'] = self::STATE_REPLY;
        $this->savePoll($chatId, $poll);
        $lines = array(
            HtmlFormatter::bold('Введите варианты ответа через запятую на вопрос:'),
            $poll['questions'][count($poll['questions'])-1]['title']
        );
        $text = implode("\n", $lines);
        $this->telegram->sendMessage($chatId, $text);
    }

    /**
     * Do you want to give next question?
     *
     * @param int $chatId
     * @param array $poll
     * @param string $text
     * @param array $replies
     */
    protected function makePause($chatId, $poll, $text = null, $replies = array())
    {
        if (empty($text)){
            $text = $this->translator->trans('ask_next_question');
            $replies = array(
                'yes' => $this->translator->trans('yes'),
                'no' => $this->translator->trans('no')
            );
        }

        $poll['state'] = self::STATE_PAUSE;
        $this->savePoll($chatId, $poll);
        $buttons = array();
        foreach ($replies as $key => $value){
            $buttons[] = new InlineKeyboardButton($value, '', $key);
        }
        $keyboard = new InlineKeyboardMarkup(array(
            $buttons
        ));
        $keyboard = json_encode($keyboard);
        $response = $this->telegram->sendMessage($chatId, $text,  'HTML', false, null, $keyboard);
        $poll['reply_message_id'] = $response['result']['message_id'];
        $this->savePoll($chatId, $poll);
    }

    protected function complete($chatId, $poll)
    {
        $poll['state'] = self::STATE_COMPLETE;
        $this->savePoll($chatId, $poll);

        $id = $this->storage->incr(StorageKeysHolder::getPollMaxIdPattern());
        $questions = array();

        if (count($poll['questions']) == 0){
            throw new \LogicException('A poll must have one question at least but nothing was given');
        }

        foreach ($poll['questions'] as $item){
            $question = new PollQuestion($item['title'], $item['replies']);
            $questions[] = $question;
        }

        $poll = new Poll($id, $poll['userId'], $poll['name'], $questions);
        $this->pollManager->addPoll($poll);

        $this->storage->remove($this->getStorageKey($chatId));

        // Now you can lead this poll in any group with a help of command
        // Your poll was added successfully!
        // Now you can poll in any group using the command

        $lines = array(
            HtmlFormatter::bold( $this->translator->trans('poll_created_successfully') ),
            $poll->getContent(),
            $this->translator->trans('now_you_can_lead_poll', array('%command%' => PollStartCommand::getName().' '.$poll->getId())),
            '',
            PollStartCommand::getDescription(),
            $this->translator->trans('you_can_find_all_your_polls', array('%command%' => StartCommand::getName()))
        );

        $text = implode("\n", $lines);

        $this->telegram->sendMessage($chatId, $text);
    }

    /**
     * Poll Name is good ?
     * @param $userId
     * @param $pollName
     * @param $chatId
     * @return bool
     */
    protected function validatePollName($userId, $pollName, $chatId)
    {
        $temp = $this->pollManager->getPoll($userId, $pollName);
        if ($temp instanceof Poll){
            $this->telegram->sendMessage($chatId, $this->translator->trans('poll_already_exists'));
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $chatId = $message->getChatId();
            $from = $message->getFrom();
            if ($this->hasPoll($chatId)){
                $poll = $this->getPoll($chatId);
                switch ($poll['state']){
                    case self::STATE_NAME:
                        $pollName = $message->getText();
                        if ($this->validatePollName($from['id'], $pollName, $chatId) == true){
                            $poll['name'] = $pollName;
                            $this->askQuestion($chatId, $poll);
                        }
                        break;
                    case self::STATE_QUESTION:
                        $poll['questions'][] = array(
                            'title' => $message->getText(),
                            'replies' => array()
                        );
                        $this->askReply($chatId, $poll);
                        break;
                    case self::STATE_REPLY:
                        $replies = $message->getText();
                        $replies = explode(',', $replies);
                        foreach ($replies as $reply){
                            $poll['questions'][count($poll['questions'])-1]['replies'][] = trim($reply);
                        }
                        $this->makePause($chatId, $poll);
                        break;
                }
            }else{
                $this->createPoll($message);                
            }
        }elseif ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $message = $callback->getMessage();
            $from = $callback->getFrom();
            $chatId = $from['id'];
            if ($this->hasPoll($chatId)){
                $poll = $this->getPoll($chatId);
                switch ($poll['state']){
                    case self::STATE_PAUSE:
                        if ($poll['reply_message_id'] == $message->getId()){
                            if (array_search($callback->getData(), array('yes', 'no')) !== false){
                                if ($callback->getData() == 'yes'){
                                    $this->askQuestion($chatId, $poll);
                                }else{
                                    $this->complete($chatId, $poll);
                                }
                            }
                        }
                        break;
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
            $text = $message->getText();
            $temp = $this->combOut($text);

            if (!empty($temp)){
                if (count($temp) > 1){
                    $args = $temp; array_shift($args);
                    $this->setArguments($args);
                }

                if ($this->stringIsCommand($temp[0]) && $this->isChatTypeAvailable($message->getChatType()) == false){
                    $this->telegram->sendMessage($message->getChatId(), $this->getWarning());
                    return false;
                }

                if ($this->stringIsCommand($temp[0]) || $this->hasPoll($message->getChatId())){
                    return true;
                }
            }
        }

        if ($this->hasCallbackQuery($data)){
            $callback = $this->getCallbackQuery($data);
            $from = $callback->getFrom();
            $chatId = $from['id'];
            if ($this->hasPoll($chatId)){
                return true;
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

    /**
     * ChatId can be only private
     *
     * @param $chatId
     * @return string
     */
    protected function getStorageKey($chatId)
    {
        return StorageKeysHolder::getPollCreateKey($chatId);
    }

    /**
     * Is there a poll in storage?
     * @param int $chatId
     * @return bool
     */
    protected function hasPoll($chatId)
    {
        $poll = $this->storage->get($this->getStorageKey($chatId));

        return !empty($poll)? true : false;
    }

    /**
     * Get poll from storage
     * @param int $chatId
     * @return array
     */
    protected function getPoll($chatId)
    {
        return $this->storage->get($this->getStorageKey($chatId));
    }

    /**
     * @param Message $message     
     */
    protected function createPoll(Message $message)
    {
        $from = $message->getFrom();
        $chatId = $message->getChatId();
        $name = '';
        $poll = array(
            'state'     => self::STATE_NAME,
            'name'      => $name,
            'userId'    => $from['id'],
            'questions' => array()
        );

        if (isset($this->arguments[0])){           
            $name = $this->arguments[0];
            if ($this->validatePollName($from['id'], $name, $chatId) != false){
                $poll['name'] = $name;
            }
        }
        
        if (empty($poll['name'])){
            $this->askName($chatId, $poll);
        }else{
            $this->askQuestion($chatId, $poll);
        }
    }
}