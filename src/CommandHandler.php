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

use Ducha\TelegramBot\Commands\CommandInterface;
use Ducha\TelegramBot\Commands\KillBotCommand;
use Ducha\TelegramBot\Commands\PingCommand;
use Ducha\TelegramBot\Commands\RunCommand;
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
     * CommandHandler constructor.
     * @param ContainerInterface $container
     * @param TelegramBot $telegramBot
     */
    public function __construct(ContainerInterface $container, TelegramBot $telegramBot)
    {
        $this->mode = static::WORKING_STATE;
        $this->container = $container;

        $needLog = $this->container->getParameter('telegram_bot_need_command_handler_log');
        if ($needLog){
            $this->testLogFile = $this->container->getParameter('telegram_bot_log_dir') . '/CommandHandler.log';
        }

        $this->groupManager = $this->container->get('ducha.telegram-bot.group.manager');

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
        // for test goal
        if (!empty($this->testLogFile)){
            file_put_contents($this->testLogFile, var_export($data, true) . "\n\n" , FILE_APPEND);
        }

        if (isset($data['message'])){
            $message = new Message($data['message']);
            $this->groupManager->lookAtMessage($message);
        }

        $sendOops = true;
        foreach ($this->commands as $key => $command){
            if ($command instanceof CommandInterface && $command->isApplicable($data)) {
                if ($this->mode == static::WORKING_STATE){
                    $command->execute($data);
                }else{
                    if ($command::getName() == RunCommand::getName() || $command::getName() == KillBotCommand::getName()){
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
        return $this->container->get('ducha.telegram-bot.poll.manager');
    }

    /**
     * @return PollStatManagerInterface
     */
    public function getPollStatManager()
    {
        return $this->container->get('ducha.telegram-bot.poll.stat.manager');
    }

    /**
     * @return PollStatManagerInterface
     */
    public function getPollSurveyStatManager()
    {
        return $this->container->get('ducha.telegram-bot.poll.survey.stat.manager');
    }

    /**
     * @return GroupManagerInterface
     */
    public function getGroupManager()
    {
        return $this->groupManager;
    }
}