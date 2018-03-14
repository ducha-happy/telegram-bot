<?php

namespace Ducha\TelegramBot\Types;

trait JsonSerializer {
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}