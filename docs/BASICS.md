# USAGE
## Table of Contents {docsify-ignore}
 1. [Introducing](#introducing)
 2. [Publish](#publish)
 3. [Consume](#consume)
 3. [RPC](#remote-procedure-call)
 
## Introducing
 Pigeon was made to be a simple messaging interface with fast ways of publishing and consuming messages
 and a Laravel way of testing the application.
 
 Pigeon will automatically create the exchange, queue and bind the routing key. 
  

## Publish
 To publish a message using the application's exchange and a routing key: 
 
```php
use Pigeon;

Pigeon::routing($routing)
    ->publish([
        'some' => 'content'
    ]);
 ```

To bind a routing key to a queue you can use the `bind` method:

```php
use Pigeon;

Pigeon::routing($routing)
    ->bind($queue);
 ```

## Consume
To consume a queue you can call the `queue` method to setup the consumer followed by `callback` and `fallback`
to setup a callback and a fallback and the `wait`:

```php
use Pigeon;

Pigeon::queue('queue.name')
    ->callback($closure)
    ->consume($timeout = 0, $multiple = true);
 ```

### Queue
The `queue` method will receive the name of queue you want to use in the consumer. When ypu call it
the package will automatically declare it for you, so you don't need to declare it manually.

As second argument, the `queue` method receive some queue properties, passed to broker through `php-amqplib`,
so you can pass ttl, dead letter exchange, max priority, etc.

### Callback
The consumer callback is a closure that is called every time a message is received.
It need to contain at last 1 argument, what is the received message.

```php
use Pigeon;

$callback = function ($message) {
    dump($message);
};

Pigeon::queue('my.awesome.queue')
    ->callback($callback)
    ->consume($timeout = 0, $multiple = true);
 ```

The above code create a queue called `my.awesome.queue` and configured a consumer in that.
Using the callback, every time a message is published in that queue, it'll dump the message content.

!> For now Pigeon only support array messages.

You will need to send a acknowledge signal to broker every time you consume a message successfully,
and you can do this from you callback with Pigeon.


```php
use Pigeon;
Use Convenia\Pigeon\Resolver\ResolverContract as Resolver;

$callback = function ($message, Resolver $resolver) {
    dump($message);
    $resolver->ack(); // $resolver->reject();
};

Pigeon::queue('my.awesome.queue')
    ->callback($callback)
    ->consume($timeout = 0, $multiple = true);
 ```

Your callback closure can receive a second argument, which is the resolver. The resolver can do the acknowledge and the
unacknowledged of message with the `ack` and `reject` methods.

### Fallback
The consumer fallback is a closure that is called every time a callback throw a exception.
It contains 2 arguments, which is the Exception and AMQPMessage instances.

 ```php
 use Pigeon;
 Use Convenia\Pigeon\Resolver\ResolverContract as Resolver;
 
 $callback = function ($message) {
     thow new Exception('Pigeon is awesome');
 };

 $fallback = function ($e, $message) {
     // Handle the exception
 };

 Pigeon::queue('my.awesome.queue')
     ->callback($callback)
     ->fallback($fallback)
     ->consume($timeout = 0, $multiple = true);
  ```

!> The behaviour of fallback is going be changed to be compatible with callback interface.

### Waiting
After setup a consumer you need to start to listen the queue adn for this you use the `consume` method.
You can pass a timeout in seconds to `consume`, so it'll start to consume the queue and it it reach the time without receive data
it thrown a timeout exception.
It can receive a second argument which is a boolean that specify if you want to consume multiple messages or not.

## Remote Procedure Call
Pigeon also support RPC's with a simple interface:
```php
use Pigeon;

$responseQueue = Pigeon::routing('rpc.')
    ->rpc($message);
    
Pigeon::queue($responseQueue)
    ->callback($callback)
    ->fallback($fallback)
    ->consume($timeout = 5, $multiple = false);
```

The RPC method return the response queue name, so you can consume it to receive the response message.

?> You can consume the response queue when you need

!> The response queue has a auto generated name and is auto-deleted.
