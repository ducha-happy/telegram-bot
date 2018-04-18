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

use InvalidArgumentException;
use Ducha\TelegramBot\Types\ReplyKeyboardMarkup;
use Ducha\TelegramBot\Types\InlineKeyboardMarkup;

class Telegram
{
    protected $token;
    protected $baseURL;
    /**
     * For any other than default mode telegram api will be silent
     * @var string $mode default prod
     */
    protected $mode = 'prod';
    /**
     * Log file for requests
     * @var string $requestsLogFile
     */
    protected $requestsLogFile;
    /**
     * Log file for responses
     * @var string $responsesLogFile
     */
    protected $responsesLogFile;
    /**
     * @var bool $useCurl
     */
    protected $useCurl = false;
    /**
     * @var string $curlProxy
     */
    protected $curlProxy;
    /**
     * @var bool $curlProxySocks5
     */
    protected $curlProxySocks5 = false;

    /**
     * @param mixed $requestsLogFile
     */
    public function setRequestsLogFile($requestsLogFile)
    {
        $this->requestsLogFile = $requestsLogFile;
    }

    /**
     * @param mixed $responsesLogFile
     */
    public function setResponsesLogFile($responsesLogFile)
    {
        $this->responsesLogFile = $responsesLogFile;
    }

    /**
     * Telegram constructor.
     * @param string $token
     */
    public function __construct($token)
    {
        if (is_null($token)) {
            throw new InvalidArgumentException('Required "token" is null');
        }

        $this->token = $token;
        $this->baseURL = 'https://api.telegram.org/bot' . $this->token . "/";
    }

    /**
     * A simple method for testing your bot's auth token.
     * Returns basic information about the bot in form of a User object.
     *
     * @link https://core.telegram.org/bots/api#getme
     *
     * @return bool|mixed
     */
    public function getMe()
    {
        return $this->sendRequest('getMe', array());
    }

    /**
     * Use this method to receive incoming updates using long polling.
     *
     * @link https://core.telegram.org/bots/api#getupdates
     *
     * @param int $offset
     * @param int $limit
     * @param int $timeout
     *
     * @return bool|mixed
     */
    public function pollUpdates($offset = null, $timeout = null, $limit = null)
    {
        $params = compact('offset', 'limit', 'timeout');

        return $this->sendRequest('getUpdates', $params);
    }

    /**
     * Send text messages.
     *
     * @link https://core.telegram.org/bots/api#sendmessage
     *
     * @param int $chat_id
     * @param string $text
     * @param string $parse_mode
     * @param bool $disable_web_page_preview
     * @param int $reply_to_message_id
     * @param ReplyKeyboardMarkup|InlineKeyboardMarkup $reply_markup
     *
     * @return bool|mixed
     */
    public function sendMessage($chat_id, $text, $parse_mode = 'HTML', $disable_web_page_preview = false, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'text', 'parse_mode', 'disable_web_page_preview', 'reply_to_message_id', 'reply_markup');

