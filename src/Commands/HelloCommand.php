<?php

namespace Ducha\TelegramBot\Commands;

use Sas\CommonBundle\Entity\User;
use Sas\CommonBundle\Util\RandomStringGenerator;

class HelloCommand extends AbstractCommand
{
    /**
     * Get name of command
     * @return string
     */
    public static function getName()
    {
        return '/hello';
    }

    /**
     * Get description of command
     * @return string
     */
    public static function getDescription()
    {
        //a registration or an identification of a user
        // Регистрация или идентификация пользователя
        return 'Registration or identification of a site user';
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function execute(array $data)
    {
        if ($this->hasMessage($data)){
            $message = $this->getMessage($data);
            $text = $message->getText();
            $chat = $message->getChat();
            $chatId = $message->getChatId();

            $token = null;
            $temp = explode(" ", $text);
            if (isset($temp[1])){
                $token = $temp[1];
            }

            if (!empty($token)){
                // a user comes from site app
                if ($newToken = $this->addTelegramUser($token, $chat)){
                    $this->telegram->sendMessage($chatId,
                        sprintf('Вы %s (%s) успешно идентифицированы! Время: %s 
                                Чтобы завершить процедуру пройдите по ссылке
                                '.$this->getRegistryLink($newToken), $chat['first_name'], $chat['username'], date('d.m.Y H:i:s'))
                    );
                }else{
                    $this->telegram->sendMessage($chatId,
                        sprintf('Чтобы завершить процедуру регистрации - пройдите по
                                <a href="'.$this->getRegistryLink($token).'">ссылке</a>
                                Время: %s', date('d.m.Y H:i:s'))
                    );
                }
            }else{
                // a user comes from telegram app and wants perhaps to reg
                $this->telegram->sendMessage($chatId,
                    'Welcome to uma team! If you wants to reg on our site then go to the <a href="' . $this->getHomePageLink() . '">link</a>'
                );
            }
        }
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
            if (preg_match("|^" . $this->getName() . "|", $text)){
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getHomePageLink()
    {
        $container = $this->handler->getContainer();

        $router = $container->get('router');
        $host = $container->getParameter('host');
        $schema = $container->getParameter('schema');

        return $schema . '://' . $host . $router->generate('home');
    }

    /**
     * @param string $token
     * @return string
     */
    protected function getRegistryLink($token)
    {
        $container = $this->handler->getContainer();

        $router = $container->get('router');
        $host = $container->getParameter('host');
        $schema = $container->getParameter('schema');

        return $schema . '://' . $host . $router->generate('telegram_signin', array('token' => $token));
    }

    /**
     * @param string $token
     * @param array $chat
     * @return bool|string
     */
    protected function addTelegramUser($token, $chat)
    {
        $chat_id = $chat['id'];
        $doctrine = $this->handler->getDoctrine();
        $telegramBot = $this->handler->getTelegramBot();

        $em = $doctrine->getManager();
        $repo = $doctrine->getRepository('SasCommonBundle:User');

        $logDir = $telegramBot->getLogDir();
        $tokenFile = $logDir . $token;

        // пользователь привязывает Telegram аккаунт к своему аккаунту на сайте
        $items = $repo->findBy(array('token' => $token));
        if (isset($items[0])){
            $user = $items[0];
            $user->setTelegramChatId($chat_id);
            $newToken = $this->generateToken();
            $user->setToken($newToken);
            $em->flush();

            if (file_exists($tokenFile)){
                unlink($tokenFile);
            }

            return $newToken;
        }

        // пользователь входит на сайт через Telegram
        $items = $repo->findBy(array('telegram_chat_id' => $chat_id));
        if (isset($items[0])){
            $user = $items[0];
            $newToken = $this->generateToken();
            $user->setToken($newToken);
            $em->flush();

            if (file_exists($tokenFile)){
                unlink($tokenFile);
            }

            return $newToken;
        }

         // It is a new user. He wants to reg
        if (file_exists($tokenFile)){
            $user = new User();
            $user->setNickname($chat['username']);
            $user->setName($chat['first_name']);
            $user->setTelegramChatId($chat_id);
            $newToken = $this->generateToken();
            $user->setToken($newToken);
            $em->persist($user);
            $em->flush();

            unlink($tokenFile);

            return $newToken;
        }

        return false;
    }

    protected function generateToken()
    {
        // Create new instance of generator class.
        $generator = new RandomStringGenerator();

        // Set token length.
        $tokenLength = 10;

        // Call method to generate random string.
        $token = $generator->generate($tokenLength);

        return $token;
    }
}