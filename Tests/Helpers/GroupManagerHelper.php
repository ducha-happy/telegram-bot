<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Tests\Helpers;

use Ducha\TelegramBot\GroupManagerInterface;
use Ducha\TelegramBot\Types\Group;
use Ducha\TelegramBot\Types\Message;

class GroupManagerHelper implements GroupManagerInterface
{
    protected $groups = array();
    /**
     * @param $id
     * @param $title
     * @return Group
     */
    public function addGroup($id, $title)
    {
        $this->groups[$id] = new Group($id, $title);

        return $this->groups[$id];
    }

    /**
     * @param $id
     * @return bool|Group
     */
    public function getGroup($id)
    {
        if (isset($this->groups[$id])){
            return $this->groups[$id];
        }

        return false;
    }

    /**
     * @param $id
     */
    public function removeGroup($id)
    {
        $group = $this->getGroup($id);

        if ($group instanceof Group){
            unset($this->groups[$id]);
        }
    }

    /**
     * Find a real user. Is he in a group? Add if not
     * @param Message $message
     */
    public function lookAtMessage(Message $message){}

}