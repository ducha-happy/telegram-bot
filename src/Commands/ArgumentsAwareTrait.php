<?php
namespace Ducha\TelegramBot\Commands;

trait ArgumentsAwareTrait {

    /**
     * Keeps arguments of a command
     * @var array
     */
    protected $arguments;

    /**
     * @param array $arguments
     */
    protected function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
}