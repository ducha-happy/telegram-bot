<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot;

use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TelegramBot implements ContainerAwareInterface
{
    const LOG_PATH = '/logs/telegram/';
    const START_MESSAGE = " — starting server... ";
    const STOP_MESSAGE = " — ending server... ";

    /**
     * Info about bot - result of botApi getMe method
     * @var array
     */
    protected static $info;

    /**
     * Logger
     * @var Logger
     */
    protected $logger;

    /**
     * Container
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Telegram Bot Api
     * @var Telegram
     */
    protected $telegram;
    /**
     * Token
     * @var string
     */
    protected $telegramBotToken;
    /**
     * AdminChatId
     * @var int
     */
    protected $telegramAdminChatId;
    /**
     * @var string
     */
    protected $fileOfProcess;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    public function getContainer()
    {
        if (null === $this->container) {
            $text = sprintf('You have an error: the container cannot be retrieved as the application instance is not yet set.  (%s %s) ', __FILE__, __METHOD__);
            $this->logger->error($text);
            throw new \LogicException($text);
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Setting telegram Api
     */
    public function setTelegram()
    {
        $container = $this->getContainer();
        $this->telegramAdminChatId = $container->getParameter('telegram_admin_chat_id');
        $this->telegramBotToken = $container->getParameter('telegram_bot_token');
        $this->telegram = new Telegram($this->telegramBotToken);
        if ($container->hasParameter('proxy')){
            $this->telegram->setProxy($container->getParameter('proxy'));
            if ($container->hasParameter('proxy_socks5')){
                $this->telegram->setProxySocks5($container->getParameter('proxy_socks5'));
            }
        }

        $needResponsesLog = $container->getParameter('telegram_bot_need_responses_log');
        $needRequestsLog = $container->getParameter('telegram_bot_need_requests_log');
        if ($needResponsesLog){
            $this->telegram->setResponsesLogFile($this->getLogDir() . '/responses.log');
        }
        if ($needRequestsLog){
            $this->telegram->setRequestsLogFile($this->getLogDir() . '/requests.log');
        }

        $temp = $this->telegram->getMe();
        if (is_array($temp) && isset($temp['result'])){
            static::$info = $temp['result'];
        }
    }

    protected function getLogDir()
    {
        return $this->getContainer()->getParameter('telegram_bot_log_dir');
    }

    protected function getStorage()
    {
        $container = $this->getContainer();
        $storage = $container->get('ducha.telegram-bot.storage');

        return $storage;
    }

    /**
     * @return int
     */
    protected function getLastUpdateId()
    {
        $storage = $this->getStorage();
        $key = StorageKeysHolder::getLastUpdateIdKey();
        $lastUpdateId = intval($storage->get($key));

        return $lastUpdateId;
    }

    /**
     * @param int $id
     */
    protected function setLastUpdateId(int $id)
    {
        $storage = $this->getStorage();
        $key = StorageKeysHolder::getLastUpdateIdKey();
        $storage->set($key, $id);
    }

    public function execute()
    {
        $this->fileOfProcess = $this->getLogDir() . '/running';
        $lastUpdateId = $this->getLastUpdateId();
        $container = $this->getContainer();
        $fl = $this->start();
        if ($fl) {
            $this->setTelegram();
            $commandHandler = new CommandHandler($container, $this);
            if (!empty($this->telegramAdminChatId)){
                $this->telegram->sendMessage($this->telegramAdminChatId, date("d.m.Y H:i:s") . self::START_MESSAGE);
            }
            while(true){
                $updates = $this->telegram->pollUpdates($lastUpdateId, 60);
                if (isset($updates['result']) && count($updates['result']) > 0){
                    $lastUpdateId = $updates['result'][count($updates['result']) - 1]['update_id'];
                    $lastUpdateId++;
                    // Write down last update id from telegram bot
                    $this->setLastUpdateId($lastUpdateId);
                    foreach($updates['result'] as $data){
                        $commandHandler->process($data);
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function start()
    {
        if (file_exists($this->fileOfProcess)){
            echo "bot already is running!\n"; exit;
        }
        // Start writing log-file
        $this->logger->info(self::START_MESSAGE);

        // Open and try to Lock «running» file, so only one process will be alive
        $fR = fopen($this->fileOfProcess, 'w');
        $fl = flock($fR, LOCK_EX | LOCK_NB);

        return $fl;
    }

    public function kill()
    {
        $this->logger->info(self::STOP_MESSAGE);
        unlink($this->fileOfProcess);

        exit();
    }

    /**
     * @return mixed
     */
    public function getTelegram()
    {
        return $this->telegram;
    }

    /**
     * @return mixed
     */
    public function getTelegramAdminChatId()
    {
        return $this->telegramAdminChatId;
    }

    /**
     * @return mixed|string
     */
    public static function getId()
    {
        return empty(static::$info)? '' : static::$info['id'];
    }

}