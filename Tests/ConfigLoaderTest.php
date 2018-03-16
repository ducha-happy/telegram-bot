<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/ConfigLoaderTest.php
 */

namespace Ducha\TelegramBot\Tests;

use PHPUnit\Framework\TestCase;
use Ducha\TelegramBot\ConfigLoader;
use Symfony\Component\Yaml\Yaml;

class ConfigLoaderTest extends TestCase
{
    public function testThatConfigLoaderIsCorrect()
    {
        $configLoader = new ConfigLoader();
        $container = $configLoader->getContainer();

        $class = 'Symfony\Component\DependencyInjection\ContainerBuilder';
        $this->assertInstanceOf($class, $container, sprintf('Must be InstanceOf %s', $class));

        $file = __DIR__ . '/../app/config/config.yml';
        $config = Yaml::parse(file_get_contents($file));
        $parameters = $config['parameters'];
        foreach ($parameters as $parameter => $value){
            $this->assertTrue($container->hasParameter($parameter), sprintf('Container does not contain "%s" parameter', $parameter));
        }

        $parameter = 'root_dir';
        $this->assertTrue($container->hasParameter($parameter), sprintf('Container does not contain "%s" parameter', $parameter));
        $this->assertEquals(realpath(__DIR__ . '/../'), $container->getParameter($parameter), sprintf('Parameter "%s" is not correct', $parameter));

        $file = __DIR__ . '/../app/config/services.yml';
        $config = Yaml::parse(file_get_contents($file));
        $services = $config['services'];
        foreach ($services as $service => $attributes){
            $this->assertTrue($container->has($service), sprintf('Container does not have "%s" service', $service));
        }
    }

}