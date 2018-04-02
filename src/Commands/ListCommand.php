<?php

namespace Ducha\TelegramBot\Commands;

/**
 * Show a list of all commands
 */
class ListCommand extends AbstractCommand
{
    /**
     * Syntax `/` works only in bot chat
     *
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('list_command_description');
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if (!$this->hasMessage($data)){
            return;
        }

        $message = $this->getMessage($data);
        $commands = $this->handler->getCommands();
        $list = array();
        foreach ($commands as $command){
            if ($command instanceof CommandInterface && $command->isHidden($data) == false) {
                $list[] = $command->getInListDescription();
            }
        }

        $this->telegram->sendMessage($message->getChatId(), implode("\n", $list));
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
                return true;
            }
        }

        return false;
    }
}