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
        return static::getTranslator()->trans('poll_start_command_description', array(
            '%format1%' => static::getName() . ' number',
            '%format2%' => static::getName() . ' name',
            '%format3%' => static::getName()
        ));
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
                    $this->telegram->sendMessage($message->getChatId(), $this->translator->trans('poll_survey_already_conducting'));
                }else{
                    if ($this->hasAnyPollSurveyForChat($message->getChatId())){
                        $this->telegram->sendMessage($message->getChatId(), $this->translator->trans('other_poll_survey_already_conducting'));
                    }else{
                        $pollSurvey = new PollSurvey($message->getChatId(), $poll, $this->telegram, $this->storage, $this->handler);
                        $pollSurvey->start($message);
                    }
                }
            }else{
                $this->telegram->sendMessage($message->getChatId(),
                    $this->translator->trans('can_not_find_any_poll') . $this->getWarning(StartCommand::getName())
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