        return $this->sendRequest('sendMessage', $params);
    }

    /**
     * Get chat members count.
     *
     * @link https://core.telegram.org/bots/api/#getchatmemberscount
     *
     * @param int $chat_id
     *
     * @return bool|mixed
     */
    public function getChatMembersCount($chat_id)
    {
        $params = compact('chat_id');

        return $this->sendRequest('getChatMembersCount', $params);
    }

    /**
     * Send Location.
     *
     * @link https://core.telegram.org/bots/api#sendlocation
     *
     * @param int            $chat_id
     * @param float          $latitude
     * @param float          $longitude
     * @param int            $reply_to_message_id
     * @param ReplyKeyboardMarkup $reply_markup
     *
     * @return bool|mixed
     */
    public function sendLocation($chat_id, $latitude, $longitude, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = compact('chat_id', 'latitude', 'longitude', 'reply_to_message_id', 'reply_markup');

        return $this->sendRequest('sendLocation', $params);
    }

    private function getCurlResponse($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($this->curlProxy)){
            curl_setopt($ch, CURLOPT_PROXY, $this->curlProxy);
            if ($this->curlProxySocks5 == true){
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); #0

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param $method
     * @param $params
     * @return bool|mixed
     */
    private function sendRequest($method, $params)
    {
        if ($this->mode == 'prod'){
            if (!empty($this->requestsLogFile)){
                file_put_contents($this->requestsLogFile, var_export($this->baseURL . $method . '?' . http_build_query($params), true) . "\n\n", FILE_APPEND);
            }

            $url = $this->baseURL . $method . '?' . http_build_query($params);

            if ($this->useCurl){
                $content = $this->getCurlResponse($url);
            }else{
                $content = @file_get_contents($url);
            }

            if ($content !== false){
                $content = self::jsonValidate($content, true);
            }

            if (!empty($this->responsesLogFile)){
                file_put_contents($this->responsesLogFile, var_export($content, true) . "\n\n", FILE_APPEND);
            }

            return $content;
        }

        return false;
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public function getRequest($method, $params)
    {
        return var_export($this->baseURL . $method . '?' . http_build_query($params), true);
    }

    /**
     * Use this method to send answers to callback queries sent from inline keyboards.
     * The answer will be displayed to the user as a notification at the top of the chat screen or as an alert. On success, True is returned.
     * @link https://core.telegram.org/bots/api#answercallbackquery
     *
     * @param string $callback_query_id
     * @param string $text Optional. Text of the notification. If not specified, nothing will be shown to the user, 0-200 characters
     * @param bool $show_alert Optional. If true, an alert will be shown by the client instead of a notification at the top of the chat screen. Defaults to false.
     * @param string $url Optional. URL that will be opened by the user's client.
     * @param int $cache_time Optional. The maximum amount of time in seconds
     *
     * @return bool|mixed
     */
    public function answerCallbackQuery($callback_query_id, $text = '', $show_alert = false, $url = '', $cache_time = 0)
    {
        $params = compact('callback_query_id', 'text', 'show_alert', 'url', 'cache_time');

        return $this->sendRequest('answerCallbackQuery', $params);
    }

    /**
     * Use this method to edit text and game messages sent by the bot or via the bot (for inline bots).
     * On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     * @link https://core.telegram.org/bots/api#editmessagetext
     *
     * @param int | string $chat_id Optional. Required if inline_message_id is not specified. Unique identifier for the target chat or username of the target channel (in the format @channelusername)
     * @param int $message_id Optional. Required if inline_message_id is not specified. Identifier of the sent message
     * @param string $inline_message_id Optional. Required if chat_id and message_id are not specified. Identifier of the inline message
     * @param string $text  Required. New text of the message
     * @param string $parse_mode Optional. Send Markdown or HTML, if you want Telegram apps to show bold, italic, fixed-width text or inline URLs in your bot's message.
     * @param bool $disable_web_page_preview Optional. Disables link previews for links in this message
     * @param InlineKeyboardMarkup $reply_markup Optional. A JSON-serialized object for an inline keyboard.
     *
     * @return bool|mixed On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     */
    public function editMessageText($chat_id = '', $message_id = 0, $inline_message_id = '', $text = '', $parse_mode = 'HTML', $disable_web_page_preview = false, $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'text', 'parse_mode', 'disable_web_page_preview', 'reply_markup');

        return $this->sendRequest('editMessageText', $params);
    }

    /**
     * Use this method to edit only the reply markup of messages sent by the bot or via the bot (for inline bots).
     * On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     * @link https://core.telegram.org/bots/api#editmessagereplymarkup
     *
     * @param int | string $chat_id Optional. Required if inline_message_id is not specified. Unique identifier for the target chat or username of the target channel (in the format @channelusername)
     * @param int $message_id Optional. Required if inline_message_id is not specified. Identifier of the sent message
     * @param string $inline_message_id Optional. Required if chat_id and message_id are not specified. Identifier of the inline message
     * @param InlineKeyboardMarkup $reply_markup Optional. A JSON-serialized object for an inline keyboard.
     *
     * @return bool|mixed On success, if edited message is sent by the bot, the edited Message is returned, otherwise True is returned.
     */
    public function editMessageReplyMarkup($chat_id = '', $message_id = 0, $inline_message_id = '', $reply_markup = null)
    {
        $params = compact('chat_id', 'message_id', 'inline_message_id', 'reply_markup');

        return $this->sendRequest('editMessageReplyMarkup', $params);
    }

    /**
     * JSON validation
     *
     * @param string $jsonString
     * @param boolean $asArray
     *
     * @return object|array
     * @throws InvalidJsonException
     */
    public static function jsonValidate($jsonString, $asArray)
    {
        $json = json_decode($jsonString, $asArray);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @param bool $useCurl
     */
    public function useCurl(bool $useCurl)
    {
        $this->useCurl = $useCurl;
    }

    /**
     * @param string $curlProxy
     */
    public function setCurlProxy(string $curlProxy)
    {
        $this->curlProxy = $curlProxy;
    }

    /**
     * @param bool $curlProxySocks5
     */
    public function setCurlProxySocks5(bool $curlProxySocks5)
    {
        $this->curlProxySocks5 = $curlProxySocks5;
    }

    /**
     * @return string|null
     */
    public function getCurlProxy()
    {
        return $this->curlProxy;
    }

    /**
     * @return bool
     */
    public function isCurlProxySocks5(): bool
    {
        return $this->curlProxySocks5;
    }
}

