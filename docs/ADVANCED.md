# ADVANCED USAGE
## Table of Contents {docsify-ignore}
 1. [Introducing](#introducing)
 1. [Headers](#headers)
 1. [Skipping Auto Declaration](#skipping-auto-declaration)
 
## Introducing
 Let's see some advanced uses of Pigeon

## Skipping Auto Declaration
 Whenever you call `->dispatch()`, `->queue()`, `->exchange()`, `->routing()` or `->events()`, Pigeon will automatically declare the queue and/or exchange being used, in case it doesn't exist yet. If the structure already exists, nothing happens.

 The goal is deal with RabbitMQ as a disposable component then you dont need to mantain a list of queues and exchange to declare when provision environment.

 This behavior is network intensive then if your queues and exchanges are managed else where (e.g. provisioned by infrastructure-as-code, or you simply want to guarantee they must already exist), you can disable this automatic declaration by setting `PIGEON_SKIP_AUTO_DECLARATION` to `true` in your `.env` file.

 ```dotenv
PIGEON_SKIP_AUTO_DECLARATION=true
```

 The default value is `false`, meaning queues and exchanges keep being auto declared as before.

 !> When this option is enabled, publishing or consuming from a queue/exchange that doesn't exist yet will raise an error from the broker, since Pigeon will no longer create it on the fly.

## Headers
 You can easily add `application_headers` when publishing a message with the `header` method.
 
 ```php
use Pigeon;

Pigeon::routing($routing)
    ->header($key, $value);    
```  

?> You can also pass **associative array** as a value for the header, it will be converted to dot notation

You can also capture all message headers with the resolver in your callback with `headers` method
 ```php
 use Pigeon;
 
$callback = function ($data, $resolver) {
    $resolver->headers();
};
 Pigeon::queue($queue)
    ->callback($callback);
 ```  
 
?> If you want a specific header you can pass it key to headers and it'll return only the header you asked for

!> In the opposite of publishing message, when consuming headers will not return only the **application_headers**
but all AMQPMessage internal headers