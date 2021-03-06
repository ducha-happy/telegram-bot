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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader
{
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var string
     */
    protected $log_dir;
    /**
     * @var string
     */
    protected $log_file;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ConfigLoader constructor.
     * @param string|null $configFile
     * @param string|null $servicesFile
     */
    public function __construct($configFile = null, $servicesFile = null)
    {
        $this->setContainer($configFile, $servicesFile);
        $this->setLogger();
    }

    /**
     * @param string|null $configFile
     * @param string|null $servicesFile
     * @throws \LogicException
     */
    private function setContainer($configFile, $servicesFile)
    {
        $containerBuilder = new ContainerBuilder();

        $file = __DIR__ . '/../app/config/config.yml';
        if (!empty($configFile)){
            $file = $configFile;
        }

        if (!file_exists($file)){
            throw new \LogicException(sprintf('Config file "%s" does not exists', $file));
        }
        $config = Yaml::parse(file_get_contents($file));

        if (!file_exists($file)){
            throw new \LogicException(sprintf('Config file "%s" does not exists', $file));
        }

        // default parameters
        $parameters = array(
            'telegram_admin_chat_id'                => '',
            'telegram_bot_need_command_handler_log' => false,
            'telegram_bot_need_requests_log'        => false,
            'telegram_bot_need_responses_log'       => false,
            'locale'                                => 'en_US', // ru_RU
        );

        $parameters = array_merge($parameters, $config['parameters']);

        foreach ($parameters as $parameter => $value){
            $containerBuilder->setParameter($parameter, $value);
        }

        $resources = array();
        $pattern = '/^messages\.([a-z]{2}_[A-Z]{2})\.xliff$/';
        $dir = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Resources', 'translations'));
        foreach (Finder::create()->files()->in($dir) as $file){
            if ($file instanceof \SplFileInfo){
                $fileName = $file->getFilename();
                if (preg_match($pattern, $fileName, $matches)){
                    $resources[$matches[1]]['locale'] = $matches[1];
                    $resources[$matches[1]]['path'] = $file->getPathname();
                }
            }
        }

        // check locale and set it correctly with country code
        $locale = $containerBuilder->getParameter('locale');
        if (preg_match('/^[a-z]{2}$/', $locale)){
            foreach ($resources as $resource){
                $temp = explode('_', $resource['locale']);
                if ($locale == $temp[0]){
                    $containerBuilder->setParameter('locale', $resource['locale']);
                    break;
                }
            }
        }

        $file = __DIR__ . '/../app/config/services.yml';
        if (!empty($servicesFile)){
            $file = $servicesFile;
        }
        $config = Yaml::parse(file_get_contents($file));

        $services = $config['services'];
        foreach ($services as $service => $attributes){
            $definition = $containerBuilder
                ->register($service, $attributes['class']);
            if (isset($attributes['arguments'])){
                foreach ($attributes['arguments'] as $argument){
                    $definition->addArgument(
                        preg_match("|^@|", $argument)? new Reference(str_replace("@", "", $argument)) : $argument
                    );
                }
            }
        }

        $this->container = $containerBuilder;

        // setting translator
        $translator = $this->container->get('ducha.telegram-bot.translator');
        $translator->addLoader('xliff', new XliffFileLoader());
        foreach ($resources as $resource){
            $translator->addResource('xliff', $resource['path'], $resource['locale']);
        }

        $translator->setFallbackLocales(array('en_US', 'en'));
    }

    public function getContainer()
    {
        return $this->container;
    }

    private function setLog()
    {
        $rootDir = __DIR__ . '/../';
        $parameter1 = 'root_dir';

        if ($this->container->hasParameter($parameter1)){
            $rootDir = $this->container->getParameter($parameter1);
            if (!file_exists($rootDir)){
                throw new \LogicException(sprintf('You have logic exception in %s of %s : Directory "%s" specified in a "%s" parameter must exist', __METHOD__, __FILE__, $rootDir, $parameter1));
            }
        }else{
            $this->container->setParameter($parameter1, realpath($rootDir));
        }

        $logDir = $this->container->getParameter('root_dir') . '/app/logs';
        $parameter2 = 'telegram_bot_log_dir';

        if ($this->container->hasParameter($parameter2)){
            $logDir = str_replace("%" . $parameter1 . "%", $this->container->getParameter($parameter1), $this->container->getParameter($parameter2));
        }

        if (!file_exists($logDir)){
            mkdir($logDir, 0777, true);
        }

        if (!is_writable($logDir)){
            throw new \LogicException(sprintf('You have logic exception in %s of %s : Directory %s must exist and be writable', __METHOD__, __FILE__, $logDir));
        }
        $this->container->setParameter($parameter2, realpath($logDir));
        $this->log_dir = $logDir;

        $file = 'telegram-bot.log';
        $parameter3 = 'telegram_bot_log_file_name';

        if ($this->container->hasParameter($parameter3)){
            $file = $this->container->getParameter($parameter3);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($logFile)){
            touch($logFile);
        }

        if (!is_writable($logFile)){
            throw new \LogicException(sprintf('You have logic exception in %s of %s : File %s must exist and be writable', __METHOD__, __FILE__, $logFile));
        }

        $this->log_file = $logFile;
    }

    private function setLogger()
    {
        $this->setLog();

        // Create the logger
        $logger = new Logger('telegram_bot_logger');

        // Now add some handlers
        $logger->pushHandler(new StreamHandler($this->getLogFile(), Logger::DEBUG));

        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getLogFile()
    {
        return $this->log_file;
    }

    public function getLogDir()
    {
        return $this->log_dir;
    }
}