# Installation
# Table of Contents
 - [Server Requirements](#requirements)
 - [Installing Pigeon](#installing)
 - [Installing Pigeon](#configuring)
 
## Server Requirements <a name="requirements"></a>
The Pigeon package has some system requirements.
 - PHP >= 7.1.3
 - JSON PHP extension
 - Sockets PHP Extension
 
## Installing Pigeon <a name="installing"></a>
Pigeon utilizes [Composer](https://getcomposer.org/) to manage its dependencies. So, before using Laravel, make sure you have Composer installed on your machine.

```bash
composer require convenia/pigeon
```

## Configuring <a name="configuring"></a>
After installing Pigeon you should configure it to connect to you broker.

### Configuration Files
Typically, the pigeon environment configurations live in the `.env` file of your Laravel application.

```dotenv
PIGEON_DRIVER=rabbit
PIGEON_ADDRESS=localhost # Your AMQP host
PIGEON_PORT=15672 # Your AMQP port
PIGEON_USER=guest
PIGEON_PASSWORD=guest
PIGEON_VHOST=/
PIGEON_EXCHANGE=application # OPTIONAL - Your applciaton default exchange
PIGEON_EXCHANGE_TYPE=fanout # OPTIONAL - Your applciaton default exchange type
PIGEON_CONSUMER_TAG=application # OPTIONAL - Your applciaton name
PIGEON_KEEPALIVE= # OPTIONAL -  
PIGEON_HEARTBEAT= # OPTIONAL -  
PIGEON_READ_TIMEOUT= # OPTIONAL -  
PIGEON_WRITE_TIMEOUT= # OPTIONAL -  
```
