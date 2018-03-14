<?php

namespace Ducha\TelegramBot\Types;


class InlineKeyboardMarkup implements \JsonSerializable
{
    use JsonSerializer;

    protected $inline_keyboard;

    public function __construct(array $rows)
    {
        foreach ($rows as $row){
            foreach ($row as $button) {
                if (!$button instanceof InlineKeyboardButton){
                    throw new \LogicException(sprintf('The button of the keyboard must be instanceof %s but %s was given', InlineKeyboardButton::class, gettype($button)));
                }
            }
        }

        $this->inline_keyboard = $rows;
    }

    /**
     * @return array
     */
    public function getInlineKeyboard()
    {
        return $this->inline_keyboard;
    }

    /**
     * @param array $inline_keyboard
     */
    public function setInlineKeyboard($inline_keyboard)
    {
        $this->inline_keyboard = $inline_keyboard;
    }
}

