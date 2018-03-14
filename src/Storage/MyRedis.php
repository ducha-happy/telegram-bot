<?php

namespace Ducha\TelegramBot\Storage;

use Predis\Client;

class myRedis
{
    protected static $client;

    protected static function getClient()
    {
        if (!self::$client instanceof Client){
            self::$client = new Client();
        }

        return self::$client;
    }

    public static function addToListIfKeyExists($key, $value){
        self::getClient()->lpushx($key, $value);
    }

    public static function addToList($key, $value){
        self::getClient()->lpush($key, $value);
    }

    public static function removeFromList($key, $index, $value){
        self::getClient()->lrem($key, $index, $value);
    }

    public static function getList($key){
        return self::getClient()->lrange($key, 0, -1);
    }

    public static function countList($key){
        return self::getClient()->llen($key);
    }

    public static function set($key, $data){
        if (is_array($data) || is_object($data)){
            $data = serialize($data);
        }
        self::getClient()->set($key, $data);
    }

    public static function get($key){
        $data = self::getClient()->get($key);
        $temp = @unserialize($data);
        if ($temp !== false){
            $data = $temp;
        }

        return $data;
    }

    public static function del($key){
        self::getClient()->del($key);
    }

    public static function exists($key){
        return self::getClient()->exists($key);
    }

    public static function remove($key){
        self::del($key);
    }

    public static function keys($pattern){
        return self::getClient()->keys($pattern);
    }

    public static function showKeys($pattern){
        $keys = self::keys($pattern);
        sort($keys);
        foreach ($keys as $key){
            self::showKey($key);
        }
    }

    public static function type($key){
        return self::getClient()->type($key);
    }

    public static function showKey($key){
        $type = self::type($key);
        echo $key . ' ' . $type . "\n";
        switch ($type){
            case 'list':
                $val = self::getList($key);
                break;
            case 'string':
                $val = self::get($key);
                break;
            default:
                $val = '';
                break;
        }
//        var_dump($val);
    }

    public static function clear($pattern){
        $keys = self::keys($pattern);
        foreach ($keys as $key){
            self::remove($key);
        }
    }
}