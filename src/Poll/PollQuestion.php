<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Poll;

use Ducha\TelegramBot\Types\ReplyKeyboardMarkup;

class PollQuestion
{
    /**
     * @var string
     */
    protected $title;
    /**
     * @var array
     */
    protected $replies;

    /**
     * PollQuestion constructor.
     * @param string $title
     * @param array $replies
     */
    public function __construct($title, $replies = array())
    {
        $this->title = $title;
        $this->replies = $replies;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get json-encoded KeyboardMarkup object
     * @return string
     */
    public function getMarkup()
    {
        return json_encode(new ReplyKeyboardMarkup(array($this->replies), true, true));
    }

    /**
     * @param $title
     */
    public function addReply($title)
    {
        if (array_search($title, $this->replies) === false){
            $this->replies[] = $title;
        }
    }

    /**
     * @return array
     */
    public function getReplies()
    {
        return $this->replies;
    }

    /**
     * @param array $replies
     */
    public function setReplies(array $replies)
    {
        $this->replies = $replies;
    }
}
