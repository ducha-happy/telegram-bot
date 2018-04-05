<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Commands;

use Ducha\TelegramBot\Commands\AbstractCommand;
use Ducha\TelegramBot\Tests\AbstractTest;
use Ducha\TelegramBot\Commands\CommandInterface;

abstract class AbstractCommandTest extends AbstractTest
{
    /**
     * @var AbstractCommand
     */
    protected $command;

    public function getCommandClass()
    {
        $class = get_class($this);
        $class = preg_replace('|Test$|', '', $class);
        $temp = explode('\\', $class);
        $temp = array_filter($temp, function($value){
            return ($value == 'Tests')? false : true;
        });

        return '\\' . implode('\\', $temp);
    }

    public function setUp()
    {
        parent::setUp();

        $class = $this->getCommandClass();

        if (class_exists($class) == false){
            throw new \LogicException(sprintf('Class %s does not exists', $class));
        }

        $this->command = new $class($this->handler);

        if (!$this->command instanceof CommandInterface){
            throw new \LogicException(sprintf('Class %s must be instance of %s', $class, CommandInterface::class));
        }
        $this->data['message']['text'] = $class::getName();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->command = null;
    }
}