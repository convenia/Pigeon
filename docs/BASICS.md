# USAGE
## Table of Contents {docsify-ignore}
 1. [Introducing](#introducing)
 2. [Publish](#publish)
 3. [Consume](#consume)
 
## Introducing
 Pigeon was made to be a simple messaging interface with fast ways of publishing and consuming messages
 and a Laravel way of testing the application.
 
 Pigeon will automatically create the exchange, queue and bind the routing key. 
  

## Publish
 To publish a message using the application's exchange and a routing key see the example below: 
 
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
The `queue` method will receive the name of the queue you want to use in the consumer. When you call it
the package will automatically declare it for you, so you don't need to declare it manually.

In the second argument, the `queue` method receives some properties, passed to the broker through `php-amqplib`,
so you can pass ttl, dead letter exchange, max priority, etc.

### Callback
The consumer callback is a closure that is called every time a message is received.
It needs to contain at least 1 argument, that is the received message.

```php
use Pigeon;

$callback = function ($message) {
    dump($message);
};

Pigeon::queue('my.awesome.queue')
    ->callback($callback)
    ->consume($timeout = 0, $multiple = true);
 ```

The code above creates a queue called `my.awesome.queue` and configures a consumer binded to that queue.
Using the callback, every time a message is published in that queue, it'll dump the message content.

!> For now Pigeon only supports array messages.

You must send an acknowledgement signal to the broker every time you successfully consume a message,
and you can do this with Pigeon's callback.


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

Your callback closure can receive a second argument, the resolver. The resolver can do the acknowledgement and the
unacknowledgedment of a message with the `ack` and `reject` methods.

### Fallback
The consumer fallback is a closure that is called every time a callback throws an exception.
It contains 2 arguments, which are the Exception and the AMQPMessage instance.

 ```php
 use Pigeon;
 Use Convenia\Pigeon\Resolver\ResolverContract as Resolver;
 
 $callback = function ($message) {
     thow new Exception('Pigeon is awesome');
 };

 $fallback = function    ($e, $message) {
     // Handle the exception
 };

 Pigeon::queue('my.awesome.queue')
     ->callback($callback)
     ->fallback($fallback)
     ->consume($timeout = 0, $multiple = true);
  ```

!> The fallback's behavior is going be changed to be compatible with the callback interface.

### Waiting
After setting up a consumer you need to start listening for the queue using the `consume` method.
You can pass a timeout in seconds to `consume`, so it'll start to consume the queue and if it reaches the time without receiving any data
a timeout exception is thrown.
It can receive a second argument which is a boolean that specifies if you want to consume multiple messages or not.