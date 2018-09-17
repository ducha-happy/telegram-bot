<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Commands;

use Ducha\TelegramBot\JsonFileLoader;
use Ducha\TelegramBot\Process;
use Ducha\TelegramBot\Storage\StorageKeysHolder;
use Ducha\TelegramBot\Types\KeyboardButton;
use Ducha\TelegramBot\Types\ReplyKeyboardMarkup;
use Ducha\TelegramBot\Types\ReplyKeyboardRemove;
use Symfony\Component\Translation\Exception\InvalidResourceException;

class RouteCommand extends AbstractCommand
{
    use ArgumentsAwareTrait;

    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/route';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        return static::getTranslator()->trans('route_command_description',
            array('%command_name%' => static::getName())
        );
    }

    /**
     * @param array $temp ApiDirectionsResponse
     * @return string filename
     */
    protected function getPng($temp)
    {
        $start = array(
            $temp['routes'][0]['legs'][0]['start_location']['lat'],
            $temp['routes'][0]['legs'][0]['start_location']['lng']
        );

        $finish = array(
            $temp['routes'][0]['legs'][0]['end_location']['lat'],
            $temp['routes'][0]['legs'][0]['end_location']['lng']
        );

        $polyLine = $temp['routes'][0]['overview_polyline']['points'];

        $container = $this->handler->getContainer();
        $config = $container->getParameter('google_maps');

        $url = $config['static_map']['url'];
        $parameters = $config['static_map']['parameters'];

        $start = str_replace(array("&", "="), array(urlencode("|"), urlencode(":")), http_build_query($config['static_map']['markers']['start_location'])) . urlencode('|') . join(",", $start);
        $finish = str_replace(array("&", "="), array(urlencode("|"), urlencode(":")), http_build_query($config['static_map']['markers']['end_location'])) . urlencode('|') . join(",", $finish);

        $markers = array($start, $finish);

        $path = $config['static_map']['path'];
        $path = str_replace('=', urlencode(':'), http_build_query($path)) .
            urlencode('|') .
            urlencode('enc:') . urlencode($polyLine);

        $paths = array($path);

        $mGlue = '&markers=';
        $pGlue = '&path=';

        $source = $url . '?' .
            http_build_query($parameters) .
            $mGlue . join($mGlue, $markers) .
            $pGlue . join($pGlue, $paths)
        ;

        $cacheDir = $config['static_map']['cache_dir'];
        $cacheFile = join('/', array($cacheDir, md5($source) . '.png'));

        if (!file_exists($cacheFile)){
            file_put_contents($cacheFile, $this->getGoogleResponse($source));
        }

        return $cacheFile;
    }

    /**
     * @param string $origin start-point-coordinates
     * @param string $destination end-point-coordinates
     * @return array
     */
    protected function getGoogleRoute($origin, $destination)
    {
        $container = $this->handler->getContainer();
        $config = $container->getParameter('google_maps');
        //var_dump($config); die();
        $url = $config['api_directions']['url'];
        $parameters = $config['api_directions']['parameters'];
        $source = $url . '?' .
            http_build_query($parameters) .
            '&origin=' . $origin .
            '&destination=' . $destination
        ;

        $cacheDir = $config['api_directions']['cache_dir'];
        $cacheFile = join('/', array($cacheDir, md5($source) . '.json'));

        if (file_exists($cacheFile)){
            $source = $cacheFile;
        }

        $loader = new JsonFileLoader();
        try {
            //$response = $this->getGoogleResponse($str);
            $response = $loader->loadResource($source);
            if ($response['status'] == "OK"){
                if (!file_exists($cacheFile)){
                    file_put_contents($cacheFile, json_encode($response));
                }

                $filePng = $this->getPng($response);
                $curlPng = new \CURLFile($filePng);
                $curlPng->setMimeType('image/png');
                $curlPng->setPostFilename($origin . ' - ' . $destination);

                return array(
                    'distance' => $response['routes'][0]['legs'][0]['distance'],
                    'duration' => $response['routes'][0]['legs'][0]['duration'],
                    'png' => $curlPng,
                );
            }else{
                return array(
                    'status' => $response['status'],
                    //error_message
                );
            }
        }
        catch (InvalidResourceException $e){
            return array(
                'error_message' => $e->getMessage()
            );
        }
    }

    /**
     * @param string $url
     * @return mixed
     */
    protected function getGoogleResponse($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param array $data
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $chatId = $message->getChatId();
            if ($this->hasDemand($chatId)){
                $demand = $this->getDemand($chatId);
                $coordinates = $this->getCoordinates($demand['place']);
                $replyTo = $message->getReplyToMessage();
//                $location = false;
//                if ($replyTo !== false && $replyTo['message_id'] == $demand['reply_message_id']){
//                    $location = $message->getLocation();
//                }
                $location = $message->getLocation();
                $keyboard = $this->getMarkup(1);
                if ($location == false){
                    $this->telegram->sendMessage($chatId, $this->translator->trans('route_command.location_is_not_available'), null, null, null, $keyboard);
                }else{
                    $origin = $location['latitude'] . ',' . $location['longitude'];
                    $destination = $coordinates[0] . ',' . $coordinates[1];
                    //$this->telegram->sendLocation($chatId, $coordinates[0], $coordinates[1], null, $keyboard);
                    $route = $this->getGoogleRoute($origin, $destination);
                    if ($route){
                        $this->telegram->sendMessage($chatId, $this->translator->trans('route_command.route_found', array(
                                    '%origin%'      => $origin,
                                    '%destination%' => $destination,
                                    '%distance%' => $route['distance']['text'],
                                    '%duration%' => $route['duration']['text']
                            )), null, null, null, $keyboard);
                        $this->telegram->sendPhoto($chatId, $route['png'], $this->translator->trans('route_command.photo_caption', array(
                            '%origin%'      => $origin,
                            '%destination%' => $destination
                        )));
                    }else{
                        $this->telegram->sendMessage($chatId, $this->translator->trans('route_command.route_not_found', array(
                            '%origin%'      => $origin,
                            '%destination%' => $destination,
                            )), null, null, null, $keyboard);
                    }
                }
                $this->removeDemand($chatId);
            }else{
                $args = $this->getArguments();
                $place = $args[0];
                $keyboard = $this->getMarkup();
                $response = $this->telegram->sendMessage($chatId, $this->translator->trans('route_command.give_me_location'), null, null, null, $keyboard);
                if (isset($response['result']['message_id'])){
                    $this->setDemand($chatId,
                        array(
                            'place'            => $place,
                            'reply_message_id' => $response['result']['message_id']
                        )
                    );
                }
            }
        }
    }

    /**
     * Расстояние между двумя точками на сфере в метрах
     * $φA, $λA - широта, долгота 1-й точки,
     * $φB, $λB - широта, долгота 2-й точки
     * Написано по мотивам http://gis-lab.info/qa/great-circles.html
     * Михаил Кобзарев <mikhail@kobzarev.com>
     *
     */
    public function calculateTheDistance ($φA, $λA, $φB, $λB)
    {
        if (!defined('EARTH_RADIUS')){
            define('EARTH_RADIUS', 6372795);
        }

        // перевести координаты в радианы
        $lat1 = $φA * M_PI / 180;
        $lat2 = $φB * M_PI / 180;
        $long1 = $λA * M_PI / 180;
        $long2 = $λB * M_PI / 180;

        // косинусы и синусы широт и разницы долгот
        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);
        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

        // вычисления длины большого круга
        $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

        //
        $ad = atan2($y, $x);
        $dist = $ad * EARTH_RADIUS;

        return $dist;
    }

    /**
     * @param $str
     * @return array|bool
     */
    protected function getCoordinates($str)
    {
        $temp = explode(',', $str);

        return (count($temp) == 2)? $temp : false;
    }

    public function getMarkup($remove = false)
    {
        $rows = array(
            array(new KeyboardButton($this->translator->trans('route_command.btn_send_location'), false, true))
        );

        return $remove? json_encode(new ReplyKeyboardRemove()) : json_encode(new ReplyKeyboardMarkup($rows, true, true));
    }

    /**
     * @param int $chatId
     */
    protected function removeDemand($chatId)
    {
        $key = StorageKeysHolder::getRouteKey($chatId);
        $this->storage->remove($key);
    }

    /**
     * @param int $chatId
     * @param array $value
     */
    protected function setDemand($chatId, $value)
    {
        $key = StorageKeysHolder::getRouteKey($chatId);
        $this->storage->set($key, json_encode($value));
    }

    /**
     * @param int $chatId
     * @return bool
     */
    protected function hasDemand($chatId)
    {
        $key = StorageKeysHolder::getRouteKey($chatId);
        $demand = $this->storage->get($key);

        return (bool) $demand;
    }

    /**
     * @param $chatId
     * @return array
     */
    protected function getDemand($chatId)
    {
        $key = StorageKeysHolder::getRouteKey($chatId);
        $demand = get_object_vars(json_decode($this->storage->get($key)));

        return $demand;
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isApplicable(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $text = $message->getText();
            $chatId = $message->getChatId();
            $chatType = $message->getChatType();

            if ($this->isChatTypeAvailable($chatType)){
                $command = '';
                if (preg_match('|^/|', $text)){
                    $temp = $this->combOut($text);
                    if (!empty($temp)){
                        if (count($temp) > 1){
                            $args = $temp; array_shift($args);
                            $this->setArguments($args);
                        }
                    }
                    $command = $temp[0];
                }
                if ($this->stringIsCommand($command) || $this->hasDemand($chatId)){

                    if ($this->stringIsCommand($command)){
                        $args = $this->getArguments();
                        $argumentIsGood = true;
                        if (empty($args)){
                            $argumentIsGood = false;
                        }
                        $place = $args[0];
                        $pattern = "|^\d{1,2}\.\d{1,15},\d{1,2}\.\d{1,15}$|";
                        if (!preg_match($pattern, $place)){
                            $argumentIsGood = false;
                        }

                        if ($argumentIsGood == false){
                            $this->telegram->sendMessage($chatId, $this->translator->trans('route_command.bad_argument'));
                            return false;
                        }
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isChatTypeAvailable($type)
    {
        return array_search($type, array('private')) !== false;
    }
}