<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Andre Vlasov <areyouhappyihopeso@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ducha\TelegramBot\Translation;

use Ducha\TelegramBot\Formatter\TranslateFormatter;
use Symfony\Component\Translation\Translator as SymfonyTranslator;

class Translator extends SymfonyTranslator
{
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        $str = parent::trans($id, $parameters, $domain, $locale);

        return TranslateFormatter::format($str);
    }
}