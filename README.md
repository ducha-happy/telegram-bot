About
=====
The bot author is @ducha_v.
The bot was created with an eye to facilitate the collection of Uma's team for various activities, including football training.

Description
===========
With my help you can create polls and conduct them in your groups!
So you can create a poll with "/pollcreate" and start that poll in any group where i live with "/pollstart name" or "/pollstart number".
You can find all your polls and statistic for them with help of the command "/start".

Commands
========
1) Admin commands:
 - /run - The bot will be start only if the command came from the administrator.
 - /stop - The bot will be stopped only if the command came from the administrator.
 - /killbot - Kills the bot so that it can start only from server. The bot will be killed only if the command came from the administrator.
 - / - show list available commands.

2) Commands for all:
 - /start - This command starts an interactive menu so that a user can manage their polls. It is available only for private chat.
 - /pollcreate - This command creates poll. The command is available only for private chats. Formats: 1) /pollcreate name ; 2) /pollcreate
 - /pollstart - This command starts poll. The command is available only in group chat. Formats: 1) /pollstart number ; 2) /pollstart string ; 3) /pollstart
 - /ping - This command sends pong message. This command is only for test.

3) Below is a copy-paste command stuff for BotFather:
start - This command starts an interactive menu so that a user can manage their polls. It is available only for private chat.
pollcreate - This command creates poll. The command is available only for private chats. Formats: 1) /pollcreate name ; 2) /pollcreate
pollstart - This command starts poll. The command is available only in group chat. Formats: 1) /pollstart number ; 2) /pollstart string ; 3) /pollstart
ping - This command sends pong message. This command is only for test.

## PREREQUISITES
This package is working with redis so you need to have "redis-server" started.

## Installation and Use

####  Create your bot    
    How to create a bot you can read here:
    - https://core.telegram.org/bots#3-how-do-i-create-a-bot
    - https://core.telegram.org/bots#6-botfather
    

####  Using this package directly as standalone application. 
      
    1) Run the following commands:
       
``` bash
$ git clone https://github.com/ducha/telegram-bot.git
$ cd telegram-bot
$ rm -rf .git
```
    
    2) Now you need to set up app/config.yml with your parameters:       
    
``` yml
parameters:
    telegram_bot_link       : "http://t.me/YourBotName"
    telegram_bot_token      : "YourBotToken"
    locale : ru #default en
```
    
    3) Next you can start your bot 

``` bash
$ app/console start
```    
    4) Go to your telegram client and bot chat "@YourBotName" and test bot with "/ping" command
    
    5) Use "/whoami" command to find out your private id. Use it to set up "telegram_admin_chat_id" config parameter in "app/config.yml". 
       Without "telegram_admin_chat_id" parameter you can`t run "admin commands" and don`t see menu items which is intended only for an admin.
       
    6) Add to your app/config.yml this row
    
``` yml
parameters:
    ...
    telegram_admin_chat_id       : "YourId"
    ...
```
    7) Restart your bot
      
``` bash
$ app/console restart
```
                
    8) If you need to stop your bot, you can use a telegram client by sending /killbot command to your bot chat. Or you can also use console command in terminal:
    
``` bash
$ app/console stop
```
    9) To test your config file on the ability to work, use
    
``` bash
$ app/console test
```

    10) To check your proxies from your proxy list (app/config/proxies) , use
    
``` bash
$ app/console check
```
    
    11) To check, that is your bot is running, use following
    
``` bash
$ app/console status
```

#### Using this package within your project

    1) Add this package to your project 

``` bash
$ composer require ducha/telegram-bot
```
    2) Next you need to add into your bot php script following 

``` php
    $configLoader = new ConfigLoader('path/To/YourConfigYmlFile');    
    
    $bot = new TelegramBot($configLoader->getLogger());
    $bot->setContainer($configLoader->getContainer());
    $bot->execute();
```
    3) Don`t forget to set up 'YourConfigYmlFile'
    
``` yml
parameters:
    telegram_bot_link       : "http://t.me/YourBotName"
    telegram_bot_token      : "YourBotToken"
    locale : ru #default en
```