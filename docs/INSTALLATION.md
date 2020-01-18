# Installation
## Table of Contents {docsify-ignore}
 1. [Server Requirements](#requirements)
 2. [Installing Pigeon](#installing)
 3. [Configuring Pigeon](#configuring)
 
## Requirements
 - PHP >= 7.1.3
 - JSON PHP extension
 - Sockets PHP Extension
 
## Installing
Pigeon utilizes [Composer](https://getcomposer.org/) to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.

```bash
composer require convenia/pigeon
```

## Configuring
Pigeon utilizes laravel auto-discovery feature, so you don't need to setup any service provider or facade.
After installing Pigeon configure it to connect to your broker.

### Application env file
Typically, Pigeon's environment configurations can be found in the `.env` file of your Laravel application.

```dotenv
PIGEON_ADDRESS=localhost # Your AMQP host
PIGEON_PORT=5672 # Your AMQP port
PIGEON_USER=guest
PIGEON_PASSWORD=guest
PIGEON_VHOST=/
PIGEON_EXCHANGE=application # OPTIONAL - The applicaton's default exchange
PIGEON_EXCHANGE_TYPE=topic # OPTIONAL - The applicaton's default exchange type
PIGEON_CONSUMER_TAG=application # OPTIONAL - The applicaton's name
PIGEON_HEARTBEAT= # OPTIONAL -  The heartbeat of the connection
```

### Configuration File
As any laravel library, you can publish the config to `config/pigeon.php` using the command below.

```bash
php artisan vendor:publish --tag=pigeon.config
```

It'll publish a config file that contains all the necessary configuration.
