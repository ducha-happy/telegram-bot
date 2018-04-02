<?php

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\CommandHandler;
use Ducha\TelegramBot\Telegram;
use Ducha\TelegramBot\Types\CallbackQuery;
use Ducha\TelegramBot\Types\Message;
use Ducha\TelegramBot\Storage\StorageInterface;
use Symfony\Component\Translation\Translator;

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

    /**
     * Symfony Translator
     *
     * @var Translator
     */
    protected $translator;

    private static $_instances = array();

    public function __construct(CommandHandler $handler)
    {
        $this->handler = $handler;
        $storage = $this->handler->getContainer()->get('ducha.telegram-bot.storage');
        if (!$storage instanceof StorageInterface){
            throw new \LogicException('Class %s must be implement %s', get_class($storage), StorageInterface::class);
        }
        $this->storage = $storage;
        $this->telegram = $this->handler->getTelegramBot()->getTelegram();
        $this->translator = $this->handler->getContainer()->get('ducha.telegram-bot.translator');
        self::$_instances['translator'] = $this->translator;
    }

    /**
     * @return string
     */
    public function getBotId()
    {
        $temp = $this->handler->getContainer()->getParameter('telegram_bot_token');
        $temp = explode(':', $temp);

        return array_shift($temp);
    }

    /**
     * @return string
     */
    public function getBotName()
    {
        $temp = $this->handler->getContainer()->getParameter('telegram_bot_link');
        $temp = explode('/', $temp);

        return array_pop($temp);
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
        $arguments = '';
        $temp = class_uses($this);
        if (is_array($temp) && array_search(ArgumentsAwareTrait::class, $temp) !== false){
            $arguments = (empty($this->arguments)?'':implode(' ', $this->arguments));
        }

        $text = $this->translator->trans('go_to_and_try_there', array(
            '%bot_name%' => '@' . $this->getBotName(),
            '%command%' => static::getName() . ' ' . $arguments
        ));

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

    public static function __callStatic($method, $args)
    {
        if ($method == 'getTranslator'){
            if (isset(self::$_instances['translator'])){
                return self::$_instances['translator'];
            }
        }

        return false;
    }
}