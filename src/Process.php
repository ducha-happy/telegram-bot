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

class Process
{
    private $pid;
    private $command;

    /**
     * Process constructor.
     * @param string|bool $cl
     */
    public function __construct($cl = false)
    {
        if ($cl != false){
            $this->command = $cl;
        }
    }

    private function runCom()
    {
        $command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
        exec($command ,$op);
        $this->pid = (int)$op[0];
    }

    /**
     * @param int $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return bool
     */
    public function status()
    {
        if (empty($this->pid)){
            return false;
        }

        $command = 'ps -p '.$this->pid;
        exec($command, $op);

        return (!isset($op[1]))? false : true;
    }

    /**
     * @return bool
     */
    public function start()
    {
        if ($this->command != ''){
            $this->runCom();

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function stop()
    {
        if (empty($this->pid)){
            return true;
        }
        $command = 'kill '.$this->pid;
        exec($command);

        return ($this->status() == false)? true : false;
    }

    public static function getTempDir()
    {
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        }
        elseif (is_dir( '/tmp')) {
            return '/tmp';
        }

        throw new \LogicException('Can`t find temp dir');
    }
}