<?php

namespace Ducha\TelegramBot\Commands;

interface CommandInterface
{
    /**
     * @param array $data
     */
    public function execute(array $data);

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data);

    /**
     * @param string $type
     * @return boolean
     */
    public function isChatTypeAvailable($type);

    /**
     * Show in the list of available commands or not
     * @param array $data
     * @return boolean
     */
    public function isHidden(array $data);

    /**
     * @return string
     */
    public static function getName();

    /**
     * @return string
     */
    public static function getDescription();

    /**
     * @return string
     */
    public function getInListDescription();
}


