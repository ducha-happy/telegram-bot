<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public static $left_chat_participant_data = array (
        'update_id' => 140331337,
        'message' =>
            array (
                'message_id' => 27,
                'from' =>
                    array (
                        'id' => 99568417,
                        'is_bot' => false,
                        'first_name' => 'Malec',
                        'username' => 'malec88',
                        'language_code' => 'ru-RU',
                    ),
                'chat' =>
                    array (
                        'id' => -1001233109538,
                        'title' => 'рар',
                        'type' => 'supergroup',
                    ),
                'date' => 1522229390,
                'left_chat_participant' =>
                    array (
                        'id' => 99568417,
                        'is_bot' => false,
                        'first_name' => 'Malec',
                        'username' => 'malec88',
                        'language_code' => 'ru-RU',
                    ),
                'left_chat_member' =>
                    array (
                        'id' => 99568417,
                        'is_bot' => false,
                        'first_name' => 'Malec',
                        'username' => 'malec88',
                        'language_code' => 'ru-RU',
                    ),
            ),
    );

    public static $new_chat_participant_data = array (
        'update_id' => 140331250,
        'message' =>
            array (
                'message_id' => 4,
                'from' =>
                    array (
                        'id' => 123456789,
                        'is_bot' => false,
                        'first_name' => 'asdfasf',
                        'username' => 'asdfasdf',
                        'language_code' => 'en-US',
                    ),
                'chat' =>
                    array (
                        'id' => -1001233109538,
                        'title' => 'рар',
                        'type' => 'supergroup',
                    ),
                'date' => 1522147101,
                'new_chat_participant' =>
                    array (
                        'id' => 99568417,
                        'is_bot' => false,
                        'first_name' => 'Malec',
                        'username' => 'malec88',
                        'language_code' => 'ru-RU',
                    ),
                'new_chat_member' =>
                    array (
                        'id' => 99568417,
                        'is_bot' => false,
                        'first_name' => 'Malec',
                        'username' => 'malec88',
                        'language_code' => 'ru-RU',
                    ),
                'new_chat_members' =>
                    array (
                        0 =>
                            array (
                                'id' => 99568417,
                                'is_bot' => false,
                                'first_name' => 'Malec',
                                'username' => 'malec88',
                                'language_code' => 'ru-RU',
                            ),
                    ),
            ),
    );
}