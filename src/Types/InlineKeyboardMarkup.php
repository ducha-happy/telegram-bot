<?php

namespace Ducha\TelegramBot\Types;


class InlineKeyboardMarkup implements \JsonSerializable
{
    use JsonSerializer;

    protected $inline_keyboard;

    public function __construct(array $rows)
    {
        $this->validate($rows);

        $this->inline_keyboard = $rows;
    }

    /**
     * @param $rows
     * @throws \LogicException
     */
    protected function validate($rows)
    {
        foreach ($rows as $row){
            foreach ($row as $button) {
                if (!$button instanceof InlineKeyboardButton){
                    throw new \LogicException(sprintf('The button of the keyboard must be instanceof %s but %s was given', InlineKeyboardButton::class, gettype($button)));
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getInlineKeyboard()
    {
        return $this->inline_keyboard;
    }

    /**
     * @param array $rows
     */
    public function setInlineKeyboard($rows)
    {
        $this->validate($rows);

        $this->inline_keyboard = $rows;
    }
}

