<?php
/**
 * phpunit57 -v -c ./phpunit.xml.dist ./Tests/HelpDifferentThingsTest.php
 */

namespace Ducha\TelegramBot\Tests;

use Ducha\TelegramBot\Formatter\TranslateFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\XliffFileLoader;

class HelpDifferentThingsTest extends TestCase
{
    public function testCommandName()
    {
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

    public function testPregMatch()
    {
//        $message = 'text must be a digit';
//        $temp = 2;
//        $this->assertTrue((bool)preg_match("|^\d+$|", $temp), 'error 1: ' . $message);
//        $this->assertTrue(is_int($temp), 'error 3: ' . $message);
//        $temp = '2';
//        $this->assertTrue((bool)preg_match("|^\d+$|", $temp), 'error 2: ' . $message);
//        $this->assertFalse(is_int($temp), 'error 4: the var is integer but must be string');
    }

    public function testArrayMerge()
    {
        // default parameters
        $parameters = array(
            'telegram_bot_need_command_handler_log' => false,
            'telegram_bot_need_requests_log'        => false,
            'telegram_bot_need_responses_log'       => false,
        );

        $config['parameters'] = array(
            'telegram_bot_need_command_handler_log' => true,
        );

        $parameters = array_merge($parameters, $config['parameters']);

        $this->assertEquals($parameters['telegram_bot_need_command_handler_log'], true, '3333');
    }

    public function testPatterns()
    {
        $pattern = '|^poll_stat_remove_action\.-\d+\.\d+(\.uncompleted){0,1}$|';
        $str = 'poll_stat_remove_action.-1001233109538.10.uncompleted';
        $str2 = 'poll_stat_remove_action.-1001233109538.10';
        //$this->assertStringMatchesFormat($pattern, $str, 'str is not match pattern');
        $this->assertTrue((bool)preg_match($pattern, $str), 'str is not match pattern');
        $this->assertTrue((bool)preg_match($pattern, $str2), 'str2 is not match pattern');
    }

    public function testLocales()
    {
        $pattern = '/^messages\.(ru|en)\.xliff$/';
        $locales = array('ru' => 'RU', 'en' => 'US');
        $dir = implode(DIRECTORY_SEPARATOR, array(__DIR__ . '/../src', 'Resources', 'translations'));
        $counter = 0;
        foreach (Finder::create()->files()->in($dir) as $file){
            if ($file instanceof \SplFileInfo){
                $fileName = $file->getFilename();
                if (preg_match($pattern, $fileName, $matches)){
                    $counter++;
                }
            }
        }
        $this->assertEquals(2, $counter, 'A quantity of locales must be equals 2');
    }

    public function testXliffFormater()
    {
        $loader = new XliffFileLoader();
        $file = __DIR__ . '/../src/Resources/translations/messages.en.xliff';
        $catalogue = $loader->load($file, 'en-US');
        $str = $catalogue->get('kill_bot_command_description');
        $str2 = TranslateFormatter::format($str);
        $this->assertEquals(195, strlen($str), 'strlen function must return 195');
        $this->assertEquals(137, strlen($str2), 'strlen function must return 137');
    }
}