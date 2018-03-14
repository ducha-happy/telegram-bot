<?php

namespace Ducha\TelegramBot\Tests;

final class TelegramData
{
    const GROUP_CHAT_ID = -123456789;
    const PRIVATE_CHAT_ID = 3216546989;
    const PRIVATE_NOT_ADMIN_CHAT_ID = 123456789;

    public static $data = array (
        'update_id' => 123456789,
        'message' =>
            array (
                'message_id' => 1228,
                'from' =>
                    array (
                        'id' => self::PRIVATE_CHAT_ID,
                        'is_bot' => false,
                        'first_name' => 'Fghgfh',
                        'username' => 'fghgfh',
                        'language_code' => 'en-US',
                    ),
                'chat' =>
                    array (
                        'id' => self::PRIVATE_CHAT_ID,
                        'first_name' => 'Fghgfh',
                        'username' => 'fghgfh',
                        'type' => 'private',
                    ),
                'date' => 1519721041,
                'text' => '/pollList',
                'entities' =>
                    array (
                        0 =>
                            array (
                                'offset' => 0,
                                'length' => 9,
                                'type' => 'bot_command',
                            ),
                    ),
            ),
    );
}