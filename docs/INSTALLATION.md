# Installation
## Table of Contents {docsify-ignore}
 1. [Server Requirements](#requirements)
 2. [Installing Pigeon](#installing)
 3. [Configuring Pigeon](#configuring)
 
## Requirements
The Pigeon package has some system requirements.
 - PHP >= 7.1.3
 - JSON PHP extension
 - Sockets PHP Extension
 
## Installing
Pigeon utilizes [Composer](https://getcomposer.org/) to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.

```bash
composer require convenia/pigeon
```

## Configuring
Pigeon utilize laravel auto-discovery feature, so you don't need to setup service provider and facade.
After installing Pigeon you should configure it to connect to you broker.

### Application env file
Typically, the pigeon environment configurations live in the `.env` file of your Laravel application.

```dotenv
PIGEON_ADDRESS=localhost # Your AMQP host
PIGEON_PORT=5672 # Your AMQP port
PIGEON_USER=guest
PIGEON_PASSWORD=guest
PIGEON_VHOST=/
PIGEON_EXCHANGE=application # OPTIONAL - Your applciaton default exchange
PIGEON_EXCHANGE_TYPE=topic # OPTIONAL - Your applciaton default exchange type
PIGEON_CONSUMER_TAG=application # OPTIONAL - Your applciaton name
PIGEON_HEARTBEAT= # OPTIONAL -  The heart beat of connection
```

### Configuration File
As any laravel library, you can publish the config to `config/pigeon.php` using bellow command

```bash
php artisan vendor:publish --tag=pigeon.config
```

It'll publish a config file that contain all needle configuration you can change.
