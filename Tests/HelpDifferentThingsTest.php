<?php
/**
 * phpunit57 -v -c src/Ducha/TelegramBot/phpunit.xml.dist src/Ducha/TelegramBot/Tests/HelpDifferentThingsTest.php
 */

namespace Ducha\TelegramBot\Tests;

use PHPUnit\Framework\TestCase;

class HelpDifferentThingsTest extends TestCase
{
    public function testPregMatch()
    {
//        $message = 'text must be a digit';
//        $temp = 2;
//        $this->assertTrue((bool)preg_match("|^\d+$|", $temp), 'error 1: ' . $message);
//        $this->assertTrue(is_int($temp), 'error 3: ' . $message);
//        $temp = '2';
//        $this->assertTrue((bool)preg_match("|^\d+$|", $temp), 'error 2: ' . $message);
//        $this->assertFalse(is_int($temp), 'error 4: the var is integer but must be string');
        
        function getBotName(){
            return 'UmaTestBot';
        }

        function getName(){
            return '/pollstart';
        }
        
        $str1 = '/pollstart';
        $this->assertTrue($str1 == getName(), 'str1 - variant one is not good');
        $this->assertFalse($str1 == getName().'@'.getBotName(), 'str1 - variant two is not good');
        $this->assertTrue($str1 == getName().'@'.getBotName() || $str1 == getName(), 'str1 - variant three is not good');
        
        $str2 = '/pollstart@UmaTestBot';
        $this->assertFalse($str2 == getName(), 'str2 - variant one is not good');
        $this->assertTrue($str2 == getName().'@'.getBotName(), 'str2 - variant two is not good');
        $this->assertTrue($str2 == getName().'@'.getBotName() || $str2 == getName(), 'str2 - variant three is not good');

        $str3 = '/pollstart@UmaTetBot';
        $this->assertFalse($str3 == getName(), 'str3 - variant one is not good');
        $this->assertFalse($str3 == getName().'@'.getBotName(), 'str3 - variant two is not good');
        $this->assertFalse($str3 == getName().'@'.getBotName() || $str3 == getName(), 'str3 - variant three is not good');
    }

}