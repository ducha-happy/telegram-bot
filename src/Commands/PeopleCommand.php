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
use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Types\Group;

class PeopleCommand extends AbstractCommand
{
    /**
     * @var GroupManagerInterface
     */
    protected $groupManager;

    public function __construct(CommandHandler $handler)
    {
        parent::__construct($handler);

        $this->groupManager = $this->handler->getGroupManager();
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/people';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('people_command_description');
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $chatId = $message->getChatId();
            $group = $this->groupManager->getGroup($chatId);
            $text = $this->translator->trans('people_command_no_info');
            if ($group instanceof Group){
                $count = count($group);
                $text = $this->translator->trans('people_command_count_humans', array('%count%' => $count));
            }

            $this->telegram->sendMessage($message->getChatId(), $text);
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
            if ($this->stringIsCommand($message->getText()) && $this->isChatTypeAvailable($message->getChatType())){
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
        return array_search($type, array('group', 'supergroup')) !== false;
    }
}