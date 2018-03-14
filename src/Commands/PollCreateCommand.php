<?php
/*
 * This command must run only in private chats
 * Create a poll
 * format: 1) /pollcreate 2) /pollcreate name
 */

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Poll\Poll;
use Ducha\TelegramBot\Poll\PollQuestion;
use Ducha\TelegramBot\Redis\PollManager;
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

        $this->pollManager = $this->handler->getContainer()->get('ducha.telegram.poll.manager');
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
        return sprintf('Create poll. The command is available only for private chats. ' . "\n" . 'Formats: 1) %s name ; 2) %s  ', self::getName(), self::getName());
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
        $this->telegram->sendMessage($chatId, 'Введите варианты ответа через запятую на вопрос: ' . $poll['questions'][count($poll['questions'])-1]['title']);
    }

    /**
     * Do you want to give next question?
     *
     * @param int $chatId
     * @param array $poll
     * @param string $text
     * @param array $replies
     */
    protected function makePause($chatId, $poll, $text = 'Вы хотите задать следующий вопрос', $replies = array('yes' => 'Да', 'no' => 'Нет'))
    {
        $text = 'Dou you want tot ask next question';
        $replies = array('yes' => 'YES', 'no' => 'NO');

        if (empty($text) || empty($replies)){
            throw new \InvalidArgumentException('Bad argument: the text and replies must not be empty!');
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

        $id = $this->storage->incr('telegram.poll.maxId');
        $questions = array();

        if (count($poll['questions']) == 0){
            throw new \LogicException('Your poll must have one question at least but nothing was given');
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

        $text = 'Ваш опрос успешно добавлен!' . "\n" .
            $poll->getContent() . "\n" .
            sprintf('Теперь вы можете проводить опрос в любой группе с помощью команды %s', PollStartCommand::getName()) . ' ' . $poll->getName() . "\n" .
            PollStartCommand::getDescription()
        ;

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
            $this->telegram->sendMessage($chatId, 'A poll with a such name already exists. Try other name.');
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
        return 'telegram.poll.create.' . $chatId;
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