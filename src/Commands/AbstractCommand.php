<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Types\CallbackQuery;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Storage\StorageInterface;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * Command Handler knows all available commands
     *
     * @var CommandHandler
     */
    protected $handler;

    /**
     * Storage where a command can save your data
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Telegram Api
     *
     * @var Telegram
     */
    protected $telegram;

    public function __construct(CommandHandler $handler)
    {
        $this->handler = $handler;
        $storage = $this->handler->getContainer()->get('ducha.telegram-bot.storage');
        if (!$storage instanceof StorageInterface){
            throw new \LogicException('Class %s must be implement %s', get_class($storage), StorageInterface::class);
        }
        $this->storage = $storage;
        $this->telegram = $this->handler->getTelegramBot()->getTelegram();
    }

    /**
     * @return string
     */
    public function getBotName()
    {
        $name = '';

        $response = $this->telegram->getMe();
        if (isset($response['result'])){
            $name = $response['result']['username'];
        }

        return $name;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param array $data
     */
    public function execute(array $data){}

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        return false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function hasMessage(array $data)
    {
        return isset($data['message']);
    }

    /**
     * @param array $data
     * @return bool|Message
     */
    public function getMessage(array $data)
    {
        if (isset($data['message'])){
            return new Message($data['message']);
        }

        return false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function hasCallbackQuery(array $data)
    {
        return isset($data['callback_query']);
    }

    /**
     * @param array $data
     * @return bool|CallbackQuery
     */
    public function getCallbackQuery(array $data)
    {
        if (isset($data['callback_query'])){
            return new CallbackQuery(
                $data['callback_query']['id'],
                $data['callback_query']['from'],
                new Message($data['callback_query']['message']),
                null,
                $data['callback_query']['chat_instance'],
                $data['callback_query']['data']);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isHidden(array $data)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isChatTypeAvailable($type)
    {
        return true;
    }

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return '';
    }

    /**
     * Parses a string with spaces, returns an array
     * @param string $text
     * @return array
     */
    protected function combOut($text)
    {
        $temp = array_diff(
            explode(" ", $text), array("")
        );

        $items = array();
        foreach ($temp as $value){
            $items[] = $value;
        }

        return $items;
    }

    /**
     * @return string
     */
    public function getWarning()
    {
        $text = 'Go to @%s and try %s ';
        $temp = class_uses($this);
        if (is_array($temp) && array_search(ArgumentsAwareTrait::class, $temp) !== false){
            $text .= (empty($this->arguments)?'':implode(' ', $this->arguments));
        }
        $text .= ' there';
        $text = sprintf($text, $this->getBotName(), static::getName());

        return $text;
    }

    /**
     * @return string
     */
    public function getInListDescription()
    {
        return sprintf("%s - %s\n", static::getName(), static::getDescription());
    }

    public function stringIsCommand($str)
    {
        return $str == static::getName() || $str == static::getName() . '@' . $this->getBotName();
    }
}