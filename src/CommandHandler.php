<?php

namespace Ducha\TelegramBot;

use Ducha\TelegramBot\Commands\CommandInterface;
use Ducha\TelegramBot\Commands\KillBotCommand;
use Ducha\TelegramBot\Commands\PingCommand;
use Ducha\TelegramBot\Commands\StartCommand;
use Ducha\TelegramBot\Poll\PollManagerInterface;
use Ducha\TelegramBot\Poll\PollStatManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Ducha\TelegramBot\Types\Message;

class CommandHandler
{
    use ContainerAwareTrait;

    const WORKING_STATE = 1;
    const SLEEPING_STATE = 2;
    /**
     * Flag to find out a need to test
     *
     * @const boolean
     */
    const TEST_LOG_ON = true;

    /**
     * Mode of operation
     * can be WORKING_STATE or SLEEPING_STATE
     * @var int
     */
    protected $mode;

    /**
     * Your real Telegram Bot which is running on somewhere
     *
     * @var object
     */
    protected $telegramBot;

    /**
     * Telegram Bot Api
     *
     * @var object
     */
    protected $telegram;

    /**
     * list of commands to process
     *
     * @var array
     */
    protected $commands;

    /**
     * log file to test
     *
     * @var string
     */
    protected $testLogFile;

    /**
     * User manager to monitor your group users
     *
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * @param string $file
     */
    public function setTestLogFile($file)
    {
        $this->testLogFile = $file;
    }

    /**
     * @return string
     */
    public function getTestLogFile()
    {
        return $this->testLogFile;
    }

    /**
     * CommandHandler constructor.
     * @param ContainerInterface $container
     * @param TelegramBot $telegramBot
     */
    public function __construct(ContainerInterface $container, TelegramBot $telegramBot)
    {
        $this->mode = static::WORKING_STATE;
        $this->container = $container;

        $this->testLogFile = $this->getTestLogFile();

        $this->groupManager = $this->container->get('ducha.telegram.group.manager');

        $this->telegramBot = $telegramBot;
        $this->telegram = $telegramBot->getTelegram();
        if (!$this->telegram instanceof Telegram){
            throw new \InvalidArgumentException('Bad telegram bot');
        }
        
        $reflection = new \ReflectionClass($this);
        $namespace = '\\' . $reflection->getNamespaceName() .  '\Commands\\';

        foreach (Finder::create()->files()->in(__DIR__ . '/Commands') as $file){
            $className = $namespace . str_replace(".php", "", $file->getRelativePathName());
            $reflection = new \ReflectionClass($className);
            if ($reflection->isTrait() ||
                $reflection->isAbstract() ||
                $reflection->isInterface() ||
                $reflection->implementsInterface(CommandInterface::class) == false
            ){
                continue;
            }
            $key = $className::getName();
            if (!empty($key)){
                if (isset($this->commands[$key])){
                    throw new \LogicException(sprintf('You have duplicate a command %s that defined in class %s , but this command is existed in class %s  ', $key, $className, $this->commands[$key]));
                }
                $this->commands[$key] = new $className($this);
            }
        }
    }

    public function getContainer()
    {
       return $this->container;
    }

    public function getTelegramBot()
    {
        return $this->telegramBot;
    }

    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param array $data
     */
    public function process($data)
    {
        // TODO must be removed
        if (self::TEST_LOG_ON && !empty($this->testLogFile)){
            file_put_contents($this->testLogFile, var_export($data, true) . "\n\n" , FILE_APPEND);
        }

        if (isset($data['message'])){
            $message = new Message($data['message']);
            $this->groupManager->lookAtMessage($message);
        }

        $sendOops = true;
        foreach ($this->commands as $key => $command){
//            echo ( sprintf("stop - %s, %s \n", time(), $command::getName() ) );
            if ($command instanceof CommandInterface && $command->isApplicable($data)) {
                if ($this->mode == static::WORKING_STATE){
                    $command->execute($data);
                }else{
                    if ($command::getName() == StartCommand::getName() || $command::getName() == KillBotCommand::getName()){
                        $command->execute($data);
                    }else{
                        if ($sendOops){
                            $message = $command->getMessage($data);
                            $this->telegram->sendMessage($message->getChatId(), 'Oops! i am stopped now! Ask me later. Use ' . PingCommand::getName() . ' to check out me!');
                            $sendOops = false;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return PollManagerInterface
     */
    public function getPollManager()
    {
        return $this->container->get('ducha.telegram.poll.manager');
    }

    /**
     * @return PollStatManagerInterface
     */
    public function getPollStatManager()
    {
        return $this->container->get('ducha.telegram.poll.stat.manager');
    }

    /**
     * @return GroupManagerInterface
     */
    public function getGroupManager()
    {
        return $this->groupManager;
    }
}