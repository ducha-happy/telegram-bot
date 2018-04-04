<?php

namespace Ducha\TelegramBot;

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
        $this->telegramAdminChatId = $this->getContainer()->getParameter('telegram_admin_chat_id');
        $this->telegramBotToken = $this->getContainer()->getParameter('telegram_bot_token');
        $this->telegram = new Telegram($this->telegramBotToken);

        $needResponsesLog = $this->getContainer()->getParameter('telegram_bot_need_responses_log');
        $needRequestsLog = $this->getContainer()->getParameter('telegram_bot_need_requests_log');
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

    public function execute()
    {
        $container = $this->getContainer();
        $logDir = $this->getLogDir();

        $fileOfLastUpdate = $logDir . '/LastUpdateId.log';
        $this->fileOfProcess = $logDir . '/running';

        $lastUpdateId = 0;
        if (file_exists($fileOfLastUpdate)){
            $lastUpdateId = intval(file_get_contents($fileOfLastUpdate));
        }

        $fl = $this->start();

        if ($fl) {
            $this->setTelegram();
            $commandHandler = new CommandHandler($container, $this);
            $this->telegram->sendMessage($this->telegramAdminChatId, date("d.m.Y H:i:s") . self::START_MESSAGE);
            $loopIndex = 0;
            while(true){
                $updates = $this->telegram->pollUpdates($lastUpdateId, 60);
                if (isset($updates['result']) && count($updates['result']) > 0){
                    $lastUpdateId = $updates['result'][count($updates['result']) - 1]['update_id'];
                    $lastUpdateId++;
                    // Write down last update id from telegram bot
                    file_put_contents($fileOfLastUpdate, $lastUpdateId); #TODO this must be replaced on a redis key
                    foreach($updates['result'] as $data){
                        $commandHandler->process($data);
                    }
                }
                $loopIndex++;
            }
        }
    }

    public function start()
    {
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